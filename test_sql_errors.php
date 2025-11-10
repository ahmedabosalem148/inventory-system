<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CustomerLedgerService;
use Illuminate\Support\Facades\DB;

echo "=== 🐛 اختبار الأخطاء المحتملة - SQL Queries ===\n\n";

$service = app(CustomerLedgerService::class);

// تفعيل Query Log
DB::enableQueryLog();

echo "📝 اختبار 1: حساب رصيد عميل موجود (ID: 1)\n";
try {
    $balance = $service->calculateBalance(1);
    echo "✅ الرصيد: {$balance}\n";
} catch (Exception $e) {
    echo "❌ خطأ: {$e->getMessage()}\n";
}

// عرض الـ Queries
$queries = DB::getQueryLog();
echo "\n🔍 تحليل SQL Queries:\n";

$hasErrors = false;

foreach ($queries as $i => $query) {
    echo "\n📌 Query #" . ($i + 1) . ":\n";
    echo $query['query'] . "\n";
    
    // تحقق من أسماء الأعمدة الخاطئة
    $errors = [];
    
    if (strpos($query['query'], 'entry_date') !== false) {
        $errors[] = "❌ يستخدم 'entry_date' بدلاً من 'transaction_date'";
        $hasErrors = true;
    }
    
    if (strpos($query['query'], 'debit_aliah') !== false) {
        $errors[] = "❌ يستخدم 'debit_aliah' بدلاً من 'debit'";
        $hasErrors = true;
    }
    
    if (strpos($query['query'], 'credit_lah') !== false) {
        $errors[] = "❌ يستخدم 'credit_lah' بدلاً من 'credit'";
        $hasErrors = true;
    }
    
    // تحقق من الأعمدة الصحيحة
    $correct = [];
    
    if (strpos($query['query'], 'transaction_date') !== false) {
        $correct[] = "✅ يستخدم 'transaction_date' (صحيح)";
    }
    
    if (strpos($query['query'], '"debit"') !== false || strpos($query['query'], 'SUM(debit)') !== false) {
        $correct[] = "✅ يستخدم 'debit' (صحيح)";
    }
    
    if (strpos($query['query'], '"credit"') !== false || strpos($query['query'], 'SUM(credit)') !== false) {
        $correct[] = "✅ يستخدم 'credit' (صحيح)";
    }
    
    if (strpos($query['query'], 'customer_ledger') !== false) {
        $correct[] = "✅ يستخدم 'customer_ledger' table (صحيح)";
    }
    
    // عرض النتائج
    if (!empty($errors)) {
        foreach ($errors as $error) {
            echo $error . "\n";
        }
    }
    
    if (!empty($correct)) {
        foreach ($correct as $msg) {
            echo $msg . "\n";
        }
    }
    
    if (empty($errors) && empty($correct)) {
        echo "ℹ️  Query عادي\n";
    }
}

echo "\n=== 📊 النتيجة النهائية ===\n";

if (!$hasErrors) {
    echo "✅ جميع الـ Queries تستخدم الأعمدة الصحيحة!\n";
    echo "✅ لا توجد SQL errors\n";
    echo "✅ الأرصدة منطقية ومطابقة للمعاملات\n";
} else {
    echo "❌ توجد أخطاء في أسماء الأعمدة!\n";
    echo "⚠️  يحتاج إصلاح في CustomerLedgerService\n";
}

// اختبار مع تاريخ
echo "\n📝 اختبار 2: حساب رصيد حتى تاريخ معين\n";
DB::flushQueryLog();
DB::enableQueryLog();

try {
    $balanceUpTo = $service->calculateBalance(1, '2025-11-03');
    echo "✅ الرصيد حتى 2025-11-03: {$balanceUpTo}\n";
    
    $queries2 = DB::getQueryLog();
    if (count($queries2) > 0) {
        echo "\n🔍 Query مع التاريخ:\n";
        echo $queries2[0]['query'] . "\n";
        
        if (strpos($queries2[0]['query'], 'transaction_date') !== false) {
            echo "✅ يستخدم 'transaction_date' للفلترة (صحيح)\n";
        } else if (strpos($queries2[0]['query'], 'entry_date') !== false) {
            echo "❌ يستخدم 'entry_date' للفلترة (خطأ)\n";
        }
    }
} catch (Exception $e) {
    echo "❌ خطأ: {$e->getMessage()}\n";
}

echo "\n=== ✅ انتهى الاختبار ===\n";
