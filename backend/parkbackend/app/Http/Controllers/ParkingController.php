<?php

namespace App\Http\Controllers;

use App\Models\ParkingSession;
use App\Models\Payment;
use App\Models\Find;
use App\Models\Reservation;
use App\Models\ParkingSpace;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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

        $transformed = $sessions->map(function ($session) {
            return [
                'id' => $session->id,
                'SessionID' => $session->id,
                'vehicle' => $session->vehicle ? [
                    'PlateNumber' => $session->vehicle->plate_number,
                    'VehicleID' => $session->vehicle->id
                ] : null,
                'plate_number' => $session->vehicle->plate_number ?? 'N/A',
                'parkingSpace' => $session->parkingSpace ? [
                    'SpaceNumber' => $session->parkingSpace->space_number,
                    'parkingLot' => $session->parkingSpace->parkingLot ? [
                        'Name' => $session->parkingSpace->parkingLot->name,
                        'id' => $session->parkingSpace->parkingLot->id
                    ] : null
                ] : null,
                'lot_name' => $session->parkingSpace->parkingLot->name ?? 'N/A',
                'space_number' => $session->parkingSpace->space_number ?? 'N/A',
                'EntryTime' => $session->entry_time,
                'entry_time' => $session->entry_time,
                'ExitTime' => $session->exit_time,
                'exit_time' => $session->exit_time,
                'DurationMinutes' => $session->duration_minutes,
                'TotalCost' => $session->total_cost
            ];
        });

        return response()->json($transformed);
    }

    // POST /api/sessions/entry - Create new entry session
    public function entrySession(Request $request)
    {
        $validated = $request->validate([
            'plate_number' => 'required|string',
            'lot_id' => 'required|exists:parking_lots,id',
            'space_number' => 'required|string'
        ]);

        // Find or create vehicle
        $vehicle = Vehicle::firstOrCreate(
            ['plate_number' => $validated['plate_number']],
            ['user_id' => $request->user()->id ?? 1]
        );

        // Find available space
        $space = ParkingSpace::whereHas('parkingLot', function($query) use ($validated) {
            $query->where('id', $validated['lot_id']);
        })->where('space_number', $validated['space_number'])->first();

        if (!$space) {
            return response()->json(['error' => 'Space not found'], 404);
        }

        // Create session
        $session = ParkingSession::create([
            'vehicle_id' => $vehicle->id,
            'space_id' => $space->id,
            'entry_time' => Carbon::now(),
            'hourly_rate' => $space->parkingLot->hourly_rate ?? 5
        ]);

        // Update space status
        $space->update(['status' => 'Occupied']);

        return response()->json([
            'message' => 'Entry logged successfully',
            'session' => $session
        ], 201);
    }

    // POST /api/sessions/{id}/exit
    public function exitSession(Request $request, $sessionId)
    {
        $session = ParkingSession::findOrFail($sessionId);
        $exitTime = $request->exit_time ? Carbon::parse($request->exit_time) : Carbon::now();
        
        $session->exit_time = $exitTime;
        $session->duration_minutes = $session->entry_time->diffInMinutes($exitTime);
        $session->total_cost = ceil($session->duration_minutes / 60) * $session->hourly_rate;
        $session->save();

        // Update parking space status
        if ($session->parkingSpace) {
            $session->parkingSpace->update(['status' => 'Available']);
        }

        return response()->json([
            'message' => 'Session exited successfully',
            'total_cost' => $session->total_cost,
            'duration_minutes' => $session->duration_minutes
        ]);
    }

    // GET /api/payments - All payments (Admin)
    public function allPayments()
    {
        $payments = Payment::with(['reservation.user', 'parkingSession'])
            ->orderBy('payment_date', 'desc')
            ->get();

        $transformed = $payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'PaymentID' => $payment->id,
                'date' => $payment->payment_date,
                'PaymentDate' => $payment->payment_date,
                'lot' => $payment->reservation?->parkingSpace?->parkingLot?->name ?? 'N/A',
                'lot_name' => $payment->reservation?->parkingSpace?->parkingLot?->name ?? 'N/A',
                'amount' => $payment->amount,
                'Amount' => $payment->amount,
                'status' => $payment->status,
                'Status' => $payment->status,
                'method' => $payment->method,
                'Method' => $payment->method,
                'transaction_id' => $payment->transaction_id
            ];
        });

        return response()->json($transformed);
    }

    // GET /api/users/{id}/payments
    public function userPayments($userId)
    {
        $payments = Payment::with(['reservation.parkingSpace.parkingLot'])
            ->whereHas('reservation', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orWhereHas('parkingSession', function ($query) use ($userId) {
                $query->whereHas('vehicle', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            })
            ->orderBy('payment_date', 'desc')
            ->get();

        $transformed = $payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'PaymentID' => $payment->id,
                'date' => $payment->payment_date,
                'PaymentDate' => $payment->payment_date,
                'lot' => $payment->reservation?->parkingSpace?->parkingLot?->name ?? 'N/A',
                'lot_name' => $payment->reservation?->parkingSpace?->parkingLot?->name ?? 'N/A',
                'amount' => $payment->amount,
                'Amount' => $payment->amount,
                'status' => $payment->status,
                'Status' => $payment->status,
                'method' => $payment->method,
                'Method' => $payment->method,
            ];
        });

        return response()->json($transformed);
    }

    // POST /api/reservations/{id}/pay
    public function processPayment(Request $request, $reservationId)
    {
        $reservation = Reservation::findOrFail($reservationId);
        
        $amount = $request->amount ?? 0;
        if ($reservation->total_amount) {
            $amount = $reservation->total_amount;
        }

        $payment = Payment::create([
            'reservation_id' => $reservation->id,
            'session_id' => $reservation->parkingSession?->id,
            'amount' => $amount,
            'method' => $request->payment_method ?? 'Credit Card',
            'status' => 'Completed',
            'transaction_id' => 'TXN-' . uniqid(),
            'payment_date' => Carbon::now()
        ]);

        $reservation->update(['payment_status' => 'Paid']);

        return response()->json([
            'message' => 'Payment processed successfully',
            'payment' => $payment,
            'amount' => $amount
        ]);
    }

    // GET /api/users/{id}/finds
    public function userFinds($userId)
    {
        $finds = Find::with(['reservation.vehicle'])
            ->whereHas('reservation', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->orderBy('issue_date', 'desc')
            ->get();

        return response()->json($finds);
    }

    // GET /api/finds/overdue
    public function overdueFinds()
    {
        $overdue = Find::with(['reservation.user'])
            ->where('status', 'Pending')
            ->where('issue_date', '<=', Carbon::now()->subDays(7))
            ->get();

        return response()->json($overdue);
    }

    // POST /api/finds/{id}/pay
    public function payFind(Request $request, $findId)
    {
        $find = Find::findOrFail($findId);

        $payment = Payment::create([
            'reservation_id' => $find->reservation_id,
            'session_id' => $find->session_id,
            'amount' => $find->amount,
            'method' => $request->input('method', 'Credit Card'),
            'status' => 'Completed',
            'transaction_id' => 'TXN-FIND-' . uniqid(),
            'payment_date' => Carbon::now()
        ]);

        $find->update([
            'status' => 'Paid',
            'payment_id' => $payment->id
        ]);

        return response()->json([
            'message' => 'Find paid successfully',
            'payment' => $payment
        ]);
    }

    // GET /api/reports/revenue
    public function revenueReport()
    {
        $report = DB::table('payments')
            ->join('reservations', 'payments.reservation_id', '=', 'reservations.id')
            ->join('parking_spaces', 'reservations.space_id', '=', 'parking_spaces.id')
            ->join('parking_lots', 'parking_spaces.parking_lot_id', '=', 'parking_lots.id')
            ->where('payments.status', 'Completed')
            ->select(
                'parking_lots.id as lot_id',
                'parking_lots.name as lot_name',
                DB::raw('COUNT(payments.id) as total_transactions'),
                DB::raw('SUM(payments.amount) as total_revenue'),
                DB::raw('AVG(payments.amount) as average_payment')
            )
            ->groupBy('parking_lots.id', 'parking_lots.name')
            ->orderBy('total_revenue', 'desc')
            ->get();

        $totalRevenue = $report->sum('total_revenue');
        $totalReservations = $report->sum('total_transactions');

        return response()->json([
            'revenue_by_lot' => $report->map(function ($item) {
                return [
                    'lot' => $item->lot_name,
                    'amount' => $item->total_revenue,
                    'transactions' => $item->total_transactions,
                    'average' => $item->average_payment
                ];
            }),
            'monthly_revenue' => $totalRevenue,
            'total_reservations' => $totalReservations,
            'occupancy' => 67 // Calculate based on real data
        ]);
    }

    // GET /api/spots - All parking spots
    public function spots()
    {
        $spots = ParkingSpace::with(['parkingLot'])->get();
        
        $transformed = $spots->map(function ($spot) {
            return [
                'id' => $spot->id,
                'lot' => $spot->parkingLot->name ?? 'N/A',
                'lot_name' => $spot->parkingLot->name ?? 'N/A',
                'parking_lot' => $spot->parkingLot,
                'spot' => $spot->space_number,
                'space_number' => $spot->space_number,
                'status' => $spot->status ?? 'Available',
                'type' => $spot->type ?? 'Standard'
            ];
        });

        return response()->json($transformed);
    }

    // POST /api/spots - Create spot
    public function storeSpot(Request $request)
    {
        $validated = $request->validate([
            'parking_lot_id' => 'required|exists:parking_lots,id',
            'space_number' => 'required|string',
            'type' => 'nullable|string',
            'status' => 'nullable|in:Available,Occupied'
        ]);

        $spot = ParkingSpace::create([
            'parking_lot_id' => $validated['parking_lot_id'],
            'space_number' => $validated['space_number'],
            'type' => $validated['type'] ?? 'Standard',
            'status' => $validated['status'] ?? 'Available'
        ]);

        return response()->json($spot, 201);
    }

    // PUT /api/spots/{id} - Update spot
    public function updateSpot(Request $request, $id)
    {
        $spot = ParkingSpace::findOrFail($id);
        $spot->update($request->all());
        return response()->json($spot);
    }

    // DELETE /api/spots/{id} - Delete spot
    public function destroySpot($id)
    {
        $spot = ParkingSpace::findOrFail($id);
        $spot->delete();
        return response()->json(['message' => 'Spot deleted successfully']);
    }
}