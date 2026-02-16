<?php

namespace app\model;

class Leave
{
    public $id;
    public $employee_id;
    public $leave_type; // Тип отпуска (ежегодный, декретный, учебный, без содержания, по болезни)
    public $start_date; // Дата начала отпуска
    public $end_date; // Дата окончания отпуска
    public $days_count; // Количество дней
    public $reason; // Причина (для отпусков без содержания)
    public $status; // Статус (заявлен, одобрен, отклонен, завершен)
    public $approved_by; // Кто одобрил
    public $approved_date; // Дата одобрения
    public $medical_certificate; // Наличие больничного листа (для больничных)
    public $created_at;
    public $updated_at;

    // Константы типов отпусков согласно ТК РК
    const LEAVE_TYPES = [
        'ежегодный' => 'Ежегодный оплачиваемый отпуск (мин. 24 дня)',
        'декретный' => 'Декретный отпуск (126 дней)',
        'учебный' => 'Учебный отпуск',
        'без_содержания' => 'Отпуск без сохранения заработной платы',
        'больничный' => 'Отпуск по временной нетрудоспособности',
        'дополнительный' => 'Дополнительный отпуск'
    ];

    private static function getDb()
    {
        return new \PDO('sqlite:' . base_path() . '/database.sqlite');
    }

    /**
     * Получить все отпуска
     */
    public static function all()
    {
        $db = self::getDb();
        $stmt = $db->query('SELECT * FROM leaves ORDER BY created_at DESC');
        $leaves = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $leave = new self();
            foreach ($row as $key => $value) {
                $leave->$key = $value;
            }
            $leaves[] = $leave;
        }

        return $leaves;
    }

    /**
     * Найти отпуск по ID
     */
    public static function find($id)
    {
        $db = self::getDb();
        $stmt = $db->prepare('SELECT * FROM leaves WHERE id = ?');
        $stmt->execute([$id]);

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $leave = new self();
            foreach ($row as $key => $value) {
                $leave->$key = $value;
            }
            return $leave;
        }

        return null;
    }

    /**
     * Получить отпуска сотрудника
     */
    public static function getLeavesByEmployee($employeeId, $year = null)
    {
        $db = self::getDb();

        if ($year) {
            $stmt = $db->prepare('SELECT * FROM leaves WHERE employee_id = ? AND strftime("%Y", start_date) = ? ORDER BY start_date DESC');
            $stmt->execute([$employeeId, $year]);
        } else {
            $stmt = $db->prepare('SELECT * FROM leaves WHERE employee_id = ? ORDER BY start_date DESC');
            $stmt->execute([$employeeId]);
        }

        $leaves = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $leave = new self();
            foreach ($row as $key => $value) {
                $leave->$key = $value;
            }
            $leaves[] = $leave;
        }

        return $leaves;
    }

    /**
     * Получить использованные дни отпуска за год
     */
    public static function getUsedLeaveDays($employeeId, $year, $leaveType = 'ежегодный')
    {
        $db = self::getDb();
        $stmt = $db->prepare('SELECT SUM(days_count) as total_days FROM leaves WHERE employee_id = ? AND leave_type = ? AND status = ? AND strftime("%Y", start_date) = ?');
        $stmt->execute([$employeeId, $leaveType, 'завершен', $year]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($result['total_days'] ?? 0);
    }

    /**
     * Сохранить отпуск
     */
    public function save()
    {
        $db = self::getDb();

        if ($this->id) {
            // Обновление существующего отпуска
            $stmt = $db->prepare('UPDATE leaves SET employee_id = ?, leave_type = ?, start_date = ?, end_date = ?, days_count = ?, reason = ?, status = ?, approved_by = ?, approved_date = ?, medical_certificate = ?, updated_at = datetime("now") WHERE id = ?');
            $stmt->execute([$this->employee_id, $this->leave_type, $this->start_date, $this->end_date, $this->days_count, $this->reason, $this->status, $this->approved_by, $this->approved_date, $this->medical_certificate, $this->id]);
        } else {
            // Создание нового отпуска
            $stmt = $db->prepare('INSERT INTO leaves (employee_id, leave_type, start_date, end_date, days_count, reason, status, approved_by, approved_date, medical_certificate, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime("now"), datetime("now"))');
            $stmt->execute([$this->employee_id, $this->leave_type, $this->start_date, $this->end_date, $this->days_count, $this->reason, $this->status, $this->approved_by, $this->approved_date, $this->medical_certificate]);
            $this->id = $db->lastInsertId();
        }
    }

    /**
     * Рассчитать количество дней отпуска
     */
    public function calculateDaysCount()
    {
        if ($this->start_date && $this->end_date) {
            $start = new \DateTime($this->start_date);
            $end = new \DateTime($this->end_date);
            $interval = $start->diff($end);
            $this->days_count = $interval->days + 1; // +1 чтобы включить последний день
        }
    }

    /**
     * Получить сотрудника
     */
    public function getEmployee()
    {
        return Employee::find($this->employee_id);
    }

    /**
     * Проверить, доступен ли ежегодный отпуск (работает более 6 месяцев)
     */
    public static function isAnnualLeaveAvailable($employeeId)
    {
        $employee = Employee::find($employeeId);
        return $employee ? $employee->isEligibleForAnnualLeave() : false;
    }

    /**
     * Получить остаток ежегодного отпуска за год
     */
    public static function getRemainingAnnualLeaveDays($employeeId, $year)
    {
        $employee = Employee::find($employeeId);
        if (!$employee) return 0;

        $usedDays = self::getUsedLeaveDays($employeeId, $year, 'ежегодный');

        // Базовый ежегодный отпуск согласно ТК РК - 24 дня
        $baseLeaveDays = 24;

        // Дополнительные дни за стаж (каждые 2 года + 1 день, макс. +12 дней)
        $experienceYears = $employee->getWorkExperienceYears();
        $additionalDays = min(floor($experienceYears / 2), 12);

        $totalAvailable = $baseLeaveDays + $additionalDays;

        return max(0, $totalAvailable - $usedDays);
    }
}
