<?php

namespace app\model;

class TimeTracking
{
    public $id;
    public $employee_id;
    public $date; // Дата
    public $check_in; // Время прихода
    public $check_out; // Время ухода
    public $regular_hours; // Обычные рабочие часы (макс 8 в день)
    public $overtime_hours; // Переработанные часы
    public $break_hours; // Время перерыва
    public $absence_reason; // Причина отсутствия (отпуск, больничный, прогул)
    public $notes; // Примечания
    public $approved; // Подтверждено (1/0)
    public $approved_by; // Кто подтвердил
    public $created_at;
    public $updated_at;

    // Константы причин отсутствия
    const ABSENCE_REASONS = [
        'работал' => 'Рабочий день',
        'отпуск' => 'Отпуск',
        'больничный' => 'Больничный',
        'прогул' => 'Прогул',
        'командировка' => 'Командировка',
        'учебный_отпуск' => 'Учебный отпуск',
        'декрет' => 'Декретный отпуск'
    ];

    private static function getDb()
    {
        return new \PDO('sqlite:' . base_path() . '/database.sqlite');
    }

    /**
     * Получить все записи учета времени
     */
    public static function all()
    {
        $db = self::getDb();
        $stmt = $db->query('SELECT * FROM time_tracking ORDER BY date DESC');
        $records = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $record = new self();
            foreach ($row as $key => $value) {
                $record->$key = $value;
            }
            $records[] = $record;
        }

        return $records;
    }

    /**
     * Найти запись по ID
     */
    public static function find($id)
    {
        $db = self::getDb();
        $stmt = $db->prepare('SELECT * FROM time_tracking WHERE id = ?');
        $stmt->execute([$id]);

        if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $record = new self();
            foreach ($row as $key => $value) {
                $record->$key = $value;
            }
            return $record;
        }

        return null;
    }

    /**
     * Получить записи сотрудника за период
     */
    public static function getEmployeeTimeTracking($employeeId, $startDate, $endDate)
    {
        $db = self::getDb();
        $stmt = $db->prepare('SELECT * FROM time_tracking WHERE employee_id = ? AND date BETWEEN ? AND ? ORDER BY date');
        $stmt->execute([$employeeId, $startDate, $endDate]);

        $records = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $record = new self();
            foreach ($row as $key => $value) {
                $record->$key = $value;
            }
            $records[] = $record;
        }

        return $records;
    }

    /**
     * Получить сводку по сотруднику за месяц
     */
    public static function getMonthlySummary($employeeId, $year, $month)
    {
        $db = self::getDb();
        $startDate = date('Y-m-01', strtotime("$year-$month-01"));
        $endDate = date('Y-m-t', strtotime("$year-$month-01"));

        $stmt = $db->prepare('SELECT
            SUM(regular_hours) as total_regular_hours,
            SUM(overtime_hours) as total_overtime_hours,
            COUNT(CASE WHEN absence_reason = "работал" THEN 1 END) as worked_days,
            COUNT(CASE WHEN absence_reason = "отпуск" THEN 1 END) as vacation_days,
            COUNT(CASE WHEN absence_reason = "больничный" THEN 1 END) as sick_days,
            COUNT(CASE WHEN absence_reason = "прогул" THEN 1 END) as absent_days
            FROM time_tracking
            WHERE employee_id = ? AND date BETWEEN ? AND ?');

        $stmt->execute([$employeeId, $startDate, $endDate]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Сохранить запись учета времени
     */
    public function save()
    {
        $db = self::getDb();

        if ($this->id) {
            // Обновление существующей записи
            $stmt = $db->prepare('UPDATE time_tracking SET employee_id = ?, date = ?, check_in = ?, check_out = ?, regular_hours = ?, overtime_hours = ?, break_hours = ?, absence_reason = ?, notes = ?, approved = ?, approved_by = ?, updated_at = datetime("now") WHERE id = ?');
            $stmt->execute([$this->employee_id, $this->date, $this->check_in, $this->check_out, $this->regular_hours, $this->overtime_hours, $this->break_hours, $this->absence_reason, $this->notes, $this->approved, $this->approved_by, $this->id]);
        } else {
            // Создание новой записи
            $stmt = $db->prepare('INSERT INTO time_tracking (employee_id, date, check_in, check_out, regular_hours, overtime_hours, break_hours, absence_reason, notes, approved, approved_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime("now"), datetime("now"))');
            $stmt->execute([$this->employee_id, $this->date, $this->check_in, $this->check_out, $this->regular_hours, $this->overtime_hours, $this->break_hours, $this->absence_reason, $this->notes, $this->approved, $this->approved_by]);
            $this->id = $db->lastInsertId();
        }
    }

    /**
     * Рассчитать рабочие часы
     */
    public function calculateHours()
    {
        if ($this->check_in && $this->check_out && $this->absence_reason === 'работал') {
            $checkIn = new \DateTime($this->check_in);
            $checkOut = new \DateTime($this->check_out);
            $breakHours = $this->break_hours ?? 0;

            $interval = $checkIn->diff($checkOut);
            $totalHours = $interval->h + ($interval->i / 60);

            // Вычесть перерыв
            $totalHours -= $breakHours;

            // Максимум 8 часов в день - обычные часы
            if ($totalHours <= 8) {
                $this->regular_hours = $totalHours;
                $this->overtime_hours = 0;
            } else {
                $this->regular_hours = 8;
                $this->overtime_hours = $totalHours - 8;
            }
        } elseif ($this->absence_reason !== 'работал') {
            $this->regular_hours = 0;
            $this->overtime_hours = 0;
        }
    }

    /**
     * Проверить соответствие нормам ТК РК (макс 40 часов в неделю)
     */
    public static function checkWeeklyHoursLimit($employeeId, $date)
    {
        $db = self::getDb();

        // Получить неделю для указанной даты
        $startOfWeek = date('Y-m-d', strtotime('monday this week', strtotime($date)));
        $endOfWeek = date('Y-m-d', strtotime('sunday this week', strtotime($date)));

        $stmt = $db->prepare('SELECT SUM(regular_hours + overtime_hours) as total_weekly_hours FROM time_tracking WHERE employee_id = ? AND date BETWEEN ? AND ? AND absence_reason = "работал"');
        $stmt->execute([$employeeId, $startOfWeek, $endOfWeek]);

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $weeklyHours = (float)($result['total_weekly_hours'] ?? 0);

        return [
            'hours' => $weeklyHours,
            'is_over_limit' => $weeklyHours > 40,
            'remaining_hours' => max(0, 40 - $weeklyHours)
        ];
    }

    /**
     * Получить сотрудника
     */
    public function getEmployee()
    {
        return Employee::find($this->employee_id);
    }

    /**
     * Создать запись для рабочего дня
     */
    public static function createWorkDay($employeeId, $date, $checkIn, $checkOut, $breakHours = 1)
    {
        $record = new self();
        $record->employee_id = $employeeId;
        $record->date = $date;
        $record->check_in = $checkIn;
        $record->check_out = $checkOut;
        $record->break_hours = $breakHours;
        $record->absence_reason = 'работал';
        $record->approved = 0;

        $record->calculateHours();
        $record->save();

        return $record;
    }

    /**
     * Создать запись об отсутствии
     */
    public static function createAbsence($employeeId, $date, $reason)
    {
        $record = new self();
        $record->employee_id = $employeeId;
        $record->date = $date;
        $record->absence_reason = $reason;
        $record->regular_hours = 0;
        $record->overtime_hours = 0;
        $record->approved = 0;

        $record->save();

        return $record;
    }
}
