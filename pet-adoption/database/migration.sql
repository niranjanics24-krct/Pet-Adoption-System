-- Pet Adoption Management System - Safe Database Migration
-- Database: pet_adoption_db

CREATE DATABASE IF NOT EXISTS `pet_adoption_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `pet_adoption_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `role` ENUM('user','admin') DEFAULT 'user',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Pets Table
CREATE TABLE IF NOT EXISTS `pets` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `type` VARCHAR(50) DEFAULT NULL,
  `breed` VARCHAR(100) DEFAULT NULL,
  `age` VARCHAR(50) DEFAULT NULL,
  `gender` VARCHAR(20) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Available','Pending','Trial','Adopted') DEFAULT 'Available',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Adoption Requests Table
CREATE TABLE IF NOT EXISTS `adoption_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `pet_id` INT(11) NOT NULL,
  `eligibility_score` DECIMAL(5,2) DEFAULT 0.00,
  `status` ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
  `admin_notes` TEXT DEFAULT NULL,
  `request_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `pet_id` (`pet_id`),
  CONSTRAINT `adoption_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `adoption_requests_ibfk_2` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Eligibility Answers Table (Stores all 18+ question answers per request)
CREATE TABLE IF NOT EXISTS `eligibility_answers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `request_id` INT(11) NOT NULL,
  `question_key` VARCHAR(100) NOT NULL,
  `question_text` VARCHAR(255) NOT NULL,
  `submitted_answer` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_eligibility_req` (`request_id`),
  CONSTRAINT `fk_eligibility_req` FOREIGN KEY (`request_id`) REFERENCES `adoption_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Trial Adoptions Table
CREATE TABLE IF NOT EXISTS `trial_adoptions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `request_id` INT(11) NOT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `status` ENUM('Ongoing','Successful','Unsuccessful') DEFAULT 'Ongoing',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  CONSTRAINT `trial_adoptions_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `adoption_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed initial available pets if none exist
INSERT INTO `pets` (`name`, `type`, `breed`, `age`, `gender`, `description`, `image`, `status`)
SELECT * FROM (
    SELECT 'Bella', 'Dog', 'Golden Retriever', '2 years', 'Female', 'Friendly, calm, and loves outdoor strolls. Bella gets along great with other animals and children.', 'https://images.unsplash.com/photo-1552053831-71594a27632d?auto=format&fit=crop&w=700&q=80', 'Available'
    UNION ALL
    SELECT 'Milo', 'Cat', 'Domestic Short Hair', '1 year', 'Male', 'Playful and curious young cat who adores sunny window sills, feather wands, and gentle head scratches.', 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=700&q=80', 'Available'
    UNION ALL
    SELECT 'Rocky', 'Dog', 'German Shepherd Mix', '3 years', 'Male', 'Energetic, highly intelligent, and loyal protector. Eager to learn tricks and perfect for an active family.', 'https://images.unsplash.com/photo-1589941013453-ec89f33b5e95?auto=format&fit=crop&w=700&q=80', 'Available'
    UNION ALL
    SELECT 'Luna', 'Cat', 'Siamese Mix', '2 years', 'Female', 'Quiet, affectionate companion with striking blue eyes. Loves cozy blankets and tranquil indoor evenings.', 'https://images.unsplash.com/photo-1533738363-b7f9aef128ce?auto=format&fit=crop&w=700&q=80', 'Available'
    UNION ALL
    SELECT 'Barnaby', 'Rabbit', 'Holland Lop', '10 months', 'Male', 'Gentle, litter-box trained bunny with floppy ears. Loves fresh timothy hay, safe indoor playpens, and kale snacks.', 'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?auto=format&fit=crop&w=700&q=80', 'Available'
    UNION ALL
    SELECT 'Daisy', 'Dog', 'Beagle', '4 years', 'Female', 'Sweet-natured hound with a curious nose and boundless love. Sociable, gentle with children, and loves park walks.', 'https://images.unsplash.com/photo-1537151625747-768eb6cf92b2?auto=format&fit=crop&w=700&q=80', 'Available'
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `pets` LIMIT 1);
