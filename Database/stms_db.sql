-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 05, 2026 at 02:10 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stms_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent','Late') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `student_id`, `subject_id`, `attendance_date`, `status`) VALUES
(9, 1, 12, '2026-03-05', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_name`, `department_name`, `description`) VALUES
(1, 'Bachelor of Science is Information Technology', 'CITE', NULL),
(2, 'Bachelor of Science in Business Administration', '', NULL),
(3, 'Bachelor of Science in Engineering ', '', NULL),
(4, 'Bachelor of Science in Nursing', '', NULL),
(5, 'Bachelor of Science in Psychology', '', NULL),
(6, 'Bachelor of Science in Hospitality Management', '', NULL),
(7, 'Bachelor of Arts in Communication', '', NULL),
(8, 'Bachelor of Arts in Political Science', 'CELA', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `exam_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `exam_type` enum('Prelim','Midterm','Finals') NOT NULL,
  `exam_title` varchar(100) NOT NULL,
  `exam_date` date NOT NULL,
  `total_score` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`exam_id`, `subject_id`, `exam_type`, `exam_title`, `exam_date`, `total_score`) VALUES
(1, 24, 'Prelim', 'Prelim Exam', '2026-03-07', 100),
(6, 24, 'Midterm', 'Midterm Exam', '2026-03-15', 100),
(9, 10, 'Prelim', 'Prelim Exam', '2026-03-07', 100),
(10, 10, 'Midterm', 'Midterm Exam', '2026-03-15', 100),
(11, 10, 'Finals', 'Finals Exam', '2026-03-30', 100),
(12, 11, 'Prelim', 'Prelim Exam', '2026-03-07', 100),
(13, 11, 'Midterm', 'Midterm Exam', '2026-03-15', 100),
(14, 11, 'Finals', 'Finals Exam', '2026-03-30', 100),
(17, 24, 'Finals', 'Finals Exam', '2026-03-30', 100);

-- --------------------------------------------------------

--
-- Table structure for table `exam_grades`
--

CREATE TABLE `exam_grades` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_grades`
--

INSERT INTO `exam_grades` (`id`, `exam_id`, `student_id`, `score`) VALUES
(1, 9, 1, 90),
(3, 10, 1, 90),
(4, 11, 1, 85);

-- --------------------------------------------------------

--
-- Table structure for table `final_grades`
--

CREATE TABLE `final_grades` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` int(11) NOT NULL,
  `total_grade` decimal(3,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `final_grades`
--

INSERT INTO `final_grades` (`id`, `exam_id`, `student_id`, `score`, `total_grade`) VALUES
(1, 12, 1, 90, 2.25),
(3, 13, 1, 90, 2.25),
(6, 14, 1, 70, 2.25);

-- --------------------------------------------------------

--
-- Table structure for table `parents`
--

CREATE TABLE `parents` (
  `parent_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `parents`
--

INSERT INTO `parents` (`parent_id`, `user_id`) VALUES
(1, 8);

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `quiz_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `quiz_title` varchar(100) NOT NULL,
  `quiz_date` date NOT NULL,
  `total_score` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`quiz_id`, `subject_id`, `quiz_title`, `quiz_date`, `total_score`) VALUES
(2, 12, 'Quiz 1', '2026-03-04', 55),
(4, 9, 'Quiz 1', '2026-03-04', 100);

-- --------------------------------------------------------

--
-- Table structure for table `quiz_grades`
--

CREATE TABLE `quiz_grades` (
  `grade_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_grades`
--

INSERT INTO `quiz_grades` (`grade_id`, `quiz_id`, `student_id`, `score`) VALUES
(1, 4, 1, 30);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `room_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `room_name`) VALUES
(4, 'Room 101'),
(5, 'Room 102'),
(3, 'Room 103'),
(7, 'Room 104'),
(6, 'Room 105');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `course_id`, `year_level`, `section`, `parent_id`) VALUES
(1, 9, 1, '1st Year', NULL, 1),
(2, 10, 1, '2nd Year', NULL, 1),
(3, 11, 1, '3rd Year', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_subjects`
--

CREATE TABLE `student_subjects` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_subjects`
--

INSERT INTO `student_subjects` (`id`, `student_id`, `subject_id`) VALUES
(21, 1, 8),
(22, 1, 9),
(23, 1, 10),
(24, 1, 11),
(25, 1, 12),
(37, 2, 13),
(38, 2, 14),
(39, 2, 15),
(40, 2, 16),
(41, 2, 17),
(42, 3, 18),
(43, 3, 19),
(44, 3, 20),
(45, 3, 21),
(46, 3, 22);

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `subject_id` int(11) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `schedule_time` varchar(100) DEFAULT NULL,
  `schedule_days` varchar(100) DEFAULT NULL,
  `semester` enum('1st Sem','2nd Sem','3rd Sem','Summer') DEFAULT NULL,
  `year` enum('1st Year','2nd Year','3rd Year','4th Year') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`subject_id`, `subject_name`, `course_id`, `teacher_id`, `room_id`, `schedule_time`, `schedule_days`, `semester`, `year`) VALUES
(8, 'Introduction to Information Technology', 1, 1, 4, '10:30 AM', 'Monday,Tuesday,Wednesday', '1st Sem', '1st Year'),
(9, 'Computer Programming Fundamentals', 1, 1, 4, '10:30 AM', 'Monday,Tuesday,Wednesday', '1st Sem', '1st Year'),
(10, 'Computer Systems & Organization', 1, 1, 4, '10:00 AM', '', '1st Sem', '1st Year'),
(11, 'Mathematics for IT (Discrete Mathematics)', 1, 1, 4, '10:00 AM', '', '1st Sem', '1st Year'),
(12, 'Communication Skills / English for IT', 1, 1, 4, '10:00 AM', 'Monday,Tuesday,Wednesday', '1st Sem', '1st Year'),
(13, 'Data Structures and Algorithms', 1, 1, 5, '12:00 PM', 'Monday,Tuesday,Thursday', '2nd Sem', '2nd Year'),
(14, 'Database Management Systems', 1, 1, 5, '12:00 PM', 'Monday,Wednesday,Thursday', '2nd Sem', '2nd Year'),
(15, 'Object-Oriented Programming', 1, 1, 5, '12:00 PM', 'Monday,Tuesday,Wednesday', '2nd Sem', '2nd Year'),
(16, 'Web Development', 1, 1, 5, '12:00 PM', 'Monday,Friday,Saturday', '2nd Sem', '2nd Year'),
(17, 'System Analysis and Design', 1, 1, 5, '12:00 PM', 'Monday,Wednesday,Saturday', '2nd Sem', '2nd Year'),
(18, 'Network Fundamentals / Networking', 1, 1, 3, '2:00 PM', 'Monday,Wednesday,Thursday', '3rd Sem', '3rd Year'),
(19, 'Operating Systems', 1, 1, 3, '2:00 PM', 'Monday,Wednesday,Thursday', '3rd Sem', '3rd Year'),
(20, 'Software Engineering', 1, 1, 3, '2:00 PM', 'Monday,Tuesday,Thursday', '3rd Sem', '3rd Year'),
(21, 'Mobile Application Development', 1, 1, 3, '2:00 PM', 'Monday,Tuesday,Wednesday', '3rd Sem', '3rd Year'),
(22, 'IT Project Management', 1, 1, 3, '2:00 PM', 'Monday,Wednesday,Saturday', '3rd Sem', '3rd Year'),
(23, 'Cybersecurity / Information Security', 1, 1, 7, '5:00 PM', 'Monday,Wednesday,Thursday', 'Summer', '4th Year'),
(24, 'Cloud Computing / Emerging Technologies', 1, 1, 7, '5:00 PM', 'Monday,Thursday,Friday', 'Summer', '4th Year'),
(25, 'IT Capstone Project', 1, 1, 7, '5:00 PM', 'Monday,Wednesday,Saturday', 'Summer', '4th Year'),
(26, 'Human-Computer Interaction', 1, 1, 7, '5:00 PM', 'Monday,Tuesday,Wednesday', 'Summer', '4th Year'),
(27, 'Internship / On-the-Job Training', 1, 1, 7, '5:00 PM', 'Monday,Wednesday,Saturday', 'Summer', '4th Year');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `user_id`, `course_id`, `employee_id`, `specialization`, `hire_date`, `status`) VALUES
(1, 7, 1, '', '', '0000-00-00', 'Active'),
(2, 12, 1, NULL, NULL, NULL, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `student_id_number` varchar(20) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student','parent') NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `student_id_number`, `username`, `password`, `role`, `first_name`, `last_name`, `email`, `contact_number`, `profile_picture`, `created_at`) VALUES
(1, NULL, 'admin@gmail.com', '0b14d501a594442a01c6859541bcb3e8164d183d32937b851835442f69d5c94e', 'admin', 'Admin', 'User1', 'admin@gmail.com', '1', NULL, '2026-03-01 13:44:36'),
(7, NULL, 'teacher1@gmail.com', '0b14d501a594442a01c6859541bcb3e8164d183d32937b851835442f69d5c94e', 'teacher', 'Teacher1', 'Test1', 'teacher1@gmail.com', '12345678901', NULL, '2026-03-01 15:10:54'),
(8, NULL, 'parent@gmail.com', '0b14d501a594442a01c6859541bcb3e8164d183d32937b851835442f69d5c94e', 'parent', 'Parent', 'Test1', 'parent@gmail.com', '1234567890', NULL, '2026-03-01 16:21:59'),
(9, '01-2122-12345', 'student@gmail.com', '0b14d501a594442a01c6859541bcb3e8164d183d32937b851835442f69d5c94e', 'student', 'Student1', 'Test1', 'student@gmail.com', '1234567890', NULL, '2026-03-01 16:44:41'),
(10, '01-2122-12346', 'student2@gmail.com', '0b14d501a594442a01c6859541bcb3e8164d183d32937b851835442f69d5c94e', 'student', 'Student2', 'Test2', 'student2@gmail.com', '1234567890', NULL, '2026-03-02 05:16:59'),
(11, '01-2122-12348', 'student3@gmail.com', '0b14d501a594442a01c6859541bcb3e8164d183d32937b851835442f69d5c94e', 'student', 'Student3', 'Test3', 'student3@gmail.com', '1234567890', NULL, '2026-03-02 05:17:22'),
(12, NULL, 'teacher2@gmail.com', '0b14d501a594442a01c6859541bcb3e8164d183d32937b851835442f69d5c94e', 'teacher', 'Teachere2', 'Test2', 'teacher2@gmail.com', '123456789', NULL, '2026-03-04 09:40:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`subject_id`,`attendance_date`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_name` (`course_name`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`exam_id`),
  ADD UNIQUE KEY `unique_exam` (`subject_id`,`exam_type`);

--
-- Indexes for table `exam_grades`
--
ALTER TABLE `exam_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_exam_student` (`exam_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `final_grades`
--
ALTER TABLE `final_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_exam_student` (`exam_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `parents`
--
ALTER TABLE `parents`
  ADD PRIMARY KEY (`parent_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`quiz_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `quiz_grades`
--
ALTER TABLE `quiz_grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD UNIQUE KEY `quiz_id` (`quiz_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD UNIQUE KEY `room_name` (`room_name`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`subject_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `subjects_ibfk_2` (`teacher_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `exam_grades`
--
ALTER TABLE `exam_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `final_grades`
--
ALTER TABLE `final_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `parents`
--
ALTER TABLE `parents`
  MODIFY `parent_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `quiz_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `quiz_grades`
--
ALTER TABLE `quiz_grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_subjects`
--
ALTER TABLE `student_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `exams`
--
ALTER TABLE `exams`
  ADD CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `exam_grades`
--
ALTER TABLE `exam_grades`
  ADD CONSTRAINT `exam_grades_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `exam_grades_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `final_grades`
--
ALTER TABLE `final_grades`
  ADD CONSTRAINT `final_grades_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`exam_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `final_grades_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `parents`
--
ALTER TABLE `parents`
  ADD CONSTRAINT `parents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_grades`
--
ALTER TABLE `quiz_grades`
  ADD CONSTRAINT `quiz_grades_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `quiz_grades_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`parent_id`) REFERENCES `parents` (`parent_id`) ON DELETE SET NULL;

--
-- Constraints for table `student_subjects`
--
ALTER TABLE `student_subjects`
  ADD CONSTRAINT `student_subjects_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`subject_id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subjects_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subjects_ibfk_3` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) ON DELETE SET NULL;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
