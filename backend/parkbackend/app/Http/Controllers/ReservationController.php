<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReservationController extends Controller
{
    // GET /api/reservations - All reservations (Staff)
    public function index()
    {
        $reservations = DB::select("
            SELECT 
                r.id,
                r.user_id,
                r.reservation_date,
                r.start_time,
                r.end_time,
                r.status,
                r.payment_status,
                r.reservation_time,
                r.created_at,
                r.total_amount,
                v.id AS vehicle_id,
                v.plate_number AS vehicle_plate,
                ps.id AS space_id,
                ps.space_number AS spot,
                pl.id AS lot_id,
                pl.name AS lot_name,
                u.name AS user_name,
                u.email AS user_email
            FROM reservations r
            LEFT JOIN vehicles v ON r.vehicle_id = v.id
            LEFT JOIN parking_spaces ps ON r.space_id = ps.id
            LEFT JOIN parking_lots pl ON ps.parking_lot_id = pl.id
            LEFT JOIN users u ON r.user_id = u.id
            ORDER BY r.created_at DESC
        ");

        $transformed = array_map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'lot_name' => $reservation->lot_name ?? 'N/A',
                'spot' => $reservation->spot ?? 'N/A',
                'date' => $reservation->reservation_date ? Carbon::parse($reservation->reservation_date)->format('Y-m-d') : 'N/A',
                'start' => $reservation->start_time ?? 'N/A',
                'end' => $reservation->end_time ?? 'N/A',
                'status' => $reservation->status ?? 'Pending',
                'payment_status' => $reservation->payment_status ?? 'Pending',
                'vehicle_plate' => $reservation->vehicle_plate ?? 'N/A',
                'user_id' => $reservation->user_id,
                'parking_space' => [
                    'id' => $reservation->space_id,
                    'space_number' => $reservation->spot
                ],
                'vehicle' => [
                    'id' => $reservation->vehicle_id,
                    'plate_number' => $reservation->vehicle_plate
                ],
                'user' => [
                    'id' => $reservation->user_id,
                    'name' => $reservation->user_name,
                    'email' => $reservation->user_email
                ],
                'reservation_time' => $reservation->reservation_time ?? $reservation->created_at,
                'created_at' => $reservation->created_at
            ];
        }, $reservations);

        return response()->json($transformed);
    }

    // POST /api/reservations - Create new reservation
    public function store(Request $request)
    {
        $validated = $request->validate([
            'lot_id' => 'required|exists:parking_lots,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'reservation_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required'
        ]);

        // Find available space in the lot
        $space = DB::select("
            SELECT * FROM parking_spaces 
            WHERE parking_lot_id = ? 
            AND status = 'Available' 
            LIMIT 1
        ", [$validated['lot_id']]);

        if (!$space) {
            return response()->json(['error' => 'No available spaces in this lot'], 400);
        }

        $space = $space[0];

        $reservationId = DB::table('reservations')->insertGetId([
            'user_id' => $request->user()->id,
            'vehicle_id' => $validated['vehicle_id'],
            'space_id' => $space->id,
            'reservation_date' => $validated['reservation_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => 'Pending',
            'payment_status' => 'Pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Update space status
        DB::table('parking_spaces')
            ->where('id', $space->id)
            ->update(['status' => 'Reserved', 'updated_at' => now()]);

        $reservation = DB::table('reservations')->where('id', $reservationId)->first();

        return response()->json([
            'message' => 'Reservation created successfully',
            'reservation' => $reservation,
            'spot_number' => $space->space_number
        ], 201);
    }

    // GET /api/reservations/{id}
    public function show($id)
    {
        $reservation = DB::select("
            SELECT 
                r.*,
                v.plate_number AS vehicle_plate,
                ps.space_number AS spot_number,
                pl.name AS lot_name,
                u.name AS user_name
            FROM reservations r
            LEFT JOIN vehicles v ON r.vehicle_id = v.id
            LEFT JOIN parking_spaces ps ON r.space_id = ps.id
            LEFT JOIN parking_lots pl ON ps.parking_lot_id = pl.id
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.id = ?
        ", [$id]);

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        return response()->json([
            'message' => 'Reservation retrieved successfully',
            'reservation' => $reservation[0]
        ], 200);
    }

    // PUT /api/reservations/{id} - Update reservation status (Staff)
    public function update(Request $request, $id)
    {
        $reservation = DB::table('reservations')->where('id', $id)->first();

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:Pending,Confirmed,Active,Completed,Cancelled'
        ]);

        DB::table('reservations')
            ->where('id', $id)
            ->update([
                'status' => $validated['status'],
                'updated_at' => now()
            ]);

        // If reservation is cancelled, free up the space
        if ($validated['status'] === 'Cancelled' && $reservation->space_id) {
            DB::table('parking_spaces')
                ->where('id', $reservation->space_id)
                ->update(['status' => 'Available', 'updated_at' => now()]);
        }

        $updatedReservation = DB::table('reservations')->where('id', $id)->first();

        return response()->json([
            'message' => 'Reservation updated successfully',
            'reservation' => $updatedReservation
        ]);
    }

    // DELETE /api/reservations/{id} - Cancel reservation
    public function destroy($id)
    {
        $reservation = DB::table('reservations')->where('id', $id)->first();

        if (!$reservation) {
            return response()->json(['message' => 'Reservation not found'], 404);
        }

        // Free up the space
        if ($reservation->space_id) {
            DB::table('parking_spaces')
                ->where('id', $reservation->space_id)
                ->update(['status' => 'Available', 'updated_at' => now()]);
        }

        DB::table('reservations')->where('id', $id)->delete();

        return response()->json([
            'message' => 'Reservation cancelled successfully'
        ]);
    }

    // GET /api/users/{id}/reservations - Get all reservations for a specific user
    public function userReservations($userId)
    {
        $reservations = DB::select("
            SELECT 
                r.id,
                r.user_id,
                r.reservation_date,
                r.start_time,
                r.end_time,
                r.status,
                r.payment_status,
                r.reservation_time,
                r.created_at,
                r.total_amount,
                v.id AS vehicle_id,
                v.plate_number AS vehicle_plate,
                ps.id AS space_id,
                ps.space_number AS spot,
                pl.id AS lot_id,
                pl.name AS lot_name,
                u.name AS user_name,
                u.email AS user_email
            FROM reservations r
            LEFT JOIN vehicles v ON r.vehicle_id = v.id
            LEFT JOIN parking_spaces ps ON r.space_id = ps.id
            LEFT JOIN parking_lots pl ON ps.parking_lot_id = pl.id
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
        ", [$userId]);

        $transformed = array_map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'lot_name' => $reservation->lot_name ?? 'N/A',
                'spot' => $reservation->spot ?? 'N/A',
                'date' => $reservation->reservation_date ? Carbon::parse($reservation->reservation_date)->format('Y-m-d') : 'N/A',
                'start' => $reservation->start_time ?? 'N/A',
                'end' => $reservation->end_time ?? 'N/A',
                'status' => $reservation->status ?? 'Pending',
                'payment_status' => $reservation->payment_status ?? 'Pending',
                'vehicle_plate' => $reservation->vehicle_plate ?? 'N/A',
                'parking_space' => [
                    'id' => $reservation->space_id,
                    'space_number' => $reservation->spot
                ],
                'vehicle' => [
                    'id' => $reservation->vehicle_id,
                    'plate_number' => $reservation->vehicle_plate
                ],
                'reservation_time' => $reservation->reservation_time ?? $reservation->created_at,
                'created_at' => $reservation->created_at
            ];
        }, $reservations);

        return response()->json([
            'message' => 'User reservations retrieved successfully',
            'reservations' => $transformed
        ], 200);
    }
}