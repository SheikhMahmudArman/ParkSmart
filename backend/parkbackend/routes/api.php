<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ParkingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LotController;

// ==================== PUBLIC ROUTES ====================
Route::post('/test-register', function () {
    return response()->json([
        'message' => 'API is working'
    ]);
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ==================== PROTECTED ROUTES (require authentication) ====================
Route::middleware('auth:sanctum')->group(function () {

    // ---------- AUTH ----------
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ---------- USERS (Admin only) ----------
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // ---------- USER VEHICLES (Driver) ----------
    Route::get('/users/{id}/vehicles', [UserController::class, 'userVehicles']);

    // ---------- USER NOTIFICATIONS (Driver) ----------
    Route::get('/users/{id}/notifications', [UserController::class, 'notifications']);

    // ---------- STAFF (Admin only) ----------
    Route::get('/staff', [UserController::class, 'staff']);
    Route::post('/staff', [UserController::class, 'storeStaff']);
    Route::put('/staff/{id}', [UserController::class, 'updateStaff']);
    Route::delete('/staff/{id}', [UserController::class, 'destroyStaff']);

    // ---------- PARKING LOTS ----------
    Route::get('/lots', [LotController::class, 'index']);
    Route::get('/lots/{id}', [LotController::class, 'show']);
    Route::post('/lots', [LotController::class, 'store']);
    Route::put('/lots/{id}', [LotController::class, 'update']);
    Route::delete('/lots/{id}', [LotController::class, 'destroy']);

    // ---------- PARKING SPOTS ----------
    Route::get('/spots', [ParkingController::class, 'spots']);
    Route::post('/spots', [ParkingController::class, 'storeSpot']);
    Route::put('/spots/{id}', [ParkingController::class, 'updateSpot']);
    Route::delete('/spots/{id}', [ParkingController::class, 'destroySpot']);

    // ---------- RESERVATIONS ----------
    Route::get('/reservations', [ReservationController::class, 'index']); // Staff & Admin
    Route::post('/reservations', [ReservationController::class, 'store']); // Driver
    Route::get('/reservations/{id}', [ReservationController::class, 'show']);
    Route::put('/reservations/{id}', [ReservationController::class, 'update']); // Staff
    Route::delete('/reservations/{id}', [ReservationController::class, 'destroy']); // Cancel

    // ---------- USER RESERVATIONS ----------
    Route::get('/users/{id}/reservations', [ReservationController::class, 'userReservations']);

    // ---------- PARKING SESSIONS (Staff) ----------
    Route::get('/sessions/active', [ParkingController::class, 'activeSessions']);
    Route::post('/sessions/entry', [ParkingController::class, 'entrySession']);
    Route::post('/sessions/{id}/exit', [ParkingController::class, 'exitSession']);

    // ---------- PAYMENTS ----------
    Route::get('/payments', [ParkingController::class, 'allPayments']); // Admin
    Route::get('/users/{id}/payments', [ParkingController::class, 'userPayments']); // Driver
    Route::post('/reservations/{id}/pay', [ParkingController::class, 'processPayment']); // Driver

    // ---------- FINDS (Driver & Staff) ----------
    Route::get('/users/{id}/finds', [ParkingController::class, 'userFinds']);
    Route::get('/finds/overdue', [ParkingController::class, 'overdueFinds']);
    Route::post('/finds/{id}/pay', [ParkingController::class, 'payFind']);

    // ---------- REPORTS (Admin) ----------
    Route::get('/reports/revenue', [ParkingController::class, 'revenueReport']);
});