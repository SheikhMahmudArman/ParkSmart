-- Run AFTER the original Laravel table migrations. No existing rows are deleted.
-- All booking/session procedures serialize mutations on a singleton InnoDB row.
-- This deliberately favors correctness over throughput for a course project.
CREATE TABLE IF NOT EXISTS parking_operation_lock (id INT PRIMARY KEY) ENGINE=InnoDB;
INSERT IGNORE INTO parking_operation_lock VALUES (1);
CREATE TABLE IF NOT EXISTS reservation_audit (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 reservation_id BIGINT UNSIGNED NOT NULL,
 old_status VARCHAR(20), new_status VARCHAR(20) NOT NULL,
 changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX(reservation_id)
) ENGINE=InnoDB;
-- Audit has no cascading foreign key: history survives administrative maintenance.
DROP VIEW IF EXISTS v_lot_availability;


------------JJEMIIII----------------------------------------------------
CREATE VIEW v_lot_availability AS
 SELECT 
  l.id, 
  l.name, 
  l.location, 
  l.hourly_rate, 
  l.type, 
  l.features,
 COUNT(s.id) AS total_spots,
 SUM(CASE 
      WHEN s.status='Available' 
      AND NOT EXISTS (
          SELECT * 
          FROM reservations r 
          WHERE r.space_id=s.id
            AND r.status IN ('Pending','Confirmed','Active')
            AND TIMESTAMP(r.reservation_date,r.start_time)<=NOW()
            AND TIMESTAMP(r.reservation_date,r.end_time)>NOW()
      ) 
      THEN 1 ELSE 0 
  END) AS available_spots
 FROM parking_lots l 
 LEFT JOIN parking_spaces s 
 ON s.parking_lot_id=l.id
 GROUP BY l.id,l.name,l.location,l.hourly_rate,l.type,l.features;


DROP VIEW IF EXISTS v_payment_details;


CREATE VIEW v_payment_details AS
 SELECT 
      p.*, 
      COALESCE(r.user_id,v.user_id) AS user_id,
      l.id AS lot_id,l.name AS lot_name
 FROM payments p
 LEFT JOIN reservations r 
 ON r.id=p.reservation_id
 LEFT JOIN parking_sessions se 
 ON se.id=p.session_id
 LEFT JOIN vehicles v 
 ON v.id=se.vehicle_id
 LEFT JOIN parking_spaces s 
 ON s.id=COALESCE(r.space_id,se.space_id)
 LEFT JOIN parking_lots l 
 ON l.id=s.parking_lot_id;


DROP VIEW IF EXISTS v_revenue_by_lot;


CREATE VIEW v_revenue_by_lot AS
 SELECT 
      lot_id,
      lot_name,
      COUNT(*) AS total_transactions,
      SUM(amount) AS total_revenue,
      AVG(amount) AS average_payment
 FROM v_payment_details 
 WHERE status='Completed' 
 GROUP BY lot_id,lot_name;


DROP FUNCTION IF EXISTS fn_parking_price;

DELIMITER $$

CREATE FUNCTION fn_parking_price(
  p_start DATETIME,
  p_end DATETIME,
  p_rate DECIMAL(10,2))
RETURNS DECIMAL(10,2) 
DETERMINISTIC NO SQL
BEGIN
  RETURN ROUND(GREATEST(1,CEIL(TIMESTAMPDIFF(SECOND,p_start,p_end)/3600))*p_rate,2);
END$$
-------------------------------------------------------------------------------------------------------


-----------------------------------MAIMOONA-------------------------------------
DROP TRIGGER IF EXISTS reservations_validate_insert$$

CREATE TRIGGER reservations_validate_insert
BEFORE INSERT ON reservations
FOR EACH ROW
BEGIN
    IF NEW.end_time <= NEW.start_time
       OR NEW.total_amount IS NULL
       OR NEW.total_amount < 0 THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid reservation interval or price';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM vehicles
        WHERE id = NEW.vehicle_id
          AND user_id = NEW.user_id
    ) THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Vehicle does not belong to this driver';
    END IF;
END$$
----------------------------------------------------------------


---------------------------MAIMOONA----------------------------
DROP TRIGGER IF EXISTS reservations_audit_insert$$

CREATE TRIGGER reservations_audit_insert
AFTER INSERT ON reservations
FOR EACH ROW
BEGIN
    INSERT INTO reservation_audit(
        reservation_id,
        old_status,
        new_status
    )
    VALUES(
        NEW.id,
        NULL,
        NEW.status
    );
END$$
-------------------------------------------------------------------


------------------------------------MAIMOONA------------------------------------
DROP TRIGGER IF EXISTS reservations_audit_update$$

CREATE TRIGGER reservations_audit_update
AFTER UPDATE ON reservations
FOR EACH ROW
BEGIN
    IF OLD.status <> NEW.status THEN
        INSERT INTO reservation_audit(
            reservation_id,
            old_status,
            new_status
        )
        VALUES(
            NEW.id,
            OLD.status,
            NEW.status
        );
    END IF;
END$$
------------------------------------------------------------------------


-----------------------------MAIMOONA----------------------------
DROP PROCEDURE IF EXISTS sp_reserve$$

CREATE PROCEDURE sp_reserve(
    IN p_user BIGINT,
    IN p_vehicle BIGINT,
    IN p_lot BIGINT,
    IN p_date DATE,
    IN p_start TIME,
    IN p_end TIME
)
BEGIN
    DECLARE v_lock INT;
    DECLARE v_space BIGINT DEFAULT NULL;
    DECLARE v_rate DECIMAL(10,2);
    DECLARE v_id BIGINT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT id
    INTO v_lock
    FROM parking_operation_lock
    WHERE id = 1
    FOR UPDATE;

    IF p_end <= p_start
       OR TIMESTAMP(p_date, p_start) <= NOW() THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Choose a future start and a later end on the same day';
    END IF;

    IF NOT EXISTS (
        SELECT 1
        FROM vehicles
        WHERE id = p_vehicle
          AND user_id = p_user
    ) THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Add your vehicle in Profile before reserving';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM reservations
        WHERE vehicle_id = p_vehicle
          AND status IN ('Pending', 'Confirmed', 'Active')
          AND reservation_date = p_date
          AND start_time < p_end
          AND end_time > p_start
    ) THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'This vehicle already has an overlapping reservation';
    END IF;

    SELECT hourly_rate
    INTO v_rate
    FROM parking_lots
    WHERE id = p_lot;

    SELECT s.id
    INTO v_space
    FROM parking_spaces s
    WHERE s.parking_lot_id = p_lot
      AND s.status = 'Available'
      AND NOT EXISTS (
          SELECT 1
          FROM reservations r
          WHERE r.space_id = s.id
            AND r.status IN ('Pending', 'Confirmed', 'Active')
            AND r.reservation_date = p_date
            AND r.start_time < p_end
            AND r.end_time > p_start
      )
      AND NOT EXISTS (
          SELECT 1
          FROM parking_sessions se
          WHERE se.space_id = s.id
            AND se.exit_time IS NULL
      )
    ORDER BY s.id
    LIMIT 1;

    IF v_space IS NULL THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'No space is available for that time interval';
    END IF;

    INSERT INTO reservations (
        user_id,
        vehicle_id,
        space_id,
        reservation_date,
        start_time,
        end_time,
        status,
        payment_status,
        total_amount,
        reservation_time,
        created_at,
        updated_at
    )
    VALUES (
        p_user,
        p_vehicle,
        v_space,
        p_date,
        p_start,
        p_end,
        'Pending',
        'Pending',
        fn_parking_price(
            TIMESTAMP(p_date, p_start),
            TIMESTAMP(p_date, p_end),
            v_rate
        ),
        NOW(),
        NOW(),
        NOW()
    );

    SET v_id = LAST_INSERT_ID();

    COMMIT;

    SELECT
        r.*,
        s.space_number AS spot_number
    FROM reservations r
    JOIN parking_spaces s
        ON s.id = r.space_id
    WHERE r.id = v_id;
END$$
-----------------------------------------------------------------

-----------------------------MAIMOONA----------------------------
DROP PROCEDURE IF EXISTS sp_reservation_status$$

CREATE PROCEDURE sp_reservation_status(
    IN p_id BIGINT,
    IN p_status VARCHAR(20)
)
BEGIN
    DECLARE v_lock INT;
    DECLARE v_status VARCHAR(20);
    DECLARE v_paid VARCHAR(20);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT id
    INTO v_lock
    FROM parking_operation_lock
    WHERE id = 1
    FOR UPDATE;

    SELECT
        status,
        payment_status
    INTO
        v_status,
        v_paid
    FROM reservations
    WHERE id = p_id
    FOR UPDATE;

    IF v_status IS NULL THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Reservation not found';
    END IF;

    IF p_status NOT IN ('Confirmed', 'Cancelled')
       OR v_status NOT IN ('Pending', 'Confirmed') THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Only pending or confirmed bookings can be confirmed or cancelled; use Entry/Exit for active bookings';
    END IF;

    IF p_status = 'Cancelled'
       AND v_paid = 'Paid' THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Paid bookings cannot be cancelled in this demo; refunds are not implemented';
    END IF;

    UPDATE reservations
    SET
        status = p_status,
        updated_at = NOW()
    WHERE id = p_id;

    COMMIT;

    SELECT *
    FROM reservations
    WHERE id = p_id;
END$$
-----------------------------------------------------------------

----------------JEMIIIIIIIIII-------------------------------------
DROP PROCEDURE IF EXISTS sp_pay_reservation$$

CREATE PROCEDURE sp_pay_reservation(IN p_id BIGINT,IN p_method VARCHAR(50))
BEGIN
    DECLARE v_lock INT;
    DECLARE v_amount DECIMAL(10,2);
    DECLARE v_paid VARCHAR(20);
    DECLARE v_status VARCHAR(20);
    DECLARE v_id BIGINT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN 
      ROLLBACK; 
      RESIGNAL; 
    END;
    START TRANSACTION;
    SELECT id INTO v_lock 
    FROM parking_operation_lock 
    WHERE id=1 
    FOR UPDATE;

    SELECT total_amount,payment_status,status 
    INTO v_amount,v_paid,v_status 
    FROM reservations 
    WHERE id=p_id 
    FOR UPDATE;

    IF v_status IS NULL OR v_status IN ('Cancelled','Expired') THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT='Reservation cannot be paid';
    END IF;
    IF v_paid='Paid' THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT='Reservation is already paid';
    END IF;
    IF v_amount IS NULL OR v_amount<0 THEN 
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT='Reservation price is missing'; 
    END IF;

    INSERT INTO payments(reservation_id,amount,payment_date,method,status,transaction_id,created_at,updated_at)
    VALUES(p_id,v_amount,NOW(),p_method,'Completed',CONCAT('DEMO-',UUID()),NOW(),NOW());
    SET v_id=LAST_INSERT_ID();
    UPDATE reservations 
    SET payment_status='Paid',
        status=IF(status='Pending','Confirmed',status),
        updated_at=NOW() WHERE id=p_id;
    COMMIT;
    SELECT * FROM payments WHERE id=v_id;
END$$
-----------------------------------------------------------------


-------------------------------MAIMOONA----------------------------
DROP PROCEDURE IF EXISTS sp_entry$$

CREATE PROCEDURE sp_entry(
    IN p_plate VARCHAR(20),
    IN p_lot BIGINT,
    IN p_number VARCHAR(20)
)
BEGIN
    DECLARE v_lock INT;
    DECLARE v_space BIGINT;
    DECLARE v_vehicle BIGINT;
    DECLARE v_res BIGINT DEFAULT NULL;
    DECLARE v_rate DECIMAL(10,2);
    DECLARE v_status VARCHAR(20);
    DECLARE v_id BIGINT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT id
    INTO v_lock
    FROM parking_operation_lock
    WHERE id = 1
    FOR UPDATE;

    SELECT id
    INTO v_vehicle
    FROM vehicles
    WHERE plate_number = p_plate;

    SELECT
        s.id,
        s.status,
        l.hourly_rate
    INTO
        v_space,
        v_status,
        v_rate
    FROM parking_spaces s
    JOIN parking_lots l
        ON l.id = s.parking_lot_id
    WHERE s.parking_lot_id = p_lot
      AND s.space_number = p_number;

    IF v_vehicle IS NULL THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Driver must register this vehicle in Profile first';
    END IF;

    IF v_space IS NULL
       OR v_status <> 'Available' THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Space is unavailable';
    END IF;

    IF EXISTS (
        SELECT 1
        FROM parking_sessions
        WHERE exit_time IS NULL
          AND (
              vehicle_id = v_vehicle
              OR space_id = v_space
          )
    ) THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Vehicle or space already has an active session';
    END IF;

    SELECT id
    INTO v_res
    FROM reservations
    WHERE vehicle_id = v_vehicle
      AND space_id = v_space
      AND status IN ('Pending', 'Confirmed')
      AND TIMESTAMP(reservation_date, start_time) <= NOW()
      AND TIMESTAMP(reservation_date, end_time) > NOW()
    ORDER BY id
    LIMIT 1;

    IF v_res IS NULL THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'A current reservation for this vehicle and space is required';
    END IF;

    INSERT INTO parking_sessions (
        reservation_id,
        vehicle_id,
        space_id,
        entry_time,
        hourly_rate,
        created_at,
        updated_at
    )
    VALUES (
        v_res,
        v_vehicle,
        v_space,
        NOW(),
        v_rate,
        NOW(),
        NOW()
    );

    SET v_id = LAST_INSERT_ID();

    UPDATE parking_spaces
    SET
        status = 'Occupied',
        updated_at = NOW()
    WHERE id = v_space;

    UPDATE reservations
    SET
        status = 'Active',
        updated_at = NOW()
    WHERE id = v_res;

    COMMIT;

    SELECT *
    FROM parking_sessions
    WHERE id = v_id;
END$$
--------------------------------------------------------------


--------------------------------MAIMOONA------------------------
DROP PROCEDURE IF EXISTS sp_exit$$

CREATE PROCEDURE sp_exit(
    IN p_session BIGINT
)
BEGIN
    DECLARE v_lock INT;
    DECLARE v_space BIGINT;
    DECLARE v_res BIGINT;
    DECLARE v_entry DATETIME;
    DECLARE v_exit DATETIME;
    DECLARE v_end DATETIME;
    DECLARE v_rate DECIMAL(10,2);
    DECLARE v_cost DECIMAL(10,2);
    DECLARE v_minutes INT;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT id
    INTO v_lock
    FROM parking_operation_lock
    WHERE id = 1
    FOR UPDATE;

    SELECT
        space_id,
        reservation_id,
        entry_time,
        exit_time,
        hourly_rate
    INTO
        v_space,
        v_res,
        v_entry,
        v_exit,
        v_rate
    FROM parking_sessions
    WHERE id = p_session
    FOR UPDATE;

    IF v_space IS NULL
       OR v_exit IS NOT NULL THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
            'Session is missing or already closed';
    END IF;


    -----------------------JEMI (CLACULTE COST)

    SET v_minutes =
        GREATEST(
            0,
            TIMESTAMPDIFF(MINUTE, v_entry, NOW())
        );

    SET v_cost =
        fn_parking_price(
            v_entry,
            NOW(),
            v_rate
        );

    ----------------------------------


    SELECT TIMESTAMP(reservation_date, end_time)
    INTO v_end
    FROM reservations
    WHERE id = v_res;

    UPDATE parking_sessions
    SET
        exit_time = NOW(),
        duration_minutes = v_minutes,
        total_cost = v_cost,
        updated_at = NOW()
    WHERE id = p_session;


    ------------------------JEMI(FREE SPACE)-------

    UPDATE parking_spaces
    SET
        status = 'Available',
        updated_at = NOW()
    WHERE id = v_space;

    --------------------------------------------


    UPDATE reservations
    SET
        status = 'Completed',
        updated_at = NOW()
    WHERE id = v_res;


    ------------------------JEMI(FINE)-------

    IF v_end < NOW() THEN

        INSERT INTO finds (
            session_id,
            reservation_id,
            amount,
            reason,
            issue_date,
            status,
            created_at,
            updated_at
        )
        VALUES (
            p_session,
            v_res,
            fn_parking_price(v_end, NOW(), v_rate),
            'Overstay beyond booked end time',
            NOW(),
            'Pending',
            NOW(),
            NOW()
        );

    END IF;

    --------------------------------


    COMMIT;

    SELECT
        v_cost AS total_cost,
        v_minutes AS duration_minutes;
END$$
-------------------------------------------------------------------


-----------------------JEMI (PAY FINE)-----------------------------
DROP PROCEDURE IF EXISTS sp_pay_fine$$
CREATE PROCEDURE sp_pay_fine(IN p_id BIGINT,IN p_method VARCHAR(50))
BEGIN
  DECLARE v_lock INT;
  DECLARE v_amount DECIMAL(10,2);
  DECLARE v_res BIGINT;
  DECLARE v_session BIGINT;
  DECLARE v_status VARCHAR(20);
  DECLARE v_payment BIGINT;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION 
  BEGIN 
    ROLLBACK; 
    RESIGNAL; 
    END;
  
 START TRANSACTION;
 SELECT id INTO v_lock 
 FROM parking_operation_lock
  WHERE id=1 
  FOR UPDATE;
 SELECT amount,reservation_id,session_id,status INTO v_amount,v_res,v_session,v_status 
 FROM finds 
 WHERE id=p_id 
 FOR UPDATE;

 IF v_status IS NULL OR v_status<>'Pending' THEN 
  SIGNAL SQLSTATE '45000' 
  SET MESSAGE_TEXT='Fine is missing or already paid'; 
 END IF;
 INSERT INTO payments(reservation_id,session_id,amount,payment_date,method,status,transaction_id,created_at,updated_at)
 VALUES(v_res,v_session,v_amount,NOW(),p_method,'Completed',CONCAT('FINE-DEMO-',UUID()),NOW(),NOW());
 SET v_payment=LAST_INSERT_ID();

 UPDATE finds 
 SET status='Paid',payment_id=v_payment,updated_at=NOW() WHERE id=p_id;
 COMMIT;
 SELECT * FROM payments 
 WHERE id=v_payment;
END$$



DROP PROCEDURE IF EXISTS sp_create_lot$$
CREATE PROCEDURE sp_create_lot(IN p_name VARCHAR(100),IN p_location VARCHAR(200),
 IN p_count INT,IN p_rate DECIMAL(10,2),IN p_type VARCHAR(50),IN p_features TEXT)
BEGIN
    DECLARE v_id BIGINT;
    DECLARE v_i INT DEFAULT 1;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION 
    BEGIN 
      ROLLBACK; 
      RESIGNAL; 
      END;
    
 IF p_count<1 OR p_count>1000 OR p_rate<0 THEN 
    SIGNAL SQLSTATE '45000' 
    SET MESSAGE_TEXT='Invalid capacity or rate'; 
 END IF;

 START TRANSACTION;
 INSERT INTO parking_lots(name,location,total_spaces,available_spaces,hourly_rate,type,features,created_at,updated_at)
 
 VALUES(p_name,p_location,p_count,p_count,p_rate,p_type,p_features,NOW(),NOW());
 SET v_id=LAST_INSERT_ID();
 WHILE v_i<=p_count DO
    INSERT INTO parking_spaces(parking_lot_id,space_number,status,type,created_at,updated_at)
    VALUES(v_id,CONCAT('A',LPAD(v_i,4,'0')),'Available',p_type,NOW(),NOW());
    SET v_i=v_i+1;
 END WHILE;

 COMMIT;
 SELECT * FROM parking_lots WHERE id=v_id;
END$$
-------------------------------------------------------------------------------------------


--------------------------------MAIMOONA--------------------------------
DROP PROCEDURE IF EXISTS sp_expire_reservations$$

CREATE PROCEDURE sp_expire_reservations()
BEGIN
    DECLARE v_done INT DEFAULT 0;
    DECLARE v_id BIGINT;
    DECLARE v_lock INT;
    DECLARE v_count INT DEFAULT 0;

    DECLARE expired CURSOR FOR
        SELECT id
        FROM reservations
        WHERE status IN ('Pending', 'Confirmed')
          AND TIMESTAMP(reservation_date, end_time) < NOW();

    DECLARE CONTINUE HANDLER FOR NOT FOUND
        SET v_done = 1;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;

    SELECT id
    INTO v_lock
    FROM parking_operation_lock
    WHERE id = 1
    FOR UPDATE;

    OPEN expired;

    expiry_loop: LOOP

        FETCH expired
        INTO v_id;

        IF v_done = 1 THEN
            LEAVE expiry_loop;
        END IF;

        UPDATE reservations
        SET
            status = 'Expired',
            updated_at = NOW()
        WHERE id = v_id;

        SET v_count = v_count + 1;

    END LOOP;

    CLOSE expired;

    COMMIT;

    SELECT v_count AS expired_count;
END$$
--------------------------------------------------------
DELIMITER ;