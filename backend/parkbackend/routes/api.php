<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ParkingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LotController;

// ==================== PUBLIC ROUTES ====================
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ==================== PROTECTED ROUTES (require authentication) ====================
Route::middleware('auth:sanctum')->group(function () {

    // ---------- AUTH ----------
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // ---------- USERS (Admin only) ----------
    Route::get('/users', [UserController::class, 'index']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // ---------- PARKING LOTS (Admin) ----------
    Route::get('/lots', [LotController::class, 'index']);
    Route::post('/lots', [LotController::class, 'store']);
    Route::put('/lots/{id}', [LotController::class, 'update']);
    Route::delete('/lots/{id}', [LotController::class, 'destroy']);

    // ---------- RESERVATIONS (User & Staff) ----------
    Route::get('/users/{id}/reservations', [ReservationController::class, 'userReservations']);
    Route::delete('/reservations/{id}', [ReservationController::class, 'destroy']); // Cancel

    // ---------- PARKING SESSIONS (Staff) ----------
    Route::get('/sessions/active', [ParkingController::class, 'activeSessions']);
    Route::post('/sessions/{id}/exit', [ParkingController::class, 'exitSession']);

    // ---------- PAYMENTS (Driver & Admin) ----------
    Route::get('/users/{id}/payments', [ParkingController::class, 'userPayments']);
    Route::post('/reservations/{id}/pay', [ParkingController::class, 'processPayment']);

    // ---------- FINDS (Driver & Staff) ----------
    Route::get('/users/{id}/finds', [ParkingController::class, 'userFinds']);
    Route::get('/finds/overdue', [ParkingController::class, 'overdueFinds']);
    Route::post('/finds/{id}/pay', [ParkingController::class, 'payFind']);

    // ---------- REPORTS (Admin) ----------
    Route::get('/reports/revenue', [ParkingController::class, 'revenueReport']);
});