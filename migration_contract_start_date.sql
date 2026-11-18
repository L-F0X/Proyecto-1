USE sena_personal;

ALTER TABLE instructors
    ADD COLUMN contract_start_date DATE NULL AFTER contract_number;

ALTER TABLE administrative_staff
    ADD COLUMN contract_start_date DATE NULL AFTER contract_number;
