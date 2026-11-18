USE sena_personal;

ALTER TABLE administrative_staff
    DROP COLUMN site_name,
    DROP COLUMN site_address,
    DROP COLUMN site_id,
    DROP COLUMN regional;

ALTER TABLE instructors
    DROP COLUMN site_name,
    DROP COLUMN site_address,
    DROP COLUMN site_id,
    DROP COLUMN regional;
