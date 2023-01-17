<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public const START_DAY_HOUR = "09";
    public const START_DAY_MINUTE = "00";
    public const END_DAY_HOUR = "21";
    public const END_DAY_MINUTE = "30";
    public const START_BREAK_HOUR = "13";
    public const START_BREAK_MINUTE = "00";
    public const END_BREAK_HOUR = "15";
    public const END_BREAK_MINUTE = "30";
    public const DAYS_OFF = ['Saturday', 'Sunday'];

    public function get(Request $request): JsonResponse {
        $date = $request->input('date');
        if (!$date) {
            $date = new \DateTime('now');
        } else {
            $date = new \DateTime($date);
        }
        $startDate = clone $date->sub(new \DateInterval("P" . $date->format('N') - 1 . "D"));
        $endDate = clone $date->add(new \DateInterval("P7D"));

        $bookedDates = Booking::where('date', '>', $startDate)
            ->where('date', '<', $endDate)
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($this->createWeekSchedule($startDate, $endDate, array_column($bookedDates->toArray(), "date")));
    }

    private function createWeekSchedule(\DateTime $startDate, \DateTime $endDate, array $bookedDates) {
        $week = [];
        $date = clone $startDate;
        $smallBreaks = [];

        $date->setTime(self::START_DAY_HOUR, self::START_DAY_MINUTE);
        while ($date < $endDate) {
            $h = $date->format('H');
            $m = $date->format('i');
            $d = $date->format('N');
            $time = "$h:$m";
            $fullDate = $date->format('Y-m-d H:i:00');

            if (in_array($date->format('l'), self::DAYS_OFF)) {
                $week[$time][$d] = [
                    "status" => "break",
                    "date" => $fullDate,
                ];
            } elseif (
                ((int)($h . $m) > (int)(self::START_BREAK_HOUR . self::START_BREAK_MINUTE)) &&
                ((int)($h . $m) < (int)(self::END_BREAK_HOUR . self::END_BREAK_MINUTE))
            ) {
                $week[$time][$d] = [
                    "status" => "break",
                    "date" => $fullDate,
                ];
            } elseif (in_array($fullDate, $smallBreaks)) {
                $week[$time][$d] = [
                    "status" => "break",
                    "date" => $fullDate,
                ];
            } elseif ((int)($h . $m) >= (int)(self::END_DAY_HOUR . self::END_DAY_MINUTE)) {
                $week[$time][$d] = [
                    "status" => "break",
                    "date" => $fullDate,
                ];
            } else {
                $week[$time][$d] = [
                    "status" => "available",
                    "date" => $fullDate,
                ];
            }

            if (in_array($fullDate, $bookedDates)) {
                $week[$time][$d] = [
                    "status" => "booked",
                    "date" => $fullDate,
                ];
                $dateClone = clone $date;
                $smallBreaks[] = $dateClone->add(new \DateInterval("PT30M"))->format('Y-m-d H:i:00');
            }

            if ((int)($h . $m) >= (int)(self::END_DAY_HOUR . self::END_DAY_MINUTE)) {
                $date->add(new \DateInterval("P1D"));
                $date->setTime(self::START_DAY_HOUR, self::START_DAY_MINUTE);
            } else {
                $date->add(new \DateInterval("PT30M"));
            }
        }

        return $week;
    }
}
