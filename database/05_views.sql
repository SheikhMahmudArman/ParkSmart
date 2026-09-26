-- Counts come from actual rows, never from stale counters.
------------jemi ----------------
CREATE VIEW v_lot_availability AS
SELECT *,
 COUNT(s.id) AS total_spots,
 COALESCE(SUM(s.status = 'Available' AND NOT EXISTS (
  SELECT * 
  FROM reservations r 
  WHERE r.space_id=s.id
  AND (r.status='Active' OR (r.status IN ('Pending','Confirmed') AND r.start_at<=NOW() AND r.end_at>NOW()))
 )),0) AS available_spots,
 COALESCE(SUM(s.status='Occupied'),0) AS occupied_spots
FROM parking_lots l 
LEFT JOIN parking_spaces s 
ON s.parking_lot_id=l.id
GROUP BY l.id,l.name,l.location,l.description,l.contact,l.opens_at,l.closes_at,l.hourly_rate,l.created_at;
-----------------------------------

--------------------------MAIMOONA--------------------------
CREATE VIEW v_reservation_details AS
SELECT
    r.*,
    u.name AS user_name,
    u.email,
    v.plate_number,
    s.space_number,
    s.type,
    l.id AS lot_id,
    l.name AS lot_name,
    l.location,
    ps.id AS session_id,
    ps.entry_time,
    ps.exit_time,
    ps.duration_minutes
FROM reservations r
JOIN users u
    ON u.id = r.user_id
JOIN vehicles v
    ON v.id = r.vehicle_id
JOIN parking_spaces s
    ON s.id = r.space_id
JOIN parking_lots l
    ON l.id = s.parking_lot_id
LEFT JOIN parking_sessions ps
    ON ps.reservation_id = r.id;
----------------------------------------------------

------------jemi ----------------
CREATE VIEW v_revenue_by_lot AS
SELECT 
    l.id AS lot_id,
    l.name AS lot_name,
    COUNT(p.id) AS total_payments,
    COALESCE(SUM(p.amount),0) AS total_revenue
FROM parking_lots l 
LEFT JOIN parking_spaces s ON s.parking_lot_id=l.id
LEFT JOIN reservations r ON r.space_id=s.id
LEFT JOIN payments p ON p.reservation_id=r.id
GROUP BY l.id,l.name;
-----------------------------------