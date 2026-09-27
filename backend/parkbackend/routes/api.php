<?php

use App\Http\Controllers\AuthController as Auth;
use App\Http\Controllers\ParkSmartController as Api;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::post('register', [Auth::class, 'register'])->middleware('throttle:10,1');
Route::post('login', [Auth::class, 'login'])->middleware('throttle:10,1');
Route::get('stats', [Api::class, 'stats']);
Route::get('revenue-by-lot', [Api::class, 'revenueByLot']); // Public aggregate for existing landing page.
Route::post('chat', [ChatController::class, 'ask'])->middleware('throttle:30,1');
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [Auth::class, 'user']);
    Route::post('logout', [Auth::class, 'logout']);
    Route::get('lots', [Api::class, 'lots']);
    Route::get('lots/{id}', [Api::class, 'lot']);
    Route::get('users/{id}', [Api::class, 'user'])->whereNumber('id');
    Route::put('users/{id}', [Api::class, 'updateUser']);
    Route::get('users/{id}/vehicles', [Api::class, 'vehicles']);
    Route::post('users/{id}/vehicles', [Api::class, 'addVehicle']);
    Route::delete('vehicles/{id}', [Api::class, 'deleteVehicle']);
    Route::get('users/{id}/notifications', [Api::class, 'notifications']);
    Route::get('users/{id}/reservations', [Api::class, 'userReservations']);
    Route::get('users/{id}/payments', [Api::class, 'userPayments']);
    Route::get('users/{id}/finds', [Api::class, 'fines']);
    Route::get('reservations/{id}', [Api::class, 'reservation']);
    Route::delete('reservations/{id}', [Api::class, 'cancel'])->middleware('role:driver,admin');
    Route::post('reservations/{id}/pay', [Api::class, 'pay'])->middleware('role:driver,admin');
    Route::post('finds/{id}/pay', [Api::class, 'payFine'])->middleware('role:driver,admin');
    Route::post('reservations', [Api::class, 'reserve'])->middleware('role:driver');
    Route::middleware('role:staff,admin')->group(function () {
        Route::get('reservations', [Api::class, 'reservations']);
        Route::put('reservations/{id}', [Api::class, 'updateReservation']);
        Route::get('spots', [Api::class, 'spots']);
        Route::put('spots/{id}', [Api::class, 'updateSpot']);
        Route::get('sessions/active', [Api::class, 'sessions']);
        Route::post('sessions/entry', [Api::class, 'entry']);
        Route::post('sessions/{id}/exit', [Api::class, 'exitSession']);
        Route::get('finds/overdue', [Api::class, 'overdue']);
    });
    Route::middleware('role:admin')->group(function () {
        Route::get('users', [Api::class, 'users']);
        Route::post('users', [Api::class, 'createUser']);
        Route::delete('users/{id}', [Api::class, 'deleteUser']);
        Route::get('staff', [Api::class, 'staff']);
        Route::post('staff', [Api::class, 'createUser']);
        Route::put('staff/{id}', [Api::class, 'updateUser']);
        Route::delete('staff/{id}', [Api::class, 'deleteUser']);
        Route::post('lots', [Api::class, 'createLot']);
        Route::put('lots/{id}', [Api::class, 'updateLot']);
        Route::delete('lots/{id}', [Api::class, 'deleteLot']);
        Route::post('spots', [Api::class, 'createSpot']);
        Route::delete('spots/{id}', [Api::class, 'deleteSpot']);
        Route::get('payments', [Api::class, 'payments']);
        Route::get('reports/revenue', [Api::class, 'reports']);
        Route::get('reports/reservations-by-lot', [Api::class, 'reservationsByLot']);
        Route::get('reports/spending-by-driver', [Api::class, 'spendingByDriver']);
    });
});
