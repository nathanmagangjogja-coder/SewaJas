<?php

namespace App\Jobs;

use App\Models\BroadcastLog;
use App\Models\BroadcastSchedule;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SendBroadcastJob // implements ShouldQueue - queue optional for now
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $customer_id;
    public int $schedule_id;
    public int $send_time_slot;
    public ?int $forced_template_index = null;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(int $customer_id, int $schedule_id, int $send_time_slot, ?int $forced_template_index = null)
    {
        $this->customer_id = $customer_id;
        $this->schedule_id = $schedule_id;
        $this->send_time_slot = $send_time_slot;
        $this->forced_template_index = $forced_template_index;
    }

    public function handle(): void
    {
        Log::info('Starting SendBroadcastJob for customer: ' . $this->customer_id . ', schedule: ' . $this->schedule_id);
        
        $customer = Customer::with(['rentals' => fn($q) => $q->orderByDesc('id')->limit(1)])->find($this->customer_id);
        $schedule = BroadcastSchedule::find($this->schedule_id);

        if (!$customer) {
            Log::warning('Customer not found: ' . $this->customer_id);
            return;
        }
        
        if (!$schedule) {
            Log::warning('Schedule not found: ' . $this->schedule_id);
            return;
        }
        
        if ($customer->is_blacklisted) {
            Log::warning('Customer is blacklisted: ' . $this->customer_id);
            return;
        }
        
        if (empty($customer->phone)) {
            Log::warning('Customer has no phone: ' . $this->customer_id);
            return;
        }

        $templates = $schedule->message_templates;
        if (!is_array($templates) || count($templates) === 0) {
            Log::warning('No templates found for schedule: ' . $this->schedule_id);
            return;
        }

        $totalTemplates = count($templates);
        $cooldownHours = $schedule->cooldown_hours ?? 24;

        // If forced template index is provided, use it directly
        if ($this->forced_template_index !== null) {
            $templateIndex = (int) $this->forced_template_index;
            $templateIndex = $totalTemplates > 0 ? $templateIndex % $totalTemplates : 0;
        } else {
            // Get recent logs for this customer and schedule
            $recentLogs = BroadcastLog::where('customer_id', $customer->id)
                ->where('broadcast_schedule_id', $schedule->id)
                ->where('sent_at', '>=', now('Asia/Jakarta')->subHours($cooldownHours))
                ->orderBy('sent_at', 'desc')
                ->get();

            // Get list of recently used template indices
            $recentlyUsedIndices = $recentLogs->pluck('template_index')->unique()->toArray();

            // Determine available templates (not used recently)
            $availableIndices = [];
            for ($i = 0; $i < $totalTemplates; $i++) {
                if (!in_array($i, $recentlyUsedIndices)) {
                    $availableIndices[] = $i;
                }
            }

            // If all templates were used recently, use any except the very last one
            if (empty($availableIndices)) {
                $lastUsedIndex = $recentLogs->first()?->template_index ?? 0;
                for ($i = 0; $i < $totalTemplates; $i++) {
                    if ($i !== $lastUsedIndex) {
                        $availableIndices[] = $i;
                    }
                }
                // Fallback: if only one template exists, use it anyway
                if (empty($availableIndices) && $totalTemplates > 0) {
                    $availableIndices[] = 0;
                }
            }

            // Select random template from available indices
            $templateIndex = $availableIndices[array_rand($availableIndices)];
        }
        $template = $templates[$templateIndex];
        $payload = $this->buildPersonalizedMessage($template, $customer);

        try {
            Log::info('Preparing to send message to customer: ' . $customer->id);
            
            $phone = $this->formatPhone($customer->phone);
            Log::info('Formatted phone: ' . $phone);
            
            $response = $this->sendViaDriver($phone, $payload['message']);
            Log::info('Send response: ', $response);

            $status = $response['success'] ? 'sent' : 'failed';
            $body = $response['body'];

            $logData = [
                'broadcast_schedule_id' => $schedule->id,
                'customer_id' => $customer->id,
                'template_index' => $templateIndex,
                'message_sent' => $payload['message'],
                'sent_at' => Carbon::now('Asia/Jakarta'),
                'status' => $status,
                'fonnte_response' => $body,
            ];
            
            Log::info('Creating BroadcastLog: ', $logData);
            $log = BroadcastLog::create($logData);
            Log::info('BroadcastLog created: ' . $log->id);

            if (!$response['success']) {
                throw new RuntimeException('Provider returned non-success response: ' . $body);
            }
            
            Log::info('Message sent successfully to customer: ' . $customer->id);
        } catch (\Throwable $exception) {
            Log::error('Exception in SendBroadcastJob: ' . $exception->getMessage(), [
                'customer_id' => $this->customer_id,
                'schedule_id' => $this->schedule_id,
                'trace' => $exception->getTraceAsString()
            ]);
            
            if ($this->attempts() < $this->tries) {
                throw $exception;
            }

            // Create failed log
            try {
                BroadcastLog::create([
                    'broadcast_schedule_id' => $schedule->id,
                    'customer_id' => $customer->id,
                    'template_index' => $templateIndex,
                    'message_sent' => $payload['message'],
                    'sent_at' => Carbon::now('Asia/Jakarta'),
                    'status' => 'failed',
                    'fonnte_response' => $exception->getMessage(),
                ]);
            } catch (\Throwable $logException) {
                Log::error('Failed to create failed log: ' . $logException->getMessage());
            }
        }
    }

    private function sendViaDriver(string $phone, string $message): array
    {
        $driver = config('services.broadcast.driver', 'fonnte');

        if ($driver === 'custom') {
            return $this->sendToCustom($phone, $message);
        }

        // default: fonnte
        if ($driver === 'local') {
            return $this->sendToLocal($phone, $message);
        }

        return $this->sendToFonnte($phone, $message);
    }

    private function sendToCustom(string $phone, string $message): array
    {
        $cfg = config('services.broadcast.custom', []);
        $url = $cfg['url'] ?? null;

        if (!$url) {
            Log::warning('Custom broadcast URL not configured; skipping send.');
            return ['success' => false, 'body' => 'no-custom-url'];
        }

        $token = $cfg['token'] ?? null;
        $tokenHeader = $cfg['token_header'] ?? 'Authorization';
        $targetKey = $cfg['target_key'] ?? 'target';
        $messageKey = $cfg['message_key'] ?? 'message';

        $payload = [
            $targetKey => $phone,
            $messageKey => $message,
        ];

        try {
            $client = Http::timeout(30)->withOptions(['verify' => false]);

            if ($token) {
                $client = $client->withHeaders([$tokenHeader => $token]);
            }

            $response = $client->post($url, $payload);

            return ['success' => $response->successful(), 'body' => $response->body()];
        } catch (\Throwable $e) {
            Log::error('Custom provider send error: ' . $e->getMessage());
            return ['success' => false, 'body' => $e->getMessage()];
        }
    }

    private function sendToLocal(string $phone, string $message): array
    {
        // Dev-only: simulate successful send and return fake provider response
        $body = json_encode(['status' => true, 'reason' => 'local-mock', 'target' => $phone]);
        return ['success' => true, 'body' => $body];
    }

    private function formatPhone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($clean, '0')) {
            return '62' . substr($clean, 1);
        }
        return str_starts_with($clean, '62') ? $clean : '62' . $clean;
    }

    private function buildPersonalizedMessage(string $template, Customer $customer): array
    {
        $activeRental = $customer->rentals()
            ->whereIn('rental_status', ['active', 'overdue'])
            ->orderByDesc('return_due_date')
            ->first();

        $product = $activeRental?->items()->with('product')->latest()->first()?->product->name
            ?? $customer->rentals()->with('items.product')->latest()->first()?->items->first()?->product->name
            ?? 'jas kesayangan';

        $returnDate = $activeRental?->return_due_date
            ? Carbon::parse($activeRental->return_due_date)->locale('id')->isoFormat('D MMMM Y')
            : null;

        $daysLeft = null;
        if ($activeRental?->return_due_date) {
            $daysLeft = Carbon::now('Asia/Jakarta')->startOfDay()
                ->diffInDays(Carbon::parse($activeRental->return_due_date)->startOfDay(), false);
        }

        $promoCodes = config('broadcast.active_promos', []);
        $promoCode = is_array($promoCodes) && count($promoCodes) > 0
            ? $promoCodes[array_rand($promoCodes)]
            : null;

        $greeting = $this->buildGreeting();

        $replacements = [
            '{name}' => $customer->name,
            '{product}' => $product,
            '{return_date}' => $returnDate ?? '-', 
            '{days_left}' => $daysLeft !== null ? (string) $daysLeft : '-', 
            '{total_rentals}' => (string) $customer->rentals()->count(),
            '{promo_code}' => $promoCode ? $promoCode : '',
            '{greeting}' => $greeting,
        ];

        $message = str_replace(array_keys($replacements), array_values($replacements), $template);

        return ['message' => $message, 'replacements' => $replacements];
    }

    private function buildGreeting(): string
    {
        $hour = Carbon::now('Asia/Jakarta')->hour;

        if ($hour < 10) {
            return 'Selamat Pagi';
        }

        if ($hour < 15) {
            return 'Selamat Siang';
        }

        if ($hour < 18) {
            return 'Selamat Sore';
        }

        return 'Selamat Malam';
    }

    private function sendToFonnte(string $phone, string $message): array
    {
        $token = config('services.fonnte.token');

        if (!$token) {
            Log::warning('Fonnte token not configured; skipping actual send.');
            return ['success' => false, 'body' => 'no-token'];
        }

        try {
            $response = Http::timeout(30)
                ->withOptions(['verify' => false])
                ->withHeaders(['Authorization' => $token])
                ->post('https://api.fonnte.com/send', [
                    'target' => $phone,
                    'message' => $message,
                ]);

            return [
                'success' => $response->successful(),
                'body' => $response->body(),
            ];
        } catch (\Throwable $e) {
            Log::error('Fonnte send error: ' . $e->getMessage());
            return ['success' => false, 'body' => $e->getMessage()];
        }
    }
}
