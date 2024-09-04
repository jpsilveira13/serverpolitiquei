<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicEmployee extends Model
{
    protected $table = 'employees_public';

    protected $fillable = [
        'employee_type_id',
        'city_id',
        'matricula',
        'active',
        'nome',
        'documento',
        'admissao',
        'cargo_funcao',
        'lotacao',
        'local_trabalho',
        'carga_horaria',
        'latitude',
        'longitude',
        'last_synced_at'
    ];

    // Relacionamento com os pagamentos
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function employeeType()
    {
        return $this->belongsTo(EmployeeType::class);
    }
}
