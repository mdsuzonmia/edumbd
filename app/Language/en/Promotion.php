<?php

return [
    // Page titles
    'page_title'                 => 'Student Promotion',
    'page_title_list'            => 'Promote Students',
    'page_title_preview'         => 'Preview Eligible Students',
    'page_title_history'         => 'Promotion History',
    'page_title_details'         => 'Promotion Details',

    // Form fields
    'field_school'               => 'School',
    'field_select_school'        => 'Select School',
    'field_from_session'         => 'From Session/Year',
    'field_select_from_session'  => 'Select From Session',
    'field_to_session'           => 'To Session/Year',
    'field_select_to_session'    => 'Select To Session',
    'field_from_class'           => 'From Class',
    'field_select_from_class'    => 'Select From Class',
    'field_to_class'             => 'To Class',
    'field_select_to_class'      => 'Select To Class',
    'field_roll_no'              => 'Roll No',
    'field_notes'                => 'Notes',

    // Buttons
    'btn_preview'                => 'Preview Students',
    'btn_promote'                => 'Promote Selected Students',
    'btn_promote_all'            => 'Promote All',
    'btn_rollback'               => 'Rollback',
    'btn_view_details'           => 'View Details',
    'btn_back'                   => 'Back to History',
    'btn_back_to_promotion'      => 'Back to Promotion',

    // Table headers
    'th_sl'                      => '#',
    'th_student_code'            => 'Student Code',
    'th_student_name'            => 'Student Name',
    'th_current_class'           => 'Current Class',
    'th_current_session'         => 'Current Session',
    'th_roll_no'                 => 'Roll No',
    'th_new_roll_no'             => 'New Roll No',
    'th_status'                  => 'Status',
    'th_date'                    => 'Date',
    'th_total'                   => 'Total',
    'th_success'                 => 'Success',
    'th_failed'                  => 'Failed',
    'th_action'                  => 'Action',
    'th_from'                    => 'From',
    'th_to'                      => 'To',
    'th_error'                   => 'Error',

    // Messages
    'msg_select_school_first'    => 'Please select a school first.',
    'msg_select_session_class'   => 'Please select from session and class.',
    'msg_no_eligible_students'   => 'No eligible students found for promotion.',
    'msg_promotion_success'      => 'Students promoted successfully!',
    'msg_promotion_partial'      => 'Promotion completed with some failures.',
    'msg_rollback_success'       => 'Promotion rolled back successfully!',
    'msg_rollback_failed'        => 'Failed to rollback promotion.',
    'msg_select_students'        => 'Please select at least one student to promote.',
    'msg_confirm_promote'        => 'Are you sure you want to promote the selected students?',
    'msg_confirm_rollback'       => 'Are you sure you want to rollback this promotion? This action cannot be undone.',
    'msg_no_history'             => 'No promotion history found.',
    'msg_student_already_enrolled' => 'Student is already enrolled in the target session/class.',
    'msg_invalid_school'         => 'Invalid school selected.',

    // Status labels
    'status_completed'           => 'Completed',
    'status_partial'             => 'Partial',
    'status_rolled_back'         => 'Rolled Back',
    'status_success'             => 'Success',
    'status_failed'              => 'Failed',

    // Info
    'info_eligible_count'        => 'Total eligible students: {count}',
    'info_promotion_summary'     => 'Promotion Summary',
    'info_from_to'               => 'From {from_class} ({from_session}) → To {to_class} ({to_session})',
];