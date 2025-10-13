<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\SequencerService;

class SequenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إعداد متسلسلة أذون الصرف
        SequencerService::configure('issue_voucher', [
            'prefix' => 'ISS-',
            'current_value' => 0,
            'increment_by' => 1,
            'min_value' => 1,
            'auto_reset' => true, // إعادة تعيين سنوياً
        ]);

        // إعداد متسلسلة أذون الإرجاع (نطاق خاص: 100001-125000)
        SequencerService::configure('return_voucher', [
            'prefix' => 'RET-',
            'current_value' => 100000, // سيبدأ من 100001
            'increment_by' => 1,
            'min_value' => 100001,
            'max_value' => 125000,
            'auto_reset' => true,
        ]);

        // إعداد متسلسلة نقل المخزون بين الفروع
        SequencerService::configure('transfer', [
            'prefix' => 'TRF-',
            'current_value' => 0,
            'increment_by' => 1,
            'min_value' => 1,
            'auto_reset' => true,
        ]);

        // إعداد متسلسلة المدفوعات
        SequencerService::configure('payment', [
            'prefix' => 'PAY-',
            'current_value' => 0,
            'increment_by' => 1,
            'min_value' => 1,
            'auto_reset' => true,
        ]);

        $this->command->info('✅ تم إنشاء المتسلسلات بنجاح:');
        $this->command->info('   - issue_voucher (ISS-00001, ISS-00002, ...)');
        $this->command->info('   - return_voucher (RET-100001, RET-100002, ...)');
        $this->command->info('   - transfer (TRF-00001, TRF-00002, ...)');
        $this->command->info('   - payment (PAY-00001, PAY-00002, ...)');
    }
}
