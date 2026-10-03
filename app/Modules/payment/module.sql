CREATE TABLE IF NOT EXISTS `#__academic_suzon` (
  `id` int(255) NOT NULL AUTO_INCREMENT,
  `mark` int(255) DEFAULT NULL,
  `mark_distribution_id` int(255) NOT NULL,
  `exam_id` int(255) NOT NULL,
  `school_id` int(10) NOT NULL,
  `session_id` int(255) NOT NULL,
  `academic_id` int(255) NOT NULL,
  `subject_id` int(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(50) DEFAULT NULL,
  `updated_by` int(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;