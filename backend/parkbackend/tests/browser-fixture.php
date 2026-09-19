<?php

use App\Support\Sql;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// Test-only current booking, so the UI entry/exit workflow can be checked without waiting.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! str_ends_with(config('database.connections.mysql.database'), '_test')) {
    exit("Use a dedicated _test database.\n");
}
$suffix = bin2hex(random_bytes(3));
$user = DB::selectOne("SELECT id FROM users WHERE email='john@driver.com'");
if (! $user) {
    exit("Run the demo seeder first.\n");
}
$lot = Sql::call('sp_create_lot', ['UI Current '.$suffix, 'Test area', 1, 50, 'Standard', '[]'])[0];
$space = DB::selectOne('SELECT * FROM parking_spaces WHERE parking_lot_id=?', [$lot->id]);
$plate = 'UI-NOW-'.$suffix;
DB::insert('INSERT INTO vehicles(user_id,plate_number,created_at,updated_at) VALUES(?,?,NOW(),NOW())', [$user->id, $plate]);
$vehicle = DB::getPdo()->lastInsertId();
Sql::locked(fn () => DB::insert("INSERT INTO reservations(user_id,vehicle_id,space_id,reservation_date,start_time,end_time,total_amount,status,payment_status,reservation_time,created_at,updated_at) VALUES(?,?,?,CURDATE(),'00:00:00','23:59:59',1200,'Confirmed','Pending',NOW(),NOW(),NOW())", [$user->id, $vehicle, $space->id]));
echo json_encode(['plate' => $plate, 'lot' => $lot->name, 'space' => $space->space_number]).PHP_EOL;
