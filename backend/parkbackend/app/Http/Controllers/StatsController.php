<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index()
    {
        $stats = DB::select("
            SELECT 
                (SELECT COUNT(*) FROM users) AS total_users,
                CASE 
                    WHEN SUM(payments.amount) IS NULL THEN 0
                    ELSE SUM(payments.amount)
                END AS total_revenue
            FROM payments
            WHERE payments.status = 'Completed'
        ");

        return response()->json([
            'total_users' => $stats[0]->total_users ?? 0,
            'total_revenue' => $stats[0]->total_revenue ?? 0,
        ]);
    }
    public function revenueByLot()
    {
        $results = DB::select("
            SELECT 
                parking_lots.name AS lot_name,
                COUNT(payments.id) AS total_transactions,
                SUM(payments.amount) AS total_revenue
            FROM payments
            JOIN reservations ON payments.reservation_id = reservations.id
            JOIN parking_spaces ON reservations.space_id = parking_spaces.id
            JOIN parking_lots ON parking_spaces.parking_lot_id = parking_lots.id
            WHERE payments.status = 'Completed'
            GROUP BY parking_lots.id, parking_lots.name
            ORDER BY total_revenue DESC
        ");

        return response()->json($results);
    }
    public function reservationsByLot()
    {
        $results = DB::select("
            SELECT
                parking_lots.name AS lot_name,
                COUNT(reservations.id) AS total_reservations
            FROM parking_lots
            JOIN parking_spaces
                ON parking_lots.id = parking_spaces.parking_lot_id
            JOIN reservations
                ON parking_spaces.id = reservations.space_id
            GROUP BY parking_lots.id, parking_lots.name
            ORDER BY total_reservations DESC
        ");

        return response()->json($results);
    }

    public function spendingByDriver()
    {
        $results = DB::select("
            SELECT
                users.name AS driver_name,
                COUNT(payments.id) AS total_payments,
                SUM(payments.amount) AS total_spent
            FROM users
            JOIN reservations
                ON users.id = reservations.user_id
            JOIN payments
                ON reservations.id = payments.reservation_id
            WHERE users.role = 'driver'
            AND payments.status = 'Completed'
            GROUP BY users.id, users.name
            ORDER BY total_spent DESC
        ");

        return response()->json($results);
    }
}