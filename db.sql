-- 
CREATE DATABASE IF NOT EXISTS hospital_management_system;
USE hospital_management_system;

------------------
CREATE TABLE `user` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL, -- Plaintext password for demo purposes
  `role` ENUM('Administrator','Doctor','Nurse','Receptionist','Patient','Cashier') NOT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: department
-- Description: Represents hospital departments
-- --------------------------------------------------------
CREATE TABLE `department` (
  `department_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(200) DEFAULT NULL,
  `head_doctor_id` INT(11) DEFAULT NULL,
  PRIMARY KEY (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: doctor
-- Description: Stores doctor details
-- --------------------------------------------------------
CREATE TABLE `doctor` (
  `doctor_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `specialization` VARCHAR(100) DEFAULT NULL,
  `contact_info` VARCHAR(200) DEFAULT NULL,
  `user_id` INT(11) NOT NULL,
  `department_id` INT(11) DEFAULT NULL,
  PRIMARY KEY (`doctor_id`),
  FOREIGN KEY (`department_id`) REFERENCES `department`(`department_id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: nurse
-- Description: Stores nurse details
-- --------------------------------------------------------
CREATE TABLE `nurse` (
  `nurse_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `department_id` INT(11) DEFAULT NULL,
  `contact_info` VARCHAR(200) DEFAULT NULL,
  `user_id` INT(11) NOT NULL,
  PRIMARY KEY (`nurse_id`),
  FOREIGN KEY (`department_id`) REFERENCES `department`(`department_id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: resource
-- Description: Represents hospital resources like wards, rooms, beds, etc.
-- --------------------------------------------------------
CREATE TABLE `resource` (
  `resource_id` INT(11) NOT NULL AUTO_INCREMENT,
  `type` ENUM('WARD','ROOM','BED','EQUIPMENT') NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `department_id` INT(11) DEFAULT NULL,
  `status` ENUM('AVAILABLE','OCCUPIED','MAINTENANCE') DEFAULT 'AVAILABLE',
  `details` VARCHAR(200) DEFAULT NULL,
  PRIMARY KEY (`resource_id`),
  FOREIGN KEY (`department_id`) REFERENCES `department`(`department_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: medicine
-- Description: Stores medicine details and stock information
-- --------------------------------------------------------
CREATE TABLE `medicine` (
  `medicine_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` VARCHAR(200) DEFAULT NULL,
  `dosage_form` VARCHAR(50) DEFAULT NULL,
  `stock_quantity` INT(11) DEFAULT 0,
  `category` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`medicine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: work_schedule
-- Description: Stores employee work schedules
-- --------------------------------------------------------
CREATE TABLE `work_schedule` (
  `schedule_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `day_of_week` ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  PRIMARY KEY (`schedule_id`),
  FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: patient
-- Description: Stores patient information
-- --------------------------------------------------------
CREATE TABLE `patient` (
  `patient_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `date_of_birth` DATE NOT NULL,
  `gender` ENUM('Male','Female','Other') NOT NULL,
  `blood_group` VARCHAR(5),
  `contact_info` VARCHAR(200) NOT NULL,
  `address` TEXT,
  `emergency_contact` VARCHAR(200),
  PRIMARY KEY (`patient_id`),
  FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: appointment
-- Description: Manages patient appointments
-- --------------------------------------------------------
CREATE TABLE `appointment` (
  `appointment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `patient_id` INT(11) NOT NULL,
  `doctor_id` INT(11) NOT NULL,
  `department_id` INT(11) NOT NULL,
  `appointment_date` DATETIME NOT NULL,
  `reason` TEXT,
  `status` ENUM('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
  PRIMARY KEY (`appointment_id`),
  FOREIGN KEY (`patient_id`) REFERENCES `patient`(`patient_id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctor`(`doctor_id`) ON DELETE CASCADE,
  FOREIGN KEY (`department_id`) REFERENCES `department`(`department_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: medical_record
-- Description: Stores medical records of patients
-- --------------------------------------------------------
CREATE TABLE `medical_record` (
  `record_id` INT(11) NOT NULL AUTO_INCREMENT,
  `patient_id` INT(11) NOT NULL,
  `doctor_id` INT(11) NOT NULL,
  `visit_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `diagnosis` TEXT,
  `treatment` TEXT,
  `prescription` TEXT,
  `notes` TEXT,
  PRIMARY KEY (`record_id`),
  FOREIGN KEY (`patient_id`) REFERENCES `patient`(`patient_id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctor`(`doctor_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: bill
-- Description: Stores billing information for patients
-- --------------------------------------------------------
CREATE TABLE `bill` (
  `bill_id` INT(11) NOT NULL AUTO_INCREMENT,
  `patient_id` INT(11) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `bill_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `description` TEXT,
  `status` ENUM('Unpaid','Paid','Partial') DEFAULT 'Unpaid',
  PRIMARY KEY (`bill_id`),
  FOREIGN KEY (`patient_id`) REFERENCES `patient`(`patient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table: payment
-- Description: Stores payment transactions (for bills and employee salaries)
-- --------------------------------------------------------
CREATE TABLE `payment` (
  `payment_id` INT(11) NOT NULL AUTO_INCREMENT,
  `bill_id` INT(11) DEFAULT NULL,
  `user_id` INT(11) DEFAULT NULL, -- For employee payments
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `payment_type` ENUM('Bill Payment','Salary','Bonus') NOT NULL,
  `description` TEXT,
  PRIMARY KEY (`payment_id`),
  FOREIGN KEY (`bill_id`) REFERENCES `bill`(`bill_id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================================
-- 3. Insert Sample Data
-- ========================================================

-- --------------------------------------------------------
-- Insert Users
-- --------------------------------------------------------
-- Administrator
INSERT INTO `user` (`username`, `password`, `role`, `last_login`) VALUES
('admin123', 'adminpass123', 'Administrator', '2024-11-12 18:09:52');

-- Nurses
INSERT INTO `user` (`username`, `password`, `role`, `last_login`) VALUES
('nurse_rahman', 'nursepass1', 'Nurse', '2024-11-12 17:24:46'),
('nurse_akter', 'nursepass2', 'Nurse', '2024-11-12 17:24:46');

-- Cashier
INSERT INTO `user` (`username`, `password`, `role`, `last_login`) VALUES
('saima', 'cashierpass', 'Cashier', '2024-11-12 17:43:45');

-- Doctors
INSERT INTO `user` (`username`, `password`, `role`, `last_login`) VALUES
('dr.mitul', 'docpass1', 'Doctor', '2024-11-12 18:10:12'),
('dr.khaled', 'docpass2', 'Doctor', '2024-11-12 17:59:31');

-- Patient Users (Added to resolve foreign key constraint)
INSERT INTO `user` (`username`, `password`, `role`, `last_login`) VALUES
('rahul.islam', 'patientpass1', 'Patient', '2024-12-15 10:30:00'),
('sabina.akter', 'patientpass2', 'Patient', '2024-12-16 15:00:00');

-- --------------------------------------------------------
-- Insert Departments
-- --------------------------------------------------------
INSERT INTO `department` (`name`, `description`) VALUES
('Cardiology', 'Department of Cardiology'),
('Orthopedics', 'Department of Orthopedics');

-- --------------------------------------------------------
-- Insert Doctors
-- --------------------------------------------------------
INSERT INTO `doctor` (`name`, `specialization`, `contact_info`, `user_id`, `department_id`) VALUES
('Dr. Mitul Rahman', 'Intern Doctor', 'mitul.rahman@hospitalbd.com, +8801712345678', 4, 1),
('Dr. Khaled Hasan', 'Cardiac Specialist', '+8801812345678', 5, 1);

-- --------------------------------------------------------
-- Update Departments with Head Doctors
-- --------------------------------------------------------
UPDATE `department` 
SET `head_doctor_id` = 1 
WHERE `department_id` = 1;

UPDATE `department` 
SET `head_doctor_id` = 2 
WHERE `department_id` = 2;

-- --------------------------------------------------------
-- Insert Medicines
-- --------------------------------------------------------
INSERT INTO `medicine` (`name`, `description`, `dosage_form`, `stock_quantity`, `category`) VALUES
('Paracetamol', 'Pain reliever and fever reducer', 'Tablet', 500, 'Painkillers'),
('Amoxicillin', 'Antibiotic for bacterial infections', 'Capsule', 300, 'Antibiotics'),
('Cough Syrup', 'Relieves cough', 'Syrup', 200, 'Cough Remedies');

-- --------------------------------------------------------
-- Insert Work Schedules
-- --------------------------------------------------------
INSERT INTO `work_schedule` (`user_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(4, 'Monday', '08:00:00', '16:00:00'),
(4, 'Tuesday', '08:00:00', '16:00:00'),
(5, 'Monday', '09:00:00', '17:00:00'),
(5, 'Tuesday', '09:00:00', '17:00:00'),
(2, 'Monday', '07:00:00', '15:00:00'),
(3, 'Tuesday', '07:00:00', '15:00:00');

-- --------------------------------------------------------
-- Insert Nurses
-- --------------------------------------------------------
INSERT INTO `nurse` (`name`, `department_id`, `contact_info`, `user_id`) VALUES
('Nurse Rahman', 1, 'rahman.nurse@hospitalbd.com, +8801711122233', 2),
('Nurse Akter', 2, 'akter.nurse@hospitalbd.com, +8801713344455', 3);

-- --------------------------------------------------------
-- Insert Resources
-- --------------------------------------------------------
INSERT INTO `resource` (`type`, `name`, `department_id`, `status`, `details`) VALUES
('WARD', 'Ward A', 1, 'AVAILABLE', 'General ward with 30 beds'),
('ROOM', 'Operation Theater 1', 1, 'MAINTENANCE', 'Equipped with advanced surgical tools'),
('BED', 'Bed 201', 1, 'OCCUPIED', 'Occupied by Patient ID 1'),
('EQUIPMENT', 'ECG Machine', 1, 'AVAILABLE', 'For cardiac monitoring');

-- --------------------------------------------------------
-- Insert Patients
-- --------------------------------------------------------
INSERT INTO `patient` (`user_id`, `name`, `date_of_birth`, `gender`, `blood_group`, `contact_info`, `address`, `emergency_contact`) VALUES
(7, 'Rahul Islam', '1985-03-10', 'Male', 'A+', 'rahul.islam@example.com, +8801711223344', '1234 Gulshan Avenue, Dhaka', 'Ayesha Islam: +8801911223344'),
(8, 'Sabina Akter', '1992-07-25', 'Female', 'B-', 'sabina.akter@example.com, +8801711334455', '5678 Banani Road, Dhaka', 'Mahir Akter: +8801911334455');

-- --------------------------------------------------------
-- Insert Appointments
-- --------------------------------------------------------
INSERT INTO `appointment` (`patient_id`, `doctor_id`, `department_id`, `appointment_date`, `reason`, `status`) VALUES
(1, 1, 1, '2024-12-15 10:00:00', 'Routine Check-up', 'Scheduled'),
(2, 2, 1, '2024-12-16 14:30:00', 'Heart Consultation', 'Scheduled');

-- --------------------------------------------------------
-- Insert Medical Records
-- --------------------------------------------------------
INSERT INTO `medical_record` (`patient_id`, `doctor_id`, `visit_date`, `diagnosis`, `treatment`, `prescription`, `notes`) VALUES
(1, 1, '2024-12-15 10:30:00', 'Hypertension', 'Dietary modifications and exercise', 'Aspirin 100mg once daily', 'Patient advised to reduce salt intake and monitor blood pressure regularly'),
(2, 2, '2024-12-16 15:00:00', 'Arrhythmia', 'Medication and regular monitoring', 'Beta-blockers 50mg twice daily', 'Scheduled for follow-up in one month');

-- --------------------------------------------------------
-- Insert Bills
-- --------------------------------------------------------
INSERT INTO `bill` (`patient_id`, `amount`, `bill_date`, `description`, `status`) VALUES
(1, 1500.00, '2024-12-15 11:00:00', 'Consultation and medication', 'Unpaid'),
(2, 3000.00, '2024-12-16 16:00:00', 'Heart consultation and tests', 'Unpaid');

-- --------------------------------------------------------
-- Insert Payments
-- --------------------------------------------------------
INSERT INTO `payment` (`bill_id`, `user_id`, `amount`, `payment_date`, `payment_type`, `description`) VALUES
(1, NULL, 1500.00, '2024-12-17 09:00:00', 'Bill Payment', 'Full payment for consultation and medication'),
(NULL, 1, 5000.00, '2024-12-31 17:00:00', 'Salary', 'Monthly salary for Administrator'),
(NULL, 4, 3000.00, '2024-12-31 17:00:00', 'Salary', 'Monthly salary for Doctor'),
(NULL, 4, 500.00, '2024-12-31 17:00:00', 'Bonus', 'Performance bonus for excellent service');
