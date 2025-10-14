<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\SequencerService;

class SequenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * تكوين التسلسلات حسب متطلبات TASK-003:
     * - أذون الصرف: 1-999999
     * - أذون الإرجاع: 100001-125000 (نطاق خاص)
     * - أذون التحويل: 1-999999
     * - المدفوعات: 1-999999
     */
    public function run(): void
    {
        $currentYear = now()->year;

        // إعداد متسلسلة أذون الصرف (Issue Vouchers)
        SequencerService::configure('issue_vouchers', [
            'year' => $currentYear,
            'prefix' => 'ISS-',
            'current_value' => 0, // سيبدأ من 1
            'increment_by' => 1,
            'min_value' => 1,
            'max_value' => 999999,
            'auto_reset' => true, // إعادة تعيين سنوياً
        ]);

        // إعداد متسلسلة أذون الإرجاع (نطاق خاص: 100001-125000)
        SequencerService::configure('return_vouchers', [
            'year' => $currentYear,
            'prefix' => 'RET-',
            'current_value' => 100000, // سيبدأ من 100001
            'increment_by' => 1,
            'min_value' => 100001,
            'max_value' => 125000,
            'auto_reset' => true,
        ]);

        // إعداد متسلسلة نقل المخزون بين الفروع
        SequencerService::configure('transfer_vouchers', [
            'year' => $currentYear,
            'prefix' => 'TRF-',
            'current_value' => 0, // سيبدأ من 1
            'increment_by' => 1,
            'min_value' => 1,
            'max_value' => 999999,
            'auto_reset' => true,
        ]);

        // إعداد متسلسلة المدفوعات
        SequencerService::configure('payments', [
            'year' => $currentYear,
            'prefix' => 'PAY-',
            'current_value' => 0, // سيبدأ من 1
            'increment_by' => 1,
            'min_value' => 1,
            'max_value' => 999999,
            'auto_reset' => true,
        ]);

        $this->command->info('✅ تم إنشاء المتسلسلات بنجاح:');
        $this->command->info('   - issue_vouchers: ISS-' . $currentYear . '/00001, ISS-' . $currentYear . '/00002, ...');
        $this->command->info('   - return_vouchers: RET-' . $currentYear . '/100001, RET-' . $currentYear . '/100002, ... (نطاق: 100001-125000)');
        $this->command->info('   - transfer_vouchers: TRF-' . $currentYear . '/00001, TRF-' . $currentYear . '/00002, ...');
        $this->command->info('   - payments: PAY-' . $currentYear . '/00001, PAY-' . $currentYear . '/00002, ...');
    }
}
