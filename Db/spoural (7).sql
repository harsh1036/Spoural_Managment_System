-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 19, 2025 at 06:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `spoural`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GenerateParticipantsData` ()   BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE student_id_prefix VARCHAR(4);
    DECLARE student_id_suffix VARCHAR(3);
    DECLARE dept_id INT;
    DECLARE event_id INT;
    DECLARE is_captain_val INT;
    -- Define the specific academic year ID you want to use
    DECLARE specific_academic_year_id INT DEFAULT 5; -- <<< CHANGE THIS TO YOUR DESIRED ACADEMIC YEAR ID

    -- Optional: Uncomment this line if you want to clear existing data before inserting
    -- TRUNCATE TABLE participants;

    WHILE i <= 2000 DO
        -- Generate student_id (e.g., 24CE001, 23IT005, etc.)
        SET student_id_prefix = CONCAT(LPAD(FLOOR(RAND() * (25 - 20 + 1) + 20), 2, '0'),
                                       CASE FLOOR(RAND() * 5)
                                           WHEN 0 THEN 'CE'
                                           WHEN 1 THEN 'IT'
                                           WHEN 2 THEN 'CS'
                                           WHEN 3 THEN 'EC'
                                           WHEN 4 THEN 'ME'
                                       END);
        SET student_id_suffix = LPAD(i, 3, '0'); -- Use 'i' to ensure unique student IDs within this generated set
        SET @student_id = CONCAT(student_id_prefix, student_id_suffix);

        -- Cycle through department IDs (from 1 to 18 based on your image)
        SET dept_id = FLOOR(1 + RAND() * 18);

        -- Cycle through event IDs (from 1 to 50 based on your image - assuming at least 50 events)
        SET event_id = FLOOR(1 + RAND() * 50);

        -- Randomly set is_captain (0 or 1)
        SET is_captain_val = FLOOR(RAND() * 2);

        -- Insert the record, always using the specific_academic_year_id
        INSERT INTO participants (student_id, dept_id, event_id, is_captain, academic_year_id)
        VALUES (@student_id, dept_id, event_id, is_captain_val, specific_academic_year_id);

        SET i = i + 1;
    END WHILE;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int(11) NOT NULL,
  `year` varchar(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `year`) VALUES
(7, '2020-21'),
(6, '2021-22'),
(4, '2022-23'),
(5, '2024-25'),
(8, '2025-26');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `admin_id` varchar(100) NOT NULL,
  `admin_name` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `admin_id`, `admin_name`, `password`, `status`) VALUES
(1, 'd24ce150', 'Raj', '1234', 1),
(4, 'D24Ce156', 'harsh', '12345', 0);

-- --------------------------------------------------------

--
-- Table structure for table `certificate_templates`
--

CREATE TABLE `certificate_templates` (
  `id` int(11) NOT NULL,
  `academic_year_id` int(9) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificate_templates`
--

INSERT INTO `certificate_templates` (`id`, `academic_year_id`, `file_path`, `uploaded_at`) VALUES
(0, 5, '../uploads/cert_templates/year_5_1752865533.jpg', '2025-07-18 19:05:33'),
(0, 8, '../uploads/cert_templates/year_8_1752931117.jpg', '2025-07-19 13:18:37');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(50) NOT NULL,
  `dept_name` varchar(50) NOT NULL,
  `academic_year_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`dept_id`, `dept_name`, `academic_year_id`) VALUES
(1, 'IIIM', 8),
(2, 'CMPICA-BSCIT', 8),
(3, 'CSPIT-AIML', 8),
(4, 'CMPICA-PG', 8),
(5, 'MTIN', 8),
(6, 'CSPIT-CE', 8),
(7, 'DEPSTAR-CSE', 8),
(8, 'ARIP', 8),
(9, 'BDIPS', 8),
(10, 'CSPIT-IT', 8),
(11, 'CSPIT-CSE', 8),
(12, 'CMPICA-UG', 8),
(13, 'DEPSTAR-IT', 8),
(14, 'PDPIS', 8),
(15, 'CSPIT-MECL', 8),
(16, 'DEPSTAR-CE', 8),
(17, 'CSPIT-ECEE', 8),
(18, 'RPCP', 8),
(20, 'CE', 8);

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `event_type` enum('Sports','Cultural') NOT NULL,
  `min_participants` int(50) NOT NULL,
  `max_participants` int(50) NOT NULL,
  `academic_year_id` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `event_name`, `event_type`, `min_participants`, `max_participants`, `academic_year_id`, `status`) VALUES
(1, 'Cricket (B)', 'Sports', 15, 15, 5, 1),
(2, 'Cricket (G)', 'Sports', 15, 15, 5, 1),
(3, 'Basketball (B)', 'Sports', 7, 12, NULL, 1),
(4, 'Basketball (G)', 'Sports', 7, 12, NULL, 1),
(5, 'Vollyball (B)', 'Sports', 7, 12, NULL, 1),
(6, 'Vollyball (G)', 'Sports', 7, 12, NULL, 1),
(7, 'TableTannis (B)', 'Sports', 1, 5, 5, 1),
(8, 'TableTannis (G)', 'Sports', 1, 5, NULL, 1),
(9, 'Kabaddi (B)', 'Sports', 9, 12, NULL, 1),
(10, 'Kabaddi (G)', 'Sports', 9, 12, NULL, 1),
(11, 'Football (B)', 'Sports', 9, 12, NULL, 1),
(12, 'Football (G)', 'Sports', 9, 12, NULL, 1),
(13, 'Tug of war (B)', 'Sports', 7, 10, NULL, 1),
(14, 'Tug of war (G)', 'Sports', 7, 10, NULL, 1),
(15, 'Tug of war (mix)', 'Sports', 7, 10, NULL, 1),
(16, 'Chess (B)', 'Sports', 1, 5, NULL, 1),
(17, 'Chess (G)', 'Sports', 1, 5, NULL, 1),
(18, 'Badminton (B)', 'Sports', 1, 5, NULL, 1),
(19, 'Badminton (G)', 'Sports', 1, 5, NULL, 1),
(20, 'BGMI', 'Sports', 5, 8, NULL, 1),
(21, 'VALO', 'Sports', 5, 8, NULL, 1),
(22, 'Intrument plaing', 'Cultural', 3, 6, 5, 1),
(23, 'Singing Solo', 'Cultural', 1, 1, NULL, 1),
(24, 'Singing Group', 'Cultural', 3, 6, NULL, 1),
(25, 'Classical Dance', 'Cultural', 1, 1, NULL, 1),
(26, 'Dance Solo', 'Cultural', 1, 1, NULL, 1),
(27, 'Dance Group', 'Cultural', 5, 10, NULL, 1),
(28, 'Garba Raas', 'Cultural', 8, 12, NULL, 1),
(29, 'Quiz', 'Cultural', 2, 2, NULL, 1),
(30, 'Debate', 'Cultural', 2, 2, NULL, 1),
(31, 'Elocution', 'Cultural', 1, 1, NULL, 1),
(32, 'Poetry Writing', 'Cultural', 1, 5, NULL, 1),
(33, 'Fashion Show', 'Cultural', 14, 20, NULL, 1),
(34, 'Mimicry', 'Cultural', 1, 1, NULL, 1),
(35, 'Skit', 'Cultural', 5, 8, NULL, 1),
(36, 'Mr Charusat', 'Cultural', 1, 1, NULL, 1),
(37, 'Ms Charusat', 'Cultural', 1, 1, NULL, 1),
(38, 'Cartooning', 'Cultural', 1, 1, NULL, 1),
(39, 'Dumb Charades', 'Cultural', 3, 3, NULL, 1),
(40, 'One minute Game', 'Cultural', 3, 3, NULL, 1),
(41, 'On the Spot Paining', 'Cultural', 1, 1, NULL, 1),
(42, 'Antakshri', 'Cultural', 3, 5, NULL, 1),
(43, 'Collage Making', 'Cultural', 1, 2, NULL, 1),
(44, 'Clay Modeling', 'Cultural', 1, 3, NULL, 1),
(45, 'Rangoli', 'Cultural', 1, 3, NULL, 1),
(46, 'Photography', 'Cultural', 0, 0, NULL, 1),
(47, 'Nail Art', 'Cultural', 1, 3, NULL, 1),
(48, 'Ad making', 'Cultural', 4, 4, NULL, 1),
(49, 'March Procession', 'Cultural', 15, 20, NULL, 1),
(50, 'Mehandi', 'Cultural', 1, 3, NULL, 1),
(61, 'freefire', 'Sports', 1, 11, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `participants`
--

CREATE TABLE `participants` (
  `id` int(50) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `dept_id` int(50) NOT NULL,
  `event_id` int(50) NOT NULL,
  `is_captain` int(11) NOT NULL,
  `academic_year_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `participants`
--

INSERT INTO `participants` (`id`, `student_id`, `dept_id`, `event_id`, `is_captain`, `academic_year_id`) VALUES
(1, '24CE001', 6, 38, 1, 5),
(2, '22IT011', 10, 2, 0, 8);

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` varchar(10) NOT NULL,
  `student_name` varchar(50) NOT NULL,
  `contact` bigint(20) NOT NULL,
  `dept_id` int(50) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `academic_year_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `student_name`, `contact`, `dept_id`, `status`, `academic_year_id`) VALUES
('22BBA011', 'Dev Sharma', 9789012345, 1, 1, 8),
('22BBA012', 'Riya Kapoor', 9890123456, 1, 1, 8),
('22BBA013', 'Aditya Sinha', 9901234567, 1, 1, 8),
('22BBA014', 'Sneha Mishra', 9812345678, 1, 1, 8),
('22BBA015', 'Harsh Jain', 9723456789, 1, 1, 8),
('22IT011', 'Dev Sharma', 9789012345, 10, 1, 8),
('22IT012', 'Riya Kapoor', 9890123456, 10, 1, 8),
('22IT013', 'Aditya Sinha', 9901234567, 10, 1, 8),
('22IT014', 'Sneha Mishra', 9812345678, 10, 1, 8),
('22IT015', 'Harsh Jain', 9723456789, 10, 1, NULL),
('23BBA006', 'Ananya Rao', 9345678901, 1, 1, NULL),
('23BBA007', 'Aryan Gupta', 9234567890, 1, 1, NULL),
('23BBA008', 'Priya Nair', 9456789012, 1, 1, NULL),
('23BBA009', 'Karan Verma', 9567890123, 1, 1, NULL),
('23BBA010', 'Meera Iyer', 9678901234, 1, 1, NULL),
('23IT006', 'Ananya Rao', 9345678901, 10, 1, NULL),
('23IT007', 'Aryan Gupta', 9234567890, 10, 1, NULL),
('23IT008', 'Priya Nair', 9456789012, 10, 1, NULL),
('23IT009', 'Karan Verma', 9567890123, 10, 1, NULL),
('23IT010', 'Meera Iyer', 9678901234, 10, 1, NULL),
('23MBA016', 'Pooja Reddy', 9634567890, 1, 1, NULL),
('23MBA017', 'Yash Malhotra', 9545678901, 1, 1, NULL),
('23MBA018', 'Sakshi Tripathi', 9456789012, 1, 1, NULL),
('23MBA019', 'Raj Thakur', 9367890123, 1, 1, NULL),
('23MBA020', 'Nisha Menon', 9278901234, 1, 1, NULL),
('24BBA001', 'Aarav Patel', 9876543210, 1, 1, NULL),
('24BBA002', 'Ishita Shah', 9123456789, 1, 1, NULL),
('24BBA003', 'Rohan Mehta', 9988776655, 1, 1, NULL),
('24BBA004', 'Kavya Joshi', 9765432101, 1, 1, NULL),
('24BBA005', 'Vihaan Desai', 9654321098, 1, 1, NULL),
('24CE001', 'Aarav Patel', 9876543210, 6, 1, NULL),
('24CE002', 'Ishita Shah', 9123456789, 6, 1, NULL),
('24CE003', 'Rohan Mehta', 9988776655, 6, 1, NULL),
('24CE004', 'Kavya Joshi', 9765432101, 6, 1, NULL),
('24CE005', 'Vihaan Desai', 9654321098, 6, 1, NULL),
('24CE006', 'Ananya Rao', 9345678901, 6, 1, NULL),
('24CE007', 'Aryan Gupta', 9234567890, 6, 1, NULL),
('24CE008', 'Priya Nair', 9456789012, 6, 1, NULL),
('24CE009', 'Karan Verma', 9567890123, 6, 1, NULL),
('24CE010', 'Meera Iyer', 9678901234, 6, 1, NULL),
('24CE011', 'Dev Sharma', 9789012345, 6, 1, NULL),
('24CE012', 'Riya Kapoor', 9890123456, 6, 1, NULL),
('24CE013', 'Aditya Sinha', 9901234567, 6, 1, NULL),
('24CE014', 'Sneha Mishra', 9812345678, 6, 1, NULL),
('24CE015', 'Harsh Jain', 9723456789, 6, 1, NULL),
('24CE016', 'Pooja Reddy', 9634567890, 6, 1, NULL),
('24CE017', 'Yash Malhotra', 9545678901, 6, 1, NULL),
('24CE018', 'Sakshi Tripathi', 9456789012, 6, 1, NULL),
('24CE019', 'Raj Thakur', 9367890123, 6, 1, NULL),
('24CE020', 'Nisha Menon', 9278901234, 6, 1, NULL),
('24dce102', 'heet', 1250360502, 16, 1, 8),
('24IT001', 'Aarav Patel', 9876543210, 10, 1, NULL),
('24IT002', 'Ishita Shah', 9123456789, 10, 1, NULL),
('24IT003', 'Rohan Mehta', 9988776655, 10, 1, NULL),
('24IT004', 'Kavya Joshi', 9765432101, 10, 1, NULL),
('24IT005', 'Vihaan Desai', 9654321098, 10, 1, NULL),
('24MBA021', 'Tanmay Shukla', 9189012345, 1, 1, NULL),
('24MBA022', 'Radhika Pandey', 9090123456, 1, 1, NULL),
('24MBA023', 'Siddharth Nair', 9981234567, 1, 1, NULL),
('24MBA024', 'Aditi Choudhary', 9872345678, 1, 1, NULL),
('24MBA025', 'Manav Tiwari', 9763456789, 1, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ulsc`
--

CREATE TABLE `ulsc` (
  `id` int(11) NOT NULL,
  `ulsc_id` varchar(50) NOT NULL,
  `ulsc_name` varchar(100) NOT NULL,
  `dept_id` int(50) NOT NULL,
  `contact` bigint(12) NOT NULL,
  `email` varchar(25) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `academic_year_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ulsc`
--

INSERT INTO `ulsc` (`id`, `ulsc_id`, `ulsc_name`, `dept_id`, `contact`, `email`, `password`, `status`, `academic_year_id`) VALUES
(1, 'D24CE155', 'Hitarth', 6, 9978683915, 'D24CE155@charusat.edu.in', '$2y$10$kedBM8XWLbBknOTyly/hwe/FQgGebEPv2GcEMpdvIdn6Xhfb.hVjS', 1, 8),
(2, '24it001', 'Raj', 10, 9988776600, '24it001@charusat.edu.in', '$2y$10$AmWaOAcN1K8lfyCYfmJrb.umW1gYtxSnrgh655jMCyxfI2wM79tsC', 1, NULL),
(3, '24dce101', 'jigar', 16, 1562926500, 'harsh@gmail.com', '$2y$10$p3Vz0l5JNjMkQW265UZvFekyWxO/8tGI.e6NgNm3AYb2DRAWndkLq', 1, 8),
(30, '24CSE001', 'Rahul', 11, 9898989898, '24CSE001@charusat.edu.in', '$2y$10$RUCs8P5J7LDZY8CmkUUHg.Bwco8ywit3l9su7VLTkX7CV0OFe8ifi', 1, NULL),
(31, '24AIML001', 'Rahul', 3, 9898989898, '24AIML001@charusat.edu.in', '$2y$10$mrs/.eCvKtK95n5PIazgBu8bG0pKg5VhQYWeFBNlVFD8KowhedOUu', 1, NULL),
(32, '24DCE001', 'Rahul', 16, 9898989898, '24DCE001@charusat.edu.in', '$2y$10$daU6RdiAvF569cORzU4hmeE8xR/iddmhI06Dc5a.6ROjIy.t4Qj7C', 1, NULL),
(33, '24DIT001', 'Rahul', 13, 9898989898, '24DIT001@charusat.edu.in', '$2y$10$xvcinccHbQtqgSLrkTgZ.OBGPpkNUciu7K0ux7Y36LqEu.agolhNu', 1, NULL),
(34, 'D24DCSE150', 'Rahul', 7, 9898989898, 'D24DCSE150@charusat.edu.i', '$2y$10$ZLg7IoOciV5lAHc3T2LRcOpi8G8.4nW/4DfCKY2B05XizacdJKIn2', 1, NULL),
(38, 'D24ce156', 'Harsh', 6, 1234567654, 'D24ce156@charusat.edu.in', '$2y$10$p3Vz0l5JNjMkQW265UZvFekyWxO/8tGI.e6NgNm3AYb2DRAWndkLq', 1, 8);

-- --------------------------------------------------------

--
-- Table structure for table `ulsc_f`
--

CREATE TABLE `ulsc_f` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `contact_no` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `academic_year_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ulsc_f`
--

INSERT INTO `ulsc_f` (`id`, `fullname`, `dept_id`, `contact_no`, `email`, `password`, `status`, `academic_year_id`) VALUES
(1232, 'Het', 17, '123423412', 'admin@renewablecloth.com', '$2y$10$lgCUSxwL.rKABhyzBUhuder5TrtL/OmNiBb8kc/A7FMdKfsy1dsre', 1, 8),
(1234, 'Harsh Vora', 6, '1234567890', 'admin@gmail.com', '$2y$10$qOjdAZIQ4c5rjo7xgR8PDOsp.qxS/N3cAP7WEgwGngBtS6oe/SbbC', 1, NULL),
(1235, 'Harsh Vora', 13, '9313230095', 'harshvoracomp@gmail.com', '$2y$10$tcueKW6M/FJ5Ho8srP5Keez.B/lliBgEHZtIFDTIshRtIdW9nOSny', 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `year` (`year`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `certificate_templates`
--
ALTER TABLE `certificate_templates`
  ADD KEY `fk_academic_year_c` (`academic_year_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`),
  ADD KEY `fk_departments_academic_year` (`academic_year_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_event_year` (`academic_year_id`);

--
-- Indexes for table `participants`
--
ALTER TABLE `participants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_participant_year` (`academic_year_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `fk_dept` (`dept_id`),
  ADD KEY `fk_student_academic_year` (`academic_year_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ulsc`
--
ALTER TABLE `ulsc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ulsc_id` (`ulsc_id`),
  ADD KEY `fk_ulsc_department` (`dept_id`),
  ADD KEY `fk_ulsc_academic_year` (`academic_year_id`);

--
-- Indexes for table `ulsc_f`
--
ALTER TABLE `ulsc_f`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `dept_id` (`dept_id`),
  ADD KEY `fk_academic_year` (`academic_year_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `dept_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `participants`
--
ALTER TABLE `participants`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ulsc`
--
ALTER TABLE `ulsc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `ulsc_f`
--
ALTER TABLE `ulsc_f`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1236;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `certificate_templates`
--
ALTER TABLE `certificate_templates`
  ADD CONSTRAINT `fk_academic_year_c` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`);

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_departments_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `fk_event_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`);

--
-- Constraints for table `participants`
--
ALTER TABLE `participants`
  ADD CONSTRAINT `fk_participant_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`);

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `fk_dept` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_student_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `ulsc`
--
ALTER TABLE `ulsc`
  ADD CONSTRAINT `fk_ulsc_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ulsc_department` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON DELETE CASCADE;

--
-- Constraints for table `ulsc_f`
--
ALTER TABLE `ulsc_f`
  ADD CONSTRAINT `fk_academic_year` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`),
  ADD CONSTRAINT `ulsc_f_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
