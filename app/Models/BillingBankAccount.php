<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingBankAccount extends Model
{
    protected $fillable = ['bank_name', 'account_name', 'account_number', 'branch', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
