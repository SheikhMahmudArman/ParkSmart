<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Sql;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ParkSmartController extends Controller
{
    private function row(string $sql, array $args = []): object
    {
        $row = DB::selectOne($sql, $args);
        abort_unless($row, 404, 'Record not found');

        return $row;
    }

    private function owner(int $id): void
    {
        abort_unless(request()->user()->role === 'admin' || request()->user()->id === $id, 403, 'Access denied');
    }

    private function booking(int $id): object
    {
        $r = $this->row('SELECT * FROM reservations WHERE id=?', [$id]);
        if (request()->user()->role === 'driver') {
            $this->owner((int) $r->user_id);
        }

        return $r;
    }

    private function method(Request $request): string
    {
        $data = $request->validate(['payment_method' => 'required|in:Demo,Cash']);

        return $data['payment_method'];
    }

    public function users()
    {
        return DB::select('SELECT id,name,email,role,phone,assigned_lot FROM users ORDER BY id DESC');
    }

    public function staff()
    {
        return DB::select("SELECT id,name,email,role,phone,assigned_lot FROM users WHERE role IN ('staff','admin') ORDER BY id DESC");
    }

    public function user($id)
    {
        $this->owner((int) $id);

        return response()->json($this->row('SELECT id,name,email,role,phone,assigned_lot FROM users WHERE id=?', [$id]));
    }

    public function createUser(Request $request)
    {
        $d = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:driver,staff,admin',
            'phone' => 'nullable|string|max:30',
            'assigned_lot' => 'nullable|string|max:255'
        ]);
        DB::insert(
            'INSERT INTO users(name,email,password,role,phone,assigned_lot,created_at,updated_at) VALUES(?,?,?,?,?,?,NOW(),NOW())',
            [$d['name'], $d['email'], Hash::make($d['password']), $d['role'], $d['phone'] ?? null, $d['assigned_lot'] ?? null]
        );

        return response()->json(['message' => 'User created'], 201);
    }

    public function updateUser(Request $request, $id)
    {
        $this->owner((int) $id);
        $old = $this->row('SELECT id,name,email,phone,role,assigned_lot FROM users WHERE id=?', [$id]);
        $d = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $old->id,
            'phone' => 'nullable|string|max:30',
            'role' => 'sometimes|in:driver,staff,admin',
            'assigned_lot' => 'nullable|string|max:255'
        ]);
        $role = $request->user()->role === 'admin' ? ($d['role'] ?? $old->role) : $old->role;
        abort_if((int) $id === $request->user()->id && $role !== $old->role, 422, 'You cannot change your own role');
        Sql::locked(function () use ($d, $id, $role, $old) {
            DB::update(
                'UPDATE users SET name=?,email=?,phone=?,role=?,assigned_lot=?,updated_at=NOW() WHERE id=?',
                [$d['name'], $d['email'], array_key_exists('phone', $d) ? $d['phone'] : $old->phone, $role, array_key_exists('assigned_lot', $d) ? $d['assigned_lot'] : $old->assigned_lot, $id]
            );
            if ($role !== $old->role) {
                DB::delete('DELETE FROM personal_access_tokens WHERE tokenable_id=? AND tokenable_type=?', [$id, User::class]);
            }
        });

        return response()->json(['message' => 'Profile saved', 'user' => $this->row('SELECT id,name,email,phone,role FROM users WHERE id=?', [$id])]);
    }

    public function deleteUser($id)
    {
        abort_if((int) $id === request()->user()->id, 422, 'You cannot delete your own account');
        Sql::locked(function () use ($id) {
            $this->row('SELECT id FROM users WHERE id=?', [$id]);
            abort_if(DB::selectOne('SELECT id FROM vehicles WHERE user_id=? LIMIT 1', [$id]), 409, 'Remove unused vehicles first; accounts with parking history are retained');
            DB::delete('DELETE FROM personal_access_tokens WHERE tokenable_id=? AND tokenable_type=?', [$id, User::class]);
            DB::delete('DELETE FROM users WHERE id=?', [$id]);
        });

        return ['message' => 'User deleted'];
    }

    public function vehicles($id)
    {
        $this->owner((int) $id);

        return DB::select('SELECT * FROM vehicles WHERE user_id=? ORDER BY id', [$id]);
    }

    public function addVehicle(Request $request, $id)
    {
        $this->owner((int) $id);
        $request->merge(['plate_number' => strtoupper(trim($request->input('plate_number', '')))]);
        $d = $request->validate(['plate_number' => 'required|string|max:20|unique:vehicles,plate_number', 'make' => 'nullable|string|max:50', 'model' => 'nullable|string|max:50', 'color' => 'nullable|string|max:30']);
        DB::insert(
            'INSERT INTO vehicles(user_id,plate_number,make,model,color,created_at,updated_at) VALUES(?,?,?,?,?,NOW(),NOW())',
            [$id, $d['plate_number'], $d['make'] ?? null, $d['model'] ?? null, $d['color'] ?? null]
        );

        return response()->json(['message' => 'Vehicle added'], 201);
    }

    public function deleteVehicle($id)
    {
        Sql::locked(function () use ($id) {
            $v = $this->row('SELECT * FROM vehicles WHERE id=?', [$id]);
            $this->owner((int) $v->user_id);
            abort_if(DB::selectOne('SELECT id FROM reservations WHERE vehicle_id=? LIMIT 1', [$id]) || DB::selectOne('SELECT id FROM parking_sessions WHERE vehicle_id=? LIMIT 1', [$id]), 409, 'Vehicle has parking history and cannot be deleted');
            DB::delete('DELETE FROM vehicles WHERE id=?', [$id]);
        });

        return ['message' => 'Vehicle deleted'];
    }

    public function lots()
    {
        $rows = DB::select('SELECT * FROM v_lot_availability ORDER BY id');
        foreach ($rows as $row) {
            $row->features = json_decode($row->features ?? '[]', true);
        }

        return $rows;
    }

    public function lot($id)
    {
        $row = $this->row('SELECT * FROM v_lot_availability WHERE id=?', [$id]);
        $row->features = json_decode($row->features ?? '[]', true);

        return response()->json($row);
    }

    public function createLot(Request $request)
    {
        $d = $request->validate([
            'name' => 'required|string|max:100',
            'location' => 'required|string|max:200',
            'total_spaces' => 'required|integer|min:1|max:1000',
            'hourly_rate' => 'required|numeric|min:0|max:999999',
            'type' => 'required|string|max:50',
            'features' => 'sometimes|array',
            'features.*' => 'string|max:100'
        ]);
        $r = Sql::call('sp_create_lot', [$d['name'], $d['location'], $d['total_spaces'], $d['hourly_rate'], $d['type'], json_encode($d['features'] ?? [])]);

        return response()->json($r[0], 201);
    }

    public function updateLot(Request $request, $id)
    {
        $d = $request->validate([
            'name' => 'required|string|max:100',
            'location' => 'required|string|max:200',
            'hourly_rate' => 'required|numeric|min:0|max:999999',
            'type' => 'required|string|max:50',
            'features' => 'sometimes|array',
            'features.*' => 'string|max:100'
        ]);
        Sql::locked(function () use ($d, $id) {
            $this->row('SELECT id FROM parking_lots WHERE id=?', [$id]);
            DB::update(
                'UPDATE parking_lots SET name=?,location=?,hourly_rate=?,type=?,features=?,updated_at=NOW() WHERE id=?',
                [$d['name'], $d['location'], $d['hourly_rate'], $d['type'], json_encode($d['features'] ?? []), $id]
            );
        });

        return ['message' => 'Lot updated'];
    }

    public function deleteLot($id)
    {
        Sql::locked(function () use ($id) {
            $this->row('SELECT id FROM parking_lots WHERE id=?', [$id]);
            abort_if(DB::selectOne('SELECT r.id FROM reservations r JOIN parking_spaces s ON s.id=r.space_id WHERE s.parking_lot_id=? LIMIT 1', [$id]) ||
                DB::selectOne('SELECT se.id FROM parking_sessions se JOIN parking_spaces s ON s.id=se.space_id WHERE s.parking_lot_id=? LIMIT 1', [$id]), 409, 'Lot has parking history and cannot be deleted');
            DB::delete('DELETE FROM parking_lots WHERE id=?', [$id]);
        });

        return ['message' => 'Lot deleted'];
    }

    public function spots()
    {
        return DB::select('SELECT s.*,s.space_number AS spot,l.name AS lot,l.name AS lot_name FROM parking_spaces s JOIN parking_lots l ON l.id=s.parking_lot_id ORDER BY l.id,s.id');
    }

    public function createSpot(Request $request)
    {
        $d = $request->validate(['parking_lot_id' => 'required|exists:parking_lots,id', 'space_number' => 'required|string|max:20', 'type' => 'required|string|max:50']);
        Sql::locked(function () use ($d) {
            abort_if(DB::selectOne('SELECT id FROM parking_spaces WHERE parking_lot_id=? AND space_number=?', [$d['parking_lot_id'], $d['space_number']]), 422, 'Space number already exists in this lot');
            DB::insert("INSERT INTO parking_spaces(parking_lot_id,space_number,type,status,created_at,updated_at) VALUES(?,?,?,'Available',NOW(),NOW())", [$d['parking_lot_id'], $d['space_number'], $d['type']]);
        });

        return response()->json(['message' => 'Space added'], 201);
    }

    public function updateSpot(Request $request, $id)
    {
        $d = $request->validate(['status' => 'required|in:Available,Maintenance']);
        Sql::locked(function () use ($d, $id) {
            $this->row('SELECT id FROM parking_spaces WHERE id=?', [$id]);
            abort_if(DB::selectOne('SELECT id FROM parking_sessions WHERE space_id=? AND exit_time IS NULL', [$id]), 409, 'Use Exit to release an occupied space');
            abort_if($d['status'] === 'Maintenance' && DB::selectOne("SELECT id FROM reservations WHERE space_id=? AND status IN ('Pending','Confirmed','Active') AND TIMESTAMP(reservation_date,end_time)>NOW()", [$id]), 409, 'Space has a current or future reservation');
            DB::update('UPDATE parking_spaces SET status=?,updated_at=NOW() WHERE id=?', [$d['status'], $id]);
        });

        return ['message' => 'Space updated'];
    }

    public function deleteSpot($id)
    {
        Sql::locked(function () use ($id) {
            $this->row('SELECT id FROM parking_spaces WHERE id=?', [$id]);
            abort_if(DB::selectOne('SELECT id FROM reservations WHERE space_id=? LIMIT 1', [$id]) || DB::selectOne('SELECT id FROM parking_sessions WHERE space_id=? LIMIT 1', [$id]), 409, 'Space has parking history');
            DB::delete('DELETE FROM parking_spaces WHERE id=?', [$id]);
        });

        return ['message' => 'Space deleted'];
    }

    private function reservationRows(?int $user = null): array
    {
        return DB::select('SELECT r.*,r.reservation_date AS date,r.start_time AS start,r.end_time AS end,
   s.space_number AS spot,l.name AS lot_name,v.plate_number AS vehicle_plate,u.name AS user_name
   FROM reservations r JOIN parking_spaces s ON s.id=r.space_id JOIN parking_lots l ON l.id=s.parking_lot_id
   JOIN vehicles v ON v.id=r.vehicle_id JOIN users u ON u.id=r.user_id ' .
            ($user !== null ? 'WHERE r.user_id=? ' : '') . 'ORDER BY r.id DESC', $user !== null ? [$user] : []);
    }

    public function reservations()
    {
        return $this->reservationRows();
    }

    public function userReservations($id)
    {
        $this->owner((int) $id);

        return ['reservations' => $this->reservationRows((int) $id)];
    }

    public function reservation($id)
    {
        return ['reservation' => $this->booking((int) $id)];
    }

    public function reserve(Request $request)
    {
        $d = $request->validate([
            'lot_id' => 'required|integer|exists:parking_lots,id',
            'vehicle_id' => 'required|integer|exists:vehicles,id',
            'reservation_date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i'
        ]);
        $rows = Sql::call('sp_reserve', [$request->user()->id, $d['vehicle_id'], $d['lot_id'], $d['reservation_date'], $d['start_time'], $d['end_time']]);

        return response()->json(['message' => 'Reservation created', 'reservation' => $rows[0], 'spot_number' => $rows[0]->spot_number], 201);
    }

    public function updateReservation(Request $request, $id)
    {
        $this->booking((int) $id);
        $d = $request->validate(['status' => 'required|in:Confirmed,Cancelled']);

        return ['reservation' => Sql::call('sp_reservation_status', [$id, $d['status']])[0]];
    }

    public function cancel($id)
    {
        $this->booking((int) $id);
        Sql::call('sp_reservation_status', [$id, 'Cancelled']);

        return ['message' => 'Reservation cancelled'];
    }

    public function pay(Request $request, $id)
    {
        $this->booking((int) $id);
        $row = Sql::call('sp_pay_reservation', [$id, $this->method($request)])[0];

        return ['message' => 'Demo payment recorded; no money charged', 'payment' => $row, 'amount' => $row->amount];
    }

    public function sessions()
    {
        return DB::select('SELECT se.*,v.plate_number,s.space_number,l.name AS lot_name FROM parking_sessions se
   JOIN vehicles v ON v.id=se.vehicle_id JOIN parking_spaces s ON s.id=se.space_id
   JOIN parking_lots l ON l.id=s.parking_lot_id WHERE se.exit_time IS NULL ORDER BY se.entry_time');
    }

    public function entry(Request $request)
    {
        $d = $request->validate(['plate_number' => 'required|string|max:20', 'lot_id' => 'required|integer', 'space_number' => 'required|string|max:20']);

        return response()->json(['session' => Sql::call('sp_entry', [strtoupper(trim($d['plate_number'])), $d['lot_id'], $d['space_number']])[0]], 201);
    }

    public function exitSession($id)
    {
        return response()->json(Sql::call('sp_exit', [$id])[0]);
    }

    public function payments()
    {
        return DB::select('SELECT * FROM v_payment_details ORDER BY id DESC');
    }

    public function userPayments($id)
    {
        $this->owner((int) $id);

        return DB::select('SELECT * FROM v_payment_details WHERE user_id=? ORDER BY id DESC', [$id]);
    }

    public function fines($id)
    {
        $this->owner((int) $id);

        return DB::select('SELECT f.* FROM finds f LEFT JOIN reservations r ON r.id=f.reservation_id LEFT JOIN parking_sessions se ON se.id=f.session_id LEFT JOIN vehicles v ON v.id=se.vehicle_id WHERE COALESCE(r.user_id,v.user_id)=? ORDER BY f.id DESC', [$id]);
    }

    public function payFine(Request $request, $id)
    {
        $f = $this->row('SELECT COALESCE(r.user_id,v.user_id) AS user_id FROM finds f LEFT JOIN reservations r ON r.id=f.reservation_id LEFT JOIN parking_sessions se ON se.id=f.session_id LEFT JOIN vehicles v ON v.id=se.vehicle_id WHERE f.id=?', [$id]);
        $this->owner((int) $f->user_id);

        return ['payment' => Sql::call('sp_pay_fine', [$id, $this->method($request)])[0]];
    }

    public function overdue()
    {
        return DB::select("SELECT * FROM finds WHERE status='Pending' AND issue_date<DATE_SUB(NOW(),INTERVAL 7 DAY)");
    }

    public function notifications($id)
    {
        $this->owner((int) $id);

        return DB::select("SELECT a.id,CONCAT('Reservation #',a.reservation_id,' is ',a.new_status) AS message,a.changed_at AS created_at FROM reservation_audit a JOIN reservations r ON r.id=a.reservation_id WHERE r.user_id=? ORDER BY a.id DESC LIMIT 50", [$id]);
    }

    public function stats()
    {
        return response()->json(DB::selectOne("SELECT (SELECT COUNT(*) FROM users) AS total_users,
   (SELECT COUNT(*) FROM parking_spaces) AS total_spaces,(SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='Completed') AS total_revenue"));
    }

    public function revenueByLot()
    {
        return DB::select('SELECT * FROM v_revenue_by_lot ORDER BY total_revenue DESC');
    }

    public function reports()
    {
        $stats = DB::selectOne("SELECT (SELECT COUNT(*) FROM reservations) AS total_reservations,
   (SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='Completed' AND payment_date>=DATE_FORMAT(NOW(),'%Y-%m-01')) AS monthly_revenue,
   (SELECT COALESCE(ROUND(100*SUM(status='Occupied')/NULLIF(COUNT(*),0)),0) FROM parking_spaces) AS occupancy");

        return [
            'monthly_revenue' => $stats->monthly_revenue,
            'total_reservations' => $stats->total_reservations,
            'occupancy' => $stats->occupancy,
            'revenue_by_lot' => DB::select('SELECT lot_name AS lot,total_revenue AS amount,total_transactions AS transactions,average_payment AS average FROM v_revenue_by_lot')
        ];
    }

    public function reservationsByLot()
    {
        return DB::select('SELECT l.name AS lot_name,COUNT(r.id) AS total_reservations FROM parking_lots l LEFT JOIN parking_spaces s ON s.parking_lot_id=l.id LEFT JOIN reservations r ON r.space_id=s.id GROUP BY l.id,l.name');
    }

    public function spendingByDriver()
    {
        return DB::select("SELECT u.name AS driver_name,COUNT(p.id) AS total_payments,COALESCE(SUM(p.amount),0) AS total_spent FROM users u LEFT JOIN v_payment_details p ON p.user_id=u.id AND p.status='Completed' WHERE u.role='driver' GROUP BY u.id,u.name");
    }
}
