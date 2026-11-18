USE sena_personal;

START TRANSACTION;

UPDATE administrative_staff
SET contract_number = TRIM(LEADING ' :#-' FROM SUBSTRING(TRIM(contract_number), 19))
WHERE LOWER(LEFT(TRIM(contract_number), 18)) IN ('número de contrato', 'numero de contrato');

UPDATE instructors
SET contract_number = TRIM(LEADING ' :#-' FROM SUBSTRING(TRIM(contract_number), 19))
WHERE LOWER(LEFT(TRIM(contract_number), 18)) IN ('número de contrato', 'numero de contrato');

UPDATE administrative_staff
SET contract_number = TRIM(TRIM(LEADING ':' FROM TRIM(contract_number)))
WHERE LEFT(TRIM(contract_number), 1) = ':';

UPDATE instructors
SET contract_number = TRIM(TRIM(LEADING ':' FROM TRIM(contract_number)))
WHERE LEFT(TRIM(contract_number), 1) = ':';

UPDATE administrative_staff
SET job_title = role_name,
    specialty = role_name,
    role_name = ''
WHERE LOWER(TRIM(job_title)) = 'contratista'
  AND TRIM(role_name) <> '';

UPDATE instructors
SET job_title = role_name,
    specialty = role_name,
    role_name = ''
WHERE LOWER(TRIM(job_title)) = 'contratista'
  AND TRIM(role_name) <> '';

COMMIT;
