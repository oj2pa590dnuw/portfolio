-- RHSYS Database Backup
-- Generated: 2025-12-08 05:48:16
-- Database: system2

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `activity_logs` VALUES ('1', '1', 'LOGIN', 'User logged in successfully', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 22:09:03'),
('2', '1', 'PATIENT_ADDED', 'Added new patient', 'patients', '0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 22:12:22'),
('3', '1', 'LOGOUT', 'User logged out successfully', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 22:24:07'),
('4', '1', 'LOGIN', 'User logged in successfully', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 22:24:27'),
('5', '1', 'USER_APPROVED', 'Approved user account', 'users', '2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 22:24:38'),
('6', '1', 'LOGOUT', 'User logged out successfully', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-30 22:24:43'),
('7', '1', 'LOGIN', 'User logged in successfully', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 10:59:33'),
('8', '1', 'LOGIN', 'User logged in successfully', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 17:51:38'),
('9', '1', 'LOGOUT', 'User logged out successfully', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 17:51:43'),
('10', '3', 'LOGIN', 'User logged in successfully', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 18:19:56'),
('11', '3', 'LOGIN', 'User logged in successfully', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 08:56:15'),
('12', '3', 'LOGIN', 'User logged in successfully', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 16:23:05'),
('13', '3', 'LOGIN', 'User logged in successfully', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-08 10:13:00');

DROP TABLE IF EXISTS `backup_logs`;
CREATE TABLE `backup_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `backup_type` enum('full','partial') DEFAULT 'full',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

DROP TABLE IF EXISTS `dispensed_medication`;
CREATE TABLE `dispensed_medication` (
  `dispense_id` int(11) NOT NULL AUTO_INCREMENT,
  `visit_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `quantity_dispensed` int(11) NOT NULL,
  `dispensed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `dispensed_by_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`dispense_id`),
  KEY `fk_dispense_item` (`item_id`),
  KEY `fk_dispense_batch` (`batch_id`),
  KEY `fk_dispense_user` (`dispensed_by_user_id`),
  KEY `fk_dispense_visit` (`visit_id`),
  CONSTRAINT `fk_dispense_visit` FOREIGN KEY (`visit_id`) REFERENCES `patient_visits` (`visit_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `dosage_forms`;
CREATE TABLE `dosage_forms` (
  `dosage_form_id` int(11) NOT NULL AUTO_INCREMENT,
  `form_name` varchar(50) NOT NULL,
  PRIMARY KEY (`dosage_form_id`),
  UNIQUE KEY `form_name` (`form_name`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `dosage_forms` VALUES ('2', 'Capsule'),
('6', 'Cream'),
('7', 'Drops'),
('8', 'Inhaler'),
('4', 'Injection'),
('13', 'Lotion'),
('15', 'Lozenge'),
('5', 'Ointment'),
('12', 'Patch'),
('11', 'Powder'),
('14', 'Spray'),
('9', 'Suppository'),
('10', 'Suspension'),
('3', 'Syrup'),
('1', 'Tablet');

DROP TABLE IF EXISTS `family_medical_history`;
CREATE TABLE `family_medical_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` varchar(36) NOT NULL,
  `condition_name` varchar(255) NOT NULL,
  `relationship_type` enum('Parent','Child','Sibling','Grandparent','Other') NOT NULL,
  `notes` text DEFAULT NULL,
  `diagnosed_age` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`history_id`),
  KEY `patient_id` (`patient_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

DROP TABLE IF EXISTS `inventory`;
CREATE TABLE `inventory` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `dosage_form_id` int(11) DEFAULT NULL,
  `unit_of_issue` varchar(20) DEFAULT 'pc',
  `reorder_point` int(11) NOT NULL DEFAULT 20,
  `last_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`item_id`),
  KEY `fk_category` (`category_id`),
  KEY `fk_dosage_form` (`dosage_form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `inventory_batches`;
CREATE TABLE `inventory_batches` (
  `batch_id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `quantity_in_batch` int(11) NOT NULL,
  `current_stock` int(11) NOT NULL,
  `expiration_date` date NOT NULL,
  `date_restocked` datetime NOT NULL DEFAULT current_timestamp(),
  `restocked_by_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`batch_id`),
  KEY `fk_batch_item` (`item_id`),
  KEY `fk_batch_restocker` (`restocked_by_user_id`),
  CONSTRAINT `fk_batch_restocker` FOREIGN KEY (`restocked_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `item_categories`;
CREATE TABLE `item_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `item_categories` VALUES ('1', 'Analgesics'),
('6', 'Antacids & GI'),
('2', 'Antibiotics'),
('5', 'Antidepressants'),
('4', 'Antidiabetics'),
('12', 'Antifungals'),
('7', 'Antihistamines'),
('3', 'Antihypertensives'),
('13', 'Antivirals'),
('10', 'Cardiovascular'),
('15', 'Dermatological'),
('11', 'Hormones'),
('14', 'Muscle Relaxants'),
('9', 'Respiratory'),
('8', 'Vitamins & Supplements');

DROP TABLE IF EXISTS `patient_relationships`;
CREATE TABLE `patient_relationships` (
  `relationship_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` varchar(36) NOT NULL,
  `related_patient_id` varchar(36) NOT NULL,
  `relationship_type` enum('Parent','Child','Spouse','Sibling','Grandparent','Grandchild','Other') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_reciprocal_created` tinyint(1) DEFAULT 0,
  `original_relationship_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`relationship_id`),
  UNIQUE KEY `unique_relationship` (`patient_id`,`related_patient_id`,`relationship_type`),
  KEY `related_patient_id` (`related_patient_id`),
  KEY `idx_reciprocal` (`original_relationship_id`,`is_reciprocal_created`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

DROP TABLE IF EXISTS `patient_visits`;
CREATE TABLE `patient_visits` (
  `visit_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` varchar(36) NOT NULL,
  `visit_date` date NOT NULL,
  `chief_complaint` varchar(255) DEFAULT NULL,
  `blood_pressure` varchar(50) DEFAULT NULL,
  `heart_rate` varchar(50) DEFAULT NULL,
  `temperature` varchar(50) DEFAULT NULL,
  `clinical_notes` text DEFAULT NULL,
  `procedures_done` text DEFAULT NULL,
  `attended_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`visit_id`),
  KEY `fk_visit_attended_by` (`attended_by_user_id`),
  KEY `fk_visit_patient` (`patient_id`),
  KEY `idx_visit_date` (`visit_date`),
  CONSTRAINT `fk_visit_attended_by` FOREIGN KEY (`attended_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_visit_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `patient_visits` VALUES ('1', 'cc9f9189-453c-8352-c6bd-ca170f25dd50', '2025-12-08', 's', '1', '1', '1', '1', '1', NULL, '2025-12-08 12:36:48');

DROP TABLE IF EXISTS `patients`;
CREATE TABLE `patients` (
  `id` varchar(36) NOT NULL,
  `patient_code` varchar(50) NOT NULL,
  `fullName` varchar(255) NOT NULL,
  `age` int(3) NOT NULL,
  `birthDate` date DEFAULT NULL,
  `philhealth_id` varchar(20) DEFAULT NULL,
  `local_patient_id` varchar(50) DEFAULT NULL,
  `contactNumber` varchar(20) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `lastCheckup` date DEFAULT NULL,
  `warning` varchar(255) DEFAULT NULL,
  `bloodPressure` varchar(20) DEFAULT NULL,
  `heartRate` varchar(20) DEFAULT NULL,
  `respiratoryRate` varchar(20) DEFAULT NULL,
  `temperature` varchar(20) DEFAULT NULL,
  `clinicalNotes` text DEFAULT NULL,
  `isCritical` tinyint(1) NOT NULL DEFAULT 0,
  `isPregnant` tinyint(1) NOT NULL DEFAULT 0,
  `isElderly` tinyint(1) NOT NULL DEFAULT 0,
  `isWarningFlag` tinyint(1) NOT NULL DEFAULT 0,
  `isStable` tinyint(1) NOT NULL DEFAULT 1,
  `registered_by_user_id` int(11) DEFAULT NULL,
  `weight` varchar(20) DEFAULT NULL,
  `height` varchar(20) DEFAULT NULL,
  `title` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `otherInfo` text DEFAULT NULL,
  `time` time DEFAULT NULL,
  `normalRanges` text DEFAULT NULL,
  `hasHighBP` tinyint(1) NOT NULL DEFAULT 0,
  `needsMedication` tinyint(1) NOT NULL DEFAULT 0,
  `needsAppointment` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_patient_code` (`patient_code`),
  KEY `fk_patient_registered_by` (`registered_by_user_id`),
  CONSTRAINT `fk_patient_registered_by` FOREIGN KEY (`registered_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `patients` VALUES ('cc9f9189-453c-8352-c6bd-ca170f25dd50', 'TEMP-0234b36f-4885-8554-d3e6-ba1573dfa5e5', '1', '1', NULL, NULL, NULL, NULL, '1', '2025-12-08', NULL, '1', '1', NULL, '1', '1', '0', '0', '0', '0', '1', NULL, NULL, NULL, 's', NULL, NULL, NULL, NULL, '0', '0', '0');

DROP TABLE IF EXISTS `schedules`;
CREATE TABLE `schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `schedule_date` date NOT NULL,
  `schedule_time` time DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `patient_id` varchar(50) DEFAULT NULL COMMENT 'Can be null if it is a general event (e.g., vaccine drive)',
  `schedule_type` enum('Appointment','Outreach','Meeting','General') NOT NULL DEFAULT 'Appointment',
  `status` enum('Pending','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`schedule_id`),
  KEY `fk_schedule_patient` (`patient_id`),
  KEY `fk_schedule_creator` (`created_by_user_id`),
  CONSTRAINT `fk_schedule_creator` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_bns` tinyint(1) NOT NULL DEFAULT 0,
  `is_bhw` tinyint(1) NOT NULL DEFAULT 0,
  `is_midwife` tinyint(1) NOT NULL DEFAULT 0,
  `fullName` varchar(150) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `approved_by_admin` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES ('3', 'Royal', 'Orange', 'a@hehe.com', '$2y$10$DxIT.HFQtWt3887UKyDMJORJHnJIwVnAP8uhJ0H2.l.eay94nlIbm', '0', '0', '1', NULL, '1', '1', '2025-12-02 18:18:47');

