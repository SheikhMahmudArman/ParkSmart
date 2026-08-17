<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    // GET /api/reservations
    public function index()
    {
        $reservations = Reservation::with([
            'user',
            'vehicle',
            'parkingSpace'
        ])->get();

        return response()->json([
            'message' => 'Reservations retrieved successfully',
            'reservations' => $reservations
        ], 200);
    }


    // POST /api/reservations
    public function store(Request $request)
    {
        $validated = $request->validate([
            'space_id' => 'required|exists:parking_spaces,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $reservation = Reservation::create($validated);

        return response()->json([
            'message' => 'Reservation created successfully',
            'reservation' => $reservation
        ], 201);
    }


    // GET /api/reservations/{id}
    public function show($id)
    {
        $reservation = Reservation::with([
            'user',
            'vehicle',
            'parkingSpace'
        ])->find($id);

        if (!$reservation) {
            return response()->json([
                'message' => 'Reservation not found'
            ], 404);
        }

        return response()->json([
            'message' => 'Reservation retrieved successfully',
            'reservation' => $reservation
        ], 200);
    }


    // PUT /api/reservations/{id}
    public function update(Request $request, $id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'message' => 'Reservation not found'
            ], 404);
        }

        $validated = $request->validate([
            'space_id' => 'sometimes|exists:parking_spaces,id',
            'vehicle_id' => 'sometimes|exists:vehicles,id',
            'user_id' => 'sometimes|exists:users,id',
        ]);

        $reservation->update($validated);

        return response()->json([
            'message' => 'Reservation updated successfully',
            'reservation' => $reservation
        ], 200);
    }


    // DELETE /api/reservations/{id}
    public function destroy($id)
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json([
                'message' => 'Reservation not found'
            ], 404);
        }

        $reservation->delete();

        return response()->json([
            'message' => 'Reservation deleted successfully'
        ], 200);
    }
}