-- ============================================================
-- AI-BASED COLLEGE ADMISSION PREDICTOR
-- Database Setup Script
-- ============================================================

CREATE DATABASE IF NOT EXISTS admission_predictor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE admission_predictor;

-- ============================================================
-- STUDENTS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    student_marks DECIMAL(5,2) DEFAULT 0,
    entrance_scores DECIMAL(5,2) DEFAULT 0,
    preferences TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- COLLEGES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS colleges (
    college_id INT AUTO_INCREMENT PRIMARY KEY,
    college_name VARCHAR(200) NOT NULL,
    location VARCHAR(100),
    course_list TEXT,
    cutoff_scores DECIMAL(5,2) DEFAULT 0,
    entrance_cutoff DECIMAL(5,2) DEFAULT 0,
    tier ENUM('Tier 1','Tier 2','Tier 3') DEFAULT 'Tier 2',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- ADMISSION RECORDS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS admission_records (
    record_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    college_id INT NOT NULL,
    outcome ENUM('Admitted','Rejected','Waitlisted') NOT NULL,
    previous_scores DECIMAL(5,2),
    year INT DEFAULT 2024,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (college_id) REFERENCES colleges(college_id) ON DELETE CASCADE
);

-- ============================================================
-- PREDICTIONS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS predictions (
    pred_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    college_id INT NOT NULL,
    probability_score DECIMAL(5,2),
    recommendation TEXT,
    generated_on TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (college_id) REFERENCES colleges(college_id) ON DELETE CASCADE
);

-- ============================================================
-- SEED COLLEGES DATA
-- ============================================================
INSERT INTO colleges (college_name, location, course_list, cutoff_scores, entrance_cutoff, tier) VALUES
('IIT Delhi', 'New Delhi', 'B.Tech CSE, B.Tech ECE, B.Tech Mechanical', 95.00, 90.00, 'Tier 1'),
('IIT Bombay', 'Mumbai', 'B.Tech CSE, B.Tech Data Science, B.Tech Chemical', 96.00, 92.00, 'Tier 1'),
('NIT Trichy', 'Tiruchirappalli', 'B.Tech CSE, B.Tech ECE, B.Tech Civil', 88.00, 80.00, 'Tier 1'),
('BITS Pilani', 'Pilani', 'B.Tech CSE, B.Tech Mechanical, B.Tech EEE', 90.00, 85.00, 'Tier 1'),
('VIT Vellore', 'Vellore', 'B.Tech CSE, B.Tech AIML, B.Tech IT', 82.00, 70.00, 'Tier 2'),
('Manipal Institute', 'Manipal', 'B.Tech CSE, B.Tech ECE, B.Tech Biomedical', 78.00, 65.00, 'Tier 2'),
('SRM University', 'Chennai', 'B.Tech CSE, B.Tech AIML, B.Tech Robotics', 75.00, 60.00, 'Tier 2'),
('Amity University', 'Noida', 'B.Tech CSE, BCA, MCA', 70.00, 55.00, 'Tier 2'),
('Lovely Professional', 'Phagwara', 'B.Tech CSE, B.Tech ECE, BCA', 65.00, 50.00, 'Tier 3'),
('SGT University', 'Gurugram', 'B.Tech CSE, BCA AIML, MBA', 60.00, 45.00, 'Tier 3');

-- ============================================================
-- SEED HISTORICAL ADMISSION RECORDS (for predictions engine)
-- ============================================================
-- Demo student
INSERT INTO students (name, email, password, student_marks, entrance_scores, preferences) VALUES
('Demo Student', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 85.00, 78.00, 'CSE,AIML');
-- password is: password

INSERT INTO admission_records (student_id, college_id, outcome, previous_scores, year) VALUES
(1, 5, 'Admitted', 85.00, 2024),
(1, 6, 'Admitted', 85.00, 2024),
(1, 3, 'Rejected', 85.00, 2024);
