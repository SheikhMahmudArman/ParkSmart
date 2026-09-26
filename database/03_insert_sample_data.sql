-- Optional sample parking lot. Run after tables, views, procedures and triggers.
-- Users are created through the website and the parksmart:admin command.
------------jemi ----------------
INSERT INTO parking_lots(name,location,description,contact,hourly_rate)
VALUES(
    'AUST Demo Parking',
    'Tejgaon, Dhaka',
    'Sample parking lot for classroom testing.',
    'Demo desk',
    50.00
);
SET @demo_lot=LAST_INSERT_ID();
CALL sp_create_spaces(@demo_lot,10,'A-');
