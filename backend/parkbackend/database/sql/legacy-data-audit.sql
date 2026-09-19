-- Read-only checks before upgrading an existing database. Resolve findings on a copy.
-- This script does not guess missing prices, delete payments, or rewrite history.
SELECT id, reservation_date, start_time, end_time, total_amount, status
FROM reservations WHERE end_time <= start_time OR total_amount IS NULL OR total_amount < 0;

SELECT s.id, s.status, COUNT(se.id) AS active_sessions
FROM parking_spaces s LEFT JOIN parking_sessions se ON se.space_id=s.id AND se.exit_time IS NULL
GROUP BY s.id,s.status
HAVING (s.status='Occupied' AND active_sessions<>1)
 OR (s.status<>'Occupied' AND active_sessions>0) OR s.status='Reserved';

SELECT r1.id AS first_booking,r2.id AS overlapping_booking,r1.space_id
FROM reservations r1 JOIN reservations r2
 ON r1.id<r2.id AND r1.space_id=r2.space_id AND r1.reservation_date=r2.reservation_date
 AND r1.start_time<r2.end_time AND r1.end_time>r2.start_time
WHERE r1.status IN ('Pending','Confirmed','Active') AND r2.status IN ('Pending','Confirmed','Active');

SELECT r.id,r.user_id,v.user_id AS vehicle_owner
FROM reservations r JOIN vehicles v ON v.id=r.vehicle_id WHERE r.user_id<>v.user_id;

SELECT reservation_id,COUNT(*) AS payment_count
FROM payments WHERE reservation_id IS NOT NULL AND status='Completed'
 AND id NOT IN (SELECT payment_id FROM finds WHERE payment_id IS NOT NULL)
GROUP BY reservation_id HAVING COUNT(*)>1;

SELECT id,name,features FROM parking_lots WHERE JSON_TYPE(features)<>'ARRAY';
