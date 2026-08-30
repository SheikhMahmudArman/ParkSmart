<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ParkingController extends Controller
{
    // GET /api/sessions/active
    public function activeSessions()
    {
        $sessions = DB::select("
            SELECT 
                ps.id,
                ps.entry_time,
                ps.exit_time,
                ps.duration_minutes,
                ps.total_cost,
                ps.hourly_rate,
                v.id AS vehicle_id,
                v.plate_number,
                psp.id AS space_id,
                psp.space_number,
                pl.id AS lot_id,
                pl.name AS lot_name
            FROM parking_sessions ps
            LEFT JOIN vehicles v ON ps.vehicle_id = v.id
            LEFT JOIN parking_spaces psp ON ps.space_id = psp.id
            LEFT JOIN parking_lots pl ON psp.parking_lot_id = pl.id
            WHERE ps.exit_time IS NULL
        ");

        $transformed = array_map(function ($session) {
            return [
                'id' => $session->id,
                'SessionID' => $session->id,
                'vehicle' => $session->vehicle_id ? [
                    'PlateNumber' => $session->plate_number,
                    'VehicleID' => $session->vehicle_id
                ] : null,
                'plate_number' => $session->plate_number ?? 'N/A',
                'parkingSpace' => $session->space_id ? [
                    'SpaceNumber' => $session->space_number,
                    'parkingLot' => $session->lot_id ? [
                        'Name' => $session->lot_name,
                        'id' => $session->lot_id
                    ] : null
                ] : null,
                'lot_name' => $session->lot_name ?? 'N/A',
                'space_number' => $session->space_number ?? 'N/A',
                'EntryTime' => $session->entry_time,
                'entry_time' => $session->entry_time,
                'ExitTime' => $session->exit_time,
                'exit_time' => $session->exit_time,
                'DurationMinutes' => $session->duration_minutes,
                'TotalCost' => $session->total_cost
            ];
        }, $sessions);

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
        $userId = $request->user()->id ?? 1;

        $vehicle = DB::table('vehicles')
            ->where('plate_number', $validated['plate_number'])
            ->first();

        if (!$vehicle) {
            $vehicleId = DB::table('vehicles')->insertGetId([
                'plate_number' => $validated['plate_number'],
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $vehicleId = $vehicle->id;
        }

        // Find available space
        $space = DB::select("
            SELECT * FROM parking_spaces 
            WHERE parking_lot_id = ? 
            AND space_number = ?
            LIMIT 1
        ", [$validated['lot_id'], $validated['space_number']]);

        if (!$space) {
            return response()->json(['error' => 'Space not found'], 404);
        }

        $space = $space[0];

        // Get hourly rate
        $lot = DB::table('parking_lots')->where('id', $validated['lot_id'])->first();
        $hourlyRate = $lot->hourly_rate ?? 5;

        // Create session
        $sessionId = DB::table('parking_sessions')->insertGetId([
            'vehicle_id' => $vehicleId,
            'space_id' => $space->id,
            'entry_time' => Carbon::now(),
            'hourly_rate' => $hourlyRate,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Update space status
        DB::table('parking_spaces')
            ->where('id', $space->id)
            ->update(['status' => 'Occupied', 'updated_at' => now()]);

        $session = DB::table('parking_sessions')->where('id', $sessionId)->first();

        return response()->json([
            'message' => 'Entry logged successfully',
            'session' => $session
        ], 201);
    }

    // POST /api/sessions/{id}/exit
    public function exitSession(Request $request, $sessionId)
    {
        $session = DB::table('parking_sessions')->where('id', $sessionId)->first();

        if (!$session) {
            return response()->json(['error' => 'Session not found'], 404);
        }

        $exitTime = $request->exit_time ? Carbon::parse($request->exit_time) : Carbon::now();

        $entryTime = Carbon::parse($session->entry_time);
        $durationMinutes = $entryTime->diffInMinutes($exitTime);
        $totalCost = ceil($durationMinutes / 60) * $session->hourly_rate;

        DB::table('parking_sessions')
            ->where('id', $sessionId)
            ->update([
                'exit_time' => $exitTime,
                'duration_minutes' => $durationMinutes,
                'total_cost' => $totalCost,
                'updated_at' => now()
            ]);

        // Update parking space status
        if ($session->space_id) {
            DB::table('parking_spaces')
                ->where('id', $session->space_id)
                ->update(['status' => 'Available', 'updated_at' => now()]);
        }

        return response()->json([
            'message' => 'Session exited successfully',
            'total_cost' => $totalCost,
            'duration_minutes' => $durationMinutes
        ]);
    }

    // GET /api/payments - All payments (Admin)
    public function allPayments()
    {
        $payments = DB::select("
            SELECT 
                p.id,
                p.payment_date,
                p.amount,
                p.status,
                p.method,
                p.transaction_id,
                pl.name AS lot_name
            FROM payments p
            LEFT JOIN reservations r ON p.reservation_id = r.id
            LEFT JOIN parking_spaces ps ON r.space_id = ps.id
            LEFT JOIN parking_lots pl ON ps.parking_lot_id = pl.id
            ORDER BY p.payment_date DESC
        ");

        $transformed = array_map(function ($payment) {
            return [
                'id' => $payment->id,
                'PaymentID' => $payment->id,
                'date' => $payment->payment_date,
                'PaymentDate' => $payment->payment_date,
                'lot' => $payment->lot_name ?? 'N/A',
                'lot_name' => $payment->lot_name ?? 'N/A',
                'amount' => $payment->amount,
                'Amount' => $payment->amount,
                'status' => $payment->status,
                'Status' => $payment->status,
                'method' => $payment->method,
                'Method' => $payment->method,
                'transaction_id' => $payment->transaction_id
            ];
        }, $payments);

        return response()->json($transformed);
    }

    // GET /api/users/{id}/payments
    public function userPayments($userId)
    {
        $payments = DB::select("
            SELECT 
                p.id,
                p.payment_date,
                p.amount,
                p.status,
                p.method,
                p.transaction_id,
                pl.name AS lot_name
            FROM payments p
            LEFT JOIN reservations r ON p.reservation_id = r.id
            LEFT JOIN parking_spaces ps ON r.space_id = ps.id
            LEFT JOIN parking_lots pl ON ps.parking_lot_id = pl.id
            LEFT JOIN parking_sessions sess ON p.session_id = sess.id
            LEFT JOIN vehicles v ON sess.vehicle_id = v.id
            WHERE r.user_id = ?
            OR v.user_id = ?
            ORDER BY p.payment_date DESC
        ", [$userId, $userId]);

        $transformed = array_map(function ($payment) {
            return [
                'id' => $payment->id,
                'PaymentID' => $payment->id,
                'date' => $payment->payment_date,
                'PaymentDate' => $payment->payment_date,
                'lot' => $payment->lot_name ?? 'N/A',
                'lot_name' => $payment->lot_name ?? 'N/A',
                'amount' => $payment->amount,
                'Amount' => $payment->amount,
                'status' => $payment->status,
                'Status' => $payment->status,
                'method' => $payment->method,
                'Method' => $payment->method,
            ];
        }, $payments);

        return response()->json($transformed);
    }

    // POST /api/reservations/{id}/pay
    public function processPayment(Request $request, $reservationId)
    {
        $reservation = DB::table('reservations')->where('id', $reservationId)->first();

        if (!$reservation) {
            return response()->json(['error' => 'Reservation not found'], 404);
        }

        // Get session if exists
        $session = DB::table('parking_sessions')
            ->where('space_id', $reservation->space_id)
            ->whereNotNull('exit_time')
            ->orderBy('id', 'desc')
            ->first();

        $amount = $request->amount ?? 0;
        if ($reservation->total_amount) {
            $amount = $reservation->total_amount;
        }

        $transactionId = 'TXN-' . uniqid();

        $paymentId = DB::table('payments')->insertGetId([
            'reservation_id' => $reservation->id,
            'session_id' => $session ? $session->id : null,
            'amount' => $amount,
            'method' => $request->payment_method ?? 'Credit Card',
            'status' => 'Completed',
            'transaction_id' => $transactionId,
            'payment_date' => Carbon::now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Update reservation payment status
        DB::table('reservations')
            ->where('id', $reservationId)
            ->update(['payment_status' => 'Paid', 'updated_at' => now()]);

        $payment = DB::table('payments')->where('id', $paymentId)->first();

        return response()->json([
            'message' => 'Payment processed successfully',
            'payment' => $payment,
            'amount' => $amount
        ]);
    }

    // GET /api/users/{id}/finds
    public function userFinds($userId)
    {
        $finds = DB::select("
            SELECT f.*
            FROM finds f
            JOIN reservations r ON f.reservation_id = r.id
            WHERE r.user_id = ?
            ORDER BY f.issue_date DESC
        ", [$userId]);

        return response()->json($finds);
    }

    // GET /api/finds/overdue
    public function overdueFinds()
    {
        $overdue = DB::select("
            SELECT f.*, u.name AS user_name
            FROM finds f
            JOIN reservations r ON f.reservation_id = r.id
            JOIN users u ON r.user_id = u.id
            WHERE f.status = 'Pending'
            AND f.issue_date <= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");

        return response()->json($overdue);
    }

    // POST /api/finds/{id}/pay
    public function payFind(Request $request, $findId)
    {
        $find = DB::table('finds')->where('id', $findId)->first();

        if (!$find) {
            return response()->json(['error' => 'Find not found'], 404);
        }

        $transactionId = 'TXN-FIND-' . uniqid();

        $paymentId = DB::table('payments')->insertGetId([
            'reservation_id' => $find->reservation_id,
            'session_id' => $find->session_id,
            'amount' => $find->amount,
            'method' => $request->input('method', 'Credit Card'),
            'status' => 'Completed',
            'transaction_id' => $transactionId,
            'payment_date' => Carbon::now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('finds')
            ->where('id', $findId)
            ->update([
                'status' => 'Paid',
                'payment_id' => $paymentId,
                'updated_at' => now()
            ]);

        $payment = DB::table('payments')->where('id', $paymentId)->first();

        return response()->json([
            'message' => 'Find paid successfully',
            'payment' => $payment
        ]);
    }

    // GET /api/reports/revenue
    public function revenueReport()
    {
        $report = DB::select("
            SELECT 
                pl.id AS lot_id,
                pl.name AS lot_name,
                COUNT(p.id) AS total_transactions,
                SUM(p.amount) AS total_revenue,
                AVG(p.amount) AS average_payment
            FROM payments p
            JOIN reservations r ON p.reservation_id = r.id
            JOIN parking_spaces ps ON r.space_id = ps.id
            JOIN parking_lots pl ON ps.parking_lot_id = pl.id
            WHERE p.status = 'Completed'
            GROUP BY pl.id, pl.name
            ORDER BY total_revenue DESC
        ");

        $totalRevenue = array_sum(array_column($report, 'total_revenue'));
        $totalReservations = array_sum(array_column($report, 'total_transactions'));

        // Calculate occupancy - count occupied vs total spaces
        $occupancyData = DB::select("
            SELECT 
                COUNT(CASE WHEN status = 'Occupied' THEN 1 END) AS occupied,
                COUNT(*) AS total
            FROM parking_spaces
        ");

        $occupancy = 0;
        if (count($occupancyData) > 0 && $occupancyData[0]->total > 0) {
            $occupancy = round(($occupancyData[0]->occupied / $occupancyData[0]->total) * 100);
        }

        return response()->json([
            'revenue_by_lot' => array_map(function ($item) {
                return [
                    'lot' => $item->lot_name,
                    'amount' => $item->total_revenue,
                    'transactions' => $item->total_transactions,
                    'average' => $item->average_payment
                ];
            }, $report),
            'monthly_revenue' => $totalRevenue,
            'total_reservations' => $totalReservations,
            'occupancy' => $occupancy
        ]);
    }

    // GET /api/spots - All parking spots
    public function spots()
    {
        $spots = DB::select("
            SELECT 
                ps.id,
                ps.space_number,
                ps.status,
                ps.type,
                pl.id AS parking_lot_id,
                pl.name AS lot_name,
                pl.location AS lot_location
            FROM parking_spaces ps
            LEFT JOIN parking_lots pl ON ps.parking_lot_id = pl.id
        ");

        $transformed = array_map(function ($spot) {
            return [
                'id' => $spot->id,
                'lot' => $spot->lot_name ?? 'N/A',
                'lot_name' => $spot->lot_name ?? 'N/A',
                'parking_lot' => [
                    'id' => $spot->parking_lot_id,
                    'name' => $spot->lot_name,
                    'location' => $spot->lot_location
                ],
                'spot' => $spot->space_number,
                'space_number' => $spot->space_number,
                'status' => $spot->status ?? 'Available',
                'type' => $spot->type ?? 'Standard'
            ];
        }, $spots);

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

        $id = DB::table('parking_spaces')->insertGetId([
            'parking_lot_id' => $validated['parking_lot_id'],
            'space_number' => $validated['space_number'],
            'type' => $validated['type'] ?? 'Standard',
            'status' => $validated['status'] ?? 'Available',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $spot = DB::table('parking_spaces')->where('id', $id)->first();

        return response()->json($spot, 201);
    }

    // PUT /api/spots/{id} - Update spot
    public function updateSpot(Request $request, $id)
    {
        $spot = DB::table('parking_spaces')->where('id', $id)->first();

        if (!$spot) {
            return response()->json(['error' => 'Spot not found'], 404);
        }

        $data = $request->all();
        $data['updated_at'] = now();

        DB::table('parking_spaces')->where('id', $id)->update($data);

        $updatedSpot = DB::table('parking_spaces')->where('id', $id)->first();

        return response()->json($updatedSpot);
    }

    // DELETE /api/spots/{id} - Delete spot
    public function destroySpot($id)
    {
        $deleted = DB::table('parking_spaces')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['error' => 'Spot not found'], 404);
        }

        return response()->json(['message' => 'Spot deleted successfully']);
    }
}