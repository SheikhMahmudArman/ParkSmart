<?php

namespace App\Http\Controllers;

use App\Models\ParkingLot;
use Illuminate\Http\Request;

class LotController extends Controller
{
    // GET /api/lots - List all parking lots
    public function index()
    {
        $lots = ParkingLot::with(['parkingSpaces'])->get();
        // Transform data to match frontend expectations
        $transformed = $lots->map(function ($lot) {
            return [
                'id' => $lot->id,
                'name' => $lot->name,
                'location' => $lot->location,
                'total_spots' => $lot->total_spaces ?? $lot->parkingSpaces->count(),
                'available_spots' => $lot->available_spaces ?? $lot->parkingSpaces->where('status', 'Available')->count(),
                'hourly_rate' => $lot->hourly_rate ?? 5,
                'type' => $lot->type ?? 'Standard',
                'features' => $lot->features ?? ['Security', 'Covered'],
                'created_at' => $lot->created_at,
                'updated_at' => $lot->updated_at
            ];
        });
        return response()->json($transformed);
    }

    // GET /api/lots/{id} - Show specific lot
    public function show($id)
    {
        $lot = ParkingLot::with(['parkingSpaces'])->findOrFail($id);
        return response()->json([
            'id' => $lot->id,
            'name' => $lot->name,
            'location' => $lot->location,
            'total_spots' => $lot->total_spaces ?? $lot->parkingSpaces->count(),
            'available_spots' => $lot->available_spaces ?? $lot->parkingSpaces->where('status', 'Available')->count(),
            'hourly_rate' => $lot->hourly_rate ?? 5,
            'type' => $lot->type ?? 'Standard',
            'features' => $lot->features ?? ['Security', 'Covered'],
        ]);
    }

    // POST /api/lots - Create a new parking lot
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'location' => 'required|string|max:200',
            'total_spaces' => 'nullable|integer',
            'hourly_rate' => 'nullable|numeric',
            'type' => 'nullable|string',
            'features' => 'nullable|array'
        ]);

        $lot = ParkingLot::create([
            'name' => $validated['name'],
            'location' => $validated['location'],
            'total_spaces' => $validated['total_spaces'] ?? 0,
            'available_spaces' => $validated['total_spaces'] ?? 0,
            'hourly_rate' => $validated['hourly_rate'] ?? 5,
            'type' => $validated['type'] ?? 'Standard',
            'features' => json_encode($validated['features'] ?? ['Security', 'Covered'])
        ]);

        return response()->json($lot, 201);
    }

    // PUT /api/lots/{id} - Update a parking lot
    public function update(Request $request, $id)
    {
        $lot = ParkingLot::findOrFail($id);
        $data = $request->all();

        if (isset($data['features']) && is_array($data['features'])) {
            $data['features'] = json_encode($data['features']);
        }

        $lot->update($data);
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