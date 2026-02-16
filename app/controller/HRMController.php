<?php

namespace app\controller;

use support\Request;
use app\model\Employee;
use app\model\EmploymentContract;
use app\model\Leave;
use app\model\Payroll;
use app\model\TimeTracking;

class HRMController
{
    /**
     * Главная страница HRM системы - дашборд
     */
    public function index(Request $request)
    {
        $stats = [
            'total_employees' => count(Employee::all()),
            'active_employees' => count(Employee::getActiveEmployees()),
            'pending_leaves' => count(array_filter(Leave::all(), function($leave) {
                return $leave->status === 'заявлен';
            })),
            'current_month_payroll' => Payroll::getTotalSalaryByPeriod(date('Y'), date('m'))
        ];

        return view('hrm/index', ['stats' => $stats]);
    }

    // ============ УПРАВЛЕНИЕ СОТРУДНИКАМИ ============

    /**
     * Список всех сотрудников
     */
    public function employees(Request $request)
    {
        $employees = Employee::all();
        return view('hrm/employees/index', ['employees' => $employees]);
    }

    /**
     * Форма создания нового сотрудника
     */
    public function createEmployee(Request $request)
    {
        return view('hrm/employees/create');
    }

    /**
     * Сохранение нового сотрудника
     */
    public function storeEmployee(Request $request)
    {
        $data = $request->post();

        $employee = new Employee();
        $employee->iin = $data['iin'] ?? '';
        $employee->full_name = $data['full_name'] ?? '';
        $employee->date_of_birth = $data['date_of_birth'] ?? null;
        $employee->gender = $data['gender'] ?? '';
        $employee->citizenship = $data['citizenship'] ?? 'Казахстан';
        $employee->address = $data['address'] ?? '';
        $employee->phone = $data['phone'] ?? '';
        $employee->email = $data['email'] ?? '';
        $employee->position = $data['position'] ?? '';
        $employee->department = $data['department'] ?? '';
        $employee->hire_date = $data['hire_date'] ?? date('Y-m-d');
        $employee->contract_type = $data['contract_type'] ?? 'бессрочный';
        $employee->salary_base = (float)($data['salary_base'] ?? 0);
        $employee->salary_type = $data['salary_type'] ?? 'месячная';
        $employee->work_schedule = $data['work_schedule'] ?? '5/2';
        $employee->status = 'работает';
        $employee->bank_account = $data['bank_account'] ?? '';
        $employee->bank_name = $data['bank_name'] ?? '';

        $employee->save();

        return redirect('/hrm/employees');
    }

    /**
     * Просмотр сотрудника
     */
    public function showEmployee(Request $request, $id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return response('Сотрудник не найден', 404);
        }

        $contracts = EmploymentContract::getActiveContractsByEmployee($id);
        $recentLeaves = array_slice(Leave::getLeavesByEmployee($id), 0, 5);

        return view('hrm/employees/show', [
            'employee' => $employee,
            'contracts' => $contracts,
            'recentLeaves' => $recentLeaves
        ]);
    }

    // ============ УПРАВЛЕНИЕ ДОГОВОРАМИ ============

    /**
     * Список трудовых договоров
     */
    public function contracts(Request $request)
    {
        $contracts = EmploymentContract::all();
        return view('hrm/contracts/index', ['contracts' => $contracts]);
    }

    /**
     * Форма создания трудового договора
     */
    public function createContract(Request $request)
    {
        $employees = Employee::getActiveEmployees();
        return view('hrm/contracts/create', ['employees' => $employees]);
    }

    /**
     * Сохранение трудового договора
     */
    public function storeContract(Request $request)
    {
        $data = $request->post();

        $contract = new EmploymentContract();
        $contract->employee_id = (int)$data['employee_id'];
        $contract->contract_number = EmploymentContract::generateContractNumber();
        $contract->contract_type = $data['contract_type'] ?? 'бессрочный';
        $contract->start_date = $data['start_date'] ?? date('Y-m-d');
        $contract->end_date = $data['end_date'] ?? null;
        $contract->position = $data['position'] ?? '';
        $contract->salary_base = (float)($data['salary_base'] ?? 0);
        $contract->salary_type = $data['salary_type'] ?? 'месячная';
        $contract->work_schedule = $data['work_schedule'] ?? '5/2';
        $contract->work_hours_per_week = (int)($data['work_hours_per_week'] ?? 40);
        $contract->trial_period = (int)($data['trial_period'] ?? 0);
        $contract->workplace = $data['workplace'] ?? '';
        $contract->responsibilities = $data['responsibilities'] ?? '';
        $contract->working_conditions = $data['working_conditions'] ?? '';
        $contract->status = 'активный';

        $contract->save();

        return redirect('/hrm/contracts');
    }

    // ============ УПРАВЛЕНИЕ ОТПУСКАМИ ============

    /**
     * Список всех заявок на отпуска
     */
    public function leaves(Request $request)
    {
        $leaves = Leave::all();
        return view('hrm/leaves/index', ['leaves' => $leaves]);
    }

    /**
     * Форма создания заявки на отпуск
     */
    public function createLeave(Request $request)
    {
        $employees = Employee::getActiveEmployees();
        return view('hrm/leaves/create', ['employees' => $employees]);
    }

    /**
     * Сохранение заявки на отпуск
     */
    public function storeLeave(Request $request)
    {
        $data = $request->post();

        $leave = new Leave();
        $leave->employee_id = (int)$data['employee_id'];
        $leave->leave_type = $data['leave_type'] ?? 'ежегодный';
        $leave->start_date = $data['start_date'];
        $leave->end_date = $data['end_date'];
        $leave->reason = $data['reason'] ?? '';
        $leave->status = 'заявлен';

        $leave->calculateDaysCount();
        $leave->save();

        return redirect('/hrm/leaves');
    }

    /**
     * Одобрение/отклонение отпуска
     */
    public function updateLeaveStatus(Request $request, $id)
    {
        $data = $request->post();
        $leave = Leave::find($id);

        if ($leave) {
            $leave->status = $data['status'];
            $leave->approved_by = $data['approved_by'] ?? 'HR Manager';
            $leave->approved_date = date('Y-m-d');
            $leave->save();
        }

        return redirect('/hrm/leaves');
    }

    // ============ РАСЧЕТ ЗАРАБОТНОЙ ПЛАТЫ ============

    /**
     * Расчет зарплаты за месяц
     */
    public function payroll(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));

        $payrolls = [];
        $employees = Employee::getActiveEmployees();

        foreach ($employees as $employee) {
            $payroll = Payroll::getPayrollByPeriod($employee->id, $year, $month);
            if (!$payroll) {
                // Создаем расчет если его нет
                $payroll = Payroll::createPayroll(
                    $employee->id,
                    $year,
                    $month,
                    $employee->salary_base
                );
            }
            $payrolls[] = $payroll;
        }

        $total = Payroll::getTotalSalaryByPeriod($year, $month);

        return view('hrm/payroll/index', [
            'payrolls' => $payrolls,
            'total' => $total,
            'year' => $year,
            'month' => $month
        ]);
    }

    // ============ УЧЕТ РАБОЧЕГО ВРЕМЕНИ ============

    /**
     * Табель учета рабочего времени
     */
    public function timeTracking(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));

        $employees = Employee::getActiveEmployees();
        $timeData = [];

        foreach ($employees as $employee) {
            $summary = TimeTracking::getMonthlySummary($employee->id, $year, $month);
            $timeData[] = [
                'employee' => $employee,
                'summary' => $summary
            ];
        }

        return view('hrm/time_tracking/index', [
            'timeData' => $timeData,
            'year' => $year,
            'month' => $month
        ]);
    }

    /**
     * Отчеты для налоговых органов
     */
    public function reports(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));

        // Получаем сводные данные
        $totalPayroll = Payroll::getTotalSalaryByPeriod($year, $month);
        $activeEmployees = Employee::getActiveEmployees();
        $totalEmployees = count($activeEmployees);

        return view('hrm/reports/index', [
            'totalPayroll' => $totalPayroll,
            'totalEmployees' => $totalEmployees,
            'year' => $year,
            'month' => $month
        ]);
    }
}
