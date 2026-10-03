<?php 
use App\Models\FieldbuilderModel;

// Get School List
$school_field = get_school_field(lang('School.select_school'), 'school_id', 'field_school', '', '', true);

// Get Custom Fields
$field_model = new FieldbuilderModel();
$section_id = get_item('id', 'fields_section', 'title', 'Student');
$fields = $field_model->where('school_id', $school_id)->where('section', $section_id)->orderBy('field_order', 'ASC')->findAll();

// Get Setting Value from school
$student_account      = esc(get_school_value($school_id, 'student_account', true));
$academic_roll        = esc(get_school_value($school_id, 'academic_roll', true));
$student_phone        = esc(get_school_value($school_id, 'student_phone', true));
$parent_account       = esc(get_school_value($school_id, 'parent_account', true));
$academic_category    = esc(get_school_value($school_id, 'academic_category', true));
$academic_group       = esc(get_school_value($school_id, 'academic_group', true));
$academic_section     = esc(get_school_value($school_id, 'academic_section', true));
$academic_grade       = esc(get_school_value($school_id, 'academic_grade', true));
$academic_shift       = esc(get_school_value($school_id, 'academic_shift', true));
$academic_department  = esc(get_school_value($school_id, 'academic_department', true));
$academic_skill       = esc(get_school_value($school_id, 'academic_skill', true));
$academic_house       = esc(get_school_value($school_id, 'academic_house', true));
$academic_version     = esc(get_school_value($school_id, 'academic_version', true));

$csv_file_name = 'student_'.date('Y-m-d');
?>

<div class="right_col" role="main">

    <div class="row">
        <div class="col-md-6 col-sm-6 ">
            <h3><i class="fa fa-upload"></i> <?= lang('Bulkstudent.page_title_form'); ?></h3>
        </div>

        <div class="col-md-6 col-sm-6 text-right pt-2">
            <button type="submit" class="btn btn-sm btn-success add"><i class="fa fa-pencil-square-o"></i> <?= lang('Mark.btn_get_input') ?></button>
        </div>
    </div>

    <div class="clearfix"></div>

    <div class="row">
        <div class="col-sm-12 col-md-12   ">
            <div class="x_panel">
                <div class="x_content">
                    <div class="col-sm-3 mb-3">
                        <?php
                        // Get school
                        $school_title = get_item('name', 'schools', 'id', $school_id);
                        ?>
                        <b><?= lang('Mark.field_school').': '.$school_title; ?></b>
                    </div>
                </div>
            </div>
        </div>
    </div>
    

    <div class="card p-2 mb-2">
        <div class="body ">
            <?= get_system_message(); ?>
            
            <h6 class="card-title"><?= lang('Mark.csv_form_title') ?></h6>

            <div class="x_title-none">
                <span>Please <b>download the CSV</b> file prepared for upload. Make sure the first row is set as the header. </span>
                <ul class="nav navbar-right panel_toolbox">
                    <li><button class="download_csv btn btn-sm btn-info"><i class="fa fa-file-excel-o"></i> Download CSV</button></li>
                </ul>
                <div class="clearfix"></div>
            </div>

            <?= form_open('bulkstudent/save-students', [
                'class'   => 'form-horizontal form-label-left', 
                'id'      => 'form', 
                'method'  => 'post', 
                'enctype' => 'multipart/form-data',
                'data-parsley-validate'=>'' 
                ]); 
            ?>

            <input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />

            <div class="input-group mb-3">
                <div class="input-group-prepend">
                <input type="file" name="student_csv" class="form-control" id="student_csv">
                </div>
                <button class="btn btn-success" type="submit"><i class="fa fa-upload"></i> Upload</button>
            </div>

            <?= form_close() ?>
            
            <p class="help-block "> Please upload a CSV file and ensure the first row is set as the header. The following fields are required: 
                <br><b style="color: red;">
                <?= lang('Student.first_name') ?>, 
                <?= lang('Student.last_name') ?>, 
                <?php 
                    foreach ($fields as $key => $field) {
                        $field_title        = $field->title;
                        echo $field_title.', ';
                    }
                ?> 
                <?= lang('Bulkstudent.school_id') ?>, 
                <?= lang('Bulkstudent.session_id') ?>, 
                <?php 
                if($academic_roll){
                    echo lang('Student.field_roll').', ';
                }

                // Get academic sesion
                echo lang('Bulkstudent.session_id').', ';
                echo lang('Bulkstudent.class_id').', ';
                echo lang('Bulkstudent.subject_id').', ';

                if($academic_grade){
                    echo lang('Bulkstudent.grade_level_id').', ';
                }
                if($academic_group){
                    echo lang('Bulkstudent.group_id').', ';
                }
                if($academic_house){
                    echo lang('Bulkstudent.house_id').', ';
                }
                if($academic_version){
                    echo lang('Bulkstudent.version_id').', ';
                }
                if($academic_section){
                    echo lang('Bulkstudent.section_id').', ';
                }
                if($academic_category){
                    echo lang('Bulkstudent.category_id').', ';
                }   
                if($academic_shift){
                    echo lang('Bulkstudent.shift_id').', ';
                }
                if($academic_department){
                    echo lang('Bulkstudent.department_id').', ';
                }
                if($academic_skill){
                    echo lang('Bulkstudent.skill_id').'';
                }
                ?>
                
                </b>
            </p> 
        
        </div>
    </div>

    <div class="row" style="display: none;">
        <div class="col-md-12 col-sm-12 ">
        <table id="csv_table" class="table table-bordered">
            <thead>
                <tr>
                    <th><?= lang('Student.first_name') ?></th>
                    <th><?= lang('Student.last_name') ?></th>

                    <?php 
                    foreach ($fields as $key => $field) {
                        $field_title        = $field->title;
                        echo '<th>'.$field_title.'</th>';
                    }
                    ?>

                    <th><?= lang('Bulkstudent.school_id') ?></th>
                    <?php 

                    if($academic_roll){
                        echo '<th>'.lang('Bulkstudent.roll_no').'</th>';
                    }

                    // Get academic sesion
                    echo '<th>'.lang('Bulkstudent.session_id').'</th>';
                    echo '<th>'.lang('Bulkstudent.class_id').'</th>';
                    echo '<th>'.lang('Bulkstudent.subject_id').'</th>';

                    if($academic_grade){
                        echo '<th>'.lang('Bulkstudent.grade_level_id').'</th>';
                    }

                    if($academic_group){
                        echo '<th>'.lang('Bulkstudent.group_id').'</th>';
                    }

                    if($academic_house){
                        echo '<th>'.lang('Bulkstudent.house_id').'</th>';
                    }

                    if($academic_version){
                        echo '<th>'.lang('Bulkstudent.version_id').'</th>';
                    }

                    if($academic_section){
                        echo '<th>'.lang('Bulkstudent.section_id').'</th>';
                    }

                    if($academic_category){
                        echo '<th>'.lang('Bulkstudent.category_id').'</th>';
                    }   

                    if($academic_shift){
                        echo '<th>'.lang('Bulkstudent.shift_id').'</th>';
                    }

                    if($academic_department){
                        echo '<th>'.lang('Bulkstudent.department_id').'</th>';
                    }

                    if($academic_skill){
                        echo '<th>'.lang('Bulkstudent.skill_id').'</th>';
                    }

                    ?>
                    
                </tr>
            </thead>
        </table>
        </div>
    </div>
    
    
</div>


<script type="text/javascript">
    $(document).ready(function() {
    
    // CSV download
    $(".download_csv").click(function() {
            let csv = [];
            $("#csv_table tr").each(function() {
                let row = [];
                $(this).find('th').each(function() {
                    let text = $(this).text().trim();
                    row.push(text);
                });
                csv.push(row.join(","));  // Join each cell with a comma
            });
            let csvContent = csv.join("\n");  // Join each row with a new line
            
            // Create a Blob for the CSV content
            let blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
            let url = URL.createObjectURL(blob);
            
            // Create a link to download the CSV
            let hiddenElement = document.createElement('a');
            hiddenElement.href = url;
            hiddenElement.target = '_blank';
            hiddenElement.download = '<?= $csv_file_name ?>.csv';
            hiddenElement.click();

            // Clean up the URL object
            URL.revokeObjectURL(url);
        });

    });
</script>