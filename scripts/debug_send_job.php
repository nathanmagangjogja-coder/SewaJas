<?php
// Temporary debug runner for SendBroadcastJob
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Starting SendBroadcastJob for customer=1 schedule=1 slot=0\n";
    $job = new App\Jobs\SendBroadcastJob(1, 1, 0);
    $job->handle();
    echo "Job executed\n";
} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

