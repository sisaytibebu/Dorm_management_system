CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50) NOT NULL,
    student_id VARCHAR(50) NOT NULL UNIQUE,
    department VARCHAR(100) NOT NULL,
    year_batch VARCHAR(50) NOT NULL,
    profile_photo VARCHAR(255),
    password VARCHAR(255) NOT NULL,
    Building VARCHAR(100) NULL,
    Dprm_no VARCHAR(50) NULL
);
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,

    student_id VARCHAR(50) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NOT NULL,

    building VARCHAR(100) NULL,
    dorm_no VARCHAR(50) NULL,

    report TEXT NOT NULL,
    admin_feedback TEXT NULL,
    student_feedback TEXT NULL,

    status ENUM('Pending','Replied') DEFAULT 'Pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE reports
ADD COLUMN report_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
AFTER created_at;

CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,

    first_name VARCHAR(100),
    middle_name VARCHAR(100),
    last_name VARCHAR(100),
    department VARCHAR(100),
    building VARCHAR(100),
    dorm_no VARCHAR(50),
    year_batch VARCHAR(50),
    profile_photo VARCHAR(255),

    attendance_date VARCHAR(20),   -- Ethiopian date
    attendance_time VARCHAR(20),   -- Ethiopian time

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE `announcements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `header` VARCHAR(255) NOT NULL,
    `subheader` TEXT NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
