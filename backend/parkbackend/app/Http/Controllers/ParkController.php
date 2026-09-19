<?php
namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ParkController extends Controller
{
    private function actor(Request $r, array $roles = [])
    {
        $u = $r->attributes->get('actor');
        abort_unless($u && (!$roles || in_array($u->role, $roles, true)), 403, 'You cannot use this feature.');
        return $u;
    }
    private function own($u, $owner): void
    {
        abort_unless($u->role !== 'driver' || (int) $owner === (int) $u->id, 403, 'This record belongs to another user.');
    }
    private function procedure(string $sql, array $values = []): void
    {
        // Drain every CALL result set so PDO can safely execute the next query.
        $q = DB::connection()->getPdo()->prepare($sql);
        try {
            $q->execute($values);
        } catch (\PDOException $e) {
            if (in_array((int) ($e->errorInfo[1] ?? 0), [1062, 1644], true)) {
                abort(409, 'The bulk operation could not be completed. Check the lot, count and unique prefix. No spots were added.');
            }
            throw $e;
        }
        while ($q->nextRowset()) {
        }
        $q->closeCursor();
    }
    private function expire(): void
    {
        $this->procedure('CALL sp_expire_reservations()');
    }
    private function passwordHash(string $password): string
    {
        abort_if(strlen($password) > 72 || str_contains($password, chr(0)), 422, 'Use a password of 8 or more characters, within 72 bytes, without null characters.');
        return Hash::make($password);
    }
    private function id(): int
    {
        return (int) DB::connection()->getPdo()->lastInsertId();
    }
    private function bookingLock(int $id)
    {
        $meta = DB::selectOne('SELECT r.*,s.parking_lot_id FROM reservations r JOIN parking_spaces s ON s.id=r.space_id WHERE r.id=?', [$id]);
        abort_unless($meta, 404, 'Booking not found.');
        // All booking changes acquire locks in the same order: lot, vehicle, spot, booking.
        DB::selectOne('SELECT id FROM parking_lots WHERE id=? FOR UPDATE', [$meta->parking_lot_id]);
        DB::selectOne('SELECT id FROM vehicles WHERE id=? FOR UPDATE', [$meta->vehicle_id]);
        DB::selectOne('SELECT id FROM parking_spaces WHERE id=? FOR UPDATE', [$meta->space_id]);
        return DB::selectOne('SELECT * FROM reservations WHERE id=? FOR UPDATE', [$id]);
    }
    public function register(Request $r)
    {
        $d = $r->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:190', 'password' => 'required|string|min:8|max:72|confirmed']);
        DB::insert("INSERT INTO users(name,email,password,role) VALUES(?,?,?,'driver')", [$d['name'], strtolower($d['email']), $this->passwordHash($d['password'])]);
        return response()->json(['message' => 'Account created. Please log in.'], 201);
    }
    public function login(Request $r)
    {
        $d = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $u = DB::selectOne('SELECT * FROM users WHERE email=?', [strtolower($d['email'])]);
        abort_unless($u && strlen($d['password']) <= 72 && !str_contains($d['password'], chr(0)) && Hash::check($d['password'], $u->password), 401, 'Incorrect email or password.');
        $token = bin2hex(random_bytes(32));
        DB::insert('INSERT INTO api_tokens(user_id,token_hash,expires_at) VALUES(?,?,?)', [$u->id, hash('sha256', $token), now()->addHours(12)]);
        unset($u->password);
        return ['user' => $u, 'token' => $token];
    }
    public function me(Request $r)
    {
        return ['user' => $this->actor($r)];
    }
    public function logout(Request $r)
    {
        $u = $this->actor($r);
        DB::delete('DELETE FROM api_tokens WHERE id=?', [$u->token_id]);
        return ['message' => 'Logged out.'];
    }
    public function profile(Request $r)
    {
        $u = $this->actor($r);
        $d = $r->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:190', 'phone' => 'nullable|string|max:30']);
        DB::update('UPDATE users SET name=?,email=?,phone=? WHERE id=?', [$d['name'], strtolower($d['email']), $d['phone'] ?? null, $u->id]);
        return ['user' => DB::selectOne('SELECT id,name,email,phone,role FROM users WHERE id=?', [$u->id])];
    }
    public function vehicles(Request $r)
    {
        $u = $this->actor($r, ['driver', 'admin']);
        return DB::select('SELECT v.*,u.name AS user_name FROM vehicles v JOIN users u ON u.id=v.user_id WHERE (? IS NULL OR v.user_id=?) ORDER BY v.id DESC', [$u->role === 'driver' ? $u->id : null, $u->role === 'driver' ? $u->id : null]);
    }
    public function saveVehicle(Request $r, ?int $id = null)
    {
        $u = $this->actor($r, ['driver', 'admin']);
        $d = $r->validate(['user_id' => 'nullable|integer|min:1', 'plate_number' => 'required|string|max:30', 'make' => 'nullable|string|max:60', 'model' => 'nullable|string|max:60']);
        $plate = strtoupper(trim($d['plate_number']));
        abort_if($plate === '', 422, 'Enter a plate number.');
        return DB::transaction(function () use ($id, $u, $d, $plate) {
            if ($id) {
                $v = DB::selectOne('SELECT * FROM vehicles WHERE id=? FOR UPDATE', [$id]);
                abort_unless($v, 404, 'Vehicle not found.');
                $this->own($u, $v->user_id);
                abort_if(DB::selectOne("SELECT id FROM reservations WHERE vehicle_id=? AND status IN ('Pending','Confirmed','Active') LIMIT 1", [$id]), 409, 'Finish or cancel open bookings before changing the vehicle.');
                DB::update('UPDATE vehicles SET plate_number=?,make=?,model=? WHERE id=?', [$plate, $d['make'] ?? null, $d['model'] ?? null, $id]);
            } else {
                $owner = $u->role === 'admin' ? ($d['user_id'] ?? null) : $u->id;
                abort_unless($owner && DB::selectOne("SELECT id FROM users WHERE id=? AND role='driver'", [$owner]), 422, 'Choose a driver who owns this vehicle.');
                DB::insert('INSERT INTO vehicles(user_id,plate_number,make,model) VALUES(?,?,?,?)', [$owner, $plate, $d['make'] ?? null, $d['model'] ?? null]);
                $id = $this->id();
            }
            return ['vehicle' => DB::selectOne('SELECT * FROM vehicles WHERE id=?', [$id])];
        }, 3);
    }
    public function deleteVehicle(Request $r, int $id)
    {
        $u = $this->actor($r, ['driver', 'admin']);
        $v = DB::selectOne('SELECT user_id FROM vehicles WHERE id=?', [$id]);
        abort_unless($v, 404, 'Vehicle not found.');
        $this->own($u, $v->user_id);
        DB::delete('DELETE FROM vehicles WHERE id=?', [$id]);
        return ['message' => 'Vehicle deleted.'];
    }
    public function lots(Request $r)
    {
        $this->actor($r);
        $this->expire();
        $d = $r->validate(['search' => 'nullable|string|max:200', 'max_rate' => 'nullable|numeric|min:0', 'type' => 'nullable|in:Standard,Disabled,EV,Reserved']);
        return DB::select("SELECT l.* FROM v_lot_availability l WHERE (l.name LIKE ? OR l.location LIKE ?) AND (? IS NULL OR l.hourly_rate<=?) AND (? IS NULL OR EXISTS(SELECT 1 FROM parking_spaces s WHERE s.parking_lot_id=l.id AND s.type=?)) ORDER BY l.name", ['%' . ($d['search'] ?? '') . '%', '%' . ($d['search'] ?? '') . '%', $d['max_rate'] ?? null, $d['max_rate'] ?? null, $d['type'] ?? null, $d['type'] ?? null]);
    }
    public function saveLot(Request $r, ?int $id = null)
    {
        $this->actor($r, ['admin']);
        $d = $r->validate(['name' => 'required|string|max:100', 'location' => 'required|string|max:200', 'hourly_rate' => 'required|numeric|min:0.01|max:100000', 'description' => 'nullable|string|max:2000', 'contact' => 'nullable|string|max:100', 'opens_at' => 'required|date_format:H:i', 'closes_at' => 'required|date_format:H:i|after:opens_at']);
        $values = [$d['name'], $d['location'], $d['hourly_rate'], $d['description'] ?? null, $d['contact'] ?? null, $d['opens_at'], $d['closes_at']];
        if ($id) {
            abort_unless(DB::selectOne('SELECT id FROM parking_lots WHERE id=?', [$id]), 404, 'Lot not found.');
            DB::update('UPDATE parking_lots SET name=?,location=?,hourly_rate=?,description=?,contact=?,opens_at=?,closes_at=? WHERE id=?', [...$values, $id]);
        } else {
            DB::insert('INSERT INTO parking_lots(name,location,hourly_rate,description,contact,opens_at,closes_at) VALUES(?,?,?,?,?,?,?)', $values);
            $id = $this->id();
        }
        return ['lot' => DB::selectOne('SELECT * FROM v_lot_availability WHERE id=?', [$id])];
    }
    public function deleteLot(Request $r, int $id)
    {
        $this->actor($r, ['admin']);
        abort_unless(DB::delete('DELETE FROM parking_lots WHERE id=?', [$id]), 404, 'Lot not found.');
        return ['message' => 'Lot deleted.'];
    }
    public function spots(Request $r)
    {
        $this->actor($r);
        $d = $r->validate(['lot_id' => 'nullable|integer|min:1']);
        return DB::select('SELECT s.*,l.name AS lot_name FROM parking_spaces s JOIN parking_lots l ON l.id=s.parking_lot_id WHERE (? IS NULL OR s.parking_lot_id=?) ORDER BY l.name,s.space_number', [$d['lot_id'] ?? null, $d['lot_id'] ?? null]);
    }
    public function saveSpot(Request $r, ?int $id = null)
    {
        $u = $this->actor($r, ['admin', 'staff']);
        $d = $r->validate(['parking_lot_id' => 'required|integer|min:1', 'space_number' => 'required|string|max:30', 'type' => 'required|in:Standard,Disabled,EV,Reserved', 'status' => 'required|in:Available,Maintenance']);
        if (!$id)
            abort_unless($u->role === 'admin', 403, 'Only admins can create spots.');
        return DB::transaction(function () use ($d, $id, $u) {
            $l = DB::selectOne('SELECT id FROM parking_lots WHERE id=? FOR UPDATE', [$d['parking_lot_id']]);
            abort_unless($l, 404, 'Lot not found.');
            if ($id) {
                $s = DB::selectOne('SELECT * FROM parking_spaces WHERE id=? FOR UPDATE', [$id]);
                abort_unless($s, 404, 'Spot not found.');
                abort_unless((int) $s->parking_lot_id === (int) $d['parking_lot_id'], 422, 'A spot cannot be moved to another lot.');
                abort_if($s->status === 'Occupied', 409, 'Record the vehicle exit first.');
                abort_if(DB::selectOne("SELECT id FROM reservations WHERE space_id=? AND (status='Active' OR (status IN ('Pending','Confirmed') AND end_at>NOW())) LIMIT 1", [$id]), 409, 'The spot has open bookings. Cancel or finish them before editing.');
                if ($u->role === 'staff')
                    abort_unless($s->space_number === $d['space_number'] && $s->type === $d['type'], 403, 'Staff can change maintenance status only.');
                DB::update('UPDATE parking_spaces SET space_number=?,type=?,status=? WHERE id=?', [$d['space_number'], $d['type'], $d['status'], $id]);
            } else {
                DB::insert('INSERT INTO parking_spaces(parking_lot_id,space_number,type,status) VALUES(?,?,?,?)', [$d['parking_lot_id'], $d['space_number'], $d['type'], $d['status']]);
            }
            return ['message' => 'Spot saved.'];
        }, 3);
    }
    public function bulkSpots(Request $r)
    {
        $this->actor($r, ['admin']);
        $d = $r->validate(['lot_id' => 'required|integer|min:1', 'count' => 'required|integer|min:1|max:200', 'prefix' => 'required|alpha_dash|max:15']);
        $this->procedure('CALL sp_create_spaces(?,?,?)', [$d['lot_id'], $d['count'], $d['prefix']]);
        return ['message' => 'Spots created using the SQL WHILE loop.'];
    }
    public function deleteSpot(Request $r, int $id)
    {
        $this->actor($r, ['admin']);
        abort_unless(DB::delete('DELETE FROM parking_spaces WHERE id=?', [$id]), 404, 'Spot not found.');
        return ['message' => 'Spot deleted.'];
    }
    public function reservations(Request $r)
    {
        $u = $this->actor($r);
        $this->expire();
        return DB::select('SELECT * FROM v_reservation_details WHERE (? IS NULL OR user_id=?) ORDER BY id DESC', [$u->role === 'driver' ? $u->id : null, $u->role === 'driver' ? $u->id : null]);
    }
    public function book(Request $r)
    {
        $u = $this->actor($r, ['driver']);
        $d = $r->validate(['lot_id' => 'required|integer|min:1', 'vehicle_id' => 'required|integer|min:1', 'date' => 'required|date_format:Y-m-d', 'start' => 'required|date_format:H:i', 'end' => 'required|date_format:H:i|after:start', 'type' => 'required|in:Standard,Disabled,EV,Reserved']);
        $start = Carbon::createFromFormat('Y-m-d H:i', $d['date'] . ' ' . $d['start'])->second(0);
        $end = Carbon::createFromFormat('Y-m-d H:i', $d['date'] . ' ' . $d['end'])->second(0);
        abort_if($start->lte(now()) || $start->gt(now()->addYear()), 422, 'Choose a future start time within the next year.');
        $this->expire();
        return DB::transaction(function () use ($d, $u, $start, $end) {
            $lot = DB::selectOne('SELECT * FROM parking_lots WHERE id=? FOR UPDATE', [$d['lot_id']]);
            abort_unless($lot, 404, 'Lot not found.');
            abort_if($start->format('H:i:s') < $lot->opens_at || $end->format('H:i:s') > $lot->closes_at, 422, 'Choose a time within the lot opening hours.');
            $v = DB::selectOne('SELECT * FROM vehicles WHERE id=? FOR UPDATE', [$d['vehicle_id']]);
            abort_unless($v && (int) $v->user_id === (int) $u->id, 403, 'Choose your own vehicle.');
            // Current locking reads avoid stale snapshots after waiting for a competing transaction.
            $conflict = DB::selectOne("SELECT id FROM reservations WHERE vehicle_id=? AND (status='Active' OR (status IN ('Pending','Confirmed') AND start_at<? AND end_at>?)) LIMIT 1 FOR UPDATE", [$v->id, $end, $start]);
            abort_if($conflict, 409, 'This vehicle already has a booking during that time.');
            $spaces = DB::select("SELECT * FROM parking_spaces WHERE parking_lot_id=? AND type=? AND status='Available' ORDER BY id FOR UPDATE", [$lot->id, $d['type']]);
            $chosen = null;
            foreach ($spaces as $space) {
                if (!DB::selectOne("SELECT id FROM reservations WHERE space_id=? AND (status='Active' OR (status IN ('Pending','Confirmed') AND start_at<? AND end_at>?)) LIMIT 1 FOR UPDATE", [$space->id, $end, $start])) {
                    $chosen = $space;
                    break;
                }
            }
            abort_unless($chosen, 409, 'No spot of this type is free for the selected time.');
            $hours = (int) ceil($start->diffInMinutes($end) / 60);
            // Integer cents prevent floating-point rounding from changing the charge.
            $amount = ($hours * (int) round((float) $lot->hourly_rate * 100)) / 100;
            DB::insert('INSERT INTO reservations(user_id,vehicle_id,space_id,start_at,end_at,hourly_rate,total_amount) VALUES(?,?,?,?,?,?,?)', [$u->id, $v->id, $chosen->id, $start, $end, $lot->hourly_rate, $amount]);
            return response()->json(['message' => 'Booking submitted for staff approval.', 'reservation' => DB::selectOne('SELECT * FROM v_reservation_details WHERE id=?', [$this->id()])], 201);
        }, 3);
    }
    public function changeBooking(Request $r, int $id)
    {
        $u = $this->actor($r);
        $d = $r->validate(['status' => 'required|in:Confirmed,Cancelled']);
        if ($d['status'] === 'Confirmed')
            $this->actor($r, ['admin', 'staff']);
        return DB::transaction(function () use ($u, $d, $id) {
            $b = $this->bookingLock($id);
            $this->own($u, $b->user_id);
            abort_unless(in_array($b->status, ['Pending', 'Confirmed'], true), 409, 'Only pending or confirmed bookings can be changed.');
            abort_if($d['status'] === 'Confirmed' && ($b->status !== 'Pending' || Carbon::parse($b->end_at)->lte(now())), 409, 'This booking cannot be confirmed.');
            DB::update('UPDATE reservations SET status=? WHERE id=?', [$d['status'], $id]);
            return ['message' => 'Booking ' . $d['status'] . '.'];
        }, 3);
    }
    public function reschedule(Request $r, int $id)
    {
        $u = $this->actor($r);
        $d = $r->validate(['date' => 'required|date_format:Y-m-d', 'start' => 'required|date_format:H:i', 'end' => 'required|date_format:H:i|after:start']);
        $start = Carbon::createFromFormat('Y-m-d H:i', $d['date'] . ' ' . $d['start'])->second(0);
        $end = Carbon::createFromFormat('Y-m-d H:i', $d['date'] . ' ' . $d['end'])->second(0);
        abort_if($start->lte(now()) || $start->gt(now()->addYear()), 422, 'Choose a future start time within the next year.');
        return DB::transaction(function () use ($u, $id, $start, $end) {
            $b = $this->bookingLock($id);
            $this->own($u, $b->user_id);
            abort_unless(in_array($b->status, ['Pending', 'Confirmed'], true) && Carbon::parse($b->end_at)->gt(now()), 409, 'Only pending or confirmed bookings that have not expired can be rescheduled.');
            $lot = DB::selectOne('SELECT l.* FROM parking_lots l JOIN parking_spaces s ON s.parking_lot_id=l.id WHERE s.id=?', [$b->space_id]);
            abort_if($start->format('H:i:s') < $lot->opens_at || $end->format('H:i:s') > $lot->closes_at, 422, 'Choose a time within the lot opening hours.');
            $spot = DB::selectOne('SELECT status FROM parking_spaces WHERE id=?', [$b->space_id]);
            abort_unless($spot->status === 'Available', 409, 'The current spot is occupied or under maintenance.');
            $conflict = DB::selectOne("SELECT id FROM reservations WHERE id<>? AND (space_id=? OR vehicle_id=?) AND (status='Active' OR (status IN ('Pending','Confirmed') AND start_at<? AND end_at>?)) LIMIT 1 FOR UPDATE", [$id, $b->space_id, $b->vehicle_id, $end, $start]);
            abort_if($conflict, 409, 'The current spot or vehicle is already booked for that time. Choose another time, or cancel and book another lot.');
            $hours = (int) ceil($start->diffInMinutes($end) / 60);
            $amount = $hours * (int) round((float) $b->hourly_rate * 100) / 100;
            DB::update("UPDATE reservations SET start_at=?,end_at=?,total_amount=?,status='Pending' WHERE id=?", [$start, $end, $amount, $id]);
            return ['message' => 'Booking time changed. Staff approval is required again.'];
        }, 3);
    }
    public function entry(Request $r, int $id)
    {
        $this->actor($r, ['admin', 'staff']);
        return DB::transaction(function () use ($id) {
            $b = $this->bookingLock($id);
            abort_unless($b->status === 'Confirmed', 409, 'Entry requires a confirmed booking.');
            $time = now();
            abort_if($time->lt(Carbon::parse($b->start_at)) || $time->gte(Carbon::parse($b->end_at)), 409, 'Entry must be within the booked time.');
            $s = DB::selectOne('SELECT * FROM parking_spaces WHERE id=? FOR UPDATE', [$b->space_id]);
            abort_unless($s->status === 'Available', 409, 'The spot is occupied or under maintenance.');
            abort_if(DB::selectOne("SELECT id FROM reservations WHERE vehicle_id=? AND status='Active' LIMIT 1 FOR UPDATE", [$b->vehicle_id]), 409, 'This vehicle is already parked.');
            DB::insert('INSERT INTO parking_sessions(reservation_id,entry_time) VALUES(?,?)', [$id, $time]);
            DB::update("UPDATE parking_spaces SET status='Occupied' WHERE id=?", [$b->space_id]);
            DB::update("UPDATE reservations SET status='Active' WHERE id=?", [$id]);
            return ['message' => 'Vehicle entry recorded.'];
        }, 3);
    }
    public function exit(Request $r, int $id)
    {
        $this->actor($r, ['admin', 'staff']);
        return DB::transaction(function () use ($id) {
            $b = $this->bookingLock($id);
            abort_unless($b->status === 'Active', 409, 'Only an active booking can exit.');
            $s = DB::selectOne('SELECT * FROM parking_sessions WHERE reservation_id=? FOR UPDATE', [$id]);
            abort_unless($s && !$s->exit_time, 409, 'No open parking session.');
            $time = now();
            $minutes = max(1, (int) ceil(Carbon::parse($s->entry_time)->diffInMinutes($time)));
            // The booked duration is the minimum charge; late exit extends the billed period.
            $billEnd = $time->gt(Carbon::parse($b->end_at)) ? $time : Carbon::parse($b->end_at);
            $hours = max(1, (int) ceil(Carbon::parse($b->start_at)->diffInMinutes($billEnd) / 60));
            $amount = $hours * (int) round((float) $b->hourly_rate * 100) / 100;
            DB::update('UPDATE parking_sessions SET exit_time=?,duration_minutes=?,total_cost=? WHERE id=?', [$time, $minutes, $amount, $s->id]);
            DB::update("UPDATE reservations SET status='Completed',total_amount=? WHERE id=?", [$amount, $id]);
            DB::update("UPDATE parking_spaces SET status='Available' WHERE id=?", [$b->space_id]);
            return ['message' => 'Vehicle exit recorded. Demo payment is now due.', 'total_amount' => $amount];
        }, 3);
    }
    public function pay(Request $r, int $id)
    {
        $u = $this->actor($r, ['driver']);
        return DB::transaction(function () use ($u, $id) {
            $b = $this->bookingLock($id);
            $this->own($u, $b->user_id);
            abort_unless($b->status === 'Completed', 409, 'Payment is available after vehicle exit.');
            if ($b->payment_status === 'Paid')
                return ['message' => 'This booking is already paid.'];
            DB::insert("INSERT INTO payments(reservation_id,amount,method,transaction_id) VALUES(?,?,'Demo',?)", [$id, $b->total_amount, 'DEMO-' . bin2hex(random_bytes(16))]);
            DB::update("UPDATE reservations SET payment_status='Paid' WHERE id=?", [$id]);
            return ['message' => 'Demo payment recorded. No real money was charged.'];
        }, 3);
    }
    public function payments(Request $r)
    {
        $u = $this->actor($r, ['driver', 'admin']);
        return DB::select('SELECT p.*,r.user_id,u.name AS user_name,l.name AS lot_name FROM payments p JOIN reservations r ON r.id=p.reservation_id JOIN users u ON u.id=r.user_id JOIN parking_spaces s ON s.id=r.space_id JOIN parking_lots l ON l.id=s.parking_lot_id WHERE (? IS NULL OR r.user_id=?) ORDER BY p.id DESC', [$u->role === 'driver' ? $u->id : null, $u->role === 'driver' ? $u->id : null]);
    }
    public function notifications(Request $r)
    {
        $u = $this->actor($r);
        return DB::select('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT 200', [$u->id]);
    }
    public function readNotification(Request $r, int $id)
    {
        $u = $this->actor($r);
        abort_unless(DB::selectOne('SELECT id FROM notifications WHERE id=? AND user_id=?', [$id, $u->id]), 404, 'Notification not found.');
        DB::update('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?', [$id, $u->id]);
        return ['message' => 'Marked as read.'];
    }
    public function users(Request $r)
    {
        $this->actor($r, ['admin']);
        return DB::select('SELECT id,name,email,phone,role,created_at FROM users ORDER BY id DESC');
    }
    public function staff(Request $r)
    {
        $this->actor($r, ['admin']);
        $d = $r->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:190', 'password' => 'required|string|min:8|max:72']);
        DB::insert("INSERT INTO users(name,email,password,role) VALUES(?,?,?,'staff')", [$d['name'], strtolower($d['email']), $this->passwordHash($d['password'])]);
        return response()->json(['message' => 'Staff account created.'], 201);
    }
    public function updateUser(Request $r, int $id)
    {
        $this->actor($r, ['admin']);
        $d = $r->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:190', 'phone' => 'nullable|string|max:30']);
        abort_unless(DB::selectOne('SELECT id FROM users WHERE id=?', [$id]), 404, 'User not found.');
        DB::update('UPDATE users SET name=?,email=?,phone=? WHERE id=?', [$d['name'], strtolower($d['email']), $d['phone'] ?? null, $id]);
        return ['message' => 'User saved.'];
    }
    public function deleteUser(Request $r, int $id)
    {
        $this->actor($r, ['admin']);
        $u = DB::selectOne('SELECT id,role FROM users WHERE id=?', [$id]);
        abort_unless($u, 404, 'User not found.');
        abort_if($u->role === 'admin', 409, 'Admin accounts cannot be deleted through the API.');
        DB::delete('DELETE FROM users WHERE id=?', [$id]);
        return ['message' => 'User deleted.'];
    }
    public function reports(Request $r)
    {
        $this->actor($r, ['admin']);
        $this->expire();
        return [
            'lots' => DB::select('SELECT * FROM v_lot_availability ORDER BY name'),
            'revenue' => DB::select('SELECT * FROM v_revenue_by_lot ORDER BY total_revenue DESC'),
            'monthly' => DB::select("SELECT DATE_FORMAT(payment_date,'%Y-%m') AS month,SUM(amount) AS revenue,COUNT(*) AS payments FROM payments GROUP BY DATE_FORMAT(payment_date,'%Y-%m') ORDER BY month DESC"),
            'statuses' => DB::select('SELECT status,COUNT(*) AS total FROM reservations GROUP BY status'),
            'drivers' => DB::select("SELECT u.id,u.name,COUNT(p.id) AS payments,COALESCE(SUM(p.amount),0) AS spent FROM users u LEFT JOIN reservations r ON r.user_id=u.id LEFT JOIN payments p ON p.reservation_id=r.id WHERE u.role='driver' GROUP BY u.id,u.name ORDER BY spent DESC"),
            'audit' => DB::select('SELECT * FROM reservation_audit ORDER BY id DESC LIMIT 100'),
        ];
    }
}
