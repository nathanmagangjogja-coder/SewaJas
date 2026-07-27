<?php

namespace App\Http\Controllers;

use App\Jobs\SendBroadcastJob;
use App\Models\BroadcastLog;
use App\Models\BroadcastSchedule;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BroadcastController extends Controller
{
    public function index()
    {
        $schedules = BroadcastSchedule::withCount('logs')
            ->latest()
            ->paginate(15);

        $logs = BroadcastLog::with('customer', 'schedule')
            ->latest()
            ->paginate(20);

        $customers = Customer::whereNotNull('phone')->where('phone', '!=', '')->where('is_blacklisted', false)->get();

        return view('broadcasts.index', compact('schedules', 'logs', 'customers'));
    }

    public function sendSelected(Request $request)
    {
        Log::info('=== sendSelected STARTED ===');
        
        $data = $request->validate([
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'integer|exists:customers,id',
            'message' => 'required|string|max:1000',
        ]);
        
        Log::info('Validated data: ', $data);

        $customers = Customer::whereIn('id', $data['customer_ids'])->get();
        Log::info('Found customers: ', ['count' => $customers->count()]);
        
        if ($customers->isEmpty()) {
            Log::warning('NO customers found!');
            return back()->with('error', 'Tidak ada pelanggan terpilih.');
        }

        $driver = config('services.broadcast.driver', 'fonnte');
        Log::info('Using driver: ', ['driver' => $driver]);
        $dispatched = 0;

        foreach ($customers as $customer) {
            try {
                Log::info('Processing customer: ', ['id' => $customer->id, 'name' => $customer->name, 'phone' => $customer->phone]);
                
                $phone = $this->formatPhoneNumber($customer->phone);
                Log::info('Formatted phone: ', ['phone' => $phone]);

                $success = false;
                $body = '';

                if ($driver === 'local') {
                    // Local mock mode
                    $body = json_encode(['status' => true, 'reason' => 'local-mock', 'target' => $phone]);
                    $success = true;
                    Log::info('Local mode, skipping real send');
                } elseif ($driver === 'custom') {
                    $cfg = config('services.broadcast.custom', []);
                    $url = $cfg['url'] ?? null;
                    $token = $cfg['token'] ?? null;
                    $tokenHeader = $cfg['token_header'] ?? 'Authorization';
                    $targetKey = $cfg['target_key'] ?? 'target';
                    $messageKey = $cfg['message_key'] ?? 'message';

                    if (!$url) {
                        throw new \RuntimeException('Custom provider not configured');
                    }

                    $client = Http::withOptions(['verify' => false])->timeout(30);
                    if ($token) {
                        $client = $client->withHeaders([$tokenHeader => $token]);
                    }

                    $response = $client->post($url, [$targetKey => $phone, $messageKey => $data['message']]);
                    $body = $response->body();
                    $success = $response->successful();
                    Log::info('Custom provider response: ', ['success' => $success, 'body' => $body]);
                } else {
                    // Fonnte mode
                    $token = config('services.fonnte.token');
                    Log::info('Fonnte token configured: ', ['has_token' => !empty($token)]);
                    
                    if (!$token) {
                        throw new \RuntimeException('Fonnte token not configured');
                    }
                    $response = Http::withOptions(['verify' => false])->timeout(30)->withHeaders(['Authorization' => $token])->post('https://api.fonnte.com/send', ['target' => $phone, 'message' => $data['message']]);
                    $body = $response->body();
                    $success = $response->successful();
                    Log::info('Fonnte response: ', ['success' => $success, 'status' => $response->status(), 'body' => $body]);
                }

                $logData = [
                    'broadcast_schedule_id' => null,
                    'customer_id' => $customer->id,
                    'template_index' => null,
                    'message_sent' => $data['message'],
                    'sent_at' => Carbon::now('Asia/Jakarta'),
                    'status' => $success ? 'sent' : 'failed',
                    'fonnte_response' => $body,
                ];
                
                Log::info('Creating BroadcastLog: ', $logData);
                $log = BroadcastLog::create($logData);
                Log::info('BroadcastLog created: ' . $log->id);

                $dispatched++;
            } catch (\Throwable $e) {
                Log::error('Manual broadcast failed for customer ' . $customer->id . ': ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                
                try {
                    BroadcastLog::create([
                        'broadcast_schedule_id' => null,
                        'customer_id' => $customer->id,
                        'template_index' => null,
                        'message_sent' => $data['message'],
                        'sent_at' => Carbon::now('Asia/Jakarta'),
                        'status' => 'failed',
                        'fonnte_response' => $e->getMessage(),
                    ]);
                } catch (\Throwable $logEx) {
                    Log::error('Failed to create error log: ' . $logEx->getMessage());
                }
            }
        }

        Log::info('=== sendSelected COMPLETED ===', ['dispatched' => $dispatched]);

        return back()->with('success', "Pengiriman manual: $dispatched pesan diproses.");
    }

    public function create()
    {
        return view('broadcasts.form', [
            'broadcast' => new BroadcastSchedule(),
            'templates' => [],
            'times' => ['08:00', '12:00', '16:00', '20:00'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);

        BroadcastSchedule::create($data);

        return redirect()->route('broadcasts.index')->with('success', 'Jadwal broadcast berhasil dibuat.');
    }

    public function edit(BroadcastSchedule $broadcast)
    {
        return view('broadcasts.form', [
            'broadcast' => $broadcast,
            'templates' => $broadcast->message_templates,
            'times' => $broadcast->send_at_times,
        ]);
    }

    public function update(Request $request, BroadcastSchedule $broadcast)
    {
        $data = $this->validateRequest($request);

        $broadcast->update($data);

        return redirect()->route('broadcasts.index')->with('success', 'Jadwal broadcast berhasil diperbarui.');
    }

    public function destroy(BroadcastSchedule $broadcast)
    {
        $broadcast->delete();
        return redirect()->route('broadcasts.index')->with('success', 'Jadwal broadcast berhasil dihapus.');
    }

    public function sendNow(Request $request, BroadcastSchedule $broadcast)
    {
        Log::info('=== sendNow STARTED ===', ['broadcast_id' => $broadcast->id]);
        
        $schedule = $broadcast->load('logs');
        $slotIndex = 0;
        $distribute = (bool) $request->input('distribute');

        Log::info('Fetching eligible customers...');
        $customersQuery = $this->eligibleCustomers($schedule)->select('customers.id')->distinct();
        $customers = $customersQuery->get()->map(fn($r) => $r->id)->toArray();

        Log::info('Eligible customer IDs:', $customers);

        if (empty($customers)) {
            Log::warning('NO eligible customers found!');
            return back()->with('error', 'Tidak ada pelanggan yang eligible untuk broadcast ini.');
        }

        $templates = $schedule->message_templates ?: [];
        Log::info('Number of templates:', ['count' => count($templates)]);
        $dispatched = 0;

        if ($distribute && count($templates) > 0) {
            Log::info('Using distribute mode');
            // Assign one template per customer: take first N customers where N = template count
            $targetCustomerIds = array_slice($customers, 0, count($templates));
            Log::info('Target customer IDs for distribute:', $targetCustomerIds);
            
            foreach ($targetCustomerIds as $i => $custId) {
                Log::info('Processing customer ' . $custId . ' with template ' . $i);
                try {
                    SendBroadcastJob::dispatchSync($custId, $schedule->id, 0, $i);
                    $dispatched++;
                    Log::info('Successfully processed customer ' . $custId);
                } catch (\Throwable $e) {
                    Log::error('Failed to process customer ' . $custId, [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        } else {
            Log::info('Using normal mode (send to all)');
            // Default behavior: dispatch to all eligible customers (one job per customer)
            foreach ($customers as $custId) {
                Log::info('Processing customer ' . $custId);
                try {
                    SendBroadcastJob::dispatchSync($custId, $schedule->id, $slotIndex);
                    $dispatched++;
                    Log::info('Successfully processed customer ' . $custId);
                } catch (\Throwable $e) {
                    Log::error('Failed to process customer ' . $custId, [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        }

        Log::info('=== sendNow COMPLETED ===', ['dispatched' => $dispatched]);

        return back()->with('success', "Broadcast berhasil: {$dispatched} pesan diproses. Periksa log untuk detail status.");
    }

    public function logs(BroadcastSchedule $broadcast)
    {
        $logs = $broadcast->logs()->with('customer')->latest()->paginate(20);

        $customers = Customer::whereNotNull('phone')->where('phone', '!=', '')->where('is_blacklisted', false)->get();

        return view('broadcasts.index', compact('logs', 'customers'))->with('broadcast', $broadcast);
    }

    public function clearLogs(BroadcastSchedule $broadcast)
    {
        // Only allow clearing logs for this schedule
        $deleted = $broadcast->logs()->delete();

        return back()->with('success', "Berhasil menghapus log broadcast: $deleted entri dihapus.");
    }

    private function eligibleCustomers(BroadcastSchedule $schedule)
    {
        Log::info('=== eligibleCustomers STARTED ===', ['target_audience' => $schedule->target_audience]);
        
        $customers = Customer::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where('is_blacklisted', false);

        Log::info('Base customer count:', ['count' => $customers->count()]);

        if ($schedule->target_audience === 'active_renters') {
            $customers->whereHas('rentals', fn($q) => $q->whereIn('rental_status', ['active', 'overdue']));
            Log::info('Applied active_renters filter');
        }

        if ($schedule->target_audience === 'overdue') {
            $customers->whereHas('rentals', fn($q) => $q->where('rental_status', 'overdue'));
            Log::info('Applied overdue filter');
        }

        if ($schedule->target_audience === 'returning_soon') {
            $customers->whereHas('rentals', fn($q) => $q->whereIn('rental_status', ['active', 'overdue'])
                ->whereDate('return_due_date', '<=', now('Asia/Jakarta')->addDays(3)));
            Log::info('Applied returning_soon filter');
        }

        $finalCount = $customers->count();
        Log::info('=== eligibleCustomers COMPLETED ===', ['final_count' => $finalCount]);
        
        return $customers;
    }

    private function validateRequest(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'send_at_times' => 'nullable|array',
            'send_at_times.*' => ['nullable', 'date_format:H:i'],
            'message_templates' => 'required|array|min:1',
            'message_templates.*' => 'required|string|max:500',
            'is_active' => 'nullable|boolean',
            'target_audience' => 'required|in:all,active_renters,overdue,returning_soon',
            'cooldown_hours' => 'required|integer|min:1',
        ]);

        // Filter out empty time slots
        $sendAtTimes = array_filter($data['send_at_times'] ?? [], function ($time) {
            return !empty($time);
        });

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'send_at_times' => array_values($sendAtTimes),
            'message_templates' => $data['message_templates'],
            'is_active' => $request->has('is_active'),
            'target_audience' => $data['target_audience'],
            'cooldown_hours' => $data['cooldown_hours'],
        ];
    }

    private function formatPhoneNumber(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($clean, '0')) {
            return '62' . substr($clean, 1);
        }
        return str_starts_with($clean, '62') ? $clean : '62' . $clean;
    }

    private function timeMatches(string $currentHHMM, string $slot): bool
    {
        if (!preg_match('/^\d{2}:\d{2}$/', $slot)) return false;

        [$ch, $cm] = array_map('intval', explode(':', $currentHHMM));
        [$sh, $sm] = array_map('intval', explode(':', $slot));

        $currentMinutes = $ch * 60 + $cm;
        $slotMinutes    = $sh * 60 + $sm;

        return abs($currentMinutes - $slotMinutes) <= 5;
    }
}