<?php

namespace app\model;

class Employee
{
    public $id;
    public $iin; // ИИН - индивидуальный идентификационный номер
    public $full_name;
    public $date_of_birth;
    public $gender;
    public $citizenship; // Гражданство
    public $address;
    public $phone;
    public $email;
    public $position; // Должность
    public $department; // Отдел/подразделение
    public $hire_date; // Дата приема на работу
    public $contract_type; // Тип договора (бессрочный, срочный, ГПХ)
    public $salary_base; // Базовая зарплата
    public $salary_type; // Тип оплаты (почасовая, месячная, сдельная)
    public $work_schedule; // График работы (5/2, 6/1, сменный)
    public $status; // Статус (работает, уволен, в отпуске)
    public $termination_date; // Дата увольнения
    public $bank_account; // Расчетный счет
    public $bank_name; // Название банка
    public $created_at;
    public $updated_at;

    private static function getDb()
    {
        return new \PDO('sqlite:' . base_path() . '/database.sqlite');
    }

    /**
     * Получить всех сотрудников
     */
    public static function all()
    {
        $db = self::getDb();
        $stmt = $db->query('SELECT * FROM employees ORDER BY hire_date DESC');
        $employees = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $employee = new self();
            foreach ($row as $key => $value) {
                $employee->$key = $value;
            }
            $employees[] = $employee;
        }

        return $employees;
    }

    /**
     * Найти сотрудника по ID
     */
    public static function find($id)
    {
        $db = self::getDb();
        $stmt = $db->prepare('SELECT * FROM employees WHERE id = ?');
        $stmt->execute([$id]);

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $employee = new self();
            foreach ($row as $key => $value) {
                $employee->$key = $value;
            }
            return $employee;
        }

        return null;
    }

    /**
     * Найти сотрудника по ИИН
     */
    public static function findByIIN($iin)
    {
        $db = self::getDb();
        $stmt = $db->prepare('SELECT * FROM employees WHERE iin = ?');
        $stmt->execute([$iin]);

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $employee = new self();
            foreach ($row as $key => $value) {
                $employee->$key = $value;
            }
            return $employee;
        }

        return null;
    }

    /**
     * Получить активных сотрудников
     */
    public static function getActiveEmployees()
    {
        $db = self::getDb();
        $stmt = $db->prepare('SELECT * FROM employees WHERE status = ? ORDER BY hire_date DESC');
        $stmt->execute(['работает']);
        $employees = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $employee = new self();
            foreach ($row as $key => $value) {
                $employee->$key = $value;
            }
            $employees[] = $employee;
        }

        return $employees;
    }

    /**
     * Сохранить сотрудника
     */
    public function save()
    {
        $db = self::getDb();

        if ($this->id) {
            // Обновление существующего сотрудника
            $stmt = $db->prepare('UPDATE employees SET iin = ?, full_name = ?, date_of_birth = ?, gender = ?, citizenship = ?, address = ?, phone = ?, email = ?, position = ?, department = ?, hire_date = ?, contract_type = ?, salary_base = ?, salary_type = ?, work_schedule = ?, status = ?, termination_date = ?, bank_account = ?, bank_name = ?, updated_at = datetime("now") WHERE id = ?');
            $stmt->execute([$this->iin, $this->full_name, $this->date_of_birth, $this->gender, $this->citizenship, $this->address, $this->phone, $this->email, $this->position, $this->department, $this->hire_date, $this->contract_type, $this->salary_base, $this->salary_type, $this->work_schedule, $this->status, $this->termination_date, $this->bank_account, $this->bank_name, $this->id]);
        } else {
            // Создание нового сотрудника
            $stmt = $db->prepare('INSERT INTO employees (iin, full_name, date_of_birth, gender, citizenship, address, phone, email, position, department, hire_date, contract_type, salary_base, salary_type, work_schedule, status, termination_date, bank_account, bank_name, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime("now"), datetime("now"))');
            $stmt->execute([$this->iin, $this->full_name, $this->date_of_birth, $this->gender, $this->citizenship, $this->address, $this->phone, $this->email, $this->position, $this->department, $this->hire_date, $this->contract_type, $this->salary_base, $this->salary_type, $this->work_schedule, $this->status, $this->termination_date, $this->bank_account, $this->bank_name]);
            $this->id = $db->lastInsertId();
        }
    }

    /**
     * Рассчитать стаж работы в годах
     */
    public function getWorkExperienceYears()
    {
        if (!$this->hire_date) return 0;

        $hireDate = new \DateTime($this->hire_date);
        $now = new \DateTime();
        $interval = $hireDate->diff($now);

        return $interval->y + ($interval->m / 12) + ($interval->d / 365);
    }

    /**
     * Проверить, имеет ли право на ежегодный отпуск (работает более 6 месяцев)
     */
    public function isEligibleForAnnualLeave()
    {
        return $this->getWorkExperienceYears() >= 0.5; // 6 месяцев = 0.5 года
    }
}
