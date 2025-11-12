<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Services\NotificationService;
use App\Models\Notification;
use Carbon\Carbon;

class SendPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:send-reminders {--days=3 : Days until payment due}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send payment reminder notifications for customers with due payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $daysUntilDue = (int) $this->option('days');
        $targetDate = Carbon::now()->addDays($daysUntilDue)->format('Y-m-d');
        
        $this->info("Checking for payments due on or before: {$targetDate}");

        // Find customers with outstanding balance
        $customers = Customer::where('outstanding_balance', '>', 0)
            ->where('is_active', true)
            ->get();

        if ($customers->isEmpty()) {
            $this->info('No customers with outstanding balance found.');
            return 0;
        }

        $notificationService = new NotificationService();
        $sentCount = 0;

        foreach ($customers as $customer) {
            try {
                // Send notification to all managers and accountants
                $notificationService->sendToRole(
                    'manager',
                    Notification::TYPE_PAYMENT_DUE,
                    'تذكير بدفعة مستحقة',
                    "العميل \"{$customer->name}\" لديه رصيد مستحق: {$customer->outstanding_balance} ريال",
                    [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'amount' => $customer->outstanding_balance,
                        'days_until_due' => $daysUntilDue,
                    ],
                    "#customers/{$customer->id}"
                );

                $notificationService->sendToRole(
                    'accounting',
                    Notification::TYPE_PAYMENT_DUE,
                    'تذكير بدفعة مستحقة',
                    "العميل \"{$customer->name}\" لديه رصيد مستحق: {$customer->outstanding_balance} ريال",
                    [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'amount' => $customer->outstanding_balance,
                        'days_until_due' => $daysUntilDue,
                    ],
                    "#customers/{$customer->id}"
                );

                $sentCount++;
                $this->info("✓ Sent reminder for customer: {$customer->name}");
            } catch (\Exception $e) {
                $this->error("✗ Failed to send reminder for customer {$customer->name}: {$e->getMessage()}");
            }
        }

        $this->info("\n✓ Payment reminders sent successfully!");
        $this->info("  • Total customers with outstanding balance: {$customers->count()}");
        $this->info("  • Notifications sent: {$sentCount}");

        return 0;
    }
}
