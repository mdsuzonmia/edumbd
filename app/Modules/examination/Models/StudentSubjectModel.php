<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class StudentSubjectModel extends Model
{
    protected $table      = 'student_subjects';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'school_id',
        'enrollment_id',
        'subject_id',
        'optional_subject_id',
        'created_at',
    ];

    protected $useTimestamps = false;
    protected $returnType    = 'object';

    // Get get_student_subject_categories
    public function get_student_subject_result_categories(int $school_id, int $enrollment_id, int $exam_id, int $class_id, int $year_id, object $student)
    {
        // Get Table
        $studentSubjectModel = new StudentSubjectModel();
        // Get Compulsory subjects (combine_group IS NULL)
        $compulsory_subjects = $studentSubjectModel
            ->select('student_subjects.*, examination_subjects.*, examination_subject_results.*')
            ->join('examination_subjects', 'examination_subjects.id = student_subjects.subject_id', 'left')
            ->join('examination_subject_results', 'examination_subject_results.subject_id = student_subjects.subject_id', 'left')
            ->where('student_subjects.school_id', $school_id)
            ->where('student_subjects.enrollment_id', $enrollment_id)

            ->where('examination_subject_results.exam_id', $exam_id)
            ->where('examination_subject_results.class_id', $class_id)
            ->where('examination_subject_results.student_id', $student->id)
            ->where('examination_subject_results.session_id', $year_id)

            ->groupStart()
                ->where('student_subjects.optional_subject_id', NULL)
                ->orWhere('student_subjects.optional_subject_id', 0)
            ->groupEnd()
            ->where('examination_subjects.combine_group IS NULL')
            ->orderBy('examination_subjects.order_number', 'ASC')
            ->findAll();

           


        // Get Combined subjects (combine_group IS NOT NULL)
        $combined_subjects = $studentSubjectModel
            ->select('student_subjects.*, examination_subjects.*, examination_subject_results.*')
            ->join('examination_subjects', 'examination_subjects.id = student_subjects.subject_id', 'left')
            ->join('examination_subject_results', 'examination_subject_results.subject_id = student_subjects.subject_id', 'left')
            ->where('student_subjects.school_id', $school_id)
            ->where('student_subjects.enrollment_id', $enrollment_id)

            ->where('examination_subject_results.exam_id', $exam_id)
            ->where('examination_subject_results.class_id', $class_id)
            ->where('examination_subject_results.student_id', $student->id)
            ->where('examination_subject_results.session_id', $year_id)
            ->groupStart()
                ->where('student_subjects.optional_subject_id', NULL)
                ->orWhere('student_subjects.optional_subject_id', 0)
            ->groupEnd()
            ->where('examination_subjects.combine_group IS NOT NULL')
            ->orderBy('examination_subjects.order_number', 'ASC')
            ->findAll();

        // Get Optional subjects (optional_subject_id IS NOT NULL AND != 0)
        $optional_subjects = $studentSubjectModel
           ->select('student_subjects.*, examination_subjects.*, examination_subject_results.*')
            ->join('examination_subjects', 'examination_subjects.id = student_subjects.subject_id', 'left')
            ->join('examination_subject_results', 'examination_subject_results.subject_id = student_subjects.subject_id', 'left')
            ->where('student_subjects.school_id', $school_id)
            ->where('student_subjects.enrollment_id', $enrollment_id)

            ->where('examination_subject_results.exam_id', $exam_id)
            ->where('examination_subject_results.class_id', $class_id)
            ->where('examination_subject_results.student_id', $student->id)
            ->where('examination_subject_results.session_id', $year_id)

            ->where('student_subjects.optional_subject_id IS NOT NULL AND student_subjects.optional_subject_id !=', 0)
            ->orderBy('examination_subjects.order_number', 'ASC')
            ->findAll();

        return [
            'compulsory_subjects' => $compulsory_subjects,
            'combined_subjects' => $combined_subjects,
            'optional_subjects' => $optional_subjects,
        ];
    }

    public function get_student_subjects(int $school_id, int $enrollment_id)
    {
        // Get Table
        $studentSubjectModel = new StudentSubjectModel();
        // Get Compulsory subjects (combine_group IS NULL)
        $compulsory_subjects = $studentSubjectModel
            ->select('student_subjects.*, examination_subjects.*, examination_subjects.id AS id')
            ->join('examination_subjects', 'examination_subjects.id = student_subjects.subject_id', 'left')
            ->where('student_subjects.school_id', $school_id)
            ->where('student_subjects.enrollment_id', $enrollment_id)

            ->groupStart()
                ->where('student_subjects.optional_subject_id', NULL)
                ->orWhere('student_subjects.optional_subject_id', 0)
            ->groupEnd()
            ->where('examination_subjects.combine_group IS NULL')
            ->orderBy('examination_subjects.order_number', 'ASC')
            ->findAll();

           


        // Get Combined subjects (combine_group IS NOT NULL)
        $combined_subjects = $studentSubjectModel
            ->select('student_subjects.*, examination_subjects.*, examination_subjects.id AS id')
            ->join('examination_subjects', 'examination_subjects.id = student_subjects.subject_id', 'left')
            ->where('student_subjects.school_id', $school_id)
            ->where('student_subjects.enrollment_id', $enrollment_id)

            ->groupStart()
                ->where('student_subjects.optional_subject_id', NULL)
                ->orWhere('student_subjects.optional_subject_id', 0)
            ->groupEnd()
            ->where('examination_subjects.combine_group IS NOT NULL')
            ->orderBy('examination_subjects.order_number', 'ASC')
            ->findAll();

        // Get Optional subjects (optional_subject_id IS NOT NULL AND != 0)
        $optional_subjects = $studentSubjectModel
           ->select('student_subjects.*, examination_subjects.*, examination_subjects.id AS id')
            ->join('examination_subjects', 'examination_subjects.id = student_subjects.subject_id', 'left')
            ->where('student_subjects.school_id', $school_id)
            ->where('student_subjects.enrollment_id', $enrollment_id)

            ->where('student_subjects.optional_subject_id IS NOT NULL AND student_subjects.optional_subject_id !=', 0)
            ->orderBy('examination_subjects.order_number', 'ASC')
            ->findAll();

        return [
            'compulsory_subjects' => $compulsory_subjects,
            'combined_subjects' => $combined_subjects,
            'optional_subjects' => $optional_subjects,
        ];
    }

    public function get_student_subject_categories(int $school_id, int $enrollment_id, int $exam_id, int $class_id, int $year_id, object $student)
    {
        // Get Table
        $studentSubjectModel = new StudentSubjectModel();
        // Get Compulsory subjects (combine_group IS NULL)
        $compulsory_subjects = $studentSubjectModel
            ->select('student_subjects.*, examination_subjects.*, examination_subjects.id AS id')
            ->join('examination_subjects', 'examination_subjects.id = student_subjects.subject_id', 'left')
            ->where('student_subjects.school_id', $school_id)
            ->where('student_subjects.enrollment_id', $enrollment_id)
            ->groupStart()
                ->where('student_subjects.optional_subject_id', NULL)
                ->orWhere('student_subjects.optional_subject_id', 0)
            ->groupEnd()
            ->where('examination_subjects.combine_group IS NULL')
            ->orderBy('examination_subjects.order_number', 'ASC')
            ->findAll();

           


        // Get Combined subjects (combine_group IS NOT NULL)
        $combined_subjects = $studentSubjectModel
            ->select('student_subjects.*, examination_subjects.*, examination_subjects.id AS id')
            ->join('examination_subjects', 'examination_subjects.id = student_subjects.subject_id', 'left')
            ->where('student_subjects.school_id', $school_id)
            ->where('student_subjects.enrollment_id', $enrollment_id)

            ->groupStart()
                ->where('student_subjects.optional_subject_id', NULL)
                ->orWhere('student_subjects.optional_subject_id', 0)
            ->groupEnd()
            ->where('examination_subjects.combine_group IS NOT NULL')
            ->orderBy('examination_subjects.order_number', 'ASC')
            ->findAll();

        // Get Optional subjects (optional_subject_id IS NOT NULL AND != 0)
        $optional_subjects = $studentSubjectModel
           ->select('student_subjects.*, examination_subjects.*, examination_subjects.id AS id')
            ->join('examination_subjects', 'examination_subjects.id = student_subjects.subject_id', 'left')
            
            ->where('student_subjects.school_id', $school_id)
            ->where('student_subjects.enrollment_id', $enrollment_id)
            
            ->where('student_subjects.optional_subject_id IS NOT NULL AND student_subjects.optional_subject_id !=', 0)
            ->orderBy('examination_subjects.order_number', 'ASC')
            ->findAll();

        return [
            'compulsory_subjects' => $compulsory_subjects,
            'combined_subjects' => $combined_subjects,
            'optional_subjects' => $optional_subjects,
        ];
    }



    public function get_student_subject_aggregate_result(int $school_id, int $enrollment_id, int $exam_id, int $class_id, int $year_id, object $student)
    {
        // Get Table
        $studentSubjectModel = new StudentSubjectModel();
        // Get Compulsory subjects (combine_group IS NULL)
        $compulsory_subjects = $studentSubjectModel
            ->select('student_subjects.*, examination_subjects.*, examination_subject_results.*, examination_final_result_subjects.*')
            ->join('examination_subjects', 'examination_subjects.id = student_subjects.subject_id', 'left')
            ->join('examination_subject_results', 'examination_subject_results.subject_id = student_subjects.subject_id', 'left')

            // Join examination_final_result_subjects
            ->join('examination_final_result_subjects', 'examination_final_result_subjects.subject_id = student_subjects.subject_id', 'left')
            ->where('student_subjects.school_id', $school_id)
            ->where('student_subjects.enrollment_id', $enrollment_id)

            ->where('examination_subject_results.exam_id', $exam_id)
            ->where('examination_subject_results.class_id', $class_id)
            ->where('examination_subject_results.student_id', $student->id)
            ->where('examination_subject_results.session_id', $year_id)

            ->groupStart()
                ->where('student_subjects.optional_subject_id', NULL)
                ->orWhere('student_subjects.optional_subject_id', 0)
            ->groupEnd()
            ->where('examination_subjects.combine_group IS NULL')
            ->orderBy('examination_subjects.order_number', 'ASC')
            ->findAll();

           


        // Get Combined subjects (combine_group IS NOT NULL)
        $combined_subjects = $studentSubjectModel
            ->select('student_subjects.*, examination_subjects.*, examination_subject_results.*, examination_final_result_subjects.*')
            ->join('examination_subjects', 'examination_subjects.id = student_subjects.subject_id', 'left')
            ->join('examination_subject_results', 'examination_subject_results.subject_id = student_subjects.subject_id', 'left')
            // Join examination_final_result_subjects
            ->join('examination_final_result_subjects', 'examination_final_result_subjects.subject_id = student_subjects.subject_id', 'left')
            ->where('student_subjects.school_id', $school_id)
            ->where('student_subjects.enrollment_id', $enrollment_id)

            ->where('examination_subject_results.exam_id', $exam_id)
            ->where('examination_subject_results.class_id', $class_id)
            ->where('examination_subject_results.student_id', $student->id)
            ->where('examination_subject_results.session_id', $year_id)
            ->groupStart()
                ->where('student_subjects.optional_subject_id', NULL)
                ->orWhere('student_subjects.optional_subject_id', 0)
            ->groupEnd()
            ->where('examination_subjects.combine_group IS NOT NULL')
            ->orderBy('examination_subjects.order_number', 'ASC')
            ->findAll();

        // Get Optional subjects (optional_subject_id IS NOT NULL AND != 0)
        $optional_subjects = $studentSubjectModel
           ->select('student_subjects.*, examination_subjects.*, examination_subject_results.*, examination_final_result_subjects.*')
            ->join('examination_subjects', 'examination_subjects.id = student_subjects.subject_id', 'left')
            ->join('examination_subject_results', 'examination_subject_results.subject_id = student_subjects.subject_id', 'left')
            // Join examination_final_result_subjects
            ->join('examination_final_result_subjects', 'examination_final_result_subjects.subject_id = student_subjects.subject_id', 'left')
            ->where('student_subjects.school_id', $school_id)
            ->where('student_subjects.enrollment_id', $enrollment_id)

            ->where('examination_subject_results.exam_id', $exam_id)
            ->where('examination_subject_results.class_id', $class_id)
            ->where('examination_subject_results.student_id', $student->id)
            ->where('examination_subject_results.session_id', $year_id)

            ->where('student_subjects.optional_subject_id IS NOT NULL AND student_subjects.optional_subject_id !=', 0)
            ->orderBy('examination_subjects.order_number', 'ASC')
            ->findAll();

        return [
            'compulsory_subjects' => $compulsory_subjects,
            'combined_subjects' => $combined_subjects,
            'optional_subjects' => $optional_subjects,
        ];
    }
}
