CREATE DATABASE IF NOT EXISTS sena_personal
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sena_personal;

CREATE TABLE IF NOT EXISTS instructors (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NULL,
    specialty VARCHAR(150) NULL,
    document_number VARCHAR(50) NULL,
    phone VARCHAR(40) NULL,
    personal_email VARCHAR(190) NULL,
    affiliation_type VARCHAR(100) NULL,
    job_title VARCHAR(150) NULL,
    role_name VARCHAR(50) NOT NULL DEFAULT 'Instructor',
    supervisor VARCHAR(150) NULL,
    site_name VARCHAR(150) NULL,
    site_address TEXT NULL,
    site_id VARCHAR(50) NULL,
    regional VARCHAR(100) NULL,
    contract_number VARCHAR(80) NULL,
    contract_end_date DATE NULL,
    contract_end_date_raw VARCHAR(50) NULL,
    vpn_access VARCHAR(100) NULL,
    extension_ip VARCHAR(100) NULL,
    source_row SMALLINT UNSIGNED NULL,
    duplicate_in_source BOOLEAN NOT NULL DEFAULT FALSE,
    duplicate_with_other_category BOOLEAN NOT NULL DEFAULT FALSE,
    invalid_contract_date BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_instructors_email (email),
    KEY idx_instructors_document (document_number),
    UNIQUE KEY uq_instructors_source_row (source_row)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS administrative_staff (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NULL,
    specialty VARCHAR(150) NULL,
    document_number VARCHAR(50) NULL,
    phone VARCHAR(40) NULL,
    personal_email VARCHAR(190) NULL,
    affiliation_type VARCHAR(100) NULL,
    job_title VARCHAR(150) NULL,
    role_name VARCHAR(50) NOT NULL DEFAULT 'Administrativo',
    supervisor VARCHAR(150) NULL,
    site_name VARCHAR(150) NULL,
    site_address TEXT NULL,
    site_id VARCHAR(50) NULL,
    regional VARCHAR(100) NULL,
    contract_number VARCHAR(80) NULL,
    contract_end_date DATE NULL,
    contract_end_date_raw VARCHAR(50) NULL,
    vpn_access VARCHAR(100) NULL,
    extension_ip VARCHAR(100) NULL,
    source_row SMALLINT UNSIGNED NULL,
    duplicate_in_source BOOLEAN NOT NULL DEFAULT FALSE,
    duplicate_with_other_category BOOLEAN NOT NULL DEFAULT FALSE,
    invalid_contract_date BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_administrative_email (email),
    KEY idx_administrative_document (document_number),
    UNIQUE KEY uq_administrative_source_row (source_row)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'talento_humano', 'instructor') NOT NULL DEFAULT 'admin',
    instructor_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_instructor (instructor_id),
    CONSTRAINT fk_users_instructor
        FOREIGN KEY (instructor_id) REFERENCES instructors (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contracts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    instructor_id BIGINT UNSIGNED NOT NULL,
    contract_number VARCHAR(50) NOT NULL,
    contract_type VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    monthly_value DECIMAL(12, 2) NOT NULL,
    status ENUM('Activo', 'Suspendido', 'Finalizado') NOT NULL DEFAULT 'Activo',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_contracts_number (contract_number),
    KEY idx_contracts_status_end_date (status, end_date),
    CONSTRAINT fk_contracts_instructor
        FOREIGN KEY (instructor_id) REFERENCES instructors (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;
