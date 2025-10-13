<?php

namespace App\Policies;

use App\Models\IssueVoucher;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class IssueVoucherPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-issue-vouchers');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, IssueVoucher $issueVoucher): bool
    {
        // Manager: يرى كل شيء
        if ($user->hasRole('manager')) {
            return true;
        }

        // Store User: يرى فقط أذون فرعه
        if ($user->hasRole('store_user')) {
            // TODO: إضافة branch_id للـ User model
            // return $issueVoucher->branch_source_id === $user->branch_id;
            return true; // مؤقت
        }

        // Accounting: يرى كل شيء (قراءة فقط)
        if ($user->hasRole('accounting')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-issue-vouchers');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, IssueVoucher $issueVoucher): bool
    {
        // لا يمكن تعديل إذن معتمد
        if ($issueVoucher->status === 'APPROVED') {
            return false;
        }

        // Manager: يعدل كل شيء
        if ($user->hasRole('manager')) {
            return $user->hasPermissionTo('edit-issue-vouchers');
        }

        // Store User: يعدل فقط أذون فرعه
        if ($user->hasRole('store_user')) {
            // return $issueVoucher->branch_source_id === $user->branch_id 
            //     && $user->hasPermissionTo('edit-issue-vouchers');
            return $user->hasPermissionTo('edit-issue-vouchers'); // مؤقت
        }

        // Accounting: لا يعدل المخزون
        return false;
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, IssueVoucher $issueVoucher): bool
    {
        // لا يمكن اعتماد إذن معتمد مسبقاً
        if ($issueVoucher->status === 'APPROVED') {
            return false;
        }

        // Manager: يعتمد كل شيء
        if ($user->hasRole('manager')) {
            return $user->hasPermissionTo('approve-issue-vouchers');
        }

        // Store User: يعتمد فقط أذون فرعه
        if ($user->hasRole('store_user')) {
            // return $issueVoucher->branch_source_id === $user->branch_id 
            //     && $user->hasPermissionTo('approve-issue-vouchers');
            return $user->hasPermissionTo('approve-issue-vouchers'); // مؤقت
        }

        // Accounting: لا يعتمد المخزون
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, IssueVoucher $issueVoucher): bool
    {
        // لا يمكن حذف إذن معتمد
        if ($issueVoucher->status === 'APPROVED') {
            return false;
        }

        return $user->hasRole('manager') 
            && $user->hasPermissionTo('delete-issue-vouchers');
    }

    /**
     * Determine whether the user can print the model.
     */
    public function print(User $user, IssueVoucher $issueVoucher): bool
    {
        // الطباعة متاحة فقط بعد الاعتماد
        if ($issueVoucher->status !== 'APPROVED') {
            return false;
        }

        return $user->hasPermissionTo('print-issue-vouchers');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, IssueVoucher $issueVoucher): bool
    {
        return $user->hasRole('manager');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, IssueVoucher $issueVoucher): bool
    {
        return $user->hasRole('manager');
    }
}
