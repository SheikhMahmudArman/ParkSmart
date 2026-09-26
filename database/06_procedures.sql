-- DELIMITER is used by phpMyAdmin/MySQL CLI; parksmart:install also understands it.
DELIMITER $$
------------jemi ----------------
CREATE PROCEDURE sp_create_spaces(IN p_lot BIGINT UNSIGNED, IN p_count INT, IN p_prefix VARCHAR(15))
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE v_lot BIGINT UNSIGNED DEFAULT NULL;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    IF p_count < 1 OR p_count > 200 OR p_prefix IS NULL OR CHAR_LENGTH(p_prefix)=0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT='Use 1 to 200 spaces and a non-empty prefix.';
    END IF;
    START TRANSACTION;

    SELECT id INTO v_lot
    FROM parking_lots 
    WHERE id=p_lot 
    FOR UPDATE;
    IF v_lot IS NULL THEN
        SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT='Parking lot not found.';
    END IF;

    WHILE i <= p_count DO
        INSERT INTO parking_spaces(parking_lot_id,space_number,type,status)
        VALUES(p_lot,CONCAT(p_prefix,LPAD(i,3,'0')),'Standard','Available');
        SET i=i+1;
    END WHILE;
 COMMIT;
 
END$$
-----------------------------------

CREATE PROCEDURE sp_expire_reservations()
BEGIN
 UPDATE reservations SET status='Expired'
 WHERE status IN ('Pending','Confirmed') AND end_at<=NOW();
END$$
DELIMITER ;
