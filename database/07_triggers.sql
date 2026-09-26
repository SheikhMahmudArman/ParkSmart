------------jemi ----------------
CREATE TRIGGER trg_reservation_created 
AFTER INSERT ON reservations
FOR EACH ROW
BEGIN
    INSERT INTO notifications(user_id,reservation_id,message)
    VALUES(NEW.user_id,NEW.id,CONCAT('Booking #',NEW.id,' is Pending. Staff will review it.'));
END$$ 

CREATE TRIGGER trg_reservation_changed
AFTER UPDATE ON reservations
FOR EACH ROW
BEGIN
 IF OLD.status <> NEW.status THEN
    INSERT INTO reservation_audit(reservation_id,old_status,new_status)
    VALUES(NEW.id,OLD.status,NEW.status);
    INSERT INTO notifications(user_id,reservation_id,message)
    VALUES(NEW.user_id,NEW.id,CONCAT('Booking #',NEW.id,' is now ',NEW.status,
      IF(NEW.status='Completed','. Your demo payment is due.','.')));
 END IF;
 IF OLD.start_at <> NEW.start_at OR OLD.end_at <> NEW.end_at THEN
    INSERT INTO notifications(user_id,reservation_id,message)
    VALUES(NEW.user_id,NEW.id,CONCAT('Booking #',NEW.id,' moved to ',NEW.start_at,' - ',NEW.end_at,'. Staff approval is required.'));
 END IF;
 IF OLD.payment_status <> NEW.payment_status THEN
    INSERT INTO notifications(user_id,reservation_id,message)
    VALUES(NEW.user_id,NEW.id,CONCAT('Demo payment recorded for booking #',NEW.id,'.'));
 END IF;
END$$


CREATE TRIGGER trg_payment_validate 
BEFORE INSERT ON payments
FOR EACH ROW
BEGIN
  DECLARE v_amount DECIMAL(10,2);
  DECLARE v_status VARCHAR(20);

  SELECT total_amount,status INTO v_amount,v_status 
  FROM reservations 
  WHERE id=NEW.reservation_id;
 IF v_status <> 'Completed' OR NEW.amount <> v_amount THEN
    SIGNAL SQLSTATE '45000' 
    SET MESSAGE_TEXT='Only the full server-calculated amount of a completed booking can be paid.';
 END IF;
END$$
DELIMITER ;
