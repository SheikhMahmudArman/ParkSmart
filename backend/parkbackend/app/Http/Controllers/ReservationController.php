<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\ParkingSpace;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReservationController extends Controller
{
    // GET /api/reservations - All reservations (Staff)
    public function index()
    {
        $reservations = Reservation::with([
            'user',
            'vehicle',
            'parkingSpace.parkingLot'
        ])->orderBy('created_at', 'desc')->get();

        $transformed = $reservations->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'lot_name' => $reservation->parkingSpace?->parkingLot?->name ?? 'N/A',
                'spot' => $reservation->parkingSpace?->space_number ?? 'N/A',
                'date' => $reservation->reservation_date ? Carbon::parse($reservation->reservation_date)->format('Y-m-d') : 'N/A',
                'start' => $reservation->start_time ?? 'N/A',
                'end' => $reservation->end_time ?? 'N/A',
                'status' => $reservation->status ?? 'Pending',
                'payment_status' => $reservation->payment_status ?? 'Pending',
                'vehicle_plate' => $reservation->vehicle?->plate_number ?? 'N/A',
                'user_id' => $reservation->user_id,
                'parking_space' => $reservation->parkingSpace,
                'vehicle' => $reservation->vehicle,
                'user' => $reservation->user,
                'reservation_time' => $reservation->reservation_time ?? $reservation->created_at,
                'created_at' => $reservation->created_at
            ];
        });

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
        $space = ParkingSpace::where('parking_lot_id', $validated['lot_id'])
            ->where('status', 'Available')
            ->first();

        if (!$space) {
            return response()->json(['error' => 'No available spaces in this lot'], 400);
        }

        $reservation = Reservation::create([
            'user_id' => $request->user()->id,
            'vehicle_id' => $validated['vehicle_id'],
            'space_id' => $space->id,
            'reservation_date' => $validated['reservation_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'status' => 'Pending',
            'payment_status' => 'Pending'
        ]);

        // Update space status
        $space->update(['status' => 'Reserved']);

        return response()->json([
            'message' => 'Reservation created successfully',
            'reservation' => $reservation,
            'spot_number' => $space->space_number
        ], 201);
    }

    // GET /api/reservations/{id}
    public function show($id)
    {
        $reservation = Reservation::with([
            'user',
            'vehicle',
            'parkingSpace.parkingLot'
        ])->findOrFail($id);

        return response()->json([
            'message' => 'Reservation retrieved successfully',
            'reservation' => $reservation
        ], 200);
    }

    // PUT /api/reservations/{id} - Update reservation status (Staff)
    public function update(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:Pending,Confirmed,Active,Completed,Cancelled'
        ]);

        $reservation->update($validated);

        // If reservation is cancelled, free up the space
        if ($validated['status'] === 'Cancelled') {
            if ($reservation->parkingSpace) {
                $reservation->parkingSpace->update(['status' => 'Available']);
            }
        }

        return response()->json([
            'message' => 'Reservation updated successfully',
            'reservation' => $reservation
        ]);
    }

    // DELETE /api/reservations/{id} - Cancel reservation
    public function destroy($id)
    {
        $reservation = Reservation::findOrFail($id);

        // Free up the space
        if ($reservation->parkingSpace) {
            $reservation->parkingSpace->update(['status' => 'Available']);
        }

        $reservation->delete();

        return response()->json([
            'message' => 'Reservation cancelled successfully'
        ]);
    }

    // GET /api/users/{id}/reservations - Get all reservations for a specific user
    public function userReservations($userId)
    {
        $reservations = Reservation::with([
            'user',
            'vehicle',
            'parkingSpace.parkingLot'
        ])->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $transformed = $reservations->map(function ($reservation) {
            return [
                'id' => $reservation->id,
                'lot_name' => $reservation->parkingSpace?->parkingLot?->name ?? 'N/A',
                'spot' => $reservation->parkingSpace?->space_number ?? 'N/A',
                'date' => $reservation->reservation_date ? Carbon::parse($reservation->reservation_date)->format('Y-m-d') : 'N/A',
                'start' => $reservation->start_time ?? 'N/A',
                'end' => $reservation->end_time ?? 'N/A',
                'status' => $reservation->status ?? 'Pending',
                'payment_status' => $reservation->payment_status ?? 'Pending',
                'vehicle_plate' => $reservation->vehicle?->plate_number ?? 'N/A',
                'parking_space' => $reservation->parkingSpace,
                'vehicle' => $reservation->vehicle,
                'reservation_time' => $reservation->reservation_time ?? $reservation->created_at,
                'created_at' => $reservation->created_at
            ];
        });

        return response()->json([
            'message' => 'User reservations retrieved successfully',
            'reservations' => $transformed
        ], 200);
    }
}