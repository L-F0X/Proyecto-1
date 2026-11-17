USE sena_personal;

ALTER TABLE users
    MODIFY role ENUM('admin', 'talento_humano', 'instructor') NOT NULL DEFAULT 'admin',
    ADD COLUMN instructor_id BIGINT UNSIGNED NULL AFTER role,
    ADD UNIQUE KEY uq_users_instructor (instructor_id),
    ADD CONSTRAINT fk_users_instructor
        FOREIGN KEY (instructor_id) REFERENCES instructors (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT;
