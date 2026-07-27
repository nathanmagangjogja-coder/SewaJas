<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$s = App\Models\BroadcastSchedule::find(1);
if (!$s) {
    echo "No schedule 1\n";
    exit;
}

App\Models\Customer::whereNotNull('phone')
    ->where('phone', '!=', '')
    ->where('is_blacklisted', false)
    ->chunk(100, function($chunk) use ($s) {
        foreach ($chunk as $c) {
            App\Jobs\SendBroadcastJob::dispatch($c->id, $s->id, 0);
        }
    });

echo "enqueued\n";
