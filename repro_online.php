<?php
// Reproduce the online-path admission 500 locally against a seeded sqlite.
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Find an ONLINE course
$course = App\Models\Course::where('delivery_mode', 'online')->first();
echo "online course: ".($course ? "{$course->id} {$course->name} status={$course->status}" : "NONE")."\n";
if (! $course) { exit("no online course\n"); }

$getRes = $kernel->handle(Illuminate\Http\Request::create('/admission', 'GET'));
$session = $app['session']->driver();
$token = $session->token();

$email = 'onlineprobe'.time().'@example.com';
$req = Illuminate\Http\Request::create('/admission', 'POST', [
    '_token' => $token,
    'name_bn' => 'অনলাইন প্রোব',
    'email' => $email,
    'phone' => '01712345678',
    'admission_mode' => 'online',
    'course_id' => $course->id,
]);
$req->setLaravelSession($session);

\Illuminate\Support\Facades\Log::flushSharedContext();
$res = $kernel->handle($req);
echo "POST (online) => ".$res->getStatusCode()."\n";
echo "Location: ".($res->headers->get('Location') ?: '(none)')."\n";

$log = storage_path('logs/laravel.log');
$c = is_file($log) ? file_get_contents($log) : '';
if (preg_match_all('/\w+\.ERROR: (.{0,500})/', $c, $m)) {
    echo "\n=== LOG ERRORS (last 2) ===\n";
    foreach (array_slice($m[1], -2) as $line) {
        echo trim(preg_replace('/\s+/', ' ', $line))."\n\n";
    }
}
