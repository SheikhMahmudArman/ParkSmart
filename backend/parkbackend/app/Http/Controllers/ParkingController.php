<?php

namespace App\Http\Controllers;

use App\Models\ParkingSession;
use App\Models\Payment;
use App\Models\Find;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ParkingController extends Controller
{
    // GET /api/sessions/active
    public function activeSessions()
    {
        $sessions = ParkingSession::with([
            'reservation.user',
            'vehicle',
            'parkingSpace.parkingLot'
        ])->whereNull('ExitTime')->get();

        return response()->json($sessions);
    }

    // POST /api/sessions/{id}/exit
    public function exitSession($sessionId)
    {
        $session = ParkingSession::findOrFail($sessionId);
        $session->ExitTime = now();
        $session->DurationMinutes = $session->EntryTime->diffInMinutes($session->ExitTime);
        $session->TotalCost = ceil($session->DurationMinutes / 60) * $session->HourlyRate;
        $session->save();

        // Update parking space status
        $session->parkingSpace->update(['Status' => 'Available']);

        return response()->json([
            'message' => 'Session exited',
            'total_cost' => $session->TotalCost
        ]);
    }

    // GET /api/users/{id}/payments
    public function userPayments($userId)
    {
        $payments = Payment::with(['reservation.parkingSpace.parkingLot'])
            ->whereHas('reservation', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('PaymentDate', 'desc')
            ->get();

        return response()->json($payments);
    }

    // POST /api/reservations/{id}/pay
    public function processPayment(Request $request, $reservationId)
    {
        $reservation = Reservation::findOrFail($reservationId);

        $payment = Payment::create([
            'ReservationID' => $reservation->id,
            'SessionID' => $reservation->parkingSession?->SessionID,
            'Amount' => $reservation->TotalAmount ?? 0,
            'Method' => $request->method,
            'Status' => 'Completed',
            'TransactionID' => 'TXN-' . uniqid()
        ]);

        $reservation->update(['PaymentStatus' => 'Paid']);

        return response()->json([
            'message' => 'Payment processed',
            'payment' => $payment
        ]);
    }

    // GET /api/users/{id}/finds
    public function userFinds($userId)
    {
        $finds = Find::with(['reservation.vehicle'])
            ->whereHas('reservation', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('IssueDate', 'desc')
            ->get();

        return response()->json($finds);
    }

    // GET /api/finds/overdue
    public function overdueFinds()
    {
        $overdue = Find::with(['reservation.user'])
            ->where('Status', 'Pending')
            ->where('IssueDate', '<=', Carbon::now()->subDays(7))
            ->get();

        return response()->json($overdue);
    }

    // POST /api/finds/{id}/pay
    public function payFind(Request $request, $findId)
    {
        $find = Find::findOrFail($findId);

        $payment = Payment::create([
            'ReservationID' => $find->ReservationID,
            'SessionID' => $find->SessionID,
            'Amount' => $find->Amount,
            'Method' => $request->method,
            'Status' => 'Completed',
            'TransactionID' => 'TXN-FIND-' . uniqid()
        ]);

        $find->update([
            'Status' => 'Paid',
            'PaymentID' => $payment->PaymentID
        ]);

        return response()->json([
            'message' => 'Find paid',
            'payment' => $payment
        ]);
    }

    // GET /api/reports/revenue
    public function revenueReport()
    {
        $report = \DB::table('payments')
            ->join('reservations', 'payments.ReservationID', '=', 'reservations.id')
            ->join('parking_spaces', 'reservations.SpaceID', '=', 'parking_spaces.id')
            ->join('parking_lots', 'parking_spaces.LotID', '=', 'parking_lots.id')
            ->where('payments.Status', 'Completed')
            ->select(
                'parking_lots.id as LotID',
                'parking_lots.Name as LotName',
                \DB::raw('COUNT(payments.id) as TotalTransactions'),
                \DB::raw('SUM(payments.Amount) as TotalRevenue'),
                \DB::raw('AVG(payments.Amount) as AveragePayment')
            )
            ->groupBy('parking_lots.id', 'parking_lots.Name')
            ->orderBy('TotalRevenue', 'desc')
            ->get();

        return response()->json($report);
    }
}