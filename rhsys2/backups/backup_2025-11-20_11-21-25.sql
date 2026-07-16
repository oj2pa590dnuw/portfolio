-- RHSYS Database Backup
-- Generated: 2025-11-20 11:21:25
-- Database: if0_40210966_system2

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
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `activity_logs` VALUES ('1', '2', 'BACKUP_CREATE', 'Created database backup: backup_2025-11-20_11-07-11.sql', NULL, NULL, '175.176.2.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 08:07:11'),
('2', '2', 'BACKUP_CREATE', 'Created database backup: backup_2025-11-20_11-07-28.sql', NULL, NULL, '175.176.2.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 08:07:28'),
('3', '2', 'VISIT_ADD', 'Added visit for patient ID: ', 'patient_visits', NULL, '175.176.2.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 08:09:07'),
('4', '2', 'BACKUP_CREATE', 'Created database backup: backup_2025-11-20_11-10-27.sql', NULL, NULL, '175.176.2.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 08:10:27');

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `dosage_forms` VALUES ('8', 'Capsule'),
('3', 'Device'),
('7', 'Drops'),
('6', 'Sachet'),
('5', 'Syrup'),
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `inventory` VALUES ('3', 'Amoxicillin Suspension (250mg/5ml)', 'qwerty', '1', '8', 'bottle', '133', '2025-10-30 20:20:53'),
('4', 'Amoxicillin Suspension (250mg/5ml)', '', '1', '3', 'bottle', '78', '2025-11-15 22:35:31'),
('5', 'Salbutamol', '', '1', '1', 'pc', '1', '2025-11-17 23:25:28'),
('6', 'pogi', 'masarap', '1', '8', '07222005', '20', '2025-11-18 23:29:11');

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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `inventory_batches` VALUES ('1', '2', '1', '11', '11', '2222-02-22', '2025-10-29 23:09:17', '2'),
('13', '2', '122', '1234567890', '1234567890', '2222-02-22', '2025-11-02 22:42:52', '2'),
('14', '3', 'Gshsgzhxuxjx', '700', '700', '2025-11-16', '2025-11-05 18:14:32', '2'),
('18', '3', 'Cvghhj', '133', '133', '2025-11-21', '2025-11-05 18:36:27', '2'),
('20', '4', '45', '3', '3', '2025-11-16', '2025-11-15 22:36:33', '2'),
('21', '3', 'wertyui', '1111', '1111', '1111-11-11', '2025-11-17 18:41:31', '2'),
('22', '4', '123', '10', '10', '2025-11-26', '2025-11-17 20:05:39', '2'),
('23', '5', '123', '1', '1', '2026-11-18', '2025-11-17 23:26:11', '2'),
('24', '6', '238383', '200', '200', '2025-11-20', '2025-11-18 23:49:45', '2');

DROP TABLE IF EXISTS `item_categories`;
CREATE TABLE `item_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `item_categories` VALUES ('1', 'General Medicine');

DROP TABLE IF EXISTS `patient_relationships`;
CREATE TABLE `patient_relationships` (
  `relationship_id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` varchar(36) NOT NULL,
  `related_patient_id` varchar(36) NOT NULL,
  `relationship_type` enum('Parent','Child','Spouse','Sibling','Grandparent','Grandchild','Other') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`relationship_id`),
  UNIQUE KEY `unique_relationship` (`patient_id`,`related_patient_id`,`relationship_type`),
  KEY `related_patient_id` (`related_patient_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

INSERT INTO `patient_relationships` VALUES ('1', 'bd16ec8a-47df-9457-1ff2-5a0e8d7d00fb', 'a1b2c3d4-e5f6-7890-1234-567890abcdef', 'Parent', '2025-11-20 00:55:17'),
('2', 'bd16ec8a-47df-9457-1ff2-5a0e8d7d00fb', '33333333-3333-3333-3333-333333333333', 'Sibling', '2025-11-20 00:55:24');

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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `patient_visits` VALUES ('1', 'a1b2c3d4-e5f6-7890-1234-567890abcdef', '2025-11-10', 'gghh', '12334', '34355', '', 'sdfghjkl;\'', 'dfghjkl', NULL, '2025-11-10 20:15:05'),
('2', 'a1b2c3d4-e5f6-7890-1234-567890abcdef', '2025-11-12', 'gghh', '12334', '34355', '17', 'dfghjkl;\'\n', 'sdfghjkl', NULL, '2025-11-12 01:12:33'),
('3', '91843ba8-454e-92e0-cc15-161aac94d664', '2025-11-16', 'PREGANTN', '1212', '12', '12', 'DFGHJ', 'FGHJ', NULL, '2025-11-16 00:00:39'),
('4', 'a6e33eb7-4516-a3de-8df0-7369b2040619', '2025-11-16', 'BUNTES', '1234', '123', '123', '234', '234', NULL, '2025-11-16 00:03:07'),
('5', 'a6e33eb7-4516-a3de-8df0-7369b2040619', '2025-11-17', 'HPN', '105/72', '75', '', '', '', NULL, '2025-11-17 19:46:55'),
('6', 'bd16ec8a-47df-9457-1ff2-5a0e8d7d00fb', '2025-11-17', 'BUNTES', '97/69', '72', '32.8', 'asdfghj', 'BP', NULL, '2025-11-17 20:03:18'),
('7', '91843ba8-454e-92e0-cc15-161aac94d664', '2025-11-18', 'Animal Bite', '', '', '', '', '', NULL, '2025-11-17 23:18:08'),
('8', 'a6e33eb7-4516-a3de-8df0-7369b2040619', '2025-11-19', '3', '', '', '', '', '', NULL, '2025-11-19 00:28:33'),
('9', 'a1b2c3d4-e5f6-7890-1234-567890abcdef', '2025-11-19', '1', '', '', '', '', '', NULL, '2025-11-19 00:34:31'),
('10', '91843ba8-454e-92e0-cc15-161aac94d664', '2025-11-19', 'tinatai', '', '', '', '', '', NULL, '2025-11-19 01:11:24'),
('11', '88888888-8888-8888-8888-888888888888', '2025-11-19', 'masakit ulo', '', '', '', 'nababaliw', 'binatukan', NULL, '2025-11-19 02:01:28'),
('12', '88888888-8888-8888-8888-888888888888', '2025-11-20', 'PREGANTN', '105/72', '12', '17', 'sdfcgvhgfd', 'sadfgh', NULL, '2025-11-20 08:09:31');

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

INSERT INTO `patients` VALUES ('11111111-1111-1111-1111-111111111111', 'P-2024-001', 'MARIA SANTOS', '68', '1956-03-15', '123456789012', 'LP-001', '09171234567', 'Zone 2, Zabali, Baler', '2024-11-18', 'High blood pressure, diabetic', '180/110', '95', '22', '37.8', 'Patient presented with severe headache and blurred vision. BP critically high.', '1', '0', '1', '1', '0', '1', '68', '155', 'Hypertensive Crisis', 'Emergency case, referred to hospital', 'Allergic to penicillin', '08:30:00', 'BP: 120/80, HR: 60-100', '1', '1', '1'),
('22222222-2222-2222-2222-222222222222', 'P-2024-002', 'JUVY REYES', '25', '1999-08-22', NULL, 'LP-002', '09178889999', 'Purok 3, Zabali, Baler', '2024-11-19', '2nd trimester, high risk', '110/70', '82', '20', '36.9', 'Regular prenatal checkup. Fetal heartbeat normal.', '0', '1', '0', '0', '1', '2', '65', '158', 'Prenatal Checkup', '2nd trimester, 24 weeks pregnant', 'First pregnancy', '09:15:00', 'BP: 110/70, Temp: 36.5-37.5', '0', '1', '1'),
('33333333-3333-3333-3333-333333333333', 'P-2024-003', 'PEDRO CRUZ', '75', '1949-12-10', '987654321098', 'LP-003', '09223334455', 'Sitio Dikapinisan, Zabali', '2024-11-17', 'Arthritis, mild dementia', '140/90', '78', '18', '36.7', 'Complains of joint pain. Memory lapses observed.', '0', '0', '1', '1', '1', '1', '62', '165', 'Geriatric Checkup', 'Routine monitoring for chronic conditions', 'Lives alone, needs caregiver', '10:00:00', 'BP: 140/90 max', '1', '1', '0'),
('44444444-4444-4444-4444-444444444444', 'P-2024-004', 'JUAN DELA CRUZ', '35', '1989-06-25', NULL, 'LP-004', '09155556666', 'Purok 5, Zabali, Baler', '2024-11-19', 'None', '120/80', '72', '16', '36.5', 'Annual physical exam. All parameters within normal range.', '0', '0', '0', '0', '1', '2', '75', '170', 'Annual Physical', 'Routine health checkup', 'Non-smoker, occasional drinker', '11:20:00', 'All within normal limits', '0', '0', '0'),
('55555555-5555-5555-5555-555555555555', 'P-2024-005', 'SUSAN TAN', '42', '1982-11-30', '555666777888', 'LP-005', '09334445566', 'Zone 1, Zabali, Baler', '2024-11-18', 'Asthma, allergic rhinitis', '130/85', '88', '24', '37.1', 'Asthma exacerbation due to weather changes.', '0', '0', '0', '1', '1', '1', '58', '160', 'Asthma Follow-up', 'Medication refill and monitoring', 'Allergic to dust and pollen', '13:45:00', 'RR: 12-20, Temp: 36.5-37.2', '0', '1', '1'),
('66666666-6666-6666-6666-666666666666', 'P-2024-006', 'MIGUEL GARCIA', '8', '2016-02-14', NULL, 'LP-006', '09447778899', 'Purok 4, Zabali, Baler', '2024-11-19', 'Childhood immunization', '100/65', '92', '28', '37.3', 'Routine immunization and growth monitoring.', '0', '0', '0', '0', '1', '2', '25', '125', 'Child Immunization', 'DTaP and MMR vaccines', 'Complete immunization record', '14:30:00', 'Age-appropriate vitals', '0', '0', '1'),
('77777777-7777-7777-7777-777777777777', 'P-2024-007', 'ROBERT LIM', '50', '1974-09-18', '444333222111', 'LP-007', '09123456789', 'Sitio Dimanpuro, Zabali', '2024-11-16', 'Pre-diabetic, overweight', '150/95', '85', '19', '37.0', 'Borderline diabetes, advised lifestyle changes.', '0', '0', '0', '1', '1', '1', '85', '168', 'Diabetes Screening', 'Pre-diabetic condition monitoring', 'Family history of diabetes', '15:10:00', 'BP: <140/90, FBS: <100', '1', '0', '1'),
('88888888-8888-8888-8888-888888888888', 'P-2024-008', 'ANNA TORRES', '29', '1995-04-05', NULL, 'LP-008', '09228889900', 'Zone 3, Zabali, Baler', '2025-11-20', '3rd trimester, low risk', '105/72', '12', '18', '17', 'sdfcgvhgfd', '0', '1', '0', '0', '1', '2', '70', '162', 'PREGANTN', '3rd trimester, 32 weeks', 'Second pregnancy', '16:00:00', 'BP: 110-120/70-80', '0', '1', '1'),
('91843ba8-454e-92e0-cc15-161aac94d664', 'TEMP-91843ba8-454e-92e0-cc15-161aac94d664', 'AUBREY NICOLEI RIVERA', '73', '0004-03-12', NULL, NULL, '091919191919', 'Phhh', '2025-11-19', 'sdfg', '', '', NULL, '', '', '0', '1', '0', '0', '0', '2', '234', '234', 'tinatai', 'sd', 'sdf', '11:11:00', '1234', '0', '0', '0'),
('99999999-9999-9999-9999-999999999999', 'P-2024-009', 'TERESITA GONZALES', '72', '1952-07-20', '777888999000', 'LP-009', '09167778888', 'Purok 2, Zabali, Baler', '2024-11-17', 'Hypertension, arthritis, vision problems', '160/100', '90', '20', '37.2', 'Multiple chronic conditions management.', '1', '0', '1', '1', '0', '1', '60', '152', 'Chronic Care', 'Management of multiple conditions', 'Difficulty walking, poor vision', '08:45:00', 'BP: <140/90, HR: 60-100', '1', '1', '1'),
('a1b2c3d4-e5f6-7890-1234-567890abcdef', 'TEMP-a1b2c3d4-e5f6-7890-1234-567890abcdef', 'LEE SANGWON', '45', NULL, NULL, NULL, '', 'Zone 1, Baler', '2025-11-19', 'Initial Checkup', '', '', NULL, '', '', '0', '0', '0', '0', '1', '1', '', '', '1', '', '', NULL, NULL, '0', '0', '0'),
('a6e33eb7-4516-a3de-8df0-7369b2040619', 'TEMP-a6e33eb7-4516-a3de-8df0-7369b2040619', 'JOHN WISSAM FAJARDO', '56', '0001-01-11', NULL, NULL, 'QWE', 'QWERT1', '2025-11-19', 'ERTYU', '', '', NULL, '', '', '0', '1', '0', '0', '0', '2', 'QWE', 'WER', '3', 'QW45', 'SRT', '11:01:00', '0', '0', '0', '0'),
('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'P-2024-010', 'CARLOS RAMOS', '22', '2002-12-03', NULL, 'LP-010', '09339998877', 'Sitio Diome, Zabali', '2024-11-19', 'None', '118/76', '70', '16', '36.4', 'Sports physical for employment.', '0', '0', '0', '0', '1', '2', '68', '175', 'Employment Physical', 'Pre-employment medical exam', 'Athlete, non-smoker', '10:30:00', 'All within normal range', '0', '0', '0'),
('bd16ec8a-47df-9457-1ff2-5a0e8d7d00fb', 'TEMP-bd16ec8a-47df-9457-1ff2-5a0e8d7d00fb', 'RESUENO, JORDAN SARENAS ', '21', '2004-08-26', NULL, NULL, '09618491990', 'BARANGAY DITUMABO SAN LUIS, AURORA', '2025-11-17', 'N/A', '97/69', '72', NULL, '32.8', 'asdfghj', '0', '0', '0', '0', '1', '2', '72', '169', 'BUNTES', 'NORMAL', 'N/A', '11:59:00', '0', '0', '0', '0'),
('e810c260-4f49-80ea-4070-b3dabf0f1609', 'TEMP-e810c260-4f49-80ea-4070-b3dabf0f1609', 'VEEJAY FLORES', '65', '1111-11-11', NULL, NULL, '091092', 'bsosisio', '2025-11-17', 'dfgh', '090', '222', NULL, '222', 'werth', '0', '1', '0', '0', '0', '20', '22', '22', 'wert', 'erty', 'sdfgh', '11:11:00', '0', '0', '0', '0'),
('fb8647e6-432d-b465-20d5-ae356cd2fc65', '', 'fdgdgdgd', '33', NULL, NULL, NULL, NULL, 'bddb', '2025-11-19', NULL, NULL, NULL, NULL, NULL, NULL, '0', '0', '0', '0', '1', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '0', '0', '0');

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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `schedules` VALUES ('1', '0000-00-00', '00:00:00', '', '', NULL, '', 'Completed', '2025-11-05 18:37:40', NULL),
('4', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:04:26', NULL),
('5', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:04:27', NULL),
('6', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:04:31', NULL),
('7', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:04:42', NULL),
('8', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:04:56', NULL),
('9', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:04:56', NULL),
('10', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:04:57', NULL),
('11', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:04:57', NULL),
('12', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:05:02', NULL),
('13', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:05:02', NULL),
('14', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:05:03', NULL),
('15', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:05:03', NULL),
('16', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:05:04', NULL),
('17', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:05:05', NULL),
('18', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:05:06', NULL),
('19', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:05:06', NULL),
('20', '2025-11-26', '09:10:00', 'JOHN WISSAM FAJARDO', 'kailngan ng gamot', NULL, '', 'Pending', '2025-11-19 03:05:07', NULL),
('21', '2025-11-20', '21:05:00', 'JOHN WISSAM FAJARDO', 'ssss', NULL, '', 'Pending', '2025-11-19 03:06:01', NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES ('1', 'Test', 'BNS', 'test.bns.user@example.com', '$2y$10$tM2m.l1U7r6P.8B7m3tQ5upT9N22M4/30L.2x6s2l3p8/A1T9B4a3m', '1', '0', '0', NULL, '0', '1', '2025-10-27 07:09:28'),
('2', 'Royal', 'Orange', 'a@hehe.com', '$2y$10$3p2ypZtZvz37VdwiESglde//LGZmDYxwwr7Pa4MsAA3NpVe0Y4c66', '0', '0', '1', NULL, '1', '1', '2025-10-27 07:44:45'),
('20', 'Royal', 'Orange', 'b@hehe.com', '$2y$10$/j4lgr9jbtNNP2Qf98SV1OK/4fS6v/96EXo4iHXe8PWdorl6mCerm', '0', '1', '0', NULL, '0', '1', '2025-11-15 23:09:55'),
('21', 'Mid', 'Wife', 'midwife@gmail.com', '$2y$10$uGNWlEub6AVTAmKYNHU0LeO/LSjZk.V45ixjl0PC15Lkp/9dM3da2', '0', '0', '1', NULL, '0', '1', '2025-11-17 22:59:13');

