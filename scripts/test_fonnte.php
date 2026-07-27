<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

try {
    $token = env('FONNTE_TOKEN');
    echo "Using token: ".($token ?: '[none]')."\n";

    $res = Http::withOptions(['verify' => false])
        ->withHeaders(['Authorization' => 'Bearer ' . $token])
        ->post('https://api.fonnte.com/send', [
            'target' => '628111222333',
            'message' => 'diagnostic test from local',
        ]);

    echo "STATUS: " . $res->status() . PHP_EOL;
    echo "BODY: " . $res->body() . PHP_EOL;
} catch (Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
