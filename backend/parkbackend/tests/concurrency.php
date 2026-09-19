<?php

use App\Support\Sql;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! str_ends_with(config('database.connections.mysql.database'), '_test')) {
    exit("Use a dedicated _test database.\n");
}
if (($argv[1] ?? '') === 'worker') {
    try {
        Sql::call($argv[2], json_decode($argv[3], true));
        echo 'accepted';
    } catch (PDOException $e) {
        if (($e->errorInfo[0] ?? '') === '45000') {
            echo 'rejected';
        } else {
            throw $e;
        }
    }
    exit;
}
function race($routine, $arguments)
{
    DB::beginTransaction();
    DB::select('SELECT id FROM parking_operation_lock WHERE id=1 FOR UPDATE');
    $workers = [];
    foreach ($arguments as $args) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, __FILE__, 'worker', $routine, json_encode($args)], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        fclose($pipes[0]);
        $workers[] = [$process, $pipes];
    }
    usleep(300000);
    DB::commit();
    $out = [];
    foreach ($workers as [$process,$pipes]) {
        $out[] = trim(stream_get_contents($pipes[1]));
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($process);
        if ($exit !== 0) {
            throw new RuntimeException($errors);
        }
    }
    sort($out);
    if ($out !== ['accepted', 'rejected']) {
        throw new RuntimeException('Race failed: '.json_encode($out));
    }
    echo 'PASS: '.$routine.' accepts one concurrent request and rejects the other'.PHP_EOL;
}
$suffix = bin2hex(random_bytes(4));
$drivers = [];
foreach ([1, 2] as $i) {
    DB::insert("INSERT INTO users(name,email,password,role,created_at,updated_at) VALUES(?,?,?,'driver',NOW(),NOW())", ['Race '.$i, 'race'.$suffix.$i.'@example.test', password_hash('TestPassword123!', PASSWORD_BCRYPT)]);
    $user = DB::getPdo()->lastInsertId();
    DB::insert('INSERT INTO vehicles(user_id,plate_number,created_at,updated_at) VALUES(?,?,NOW(),NOW())', [$user, 'RACE-'.$suffix.$i]);
    $drivers[] = [$user, DB::getPdo()->lastInsertId()];
}
$lot = Sql::call('sp_create_lot', ['Race '.$suffix, 'Test', 1, 50, 'Standard', '[]'])[0]->id;
$date = now()->addDays(2)->toDateString();
race('sp_reserve', array_map(fn ($d) => [$d[0], $d[1], $lot, $date, '10:00', '11:00'], $drivers));
$reservation = DB::selectOne('SELECT r.id FROM reservations r JOIN parking_spaces s ON s.id=r.space_id WHERE s.parking_lot_id=?', [$lot])->id;
race('sp_pay_reservation', [[$reservation, 'Demo'], [$reservation, 'Demo']]);
if ((int) DB::selectOne('SELECT COUNT(*) AS n FROM payments WHERE reservation_id=?', [$reservation])->n !== 1) {
    throw new RuntimeException('Duplicate payment rows');
}
echo "PASS: one durable payment row after concurrent requests\n";
