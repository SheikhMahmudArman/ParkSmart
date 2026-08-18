<?php

namespace App\Http\Controllers;

use App\Models\ParkingLot;
use Illuminate\Http\Request;

class LotController extends Controller
{
    // GET /api/lots - List all parking lots
    public function index()
    {
        $lots = ParkingLot::all();
        return response()->json($lots);
    }

    // POST /api/lots - Create a new parking lot
    public function store(Request $request)
    {
        $validated = $request->validate([
            'Name' => 'required|string|max:100',
            'Location' => 'required|string|max:200',
            'TotalSpaces' => 'required|integer',
            'HourlyRate' => 'required|numeric',
            'Type' => 'required|in:Standard,Premium,VIP'
        ]);

        $lot = ParkingLot::create($validated);
        return response()->json($lot, 201);
    }

    // PUT /api/lots/{id} - Update a parking lot
    public function update(Request $request, $id)
    {
        $lot = ParkingLot::findOrFail($id);
        $lot->update($request->all());
        return response()->json($lot);
    }

    // DELETE /api/lots/{id} - Delete a parking lot
    public function destroy($id)
    {
        $lot = ParkingLot::findOrFail($id);
        $lot->delete();
        return response()->json(['message' => 'Lot deleted successfully']);
    }
}