<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PublicEmployee;




class FuncionarioPublicoController extends Controller
{

    public function index(Request $request)
    {
        $baseQuery = PublicEmployee::query();

        // Filtragem por nome
        if ($request->has('nome') && $request->nome !== '') {
            $baseQuery->where('nome', 'like', '%' . $request->nome . '%');
        }

        // Filtragem por tipo de funcionário
        if ($request->has('employee_type_id') && $request->employee_type_id !== null) {

            $baseQuery->where('employee_type_id', $request->employee_type_id);
        }


        // Calcula a quantidade total de funcionários
        $totalEmployees = $baseQuery->count();

        // Clona a consulta base para calcular a soma total dos salários
        $salaryQuery = clone $baseQuery;
        $totalSalary = $salaryQuery->leftJoin('payments', 'employees_public.id', '=', 'payments.public_employee_id')
            ->sum('payments.amount');

        // Consulta principal para obter os funcionários paginados
        $employees = $baseQuery->with(['employeeType', 'city'])
            ->leftJoin('payments', 'employees_public.id', '=', 'payments.public_employee_id')
            ->selectRaw('employees_public.*, MAX(payments.amount) as max_salary, SUM(payments.descontos) as total_descontos')
            ->groupBy('employees_public.id')
            ->orderByDesc('max_salary')
            ->paginate(300);

        return response()->json([
            'total_employees' => $totalEmployees,
            'total_salary' => $totalSalary,
            'employees' => $employees,
        ]);
    }

}
