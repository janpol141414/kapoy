-- ============================================================
-- Land Surveying Services Portal System - Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS land_surveying_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE land_surveying_db;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('client','engineer','admin') NOT NULL DEFAULT 'client',
    phone VARCHAR(20),
    address TEXT,
    profile_photo VARCHAR(255) DEFAULT 'default_avatar.png',
    bio TEXT,
    is_active TINYINT(1) DEFAULT 1,
    email_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- COMPANIES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    logo VARCHAR(255) DEFAULT 'default_company.png',
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(150),
    website VARCHAR(255),
    services TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- ENGINEERS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS engineers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_id INT,
    license_number VARCHAR(100),
    specialization VARCHAR(255),
    experience_years INT DEFAULT 0,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    availability_status ENUM('available','busy','offline') DEFAULT 'available',
    bio TEXT,
    skills TEXT,
    certifications TEXT,
    hourly_rate DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL
);

-- ============================================================
-- SCHEDULES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    engineer_id INT NOT NULL,
    date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_available TINYINT(1) DEFAULT 1,
    slot_type ENUM('morning','afternoon','evening','full_day') DEFAULT 'morning',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (engineer_id) REFERENCES engineers(id) ON DELETE CASCADE
);

-- ============================================================
-- APPOINTMENTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    engineer_id INT NOT NULL,
    schedule_id INT,
    service_type VARCHAR(255) NOT NULL,
    location TEXT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending','confirmed','in_progress','completed','cancelled') DEFAULT 'pending',
    notes TEXT,
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    ai_suggested TINYINT(1) DEFAULT 0,
    confirmation_code VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (engineer_id) REFERENCES engineers(id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE SET NULL
);

-- ============================================================
-- PAYMENTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    client_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('gcash','bank_transfer','cash','credit_card','paypal') NOT NULL,
    reference_number VARCHAR(100),
    proof_image VARCHAR(255),
    status ENUM('pending','verified','rejected') DEFAULT 'pending',
    admin_notes TEXT,
    verified_by INT,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- FEEDBACK TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    engineer_id INT NOT NULL,
    appointment_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    is_public TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (engineer_id) REFERENCES engineers(id) ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

-- ============================================================
-- MESSAGES TABLE (CHAT SYSTEM)
-- ============================================================
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    is_ai_reply TINYINT(1) DEFAULT 0,
    attachment VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- NOTIFICATIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('appointment','payment','message','status','system') DEFAULT 'system',
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- PROGRESS UPDATES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS progress_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    engineer_id INT NOT NULL,
    status VARCHAR(100) NOT NULL,
    description TEXT,
    photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
    FOREIGN KEY (engineer_id) REFERENCES engineers(id) ON DELETE CASCADE
);

-- ============================================================
-- SERVICES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    base_price DECIMAL(10,2) DEFAULT 0.00,
    duration_days INT DEFAULT 1,
    icon VARCHAR(100) DEFAULT 'fa-map',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default Services
INSERT INTO services (name, description, base_price, duration_days, icon) VALUES
('Boundary Survey', 'Determine exact property boundaries and corners', 5000.00, 3, 'fa-border-all'),
('Topographic Survey', 'Map terrain features, elevations, and contours', 8000.00, 5, 'fa-mountain'),
('Construction Layout', 'Stake out building positions and grades', 6000.00, 2, 'fa-building'),
('Subdivision Survey', 'Divide land into smaller parcels', 15000.00, 7, 'fa-th-large'),
('Route Survey', 'Survey for roads, pipelines, and utilities', 12000.00, 10, 'fa-road'),
('Hydrographic Survey', 'Map underwater terrain and water bodies', 20000.00, 14, 'fa-water'),
('Geodetic Survey', 'Large-scale precise measurements of earth', 25000.00, 21, 'fa-globe'),
('As-Built Survey', 'Document completed construction vs plans', 7000.00, 4, 'fa-drafting-compass');

-- Default Companies
INSERT INTO companies (name, description, logo, address, phone, email, website, services) VALUES
('GeoTech Surveying Inc.', 'Leading land surveying company with 20+ years of experience in boundary, topographic, and construction surveys across the Philippines.', 'company1.png', '123 Rizal Ave, Makati City, Metro Manila', '+63 2 8123 4567', 'info@geotech.ph', 'www.geotech.ph', 'Boundary Survey, Topographic Survey, Construction Layout'),
('PrecisionMap Solutions', 'Specialized in high-accuracy GPS and drone-assisted surveys for large-scale infrastructure projects.', 'company2.png', '456 EDSA, Quezon City, Metro Manila', '+63 2 8234 5678', 'contact@precisionmap.ph', 'www.precisionmap.ph', 'Geodetic Survey, Route Survey, Hydrographic Survey'),
('LandMark Surveyors Co.', 'Trusted by developers and government agencies for subdivision and as-built surveys since 2005.', 'company3.png', '789 Bonifacio St, Cebu City', '+63 32 234 5678', 'hello@landmark.ph', 'www.landmark.ph', 'Subdivision Survey, As-Built Survey, Boundary Survey'),
('TerraScan Philippines', 'Modern surveying firm using LiDAR and drone technology for fast and accurate results.', 'company4.png', '321 Ayala Ave, Davao City', '+63 82 234 5678', 'info@terrascan.ph', 'www.terrascan.ph', 'Topographic Survey, Route Survey, Construction Layout'),
('NorthStar Survey Group', 'Full-service land surveying and mapping company serving residential and commercial clients.', 'company5.png', '654 National Highway, Iloilo City', '+63 33 234 5678', 'support@northstar.ph', 'www.northstar.ph', 'Boundary Survey, Subdivision Survey, Geodetic Survey');

-- Default Users (passwords are hashed '123456')
-- Passwords are bcrypt hashes of '123456'
-- Generated with: password_hash('123456', PASSWORD_BCRYPT)
INSERT INTO users (name, email, password, role, phone, address, profile_photo, bio) VALUES
('Juan dela Cruz', 'client@test.com', '$2y$10$PjAGD76nKNVHH3rpu6niMeDWtKe6k2cPMgpYkScv4/vM9isqirYNK', 'client', '+63 917 123 4567', '123 Main St, Manila', 'client1.jpg', 'Property owner looking for reliable surveying services.'),
('Engr. Maria Santos', 'engineer@test.com', '$2y$10$PjAGD76nKNVHH3rpu6niMeDWtKe6k2cPMgpYkScv4/vM9isqirYNK', 'engineer', '+63 918 234 5678', '456 Engineer Ave, Quezon City', 'engineer1.jpg', 'Licensed Geodetic Engineer with 10 years of experience.'),
('Admin User', 'admin@test.com', '$2y$10$PjAGD76nKNVHH3rpu6niMeDWtKe6k2cPMgpYkScv4/vM9isqirYNK', 'admin', '+63 919 345 6789', 'Admin Office, Makati City', 'admin1.jpg', 'System Administrator'),
-- Additional clients
('Ana Reyes', 'ana.reyes@email.com', '$2y$10$PjAGD76nKNVHH3rpu6niMeDWtKe6k2cPMgpYkScv4/vM9isqirYNK', 'client', '+63 917 234 5678', '789 Rizal St, Cebu', 'client2.jpg', 'Real estate developer.'),
('Carlos Mendoza', 'carlos.m@email.com', '$2y$10$PjAGD76nKNVHH3rpu6niMeDWtKe6k2cPMgpYkScv4/vM9isqirYNK', 'client', '+63 917 345 6789', '321 Mabini St, Davao', 'client3.jpg', 'Construction company owner.'),
-- Additional engineers
('Engr. Roberto Cruz', 'roberto.c@email.com', '$2y$10$PjAGD76nKNVHH3rpu6niMeDWtKe6k2cPMgpYkScv4/vM9isqirYNK', 'engineer', '+63 918 345 6789', '654 Engineer Blvd, Makati', 'engineer2.jpg', 'Specialist in topographic and geodetic surveys.'),
('Engr. Lisa Tan', 'lisa.t@email.com', '$2y$10$PjAGD76nKNVHH3rpu6niMeDWtKe6k2cPMgpYkScv4/vM9isqirYNK', 'engineer', '+63 918 456 7890', '987 Survey Lane, BGC', 'engineer3.jpg', 'Expert in construction layout and as-built surveys.'),
('Engr. Mark Villanueva', 'mark.v@email.com', '$2y$10$PjAGD76nKNVHH3rpu6niMeDWtKe6k2cPMgpYkScv4/vM9isqirYNK', 'engineer', '+63 918 567 8901', '147 Geodetic Ave, Pasig', 'engineer4.jpg', 'Drone survey specialist with LiDAR expertise.'),
('Engr. Grace Lim', 'grace.l@email.com', '$2y$10$PjAGD76nKNVHH3rpu6niMeDWtKe6k2cPMgpYkScv4/vM9isqirYNK', 'engineer', '+63 918 678 9012', '258 Boundary St, Mandaluyong', 'engineer5.jpg', 'Subdivision and boundary survey expert.'),
('Engr. James Aquino', 'james.a@email.com', '$2y$10$PjAGD76nKNVHH3rpu6niMeDWtKe6k2cPMgpYkScv4/vM9isqirYNK', 'engineer', '+63 918 789 0123', '369 Route Rd, Taguig', 'engineer6.jpg', 'Route and hydrographic survey specialist.');

-- Engineer profiles
INSERT INTO engineers (user_id, company_id, license_number, specialization, experience_years, rating, total_reviews, availability_status, bio, skills, certifications, hourly_rate) VALUES
(2, 1, 'GE-2014-001234', 'Boundary & Topographic Survey', 10, 4.80, 45, 'available', 'Licensed Geodetic Engineer with expertise in boundary determination and topographic mapping. Served 200+ clients across Metro Manila.', 'GPS Surveying, AutoCAD, Total Station, Drone Piloting', 'PRC Licensed GE, NAMRIA Accredited', 1500.00),
(6, 2, 'GE-2012-005678', 'Geodetic & Route Survey', 12, 4.90, 62, 'available', 'Senior geodetic engineer specializing in large-scale infrastructure surveys. Expert in GPS and LiDAR technology.', 'LiDAR, GPS, GIS, AutoCAD Civil 3D, Drone Survey', 'PRC Licensed GE, ISO 9001 Certified', 2000.00),
(7, 1, 'GE-2016-009012', 'Construction Layout & As-Built', 8, 4.70, 38, 'busy', 'Construction survey specialist with experience in high-rise buildings and infrastructure projects.', 'Total Station, AutoCAD, Revit, Construction Layout', 'PRC Licensed GE, PCAB Accredited', 1800.00),
(8, 3, 'GE-2018-003456', 'Drone & Topographic Survey', 6, 4.85, 29, 'available', 'Modern surveying professional using drone and LiDAR technology for fast and accurate topographic surveys.', 'Drone Piloting, LiDAR, Photogrammetry, GIS, AutoCAD', 'PRC Licensed GE, CAA Drone Pilot License', 1600.00),
(9, 4, 'GE-2015-007890', 'Subdivision & Boundary Survey', 9, 4.75, 51, 'available', 'Expert in subdivision surveys and boundary determination for residential and commercial developments.', 'GPS, Total Station, AutoCAD, Subdivision Planning', 'PRC Licensed GE, HLURB Accredited', 1700.00),
(10, 5, 'GE-2013-002345', 'Route & Hydrographic Survey', 11, 4.65, 33, 'offline', 'Specialist in route surveys for roads and pipelines, and hydrographic surveys for water bodies.', 'Hydrographic Equipment, GPS, AutoCAD, Route Planning', 'PRC Licensed GE, NAMRIA Accredited', 1900.00);

-- Sample schedules (next 30 days)
INSERT INTO schedules (engineer_id, date, start_time, end_time, is_available, slot_type) VALUES
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '12:00:00', 1, 'morning'),
(1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '13:00:00', '17:00:00', 1, 'afternoon'),
(1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '08:00:00', '17:00:00', 1, 'full_day'),
(1, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '08:00:00', '12:00:00', 0, 'morning'),
(1, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '08:00:00', '12:00:00', 1, 'morning'),
(1, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '08:00:00', '17:00:00', 1, 'full_day'),
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '17:00:00', 1, 'full_day'),
(2, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '08:00:00', '12:00:00', 1, 'morning'),
(2, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '13:00:00', '17:00:00', 0, 'afternoon'),
(2, DATE_ADD(CURDATE(), INTERVAL 6 DAY), '08:00:00', '17:00:00', 1, 'full_day'),
(3, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '08:00:00', '12:00:00', 1, 'morning'),
(3, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '08:00:00', '17:00:00', 0, 'full_day'),
(3, DATE_ADD(CURDATE(), INTERVAL 8 DAY), '13:00:00', '17:00:00', 1, 'afternoon'),
(4, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '12:00:00', 1, 'morning'),
(4, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '08:00:00', '17:00:00', 1, 'full_day'),
(4, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '13:00:00', '17:00:00', 1, 'afternoon'),
(5, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '08:00:00', '17:00:00', 1, 'full_day'),
(5, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '08:00:00', '12:00:00', 1, 'morning'),
(5, DATE_ADD(CURDATE(), INTERVAL 6 DAY), '08:00:00', '17:00:00', 0, 'full_day');

-- Sample feedback
INSERT INTO feedback (client_id, engineer_id, rating, comment, is_public) VALUES
(1, 1, 5, 'Excellent work! Very professional and accurate. Highly recommended!', 1),
(4, 1, 5, 'Maria is very knowledgeable and delivered results on time. Will hire again.', 1),
(5, 2, 5, 'Roberto is the best! His geodetic survey was incredibly precise.', 1),
(1, 2, 4, 'Great service, very detailed report. Slightly delayed but worth it.', 1),
(4, 3, 5, 'Lisa did an amazing job on our construction layout. Very efficient!', 1),
(5, 4, 5, 'Mark used drone technology and finished in half the expected time. Impressive!', 1),
(1, 5, 4, 'Grace is very thorough with boundary surveys. Professional and courteous.', 1),
(4, 6, 4, 'James handled our route survey professionally. Good communication throughout.', 1);

-- Sample appointments
INSERT INTO appointments (client_id, engineer_id, service_type, location, appointment_date, appointment_time, status, notes, total_amount, confirmation_code) VALUES
(1, 1, 'Boundary Survey', 'Lot 5, Block 3, Subdivision, Quezon City', DATE_ADD(CURDATE(), INTERVAL 5 DAY), '09:00:00', 'confirmed', 'Need to determine exact lot boundaries for title transfer.', 5000.00, 'CONF-2024-001'),
(4, 2, 'Topographic Survey', '123 Mountain View, Antipolo City', DATE_ADD(CURDATE(), INTERVAL 3 DAY), '08:00:00', 'in_progress', 'Topographic survey for resort development.', 8000.00, 'CONF-2024-002'),
(5, 3, 'Construction Layout', 'BGC Tower Site, Taguig City', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '07:00:00', 'pending', 'Layout for 20-story commercial building.', 6000.00, 'CONF-2024-003');

-- Sample notifications
INSERT INTO notifications (user_id, title, message, type, is_read) VALUES
(1, 'Appointment Confirmed', 'Your appointment with Engr. Maria Santos has been confirmed for the boundary survey.', 'appointment', 0),
(2, 'New Appointment Assigned', 'You have been assigned a new boundary survey appointment.', 'appointment', 0),
(3, 'New Payment Pending', 'A payment verification is pending for appointment #1.', 'payment', 0);
