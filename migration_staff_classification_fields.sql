USE sena_personal;

ALTER TABLE administrative_staff
    CHANGE COLUMN role_name dependency VARCHAR(50) NOT NULL DEFAULT '';

ALTER TABLE instructors
    CHANGE COLUMN role_name area VARCHAR(50) NOT NULL DEFAULT '';

UPDATE instructors
SET supervisor = NULL;
