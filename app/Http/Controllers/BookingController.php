<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function get(Request $request): View {
        $bookings = Booking::selectRaw('*, day(`date`) as `day`')
            ->where('email', 'like', $request->user()->email)
            ->orderBy('date', 'asc')
            ->get();

        $result = [];
        foreach ($bookings->toArray() as $booking) {
            $result[$booking['day']] ??= $booking;
        }

        return view('dashboard', [
            'bookings' => $result,
        ]);
    }

    public function post(Request $request): RedirectResponse {
        $validated = $request->validateWithBag('booking', [
            'date' => ['required', 'date_format:Y-m-d H:i:00', 'unique:bookings'],
            'email' => ['required', 'max:255', 'email'],
            'reason' => ['required'],
        ]);

        $minute = (new \DateTime($validated['date']))->format('i');
        if (!in_array($minute, ['00', '30'])) {
            return back()->withErrors(['date' => 'Invalid date']);
        }

        $day = (new \DateTime($validated['date']))->format('l');
        if (in_array($day, ScheduleController::DAYS_OFF)) {
            return back()->withErrors(['date' => 'Invalid date']);
        }

        $hour = (new \DateTime($validated['date']))->format('Hi');
        if (
            ((int)$hour < (int)(ScheduleController::START_DAY_HOUR.ScheduleController::START_DAY_MINUTE)) ||
            ((int)$hour >= (int)(ScheduleController::END_DAY_HOUR.ScheduleController::END_DAY_MINUTE)) ||
            (
                ((int)$hour > (int)(ScheduleController::START_BREAK_HOUR.ScheduleController::START_BREAK_MINUTE)) &&
                ((int)$hour < (int)(ScheduleController::END_BREAK_HOUR.ScheduleController::END_BREAK_MINUTE))
            )
        ){
            return back()->withErrors(['date' => 'Invalid date']);
        }

        $breakCheck = Booking::where('date', '=', (new \DateTime($validated['date']))->sub(new \DateInterval("PT30M"))->format('Y-m-d H:i:00'))
            ->first();

        if ($breakCheck) {
            return back()->withErrors(['email' => 'Date is unavailable']);
        }

        $existingBooking = Booking::where('email', 'like', $validated['email'])
            ->where('date', '<=', (new \DateTime($validated['date']))->format('Y-m-d ' . ScheduleController::END_DAY_HOUR . ':00:00'))
            ->where('date', '>=', (new \DateTime($validated['date']))->format('Y-m-d ' . ScheduleController::START_DAY_HOUR . ':00:00'))
            ->first();

        if ($existingBooking) {
            return back()->withErrors(['email' => 'Already booked one that day']);
        }

        $booking = new Booking();
        $booking->date = (new \DateTime($validated['date']))->format('Y-m-d H:i:00');
        $booking->email = $validated['email'];
        $booking->reason = $validated['reason'];
        $booking->save();

        $booking = new Booking();
        $booking->date = (new \DateTime($validated['date']))->add(new \DateInterval("PT30M"))->format('Y-m-d H:i:00');
        $booking->email = $validated['email'];
        $booking->reason = $validated['reason'];
        $booking->save();

        return back()->with('status', 'booking-saved');
    }
}
