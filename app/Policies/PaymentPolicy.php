<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-payments');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->hasPermissionTo('view-payments');
    }

    public function create(User $user): bool
    {
        // فقط المحاسب والمدير
        return $user->hasAnyRole(['manager', 'accounting']) 
            && $user->hasPermissionTo('create-payments');
    }

    public function update(User $user, Payment $payment): bool
    {
        return $user->hasAnyRole(['manager', 'accounting']) 
            && $user->hasPermissionTo('edit-payments');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $user->hasAnyRole(['manager', 'accounting']) 
            && $user->hasPermissionTo('delete-payments');
    }
}
