<?php

use App\Support\Sql;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('parking:expire', function () {
    $this->info('Expired: ' . Sql::call('sp_expire_reservations')[0]->expired_count);
})->purpose('Expire bookings that ended without an entry');
Schedule::command('parking:expire')->everyMinute()->withoutOverlapping();
