<?php

use App\Models\User;
use App\Support\Sql;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Run: php tests/integration.php against a dedicated database ending in _test.
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (!str_ends_with(config('database.connections.mysql.database'), '_test')) {
    fwrite(STDERR, "Refusing: DB_DATABASE must end in _test. This test adds fixtures.\n");
    exit(1);
}
$count = 0;
function check($condition, $message)
{
    global $count;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo 'PASS ' . ++$count . ': ' . $message . PHP_EOL;
}
function callApi($method, $uri, $data = [], $token = null)
{
    global $app;
    // Clear cached authentication guards between independent HTTP requests.
    $app['auth']->forgetGuards();
    $request = Request::create('/api' . $uri, $method, [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/json',
        'HTTP_AUTHORIZATION' => $token ? 'Bearer ' . $token : '',
    ], json_encode($data));
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($request);
    $body = json_decode($response->getContent(), true);
    $kernel->terminate($request, $response);

    return [$response->getStatusCode(), $body];
}
function loginAs($id)
{
    return User::findOrFail($id)->createToken('integration')->plainTextToken;
}
$suffix = bin2hex(random_bytes(5));
$ids = [];
foreach (['driver', 'other', 'staff', 'admin'] as $role) {
    DB::insert(
        'INSERT INTO users(name,email,password,role,created_at,updated_at) VALUES(?,?,?,?,NOW(),NOW())',
        [$role . ' ' . $suffix, $role . $suffix . '@example.test', Hash::make('TestPassword123!'), $role === 'other' ? 'driver' : $role]
    );
    $ids[$role] = (int) DB::getPdo()->lastInsertId();
}
$driver = loginAs($ids['driver']);
$other = loginAs($ids['other']);
$staff = loginAs($ids['staff']);
$admin = loginAs($ids['admin']);
[$status, $body] = callApi('POST', '/register', ['name' => 'Test signup', 'email' => 'signup' . $suffix . '@example.test', 'password' => 'TestPassword123!', 'password_confirmation' => 'TestPassword123!', 'role' => 'admin']);
check($status === 201 && $body['user']['role'] === 'driver', 'Public registration cannot grant admin');
[$status, $body] = callApi('POST', '/login', ['email' => 'signup' . $suffix . '@example.test', 'password' => 'TestPassword123!']);
check($status === 200 && isset($body['token']), 'Login issues token');
$signupToken = $body['token'];
check(callApi('POST', '/logout', [], $signupToken)[0] === 200 && callApi('GET', '/user', [], $signupToken)[0] === 401, 'Logout revokes token');
check(callApi('GET', '/lots')[0] === 401, 'Protected endpoints require authentication');
check(callApi('GET', '/users', [], $driver)[0] === 403, 'Driver cannot list user accounts');
check(callApi('GET', '/users/' . $ids['driver'] . '/vehicles', [], $other)[0] === 403, 'Other driver cannot read vehicles');
[$status, $users] = callApi('GET', '/users', [], $admin);
check($status === 200 && !array_key_exists('password', $users[0]), 'User list excludes password hashes');
[$status, $body] = callApi('PUT', '/users/' . $ids['driver'], ['name' => 'Updated', 'email' => 'driver' . $suffix . '@example.test', 'phone' => '01700000000', 'role' => 'admin'], $driver);
check($status === 200 && $body['user']['role'] === 'driver', 'Profile edits persist and cannot escalate role');
[$status] = callApi('POST', '/users/' . $ids['driver'] . '/vehicles', ['plate_number' => 'TEST-' . $suffix, 'make' => 'Toyota'], $driver);
check($status === 201, 'Driver can add a vehicle');
$vehicle = DB::selectOne('SELECT id FROM vehicles WHERE plate_number=?', ['TEST-' . $suffix])->id;
[$status, $lot] = callApi('POST', '/lots', ['name' => 'Test ' . $suffix, 'location' => 'Dhaka', 'total_spaces' => 1, 'hourly_rate' => 50, 'type' => 'Standard', 'features' => ['Security']], $admin);
check($status === 201, 'Admin creates lot through SQL loop');
$lotId = $lot['id'];
$space = DB::selectOne('SELECT * FROM parking_spaces WHERE parking_lot_id=?', [$lotId]);
check((int) DB::selectOne('SELECT COUNT(*) AS n FROM parking_spaces WHERE parking_lot_id=?', [$lotId])->n === 1, 'Loop creates exactly the requested spaces');
$date = now()->addDay()->toDateString();
$booking = ['lot_id' => $lotId, 'vehicle_id' => $vehicle, 'reservation_date' => $date, 'start_time' => '10:00', 'end_time' => '11:01'];
[$status, $body] = callApi('POST', '/reservations', $booking, $driver);
check($status === 201 && (float) $body['reservation']['total_amount'] === 100.0, 'Reservation uses SQL hourly rounding');
$rid = $body['reservation']['id'];
check(callApi('POST', '/reservations', $booking, $driver)[0] === 422, 'Overlapping vehicle booking is rejected');
check(callApi('POST', '/reservations', $booking, $other)[0] === 422, 'Booking another driver vehicle is rejected');
check(callApi('GET', '/reservations/' . $rid, [], $other)[0] === 403, 'Reservation ownership is enforced');
check(callApi('POST', '/reservations/' . $rid . '/pay', ['payment_method' => 'Demo'], $other)[0] === 403, 'Another driver cannot pay or alter a booking');
check(callApi('DELETE', '/reservations/' . $rid, [], $other)[0] === 403, 'Another driver cannot cancel a booking');
$bad = $booking;
$bad['end_time'] = '09:00';
check(callApi('POST', '/reservations', $bad, $driver)[0] === 422, 'Invalid time range is rejected');
$adjacent = $booking;
$adjacent['start_time'] = '11:01';
$adjacent['end_time'] = '12:00';
[$status, $second] = callApi('POST', '/reservations', $adjacent, $driver);
check($status === 201, 'Adjacent non-overlapping reservation can reuse the space');
$secondId = $second['reservation']['id'];
check(callApi('DELETE', '/reservations/' . $secondId, [], $driver)[0] === 200, 'Unpaid reservation can be cancelled');
check(DB::selectOne('SELECT status FROM reservations WHERE id=?', [$secondId])->status === 'Cancelled', 'Cancellation retains reservation history');
[$status, $payment] = callApi('POST', '/reservations/' . $rid . '/pay', ['payment_method' => 'Demo', 'amount' => 0.01], $driver);
check($status === 200 && (float) $payment['amount'] === 100.0, 'Payment ignores client supplied amount');
check(callApi('POST', '/reservations/' . $rid . '/pay', ['payment_method' => 'Demo'], $driver)[0] === 422, 'Duplicate payment is rejected');
check((int) DB::selectOne('SELECT COUNT(*) AS n FROM payments WHERE reservation_id=?', [$rid])->n === 1, 'Duplicate payment does not insert a second row');
check(callApi('DELETE', '/reservations/' . $rid, [], $driver)[0] === 422, 'Paid reservation cancellation requires a refund workflow');
check(callApi('PUT', '/spots/' . $space->id, ['status' => 'Maintenance'], $staff)[0] === 409, 'Maintenance cannot invalidate future bookings');
check(callApi('DELETE', '/lots/' . $lotId, [], $admin)[0] === 409, 'Lot deletion cannot erase reservation history');
$entry = ['plate_number' => 'TEST-' . $suffix, 'lot_id' => $lotId, 'space_number' => $space->space_number];
check(callApi('POST', '/sessions/entry', $entry, $driver)[0] === 403, 'Drivers cannot record staff entry');
check(callApi('POST', '/sessions/entry', $entry, $staff)[0] === 422, 'Entry before booked interval is rejected');
// Move only this test fixture into the current interval to exercise entry without waiting.
DB::update("UPDATE reservations SET reservation_date=CURDATE(),start_time='00:00:00',end_time='23:59:59' WHERE id=?", [$rid]);
[$status, $session] = callApi('POST', '/sessions/entry', $entry, $staff);
check($status === 201, 'Staff can enter a vehicle with a current matching reservation');
$sid = $session['session']['id'];
check(callApi('POST', '/sessions/entry', $entry, $staff)[0] === 422, 'Duplicate active entry is rejected');
check(DB::selectOne('SELECT status FROM parking_spaces WHERE id=?', [$space->id])->status === 'Occupied', 'Entry occupies physical space');
// Force this fixture beyond its booked interval so exit creates an overstay fine.
DB::update('UPDATE reservations SET reservation_date=DATE_SUB(CURDATE(),INTERVAL 1 DAY) WHERE id=?', [$rid]);
[$status] = callApi('POST', '/sessions/' . $sid . '/exit', [], $staff);
check($status === 200, 'Exit completes session');
check(callApi('POST', '/sessions/' . $sid . '/exit', [], $staff)[0] === 422, 'Repeated exit is rejected');
check(DB::selectOne('SELECT status FROM reservations WHERE id=?', [$rid])->status === 'Completed', 'Exit completes linked reservation');
check(DB::selectOne('SELECT status FROM parking_spaces WHERE id=?', [$space->id])->status === 'Available', 'Exit releases physical space');
$fine = DB::selectOne('SELECT * FROM finds WHERE session_id=?', [$sid]);
check($fine && (float) $fine->amount > 0, 'Overstay generates a priced fine');
check(callApi('POST', '/finds/' . $fine->id . '/pay', ['payment_method' => 'Demo'], $driver)[0] === 200, 'Driver pays overstay fine in demo');
check(callApi('POST', '/finds/' . $fine->id . '/pay', ['payment_method' => 'Demo'], $driver)[0] === 422, 'Fine payment is protected against duplicates');
[$status, $notices] = callApi('GET', '/users/' . $ids['driver'] . '/notifications', [], $driver);
check($status === 200 && count($notices) >= 4, 'Audit triggers produce reservation notifications');
DB::update("UPDATE reservations SET reservation_date=DATE_SUB(CURDATE(),INTERVAL 1 DAY),status='Pending' WHERE id=?", [$secondId]);
$expired = Sql::call('sp_expire_reservations');
check($expired[0]->expired_count >= 1 && DB::selectOne('SELECT status FROM reservations WHERE id=?', [$secondId])->status === 'Expired', 'Cursor loop expires unused bookings');
check(callApi('GET', '/reports/revenue', [], $staff)[0] === 403, 'Staff cannot access admin reports');
[$status, $report] = callApi('GET', '/reports/revenue', [], $admin);
check($status === 200 && count($report['revenue_by_lot']) > 0, 'Reports aggregate persisted payments');
// A SQL failure after a transaction starts must release the lock and leave no partial insert.
$before = DB::selectOne('SELECT COUNT(*) AS n FROM reservations')->n;
try {
    Sql::call('sp_reserve', [$ids['driver'], $vehicle, $lotId, $date, '15:00', '14:00']);
} catch (PDOException $e) {
}
check(DB::selectOne('SELECT COUNT(*) AS n FROM reservations')->n === $before && !DB::getPdo()->inTransaction(), 'Procedure exception rolls back transaction');
[$status] = callApi('POST', '/staff', ['name' => 'New Staff', 'email' => 'newstaff' . $suffix . '@example.test', 'password' => 'TestPassword123!', 'role' => 'staff', 'phone' => '01700000000', 'assigned_lot' => 'Test assignment'], $admin);
check($status === 201 && DB::selectOne('SELECT phone FROM users WHERE email=?', ['newstaff' . $suffix . '@example.test'])->phone === '01700000000', 'Admin can create staff accounts including phone');
$newStaff = DB::selectOne('SELECT id FROM users WHERE email=?', ['newstaff' . $suffix . '@example.test'])->id;
check(callApi('PUT', '/staff/' . $newStaff, ['name' => 'Edited Staff', 'email' => 'newstaff' . $suffix . '@example.test', 'role' => 'staff', 'phone' => '', 'assigned_lot' => ''], $admin)[0] === 200 && DB::selectOne('SELECT phone FROM users WHERE id=?', [$newStaff])->phone === null, 'Admin can edit staff and clear optional phone');
check(callApi('DELETE', '/staff/' . $newStaff, [], $admin)[0] === 200, 'Admin can remove unused staff account');
check(callApi('PUT', '/lots/' . $lotId, ['name' => 'Edited ' . $suffix, 'location' => 'Dhaka', 'hourly_rate' => 65, 'type' => 'Standard', 'features' => ['Covered']], $admin)[0] === 200, 'Admin can edit lot details');
check(callApi('POST', '/spots', ['parking_lot_id' => $lotId, 'space_number' => 'EXTRA', 'type' => 'Standard'], $admin)[0] === 201, 'Admin can add a space');
$extra = DB::selectOne("SELECT id FROM parking_spaces WHERE parking_lot_id=? AND space_number='EXTRA'", [$lotId])->id;
check(callApi('POST', '/spots', ['parking_lot_id' => $lotId, 'space_number' => 'EXTRA', 'type' => 'Standard'], $admin)[0] === 422, 'Duplicate space number gives a validation error');
check(callApi('PUT', '/spots/' . $extra, ['status' => 'Maintenance'], $staff)[0] === 200, 'Staff can place an unused space into maintenance');
check(callApi('PUT', '/spots/' . $extra, ['status' => 'Available'], $staff)[0] === 200, 'Staff can restore unused space availability');
check(callApi('DELETE', '/spots/' . $extra, [], $admin)[0] === 200, 'Admin can remove an unused space');
foreach (['/users', '/staff', '/lots', '/spots', '/payments', '/reports/reservations-by-lot', '/reports/spending-by-driver'] as $url) {
    check(callApi('GET', $url, [], $admin)[0] === 200, 'Admin data endpoint ' . $url . ' responds');
}
check(callApi('DELETE', '/users/' . $ids['admin'], [], $admin)[0] === 422, 'Admin cannot delete own account');
check(callApi('GET', '/does-not-exist', [], $admin)[0] === 404, 'Unknown API route remains a 404, not the React HTML');
echo "All $count integration checks passed. Fixtures retained in test database.\n";
