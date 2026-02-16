<?php

namespace app\model;

class Payroll
{
    public $id;
    public $employee_id;
    public $period_start; // Начало периода
    public $period_end; // Конец периода
    public $year; // Год
    public $month; // Месяц
    public $base_salary; // Базовая зарплата
    public $overtime_hours; // Переработанные часы
    public $overtime_rate; // Ставка за переработку
    public $bonuses; // Премии и надбавки
    public $deductions; // Вычеты
    public $gross_salary; // Валовая зарплата
    public $income_tax; // Индивидуальный подоходный налог (10%)
    public $social_tax; // Социальный налог (9.5% для работодателя)
    public $social_contribution; // Обязательные пенсионные взносы (10% для работника)
    public $medical_contribution; // Взносы на обязательное медицинское страхование (2% для работника)
    public $net_salary; // Чистая зарплата
    public $status; // Статус (расчетный, выплачен)
    public $payment_date; // Дата выплаты
    public $created_at;
    public $updated_at;

    private static function getDb()
    {
        return new \PDO('sqlite:' . base_path() . '/database.sqlite');
    }

    /**
     * Получить все расчеты зарплаты
     */
    public static function all()
    {
        $db = self::getDb();
        $stmt = $db->query('SELECT * FROM payroll ORDER BY year DESC, month DESC');
        $payrolls = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $payroll = new self();
            foreach ($row as $key => $value) {
                $payroll->$key = $value;
            }
            $payrolls[] = $payroll;
        }

        return $payrolls;
    }

    /**
     * Найти расчет зарплаты по ID
     */
    public static function find($id)
    {
        $db = self::getDb();
        $stmt = $db->prepare('SELECT * FROM payroll WHERE id = ?');
        $stmt->execute([$id]);

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $payroll = new self();
            foreach ($row as $key => $value) {
                $payroll->$key = $value;
            }
            return $payroll;
        }

        return null;
    }

    /**
     * Получить расчеты зарплаты сотрудника
     */
    public static function getPayrollsByEmployee($employeeId, $year = null, $month = null)
    {
        $db = self::getDb();

        $query = 'SELECT * FROM payroll WHERE employee_id = ?';
        $params = [$employeeId];

        if ($year) {
            $query .= ' AND year = ?';
            $params[] = $year;
        }

        if ($month) {
            $query .= ' AND month = ?';
            $params[] = $month;
        }

        $query .= ' ORDER BY year DESC, month DESC';

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        $payrolls = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $payroll = new self();
            foreach ($row as $key => $value) {
                $payroll->$key = $value;
            }
            $payrolls[] = $payroll;
        }

        return $payrolls;
    }

    /**
     * Получить расчет зарплаты за период
     */
    public static function getPayrollByPeriod($employeeId, $year, $month)
    {
        $db = self::getDb();
        $stmt = $db->prepare('SELECT * FROM payroll WHERE employee_id = ? AND year = ? AND month = ?');
        $stmt->execute([$employeeId, $year, $month]);

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $payroll = new self();
            foreach ($row as $key => $value) {
                $payroll->$key = $value;
            }
            return $payroll;
        }

        return null;
    }

    /**
     * Сохранить расчет зарплаты
     */
    public function save()
    {
        $db = self::getDb();

        if ($this->id) {
            // Обновление существующего расчета
            $stmt = $db->prepare('UPDATE payroll SET employee_id = ?, period_start = ?, period_end = ?, year = ?, month = ?, base_salary = ?, overtime_hours = ?, overtime_rate = ?, bonuses = ?, deductions = ?, gross_salary = ?, income_tax = ?, social_tax = ?, social_contribution = ?, medical_contribution = ?, net_salary = ?, status = ?, payment_date = ?, updated_at = datetime("now") WHERE id = ?');
            $stmt->execute([$this->employee_id, $this->period_start, $this->period_end, $this->year, $this->month, $this->base_salary, $this->overtime_hours, $this->overtime_rate, $this->bonuses, $this->deductions, $this->gross_salary, $this->income_tax, $this->social_tax, $this->social_contribution, $this->medical_contribution, $this->net_salary, $this->status, $this->payment_date, $this->id]);
        } else {
            // Создание нового расчета
            $stmt = $db->prepare('INSERT INTO payroll (employee_id, period_start, period_end, year, month, base_salary, overtime_hours, overtime_rate, bonuses, deductions, gross_salary, income_tax, social_tax, social_contribution, medical_contribution, net_salary, status, payment_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime("now"), datetime("now"))');
            $stmt->execute([$this->employee_id, $this->period_start, $this->period_end, $this->year, $this->month, $this->base_salary, $this->overtime_hours, $this->overtime_rate, $this->bonuses, $this->deductions, $this->gross_salary, $this->income_tax, $this->social_tax, $this->social_contribution, $this->medical_contribution, $this->net_salary, $this->status, $this->payment_date]);
            $this->id = $db->lastInsertId();
        }
    }

    /**
     * Рассчитать зарплату
     */
    public function calculateSalary()
    {
        // Валовая зарплата = базовая + переработка + премии - вычеты
        $overtimePay = ($this->overtime_hours ?? 0) * ($this->overtime_rate ?? 0);
        $this->gross_salary = ($this->base_salary ?? 0) + $overtimePay + ($this->bonuses ?? 0) - ($this->deductions ?? 0);

        // Налоги и отчисления согласно законодательству РК
        $this->income_tax = $this->gross_salary * 0.10; // ИПН 10%
        $this->social_tax = $this->gross_salary * 0.095; // Социальный налог 9.5% (работодатель)
        $this->social_contribution = $this->gross_salary * 0.10; // ОПВ 10% (работник)
        $this->medical_contribution = $this->gross_salary * 0.02; // ОМС 2% (работник)

        // Чистая зарплата = валовая - ИПН - ОПВ - ОМС
        $this->net_salary = $this->gross_salary - $this->income_tax - $this->social_contribution - $this->medical_contribution;
    }

    /**
     * Получить сотрудника
     */
    public function getEmployee()
    {
        return Employee::find($this->employee_id);
    }

    /**
     * Создать расчет зарплаты для сотрудника за период
     */
    public static function createPayroll($employeeId, $year, $month, $baseSalary, $overtimeHours = 0, $overtimeRate = 0, $bonuses = 0, $deductions = 0)
    {
        $payroll = new self();
        $payroll->employee_id = $employeeId;
        $payroll->year = $year;
        $payroll->month = $month;
        $payroll->period_start = date('Y-m-01', strtotime("$year-$month-01"));
        $payroll->period_end = date('Y-m-t', strtotime("$year-$month-01"));
        $payroll->base_salary = $baseSalary;
        $payroll->overtime_hours = $overtimeHours;
        $payroll->overtime_rate = $overtimeRate;
        $payroll->bonuses = $bonuses;
        $payroll->deductions = $deductions;
        $payroll->status = 'расчетный';

        $payroll->calculateSalary();
        $payroll->save();

        return $payroll;
    }

    /**
     * Получить общую сумму зарплат за период
     */
    public static function getTotalSalaryByPeriod($year, $month)
    {
        $db = self::getDb();
        $stmt = $db->prepare('SELECT SUM(gross_salary) as total_gross, SUM(net_salary) as total_net, SUM(income_tax) as total_tax, SUM(social_tax) as total_social_tax FROM payroll WHERE year = ? AND month = ?');
        $stmt->execute([$year, $month]);

        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
}
