<?php 
use App\Models\StudentModel;
use App\Models\MarkModel;
use App\Models\AcademicsModel;
use App\Models\ExamModel;
use App\Models\SubjectModel;
use App\Models\SkillModel;
use App\Models\MdistributionModel;
use App\Models\RemarkModel;


// mark summary function
if (!function_exists('student_mark_summary')) {
	function student_mark_summary($student_id, $school_id){
		$remark_model            = new RemarkModel();
		$mark_model              = new MarkModel();
		$exam_model              = new ExamModel();
		$subject_model           = new SubjectModel();
		$mark_distribution_model = new MdistributionModel();
		$skill_model             = new SkillModel();
		$mark_model              = new MarkModel();
		$academmic_model         = new AcademicsModel();

		// Get school settings
		$academic_skill       = esc(get_school_value($school_id, 'academic_skill', true));
		$final_grading_system = esc(get_school_value($school_id, 'grading_system', true));
		$show_attendance_info = esc(get_school_value($school_id, 'show_attendance_info', true));

		// Get student academic information by student id
		if($student_id){
			$academic_info = $academmic_model->where('student_id', $student_id);
		}

		if($school_id){
			$academic_info = $academmic_model->where('school_id', $school_id);
		}
			
		$academic_info = $academmic_model->findAll();
		
		
		$academic_data = array();
		if($academic_info){
			
			foreach($academic_info as $academic_item){
				$academic_id = $academic_item->id;
				$session_id  = $academic_item->session_id;
				$class_id    = $academic_item->class_id;

				// Store academic details properly in an indexed array
				$academic_entry = [
					'academic_id' => $academic_id,
					'session_id'  => $session_id,
					'class_id'    => $class_id,
					'exams'       => []
				];

				if($academic_id){
					$mark_model->select('DISTINCT(exam_id)');
					$exam_list = $mark_model->where('academic_id', $academic_id)->findAll();
				}

				$subject_ids = $academic_item->subject_ids;
				$subject_array     = explode(',', $subject_ids);

				$skill_ids         = $academic_item->skill_ids;
				$skill_array       = explode(',', $skill_ids); 

				$exam_info = array();
				if($exam_list){
					foreach($exam_list as $exam){
						$exam_id   = $exam->exam_id;
						$exam_title = get_item('title', 'academic_exam', 'id', $exam_id);

						$academic_entry['exams'][] = [
							'exam_id'    => $exam_id,
							'exam_title' => $exam_title,
							'result'     => []
						];

						// Get the last index of the exams array
						$last_exam_index = array_key_last($academic_entry['exams']);

						
						$exam_total_mark = array();
						$exam_total_gp   = array();
						$total_subject   = count($subject_array);
						foreach ($subject_array as $j=>$subject_id) {
							// Get Subject data
							$subject                 = $subject_model->where('id', $subject_id)->first();
							$subject_title           = $subject->title;
							$subject_grading_system  = $subject->grade_system;
							$subject_short_title     = $subject->short_title;
							$mark_distribution       = $subject->mark_distribution;
							$mark_distribution_array = explode(',', $mark_distribution);

							
							$subject_total_mark = array();
									$total_mark_distribution_item = count($mark_distribution_array);
									
									foreach ($mark_distribution_array as $key => $mark_distribution_id) {
										// Get Mark Distribution data
										$subject_mark = get_distribution_mark('mark', $exam_id, $school_id, $session_id, $academic_id, $subject_id, $mark_distribution_id);

										if(!empty($subject_mark)){
											$mark = $subject_mark;
										}else{
											$mark = '0';
										}
										$subject_total_mark[] = $mark;

									}
									
							$subject_total_mark = array_sum($subject_total_mark);
							$exam_total_mark[]  = $subject_total_mark;

							$grading_value      = get_grading_value($subject_grading_system, $subject_total_mark, $school_id);

							$grading_point   = $grading_value['gp'];
							$exam_total_gp[] = $grading_point;
							$letter_grade    = $grading_value['gpa'];

							// Get Highest mark
							$highest_marks = get_highest_mark($exam_id, $school_id, $session_id, $subject_id);
							
							$academic_entry['exams'][$last_exam_index]['result'][] = [
								$subject_title    => $subject_total_mark,
								'highest_mark' => $highest_marks,
								'gp'           => $grading_point,
								'grade'        => $letter_grade
							];

						} // End of subject loop

						// If active $academic_skill
						if($academic_skill){
											
							foreach ($skill_array as $sk=>$skill_id) {
								// Get Skill data
								$skill                 = $skill_model->where('id', $skill_id)->first();
								$skill_title           = $skill->title;
								$skill_grading_system  = $skill->grade_system;

								// Get Skill Mark
								$skill_mark = get_skill_mark('mark', $exam_id, $school_id, $session_id, $academic_id, $skill_id);
								$exam_total_mark[]  = $skill_mark;
								$skill_grading_value = get_grading_value($skill_grading_system, $skill_mark, $school_id);

								$skill_gp              = $skill_grading_value['gp'];
								$exam_total_gp[]       = $skill_gp;
								$skill_letter_grade    = $skill_grading_value['gpa'];

								// Get Highest mark
								$highest_skill_marks = get_highest_skill_mark($exam_id, $school_id, $session_id, $skill_id);
								$academic_entry['exams'][$last_exam_index]['result'][] = [
									$skill_title    => $skill_mark,
									'highest_mark' => $highest_skill_marks,
									'gp'           => $skill_gp,
									'grade'        => $skill_letter_grade
								];
							}
							
						} // End of active $academic_skill

						// Get calculate exam total mark and gp
						$exam_total_mark = array_map('floatval', $exam_total_mark);
						$final_total_mark = array_sum($exam_total_mark);

						$exam_total_gp = array_map('floatval', $exam_total_gp);
						$exam_total_gp = array_sum($exam_total_gp);

						// Get calculate exam gp
						if($academic_skill){
							$total_subject       = count($subject_array);
							$total_subject = $total_subject + count($skill_array);
						}else{
							$total_subject = count($subject_array);
						}
						$final_grading_point = round(($exam_total_gp / $total_subject), 2);
						$final_letter_grade  = get_letter_grade($final_grading_system, $final_grading_point, $school_id);

						$academic_entry['exams'][$last_exam_index]['total_mark'] = $final_total_mark;
						$academic_entry['exams'][$last_exam_index]['total_subject'] = $total_subject;
						$academic_entry['exams'][$last_exam_index]['final_gp']   = $final_grading_point;
						$academic_entry['exams'][$last_exam_index]['final_grade'] = $final_letter_grade;

						$remark_data = $remark_model->getRemarks('remark', $exam_id, $session_id, $academic_id, $school_id);
						if($remark_data){
							$teacher_remark = $remark_data->remark;
						}else{
							$teacher_remark = '';
						}
						$academic_entry['exams'][$last_exam_index]['teacher_remark'] = $teacher_remark;

						$principal_remarks_data = $remark_model->getRemarks('principal_remarks', $exam_id, $session_id, $academic_id, $school_id);
						if($principal_remarks_data){
							$principal_remarks = $principal_remarks_data->principal_remarks;
						}else{
							$principal_remarks = '';
						}
						$academic_entry['exams'][$last_exam_index]['principal_remark'] = $principal_remarks;

						if($show_attendance_info){
							$school_days  = $remark_model->getRemarks('school_days', $exam_id, $session_id, $academic_id, $school_id);
							$present_days = $remark_model->getRemarks('present_days', $exam_id, $session_id, $academic_id, $school_id);
							$absent_days  = $remark_model->getRemarks('absent_days', $exam_id, $session_id, $academic_id, $school_id);

							$academic_entry['exams'][$last_exam_index]['attendance'] = [
								'school_days'   => $school_days,
								'present_days'  => $present_days,
								'absent_days'   => $absent_days
							];
						}
					}

				}

				// Store each academic record properly
				$academic_data[] = $academic_entry;
			}
			
		}

		return $academic_data;
	}
}


// Get student saubject data from all exam
if (!function_exists('exam_subject_summary')) {
	function exam_subject_summary($academic_data){
		
		$exam_subject_summary = [];
		if (!empty($academic_data)) {
			$exam_subject_summary = [];
			$max_marks_per_subject = 100; // Define max marks for each subject
		
			// Get the summary of each subject
			foreach ($academic_data as $academic) {
				$exam_data = $academic['exams'];
				$subject_summary = [];
		
				foreach ($exam_data as $exam) {
					foreach ($exam['result'] as $subject_result) {
						foreach ($subject_result as $subject => $score) {
							if ($subject === 'highest_mark' || $subject === 'gp' || $subject === 'grade') {
								continue;
							}
		
							// Initialize if not exists
							if (!isset($subject_summary[$subject])) {
								$subject_summary[$subject] = [
									'total_marks' => 0,
									'highest_mark' => 0,
									'total_gp' => 0,
									'count' => 0,
									'grades' => []
								];
							}
		
							// Convert empty values to 0
							$mark = is_numeric($score) ? $score : 0;
							$gp = isset($subject_result['gp']) ? $subject_result['gp'] : 0;
							$grade = isset($subject_result['grade']) ? $subject_result['grade'] : '';
		
							// Add values
							$subject_summary[$subject]['total_marks'] += $mark;
							$subject_summary[$subject]['highest_mark'] = max($subject_summary[$subject]['highest_mark'], $mark);
							$subject_summary[$subject]['total_gp'] += $gp;
							$subject_summary[$subject]['count']++;
		
							// Store grade frequency
							if ($grade) {
								if (!isset($subject_summary[$subject]['grades'][$grade])) {
									$subject_summary[$subject]['grades'][$grade] = 0;
								}
								$subject_summary[$subject]['grades'][$grade]++;
							}
						}
					}
				}
		
				// Compute final summary
				$final_summary = [];
				foreach ($subject_summary as $subject => $data) {
					$avg_mark   = $data['count'] > 0 ? round($data['total_marks'] / $data['count'], 2) : 0;
					$percentage = ($avg_mark / $max_marks_per_subject) * 100;
		
					$final_summary[$subject] = [
						'avg_mark' => $avg_mark,
						'highest_mark' => $data['highest_mark'],
						'avg_gp' => $data['count'] > 0 ? round($data['total_gp'] / $data['count'], 2) : 0,
						'most_common_grade' => !empty($data['grades']) ? array_search(max($data['grades']), $data['grades']) : 'N/A',
						'percentage' => round($percentage, 2)
					];
				}
		
				// Merge all summaries for multiple academic records
				$exam_subject_summary = array_merge_recursive($exam_subject_summary, $final_summary);
			}
		} else {
			$exam_subject_summary = [];
		}
		
		return $exam_subject_summary;
	}
}

// exam_summary function
if (!function_exists('exam_summary')) {
	function exam_summary($academic_data){
		
		$exam_summary = [];
		if (!empty($academic_data)) {
			
		
			// Loop through each academic record
			foreach ($academic_data as $academic) {
				$exam_data = $academic['exams'];
		
				foreach ($exam_data as $exam) {
					$exam_title = $exam['exam_title'];
					$total_mark = isset($exam['total_mark']) ? $exam['total_mark'] : 0;
		
					// Store the exam summary
					$exam_summary[] = [
						'exam_title' => $exam_title,
						'total_mark' => $total_mark
					];
				}
			}
		} else {
			$exam_summary = [];
		}		
		
		return $exam_summary;
	}
}

// attendance summary function
if (!function_exists('attendance_summary')) {
	function attendance_summary($academic_data){
		
		if (!empty($academic_data)) {
			$attendance_summary = [
				'total_school_days' => 0,
				'total_present_days' => 0,
				'total_absent_days' => 0,
				'students' => []
			];
		
			// Iterate through each student's academic data
			foreach ($academic_data as $academic) {
				if (isset($academic['exams']) && is_array($academic['exams'])) {
					foreach ($academic['exams'] as $exam) {
						if (!isset($exam['attendance'])) {
							continue;
						}
		
						$attendance = $exam['attendance'];
		
						// Extract attendance values
						$school_days = isset($attendance['school_days']->school_days) ? intval($attendance['school_days']->school_days) : 0;
						$present_days = isset($attendance['present_days']->present_days) ? intval($attendance['present_days']->present_days) : 0;
						$absent_days = isset($attendance['absent_days']->absent_days) ? intval($attendance['absent_days']->absent_days) : 0;
		
						// Store student-wise attendance
						$attendance_summary['students'][] = [
							'exam_title' => $exam['exam_title'],
							'school_days' => $school_days,
							'present_days' => $present_days,
							'absent_days' => $absent_days,
							'attendance_percentage' => ($school_days > 0) ? round(($present_days / $school_days) * 100, 2) . '%' : 'N/A'
						];
		
						// Accumulate totals
						$attendance_summary['total_school_days'] += $school_days;
						$attendance_summary['total_present_days'] += $present_days;
						$attendance_summary['total_absent_days'] += $absent_days;
					}
				}
			}
		
			// Compute overall attendance percentage
			$attendance_summary['overall_attendance_percentage'] = ($attendance_summary['total_school_days'] > 0) ? 
				round(($attendance_summary['total_present_days'] / $attendance_summary['total_school_days']) * 100, 2) . '%' : 'N/A';
		
		} else {
			$attendance_summary = [];
		}
		
		return $attendance_summary;
	}
}

