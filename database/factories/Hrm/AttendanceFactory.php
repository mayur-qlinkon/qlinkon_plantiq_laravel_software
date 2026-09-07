<?php

namespace Database\Factories\Hrm;

use App\Models\Hrm\Attendance;
use App\Models\Hrm\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition()
    {
        // Get random employee (must exist)
        $employee = Employee::inRandomOrder()->first();

        if (! $employee) {
            throw new \Exception('No employees found. Please seed employees first.');
        }

        $companyId = $employee->company_id;
        $storeId = $employee->store_id;

        // Random date (last 30 days)
        $date = $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d');

        // Generate check-in time
        $checkIn = Carbon::parse($date.' 09:00:00')
            ->addMinutes(rand(0, 60));

        // Generate check-out time
        $checkOut = (clone $checkIn)->addHours(rand(6, 10));

        // Calculate worked hours
        $workedHours = round($checkIn->diffInMinutes($checkOut) / 60, 2);

        return [
            'company_id' => $companyId,
            'employee_id' => $employee->id,
            'store_id' => $storeId,

            'date' => $date,

            'check_in_time' => $checkIn,
            'check_out_time' => $checkOut,

            'check_in_lat' => $this->faker->latitude,
            'check_in_lng' => $this->faker->longitude,

            'check_out_lat' => $this->faker->latitude,
            'check_out_lng' => $this->faker->longitude,

            'check_in_method' => 'qr',
            'check_out_method' => 'qr',

            'worked_hours' => $workedHours,
            'overtime_hours' => $workedHours > 8 ? $workedHours - 8 : 0,

            'status' => $this->faker->randomElement([
                'present',
                'late',
                'half_day',
            ]),

            'is_overridden' => false,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
