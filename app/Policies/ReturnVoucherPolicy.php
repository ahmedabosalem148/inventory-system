<?php

namespace App\Policies;

use App\Models\ReturnVoucher;
use App\Models\User;

class ReturnVoucherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-return-vouchers');
    }

    public function view(User $user, ReturnVoucher $returnVoucher): bool
    {
        if ($user->hasRole('manager')) {
            return true;
        }

        if ($user->hasRole('store_user')) {
            // return $returnVoucher->branch_target_id === $user->branch_id;
            return true; // مؤقت
        }

        if ($user->hasRole('accounting')) {
            return true; // قراءة فقط
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-return-vouchers');
    }

    public function update(User $user, ReturnVoucher $returnVoucher): bool
    {
        if ($returnVoucher->status === 'APPROVED') {
            return false;
        }

        if ($user->hasRole('manager')) {
            return $user->hasPermissionTo('edit-return-vouchers');
        }

        if ($user->hasRole('store_user')) {
            return $user->hasPermissionTo('edit-return-vouchers'); // مؤقت
        }

        return false; // Accounting لا يعدل
    }

    public function approve(User $user, ReturnVoucher $returnVoucher): bool
    {
        if ($returnVoucher->status === 'APPROVED') {
            return false;
        }

        if ($user->hasRole('manager')) {
            return $user->hasPermissionTo('approve-return-vouchers');
        }

        if ($user->hasRole('store_user')) {
            return $user->hasPermissionTo('approve-return-vouchers'); // مؤقت
        }

        return false; // Accounting لا يعتمد
    }

    public function delete(User $user, ReturnVoucher $returnVoucher): bool
    {
        if ($returnVoucher->status === 'APPROVED') {
            return false;
        }

        return $user->hasRole('manager') 
            && $user->hasPermissionTo('delete-return-vouchers');
    }

    public function print(User $user, ReturnVoucher $returnVoucher): bool
    {
        if ($returnVoucher->status !== 'APPROVED') {
            return false;
        }

        return $user->hasPermissionTo('print-return-vouchers');
    }
}
