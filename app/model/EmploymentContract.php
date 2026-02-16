<?php

namespace app\model;

class EmploymentContract
{
    public $id;
    public $employee_id;
    public $contract_number; // Номер договора
    public $contract_type; // Тип договора (бессрочный, срочный, ГПХ)
    public $start_date; // Дата начала
    public $end_date; // Дата окончания (для срочных договоров)
    public $position; // Должность
    public $salary_base; // Базовая зарплата
    public $salary_type; // Тип оплаты
    public $work_schedule; // График работы
    public $work_hours_per_week; // Часов в неделю (макс 40 согласно ТК РК)
    public $trial_period; // Испытательный срок (дни)
    public $workplace; // Место работы
    public $responsibilities; // Обязанности
    public $working_conditions; // Условия труда
    public $status; // Статус договора (активный, расторгнут, приостановлен)
    public $termination_reason; // Причина расторжения
    public $termination_date; // Дата расторжения
    public $signed_by_employee; // Подписан сотрудником (1/0)
    public $signed_by_employer; // Подписан работодателем (1/0)
    public $created_at;
    public $updated_at;

    private static function getDb()
    {
        return new \PDO('sqlite:' . base_path() . '/database.sqlite');
    }

    /**
     * Получить все договоры
     */
    public static function all()
    {
        $db = self::getDb();
        $stmt = $db->query('SELECT * FROM employment_contracts ORDER BY created_at DESC');
        $contracts = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $contract = new self();
            foreach ($row as $key => $value) {
                $contract->$key = $value;
            }
            $contracts[] = $contract;
        }

        return $contracts;
    }

    /**
     * Найти договор по ID
     */
    public static function find($id)
    {
        $db = self::getDb();
        $stmt = $db->prepare('SELECT * FROM employment_contracts WHERE id = ?');
        $stmt->execute([$id]);

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $contract = new self();
            foreach ($row as $key => $value) {
                $contract->$key = $value;
            }
            return $contract;
        }

        return null;
    }

    /**
     * Получить активные договоры сотрудника
     */
    public static function getActiveContractsByEmployee($employeeId)
    {
        $db = self::getDb();
        $stmt = $db->prepare('SELECT * FROM employment_contracts WHERE employee_id = ? AND status = ? ORDER BY start_date DESC');
        $stmt->execute([$employeeId, 'активный']);
        $contracts = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $contract = new self();
            foreach ($row as $key => $value) {
                $contract->$key = $value;
            }
            $contracts[] = $contract;
        }

        return $contracts;
    }

    /**
     * Сохранить договор
     */
    public function save()
    {
        $db = self::getDb();

        if ($this->id) {
            // Обновление существующего договора
            $stmt = $db->prepare('UPDATE employment_contracts SET employee_id = ?, contract_number = ?, contract_type = ?, start_date = ?, end_date = ?, position = ?, salary_base = ?, salary_type = ?, work_schedule = ?, work_hours_per_week = ?, trial_period = ?, workplace = ?, responsibilities = ?, working_conditions = ?, status = ?, termination_reason = ?, termination_date = ?, signed_by_employee = ?, signed_by_employer = ?, updated_at = datetime("now") WHERE id = ?');
            $stmt->execute([$this->employee_id, $this->contract_number, $this->contract_type, $this->start_date, $this->end_date, $this->position, $this->salary_base, $this->salary_type, $this->work_schedule, $this->work_hours_per_week, $this->trial_period, $this->workplace, $this->responsibilities, $this->working_conditions, $this->status, $this->termination_reason, $this->termination_date, $this->signed_by_employee, $this->signed_by_employer, $this->id]);
        } else {
            // Создание нового договора
            $stmt = $db->prepare('INSERT INTO employment_contracts (employee_id, contract_number, contract_type, start_date, end_date, position, salary_base, salary_type, work_schedule, work_hours_per_week, trial_period, workplace, responsibilities, working_conditions, status, termination_reason, termination_date, signed_by_employee, signed_by_employer, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime("now"), datetime("now"))');
            $stmt->execute([$this->employee_id, $this->contract_number, $this->contract_type, $this->start_date, $this->end_date, $this->position, $this->salary_base, $this->salary_type, $this->work_schedule, $this->work_hours_per_week, $this->trial_period, $this->workplace, $this->responsibilities, $this->working_conditions, $this->status, $this->termination_reason, $this->termination_date, $this->signed_by_employee, $this->signed_by_employer]);
            $this->id = $db->lastInsertId();
        }
    }

    /**
     * Проверить, истек ли срочный договор
     */
    public function isExpired()
    {
        if ($this->contract_type === 'срочный' && $this->end_date) {
            $endDate = new \DateTime($this->end_date);
            $now = new \DateTime();
            return $endDate < $now;
        }
        return false;
    }

    /**
     * Получить сотрудника по договору
     */
    public function getEmployee()
    {
        return Employee::find($this->employee_id);
    }

    /**
     * Генерировать номер договора
     */
    public static function generateContractNumber()
    {
        $db = self::getDb();
        $stmt = $db->query('SELECT COUNT(*) as count FROM employment_contracts');
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $count = $result['count'] + 1;

        return 'Д-' . date('Y') . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
