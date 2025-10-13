<?php

require_once __DIR__ . '/bootstrap/app.php';

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

// Create token
$user = App\Models\User::first();
if ($user) {
    $token = $user->createToken('api-token');
    echo "New Token: " . $token->plainTextToken . PHP_EOL;
    echo "User: " . $user->name . " (" . $user->email . ")" . PHP_EOL;
} else {
    echo "No users found!" . PHP_EOL;
}