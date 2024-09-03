<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'public_employee_id', 'payment_month', 'amount', 'descontos',
    ];

    // Exemplo de relação com PublicEmployee
    public function publicEmployee()
    {
        return $this->belongsTo(PublicEmployee::class);
    }
}
