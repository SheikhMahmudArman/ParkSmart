-- Read-only examples. These run in phpMyAdmin after setup.
-- JOIN and VIEW
------------jemi ----------------
SELECT *
FROM v_reservation_details 
ORDER BY created_at DESC;
-----------------------------------


-- GROUP BY, HAVING, aggregate function
SELECT user_id,COUNT(*) AS bookings FROM reservations GROUP BY user_id HAVING COUNT(*)>=2;
-- LEFT JOIN includes lots with no payments
SELECT * FROM v_revenue_by_lot ORDER BY total_revenue DESC;
-- Subquery
SELECT name FROM users WHERE id IN (SELECT user_id FROM reservations WHERE status='Active');
-- CASE expression


------------jemi ----------------
SELECT id,
    CASE 
        WHEN payment_status='Paid' THEN 'Nothing due' 
        ELSE 'Unpaid' 
    END AS payment_label 
FROM reservations;
-----------------------------------


-- UNION
SELECT email FROM users WHERE role='admin' UNION SELECT email FROM users WHERE role='staff';
-- Transaction and ROLLBACK: this change is deliberately undone.

------------jemi ----------------
START TRANSACTION;
    UPDATE parking_lots 
    SET hourly_rate=hourly_rate+10 
    WHERE id=1;

    SELECT hourly_rate 
    FROM parking_lots 
    WHERE id=1;
ROLLBACK;
-- Loop and procedure are used by the admin Bulk add spots button.
-- CALL sp_create_spaces(1,3,'B-');
