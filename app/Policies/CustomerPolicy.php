<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-customers');
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('view-customers');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-customers');
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('edit-customers');
    }

    public function delete(User $user, Customer $customer): bool
    {
        // فقط المدير يحذف العملاء
        return $user->hasRole('manager') 
            && $user->hasPermissionTo('delete-customers');
    }

    public function viewLedger(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('view-customer-ledger');
    }

    public function printStatement(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('print-customer-statement');
    }
}
