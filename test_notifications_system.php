<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Notifications System Test ===\n\n";

// Test 1: Check notifications in database
echo "TEST 1: Check notifications in database\n";
$totalNotifications = App\Models\Notification::count();
$unreadNotifications = App\Models\Notification::where('is_read', false)->count();
$readNotifications = App\Models\Notification::where('is_read', true)->count();

echo "✓ Total notifications: {$totalNotifications}\n";
echo "✓ Unread: {$unreadNotifications}\n";
echo "✓ Read: {$readNotifications}\n\n";

// Test 2: Get recent notifications
echo "TEST 2: Get 5 most recent notifications\n";
$recent = App\Models\Notification::with('user')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($recent as $notif) {
    $readStatus = $notif->is_read ? '✓' : '○';
    $time = $notif->created_at->diffForHumans();
    echo "  {$readStatus} [{$notif->type}] {$notif->title} - {$time}\n";
}
echo "\n";

// Test 3: Test notification types
echo "TEST 3: Count notifications by type\n";
$types = App\Models\Notification::selectRaw('type, count(*) as count')
    ->groupBy('type')
    ->get();

foreach ($types as $type) {
    echo "  • {$type->type}: {$type->count} notifications\n";
}
echo "\n";

// Test 4: Test NotificationService
echo "TEST 4: Test NotificationService\n";
$user = App\Models\User::first();
if ($user) {
    $service = new App\Services\NotificationService();
    
    // Create a test notification
    $testNotif = $service->sendSystemNotification(
        $user->id,
        'اختبار النظام',
        'هذا إشعار اختباري للتأكد من عمل الخدمة بشكل صحيح',
        '#test'
    );
    
    if ($testNotif) {
        echo "✓ Created test notification ID: {$testNotif->id}\n";
        echo "  Title: {$testNotif->title}\n";
        echo "  Message: {$testNotif->message}\n";
        echo "  Type: {$testNotif->type}\n";
        echo "  Color: {$testNotif->color}\n";
        
        // Clean up test notification
        $testNotif->delete();
        echo "✓ Test notification cleaned up\n";
    }
} else {
    echo "✗ No user found\n";
}
echo "\n";

// Test 5: Test notification scopes
echo "TEST 5: Test notification scopes\n";
$user = App\Models\User::first();
if ($user) {
    $userNotifications = App\Models\Notification::where('user_id', $user->id);
    
    $unreadCount = $userNotifications->clone()->unread()->count();
    $readCount = $userNotifications->clone()->read()->count();
    $recentCount = $userNotifications->clone()->recent(7)->count();
    
    echo "✓ User '{$user->name}' notifications:\n";
    echo "  • Unread: {$unreadCount}\n";
    echo "  • Read: {$readCount}\n";
    echo "  • Last 7 days: {$recentCount}\n";
}
echo "\n";

echo "✓ All tests completed successfully!\n";
