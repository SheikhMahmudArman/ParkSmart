<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index()
    {
        // Raw SQL with JOIN and aggregate functions
        $stats = DB::select("
            SELECT 
                (SELECT COUNT(*) FROM users) AS total_users,
                COALESCE(SUM(payments.amount), 0) AS total_revenue
            FROM payments
            WHERE payments.status = 'Completed'
        ");

        return response()->json([
            'total_users' => $stats[0]->total_users ?? 0,
            'total_revenue' => $stats[0]->total_revenue ?? 0,
        ]);
    }
}