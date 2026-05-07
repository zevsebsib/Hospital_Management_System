CREATE DATABASE IF NOT EXISTS medicore_hms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE medicore_hms;

CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    firstname  VARCHAR(100) NOT NULL,
    lastname   VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','doctor') NOT NULL DEFAULT 'doctor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE doctors (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL,
    specialization VARCHAR(150),
    schedule       VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE patients (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    firstname  VARCHAR(100) NOT NULL,
    lastname   VARCHAR(100) NOT NULL,
    birthdate  DATE,
    sex        ENUM('Male','Female','Other'),
    address    TEXT,
    contact    VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE appointments (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    patient_id       INT NOT NULL,
    doctor_id        INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status           ENUM('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id)  REFERENCES doctors(id)  ON DELETE CASCADE
);

CREATE TABLE consultations (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id    INT NOT NULL,
    diagnosis         TEXT NOT NULL,
    notes             TEXT,
    consultation_date DATE NOT NULL,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
);

CREATE TABLE prescriptions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    medicine_name   VARCHAR(200) NOT NULL,
    dosage          VARCHAR(100),
    frequency       VARCHAR(100),
    duration        VARCHAR(100),
    instructions    TEXT,
    FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE
);

-- Default accounts (admin123 / doctor123)
INSERT INTO users (firstname, lastname, email, password, role) VALUES
('System', 'Admin',  'admin@hms.com',     '$2y$10$irF/DezpxrFtJqibe7sRK.rzLD.5PRi5dZ8uGcpOjo.qoMkG6Ip9e', 'admin'),
('Maria',  'Santos', 'dr.santos@hms.com', '$2y$10$yCingyw1JxzrANJdvm8uT.iZnQoMvvWHd8l1WEmtBmMZWg65eS6Pq', 'doctor'),
('Carlos', 'Reyes',  'dr.reyes@hms.com',  '$2y$10$yCingyw1JxzrANJdvm8uT.iZnQoMvvWHd8l1WEmtBmMZWg65eS6Pq', 'doctor');

INSERT INTO doctors (user_id, specialization, schedule) VALUES
(2, 'Cardiologist', 'Mon–Fri, 8:00 AM – 5:00 PM'),
(3, 'Neurologist',  'Tue–Sat, 9:00 AM – 6:00 PM');

INSERT INTO patients (firstname, lastname, birthdate, sex, address, contact) VALUES
('Jose',   'Dela Cruz', '1985-03-14', 'Male',   '123 Rizal St, Manila',            '09171234567'),
('Luisa',  'Santos',    '1992-07-22', 'Female', '456 Mabini Ave, Cebu City',       '09281234567'),
('Miguel', 'Torres',    '1978-11-05', 'Male',   '789 Quezon Blvd, Quezon City',    '09391234567');
