-- ============================================================
-- Table: examination_final_result_subjects
-- Description: Stores subject-wise weighted aggregate results
--              calculated from multiple exams with weight_percentage
-- ============================================================

CREATE TABLE IF NOT EXISTS `examination_final_result_subjects` (
    `id`                    INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Ownership & User
    `school_owner_uid`      INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `student_uid`           INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `enrollment_id`         INT(11) UNSIGNED NOT NULL DEFAULT 0,

    -- Academic Session
    `session_id`            INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `class_id`              INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `section_id`            INT(11) UNSIGNED NOT NULL DEFAULT 0,

    -- Subject
    `subject_id`            INT(11) UNSIGNED NOT NULL DEFAULT 0,

    -- Aggregate Marks (weighted sum across all exams)
    `aggregate_full_mark`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `aggregate_obtained_mark`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `aggregate_percentage`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    -- Grade Info
    `grade_point`           DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `grade`                 VARCHAR(50)   NOT NULL DEFAULT '',
    `letter_grade`          VARCHAR(50)   NOT NULL DEFAULT '',

    -- Status Flags
    `is_fail`               TINYINT(1)    NOT NULL DEFAULT 0,
    `is_generated`          TINYINT(1)    NOT NULL DEFAULT 0,
    `generated_at`          DATETIME      NULL DEFAULT NULL,

    -- Audit
    `created_by`            INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `updated_by`            INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `created_at`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    -- Indexes for fast lookups
    INDEX `idx_session_class` (`session_id`, `class_id`),
    INDEX `idx_student_session` (`student_uid`, `session_id`),
    INDEX `idx_subject` (`subject_id`),
    INDEX `idx_section` (`section_id`),
    INDEX `idx_student_subject` (`student_uid`, `subject_id`),
    INDEX `idx_generated` (`is_generated`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;