<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LotController extends Controller
{
    // GET /api/lots - List all parking lots
    public function index()
    {
        $lots = DB::select("
            SELECT 
                pl.id,
                pl.name,
                pl.location,
                pl.total_spaces AS total_spots,
                pl.available_spaces AS available_spots,
                pl.hourly_rate,
                pl.type,
                pl.features,
                pl.created_at,
                pl.updated_at,
                COUNT(ps.id) AS actual_spaces_count,
                SUM(CASE WHEN ps.status = 'Available' THEN 1 ELSE 0 END) AS available_count
            FROM parking_lots pl
            LEFT JOIN parking_spaces ps ON pl.id = ps.parking_lot_id
            GROUP BY pl.id, pl.name, pl.location, pl.total_spaces, 
                     pl.available_spaces, pl.hourly_rate, pl.type, 
                     pl.features, pl.created_at, pl.updated_at
        ");

        $transformed = array_map(function ($lot) {
            return [
                'id' => $lot->id,
                'name' => $lot->name,
                'location' => $lot->location,
                'total_spots' => $lot->total_spots ?? $lot->actual_spaces_count,
                'available_spots' => $lot->available_spots ?? $lot->available_count,
                'hourly_rate' => $lot->hourly_rate ?? 5,
                'type' => $lot->type ?? 'Standard',
                'features' => $lot->features ? json_decode($lot->features, true) : ['Security', 'Covered'],
                'created_at' => $lot->created_at,
                'updated_at' => $lot->updated_at
            ];
        }, $lots);

        return response()->json($transformed);
    }

    // GET /api/lots/{id} - Show specific lot
    public function show($id)
    {
        $lot = DB::select("
            SELECT 
                pl.id,
                pl.name,
                pl.location,
                pl.total_spaces AS total_spots,
                pl.available_spaces AS available_spots,
                pl.hourly_rate,
                pl.type,
                pl.features,
                COUNT(ps.id) AS actual_spaces_count,
                SUM(CASE WHEN ps.status = 'Available' THEN 1 ELSE 0 END) AS available_count
            FROM parking_lots pl
            LEFT JOIN parking_spaces ps ON pl.id = ps.parking_lot_id
            WHERE pl.id = ?
            GROUP BY pl.id, pl.name, pl.location, pl.total_spaces, 
                     pl.available_spaces, pl.hourly_rate, pl.type, 
                     pl.features, pl.created_at, pl.updated_at
        ", [$id]);

        if (!$lot) {
            return response()->json(['message' => 'Lot not found'], 404);
        }

        $lot = $lot[0];
        return response()->json([
            'id' => $lot->id,
            'name' => $lot->name,
            'location' => $lot->location,
            'total_spots' => $lot->total_spots ?? $lot->actual_spaces_count,
            'available_spots' => $lot->available_spots ?? $lot->available_count,
            'hourly_rate' => $lot->hourly_rate ?? 5,
            'type' => $lot->type ?? 'Standard',
            'features' => $lot->features ? json_decode($lot->features, true) : ['Security', 'Covered'],
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

        $id = DB::table('parking_lots')->insertGetId([
            'name' => $validated['name'],
            'location' => $validated['location'],
            'total_spaces' => $validated['total_spaces'] ?? 0,
            'available_spaces' => $validated['total_spaces'] ?? 0,
            'hourly_rate' => $validated['hourly_rate'] ?? 5,
            'type' => $validated['type'] ?? 'Standard',
            'features' => json_encode($validated['features'] ?? ['Security', 'Covered']),
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $lot = DB::table('parking_lots')->where('id', $id)->first();

        return response()->json($lot, 201);
    }

    // PUT /api/lots/{id} - Update a parking lot
    public function update(Request $request, $id)
    {
        $lot = DB::table('parking_lots')->where('id', $id)->first();

        if (!$lot) {
            return response()->json(['message' => 'Lot not found'], 404);
        }

        $data = $request->all();

        if (isset($data['features']) && is_array($data['features'])) {
            $data['features'] = json_encode($data['features']);
        }

        $data['updated_at'] = now();

        DB::table('parking_lots')->where('id', $id)->update($data);

        $updatedLot = DB::table('parking_lots')->where('id', $id)->first();

        return response()->json($updatedLot);
    }

    // DELETE /api/lots/{id} - Delete a parking lot
    public function destroy($id)
    {
        $deleted = DB::table('parking_lots')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Lot not found'], 404);
        }

        return response()->json(['message' => 'Lot deleted successfully']);
    }
}