<?php

use App\Models\MenuModel;
use App\Models\SchoolModel;
use App\Models\FielddataModel;
use App\Models\DocumentdataModel;
use App\Models\YearModel;
use App\Models\CategoryModel;
use App\Models\ShiftModel;
use App\Models\DepartmentModel;
use App\Models\ClassModel;
use App\Models\GradecatModel;
use App\Models\MdistributionModel;
use App\Models\AcademicsModel;
use App\Models\MarkModel;
use App\Models\SkillvalueModel;
use App\Models\GradingSystemModel;
use App\Models\ResultModel;
use App\Models\ModuleModel;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

use Picqer\Barcode\BarcodeGeneratorPNG;


if (! function_exists('result_switch'))
{
    function result_switch(
        string $name,
        string $label,
        bool|int $value = 0,
        string $description = ''
    ): string
    {
        $checked = $value ? 'checked' : '';

        $html = '
        <div class="col-md-6 mb-3">
            <div class="setting-item">
                <div>
                    <h6 class="mb-1">' . esc($label) . '</h6>';

        if (! empty($description)) {
            $html .= '
                    <small class="text-muted">
                        ' . esc($description) . '
                    </small>';
        }

        $html .= '
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input"
                           type="checkbox"
                           name="' . esc($name) . '"
                           value="1"
                           ' . $checked . '>
                </div>
            </div>
        </div>';

        return $html;
    }
}

	if (!function_exists('generate_barcode')) {
		/**
		 * Generate a barcode from a number
		 *
		 * @param string $number
		 * @return string Base64 encoded barcode image
		 */
		function generate_barcode(string $number, int $width = 5, int $height = 70): string
		{
			// Create a new Barcode Generator
			$generator = new BarcodeGeneratorPNG();

			// Generate the barcode as a PNG image (Base64 encoded)
			$barcodeImage = base64_encode($generator->getBarcode($number, $generator::TYPE_CODE_128, $width, $height));
	
			// Create HTML structure for barcode image and number text
			$html = '<div class="barcode-container">';
			$html .= '<img class="barcode" src="data:image/png;base64,' . $barcodeImage . '" alt="Barcode" />';
			$html .= '<br><span class="idnumber">' . htmlspecialchars($number) . '</span></div>';
	
			return $html;
		}
	}

	if (!function_exists('generate_qr_code')) {
		/**
		 * Generate a QR Code
		 *
		 * @param string $data The data to encode in the QR code
		 * @param int $size The size of the QR code in pixels
		 * @return string Base64 encoded QR code image
		 */
		function generate_qr_code(string $data, int $size = 100): string
		{
			// Instantiate the QrCode object
			//$qrCode = new QrCode($data);

			// Create QR code
			$qrCode = new QrCode(
				data: $data,
				size: $size,
				margin: 0
			);


			// Create a PNG writer
			$writer = new PngWriter();
			$result = $writer->write($qrCode);
	
			// Return Base64 encoded QR code image
			return 'data:image/png;base64,' . base64_encode($result->getString());
		}
	}

	/**
	 * System message generator 
	 */
	if (!function_exists('message_generator')) {
		function message_generator($type, $message)
		{
			$output = '';

			if($type == 'error'){ 
				$output .= '<div class="alert alert-danger alert-dismissible fade show" role="alert" >';
				$output .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
				$output .= $message;                    
				$output .= '</div>';
			}

			// Success
			if($type == 'success'){ 
				$output .= '<div class="alert alert-success alert-dismissible fade show" role="alert" >';
				$output .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
				$output .= $message;                    
				$output .= '</div>';
			} 

			return $output;
		}
	}

	/**
	 * System message
	 */
	if (!function_exists('get_system_message')) {
		function get_system_message()
		{
			$session = session();

			$types = [
				'error'   => 'warning',
				'warning' => 'warning',
				'info'    => 'info',
				'danger'  => 'danger',
				'success' => 'success',
			];

			$output = '';

			foreach ($types as $key => $class) {
				$message = $session->get($key);

				if (!empty($message)) {
					$output .= sprintf(
						'<div class="alert alert-%s alert-dismissible fade show" role="alert">%s
							<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
						</div>',
						esc($class),
						esc($message)
					);
				}
			}

			return $output;
		}
	}

    /**
	** Get Item
	**/
	if (!function_exists('get_item')) {
		function get_item($select_field, $table, $where_field, $where_value){
			$school_id   = session()->get('school_id');
			$db = \Config\Database::connect();
			// Generate the query
			$query = $db->table($table)
						->select('*')
						->where($where_field, $where_value)
						->get();
					
			$result = $query->getRow();
			
			if ($result) {
				$output = $result->$select_field;
			} else {
				$output = '';
			}
			return $output;
		}
	}

	/**
	 * Side Menu
	 */
	if (!function_exists('get_menu')) {
		function get_menu(){
			
			$model      = new MenuModel();
			$menu_items = $model->orderBy('menu_order', 'ASC')->findAll();

			$module_model = new ModuleModel();
            $module_data  = $module_model->where('sidebar_menu', 1)->where('status', 1)->findAll();

			// Get current link segment
			$uri = service('uri');
			$segment = $uri->getSegment(1);

			
			$role = session()->get('role_id');

			$output = '';
			foreach ($menu_items as $item) {
				
				$menu_title      = $item['title'];
				$menu_link       = $item['link'];
				$menu_icon       = $item['menu_icon'];
				$parent          = $item['parent'];
				$child           = $item['child'];
				$access          = $item['access'];
				$access_ids      = explode(",",$access);
				$child_ids       = explode(",",$child);

				$school_id       = session()->get('school_id');
				$academic_skill  = esc(get_school_value($school_id, 'academic_skill', true));

				if($segment == $menu_link){
					$active = ' active';
				}else{
					$active = '';
				}

				if (in_array($role, $access_ids) && $parent == 0) {
					
					$output .='<li class="nav-item">';
					if($menu_title == 'module_position'){
						
						if($module_data){
							foreach($module_data as $module){
								$output .= module_menu($module);
							}
						}
					}else{

						
						// child item right icon for each main item
						if (!empty($child)) {
							$output .='<a class=" '.$active.' nav-link d-flex justify-content-between align-items-center" href="javascript:void(0)" onclick="toggleSubmenu(\'studentsSubmenu\', this)">';
							$output .='<span><i class=" '.$menu_icon.' me-2"></i> '.lang('System.'.$menu_title).'</span>';
							$output .='<i class="bi bi-chevron-down small chevron"></i>';
						}else{
							$output .='<a class="'.$active.' nav-link" href="'.base_url($menu_link).'">';
							$output .='<i class=" '.$menu_icon.' me-2"></i>';
							$output .=lang('System.'.$menu_title);
						}
						$output .='</a>';
					}
					
					

					// start child item checker
					if (!empty($child)) {
						$output .='<ul class="submenu">';
						foreach ($child_ids as $value) {
							$sub_title            = $model->getMenuValue('title', $value);
							$sub_link             = $model->getMenuValue('link', $value);
							$sub_menu_icon        = $model->getMenuValue('menu_icon', $value);
							$sub_menu_access      = $model->getMenuValue('access', $value);
							$sub_menu_access_ids  = explode(",",$sub_menu_access);
							$sub_depent           = $model->getMenuValue('depent', $value);

							// Get active class for submenu
							if($segment == $sub_link){
								$active = ' active';
							}else{
								$active = '';
							}
							// check access
							if (in_array($role, $sub_menu_access_ids)) {
								if($sub_depent){
									if($academic_skill){
									$output .='<li class="nav-item">';
										$output .='<a class="'.$active.' nav-link"  href="'.base_url().$sub_link.'">';
											//$output .='<i class="fa '.$sub_menu_icon.' me-2"></i>';
											$output .='<span>'.lang('System.'.$sub_title).'</span>';
										$output .='</a>';
									$output .='</li>';
									}
								}else{
									$output .='<li class="nav-item">';
										$output .='<a class="'.$active.' nav-link" href="'.base_url().$sub_link.'">';
											//$output .='<i class="fa '.$sub_menu_icon.' me-2"></i>';
											$output .='<span>'.lang('System.'.$sub_title).'</span>';
										$output .='</a>';
									$output .='</li>';
								}
							}
						} // end submenu loop
						$output .='</ul>';
					} // end child checker

					$output .='</li>';
				}else{
					
				}

			} // end main menu loop

			return $output;
		}
	}

	
	/**
	** Function to create enable/disable dropdown
	**/
	if (!function_exists('create_enable_dropdown')) {
		function create_enable_dropdown($field_name, $field_id, $value) {

			$array = array(
				'0' => lang('Common.sys_disabled'),
				'1' => lang('Common.sys_enabled')
			);
			$data = [
				'id'    => $field_id,
				'class' => 'form-control mb-1'
			];
			return form_dropdown($field_name, $array, $value, $data);
		}
	}

	/**
	** Get enable/disable
	**/
	if (!function_exists('get_enable_disable')) {
		function get_enable_disable($value) {

			$array = array(
				'0' => lang('Common.sys_disabled'),
				'1' => lang('Common.sys_enabled')
			);
			$output = $array[$value];
			return $output;
		}
	}


	/**
	** Function to create yes/no dropdown
	**/
	if (!function_exists('yes_no_dropdown')) {
		function yes_no_dropdown($field_name, $field_id, $value) {

			$array = array(
				'0' => lang('Common.sys_no'),
				'1' => lang('Common.sys_yes')
			);
			$data = [
				'id'    => $field_id,
				'class' => 'form-control mb-1'
			];
			return form_dropdown($field_name, $array, $value, $data);
		}
	}

	/**
	** Get yes no
	**/
	if (!function_exists('get_yes_no')) {
		function get_yes_no($value) {
			$array = array(
				'0' => lang('Common.sys_no'),
				'1' => lang('Common.sys_yes')
			);
			$output = $array[$value];
			return $output;
		}
	}

	/**
	 * Get Status 
	 */
	if (!function_exists('get_status')) {
		function get_status(){
			$output = array(
				'0' => lang('Common.sys_unpublished'), 
				'1' => lang('Common.sys_published'),
				'2' => lang('Common.text_trash')
			);
			return $output;
		}
	}

	/**
	 * Get Status List
	 */
	if (!function_exists('get_status_list')) {
		function get_status_list($label, $field_name, $field_id, $value, $required = false, $filter = false, $class = ''){	
			$star = ($required) ? '<span class="required">*</span>': '';
			
			

			if($filter){
				$status_label = array(
					'' => $label
				);
				$status_array = get_status();
				// join array
				$status_array = array_merge($status_label, $status_array);

			}else{
				$status_array = get_status();
			}
			$status_data = [
				'id'    => $field_id,
				'class' => 'form-control mb-3 '. $class,
			];
			if($required){
				$status_data['required'] = 'required';
			}

			if($filter){
				$output = form_dropdown($field_name, $status_array, $value, $status_data);
			}else{
				$output = '
					<label class="form-label " for="'.$field_id.'">'.$label.' '.$star.'</label>
					'.form_dropdown($field_name, $status_array, $value, $status_data).'
				';
			}
			
			return $output;
		}
	}

	
	/**
	** Create list by object/array
	**/
	if (!function_exists('get_list')) {
		function get_list($object_list, $field_name, $value, $option_label, $multiple = false){
			if($multiple){
				$multiple_att = 'multiple="multiple"';
				$field_naming = $field_name.'[]';
			}else{
				$multiple_att = '';
				$field_naming = $field_name;
			}

			$output = '<select name="'.$field_naming.'" id="field_'.$field_name.'" class="form-control mb-3" '.$multiple_att.' >';
			if($option_label){
				$output .= '<option value="">'.$option_label.'</option>';
			}
			foreach ($object_list as $key => $item) {
				if ($key == $value ) {
					$output .= '<option selected="selected" value="'.$key.'">'.$item.'</option>';
				}else{
					$output .= '<option value="'.$key.'">'.$item.'</option>';
				}
			}
			$output .= '</select>';
			return $output;
		}
	}

	/**
	 * Get academic list
	 */
	if (!function_exists('get_academic_list')) {
		function get_academic_list($label, $field_name, $value, $option_label, $model, $multiple = false, $required = false, $enabled_school = true, $class = ''){

			// Get school id from session
            $school_id    = session()->get('school_id'); 

			if($enabled_school){
				$items = $model->where('school_id', $school_id)->orderBy('id', 'ASC')->findAll();
			}else{
				$items = $model->orderBy('id', 'ASC')->findAll();
			}

			$star = ($required) ? '<span class="required">*</span>': '';

			$output = '';
			$options = [];

			if($option_label){
				$options[''] = $option_label;
			}
			
			foreach ($items as $item) {
				$options[$item->id] = $item->title;
			}

			// Generate dropdown
			if($multiple){
				$field_id = $field_name;
				$field_name = $field_name.'[]';
				$data = [
					'id' => 'field_'.$field_id,
					'class' => 'form-control mb-3 '. $class,
				];
				if($required){
					$data['required'] = 'required';
				}
				if($label){
					$output .='<div class="row form-group">
						<label class="col-form-label col-sm-4 control-label" for="field_'.$field_name.'">'.$label.' '.$star.'</label>
						<div class="col-sm-8">'.form_multiselect($field_name, $options, $value, $data).'</div>
					</div>';
				}else{
					$output .= form_multiselect($field_name, $options, $value, $data);
				}
				
			}else{
                $data = [
					'id' => 'field_'.$field_name,
					'class' => 'form-control mb-1 '.$class,
				];
				if($required){
					$data['required'] = 'required';
				}

				if($label){
				    $output .='<div class="row form-group">
							<label class="col-form-label col-sm-4 control-label" for="field_'.$field_name.'">'.$label.' '.$star.'</label>
							<div class="col-sm-8">'.form_dropdown($field_name, $options, $value,$data).'</div>
						</div>';
				}else{
					$output .=form_dropdown($field_name, $options, $value,$data);
				}
			}
			
			return $output;
		}

	}

	if (!function_exists('deleteFolder')) {
		function deleteFolder($folderPath) {
			if (!is_dir($folderPath)) {
				return false; // Folder does not exist
			}
	
			$files = array_diff(scandir($folderPath), ['.', '..']);
			foreach ($files as $file) {
				$filePath = $folderPath . DIRECTORY_SEPARATOR . $file;
				if (is_dir($filePath)) {
					deleteFolder($filePath); // Recursive delete for subfolders
				} else {
					unlink($filePath); // Delete file
				}
			}
	
			return rmdir($folderPath); // Remove empty folder
		}
	}

	/**
	 * Get School Field 
	 */
	if (!function_exists('get_school_field')) {
		function get_school_field($label, $field_name, $field_id, $field_class, $field_value, $required){
			$output = '';
			$school_model = new SchoolModel();
			$school_items = $school_model->where('status', 1)->orderBy('id', 'ASC')->findAll();
			$school_options = [];
			$school_options[''] = $label;
			foreach ($school_items as $item) {
				$school_options[$item->id] = $item->name;
			}
			$school_data = [
				'id' => $field_id,
				'class' => 'form-control mb-3 '. $field_class
			];

			if($required){
				$school_data['required'] = 'required';
			}

			$output = form_dropdown($field_name, $school_options, $field_value, $school_data);
			return $output;
		}
	}

	/**
	 * Get List Show 
	 */
	if (!function_exists('get_list_show')) {
		function get_list_show($label, $field_name, $field_id, $field_class, $field_value, $required){
			$output = '';
			$show_list_array = array(
				''  => $label, 
				'10' => '10', 
				'50' => '50',
				'100' => '100',
				'200' => '200',
				'500' => '500',
				'1000' => '1000'
			);
			$show_list_data = [
				'id'    => $field_id,
				'class' => 'form-control mb-3 '. $field_class
			];

			if($required){
				$show_list_data['required'] = 'required';
			}
		
			$output = form_dropdown($field_name, $show_list_array, $field_value, $show_list_data);
			return $output;
		}
	}

	/**
	 * Get Text Field 
	 */
	if (!function_exists('field_text')) {
		function field_text($label, $field_name, $field_id, $field_class, $field_value, $required, $help = '', $placeholder = '', $horizontal = false){
			$output = '';
			$star = ($required) ? '<span class="required">*</span>': '';
			$param = [
				'type'     => 'text',
				'name'     => $field_name,
				'id'       => $field_id,
				'class'    => 'form-control '.$field_class,
				'value'    => $field_value,
				'placeholder' => $placeholder
			];
			if($required){
				$param['required'] = 'required';
			}

			if($help){
				$help = '<label>'.$help.'</label>';
			}

			if(!$horizontal){
				$output .='<div class="row form-group">
					<label class="col-form-label col-sm-4 control-label" for="'.$field_id.'">'.$label.' '.$star.'</label>
					<div class="col-sm-8">'.form_input($param). $help.'</div>
				</div>';
			}else{
				$output .='<div class="row form-group">
					<label class="col-form-label col-sm-12 control-label" for="'.$field_id.'">'.$label.' '.$star.'</label>
					<div class="col-sm-12">'.form_input($param). $help.'</div>
				</div>';
			}
			return $output;
		}
	}

	// Get field_dropdown
	if (!function_exists('field_dropdown')) {
		function field_dropdown($label, $field_name, $field_id, $field_class, $options, $value, $required, $help = ''){
			$output = '';
			$star = ($required) ? '<span class="required">*</span>': '';
			$param = [
				'name'     => $field_name,
				'id'       => $field_id,
				'class'    => 'form-control '.$field_class
			];

			
			if($required){
				$param['required'] = 'required';
			}

			if($help){
				$help = '<label>'.$help.'</label>';
			}

			if($label){
				$output .='<div class="row form-group">
					<label class="col-form-label col-sm-4 control-label" for="'.$field_id.'">'.$label.' '.$star.'</label>
					<div class="col-sm-8">'.form_dropdown($field_name, $options, $value, $param). $help.'</div>
				</div>';
			}else{
				$output .= form_dropdown($field_name, $options, $value, $param). $help;
			}
			return $output;
			
		}
	}

	/**
	 * Get Textarea Field 
	 */
	if (!function_exists('field_textarea')) {
		function field_textarea($label, $field_name, $field_id, $field_class, $field_value, $required, $help = '', $placeholder = ''){
			$output = '';
			$star = ($required) ? '<span class="required">*</span>': '';
			$param = [
				'name'        => $field_name,
				'id'          => $field_id,
				'rows'        => '4',
				'cols'        => '50',
				'class'       => 'form-control'.$field_class,
				'value'       => $field_value,
				'placeholder' => $placeholder
			];
			if($required){
				$param['required'] = 'required';
			}

			if($help){
				$help = '<label>'.$help.'</label>';
			}
			$output .='<div class="row form-group">
				<label class="col-form-label col-sm-4 control-label" for="'.$field_id.'">'.$label.' '.$star.'</label>
				<div class="col-sm-8">'.form_textarea($param). $help.'</div>
			</div>';
			return $output;
		}
	}


	/**
	 * Get Field Data
	 */
	if (!function_exists('get_field_data')) {
		function get_field_data($select_field, $field_id, $section_id, $item_id){
            $field_data_model = new FielddataModel();
			$result = $field_data_model->where('field_id', $field_id)
					->where('section_id', $section_id)
					->where('item_id', $item_id)
                    ->get()
                    ->getRow();

			if ($result) {
				$output = $result->$select_field;
			} else {
				$output = '';
			}
			return $output;
		}
	}

	/**
	 * Get Document Field Data
	 */
	if (!function_exists('get_document_field_data')) {
		function get_document_field_data($select_field, $field_id, $section_id, $item_id){
            $field_data_model = new DocumentdataModel();
			$result = $field_data_model->where('field_id', $field_id)
					->where('section_id', $section_id)
					->where('item_id', $item_id)
                    ->get()
                    ->getRow();

			if ($result) {
				$output = $result->$select_field;
			} else {
				$output = '';
			}
			return $output;
		}
	}

	/**
	 * Get Field item
	 */
	if (!function_exists('get_field_item')) {
		function get_field_item($field, $field_id, $section_id){
			$db = \Config\Database::connect();
			// Generate the query
			$query = $db->table('fields')
						->select($field)
						->where('id', $field_id)
						->where('section', $section_id)
						->get();
			$result = $query->getRow();
			if ($result) {
				$output = $result->$field;
			} else {
				$output = '';
			}
			return $output;
		}
	}


	/**
	 * Get Custom Field data save
	 */
	if (!function_exists('save_field_data')) {
		function save_field_data($field_id, $section_id, $item_id, $field_value){

			$field_data_model = new FielddataModel();

			$field_data = [
				'field_id'     => $field_id,
				'section_id'   => $section_id,
				'item_id'      => $item_id,
				'data'         => $field_value,
				'school_id'    => session()->get('school_id')
			];

			// get field exit value
			$field_data_id = get_field_data('id', $field_id, $section_id, $item_id);
			if($field_data_id){
                // Get Update field data
				$field_data_model->update($field_data_id, $field_data);
			}else{
				// Get Insert field data 
				$field_data_model->insert($field_data);
			}

			return true;
			
		}
	}

	/**
	 * Get Document Field data save
	 */
	if (!function_exists('save_document_field_data')) {
		function save_document_field_data($field_id, $section_id, $item_id, $field_value){

			$field_data_model = new DocumentdataModel();

			$field_data = [
				'field_id'     => $field_id,
				'section_id'   => $section_id,
				'item_id'      => $item_id,
				'data'         => $field_value,
				'school_id'    => session()->get('school_id')
			];

			// get field exit value
			$field_data_id = get_document_field_data('id', $field_id, $section_id, $item_id);
			if($field_data_id){
                // Get Update field data
				$field_data_model->update($field_data_id, $field_data);
			}else{
				// Get Insert field data 
				$field_data_model->insert($field_data);
			}

			return true;
			
		}
	}

	/**
	 * Get Valid Academic Data
	 */
	if (!function_exists('get_valid_academic_data')) {
		function get_valid_academic_data($validationRule, $session_id){

			// Get Setting Value from school
			$school_id            = session()->get('school_id');
			$academic_roll        = esc(get_school_value($school_id, 'academic_roll', true));
			$academic_category    = esc(get_school_value($school_id, 'academic_category', true));
			$academic_group       = esc(get_school_value($school_id, 'academic_group', true));
			$academic_section     = esc(get_school_value($school_id, 'academic_section', true));
			$academic_grade       = esc(get_school_value($school_id, 'academic_grade', true));
			$academic_shift       = esc(get_school_value($school_id, 'academic_shift', true));
			$academic_department  = esc(get_school_value($school_id, 'academic_department', true));
			$academic_skill       = esc(get_school_value($school_id, 'academic_skill', true));
			$academic_house       = esc(get_school_value($school_id, 'academic_house', true));
			$academic_version     = esc(get_school_value($school_id, 'academic_version', true));

			$validationRule['session_'.$session_id]  = 'required';
			$validationRule['class_'.$session_id]  = 'required';
			$validationRule['subject_'.$session_id]  = 'required';
			$validationRule['school_id_'.$session_id]  = 'required';

			// If student class roll is active
			if($academic_roll){
				$validationRule['roll_'.$session_id]  = 'required';
			}
	
			// If student academic grade is active
			if($academic_grade){
				$validationRule['grade_'.$session_id]  = 'required';
			}
	
			// If student academic group is active
			if($academic_group){
				$validationRule['group_'.$session_id]  = 'required';
			}
	
			// If student academic house is active
			if($academic_house){
				$validationRule['house_'.$session_id]  = 'required';
			}
	
			// If student academic version is active
			if($academic_version){
				$validationRule['version_'.$session_id]  = 'required';
			}
	
			// If student academic section is active
			if($academic_section){
				$validationRule['section_'.$session_id]  = 'required';
			}
	
			// If student academic shift is active
			if($academic_shift){
				$validationRule['shift_'.$session_id]  = 'required';
			}
	
			// If student academic department is active
			if($academic_department){
				$validationRule['department_'.$session_id]  = 'required';
			}
	
			// If student academic category is active
			if($academic_category){
				$validationRule['category_'.$session_id]  = 'required';
			}
	
			// If student academic skill is active
			if($academic_skill){
				$validationRule['skill_'.$session_id]  = 'required';
			}
	
			return true;
			
		}
	}

	/**
	 * Get Save Academic Data
	 */
	if (!function_exists('save_academic_data')) {
		function save_academic_data($post_data, $session_id, $student_id){

			$academic_model = new AcademicsModel();

			// Get Setting Value from school
			$school_id            = session()->get('school_id');
			$academic_roll        = esc(get_school_value($school_id, 'academic_roll', true));
			$academic_category    = esc(get_school_value($school_id, 'academic_category', true));
			$academic_group       = esc(get_school_value($school_id, 'academic_group', true));
			$academic_section     = esc(get_school_value($school_id, 'academic_section', true));
			$academic_grade       = esc(get_school_value($school_id, 'academic_grade', true));
			$academic_shift       = esc(get_school_value($school_id, 'academic_shift', true));
			$academic_department  = esc(get_school_value($school_id, 'academic_department', true));
			$academic_skill       = esc(get_school_value($school_id, 'academic_skill', true));
			$academic_house       = esc(get_school_value($school_id, 'academic_house', true));
			$academic_version     = esc(get_school_value($school_id, 'academic_version', true));

			// Get subject value
			if($post_data['subject_'.$session_id]){
				$subject_value = implode(',', $post_data['subject_'.$session_id]);
			}else{
				$subject_value = '';
			}

			// Data for Student Academic
			$academic_data = [
				'student_id'      => $student_id,
				'class_id'        => $post_data['class_'.$session_id],
				'session_id'      => $post_data['session_'.$session_id],
				'subject_ids'     => $subject_value,
				'status'          => $post_data['status_'.$session_id],
				'school_id'       => $post_data['school_id_'.$session_id]
			];

			// If student class roll is active
			if($academic_roll){
				$academic_data['roll']  = $post_data['roll_'.$session_id];
			}
	
			// If student academic grade is active
			if($academic_grade){
				$academic_data['grade_level_id']  = $post_data['grade_'.$session_id];
			}
	
			// If student academic group is active
			if($academic_group){
				$academic_data['group_id']  = $post_data['group_'.$session_id];
			}
	
			// If student academic house is active
			if($academic_house){
				$academic_data['house_id']  = $post_data['house_'.$session_id];
			}
	
			// If student academic version is active
			if($academic_version){
				$academic_data['version_id']  = $post_data['version_'.$session_id];
			}
	
			// If student academic section is active
			if($academic_section){
				$academic_data['section_id']  = $post_data['section_'.$session_id];
			}
	
			// If student academic shift is active
			if($academic_shift){
				$academic_data['shift_id']  = $post_data['shift_'.$session_id];
			}
	
			// If student academic department is active
			if($academic_department){
				$academic_data['department_id']  = $post_data['department_'.$session_id];
			}
	
			// If student academic category is active
			if($academic_category){
				$academic_data['category_id']  = $post_data['category_'.$session_id];
			}
	
			// If student academic skill is active
			if($academic_skill){
				// Get skill value
				if($post_data['skill_'.$session_id]){
					$skill_value = implode(',', $post_data['skill_'.$session_id]);
				}else{
					$skill_value = '';
				}
				$academic_data['skill_ids']  = $skill_value;
			}

			// Check exit student academic id
			if($post_data['academic_id_'.$session_id]){
				// Get Update student
				$academic_data['updated_by'] =  session()->get('user_id');
				$academic_id = $post_data['academic_id_'.$session_id];
				$academic_model->update($academic_id, $academic_data);
			}else{
				// Get Insert student 
				if($post_data['session_0']){
					$academic_data['created_by'] =  session()->get('user_id');
					$academic_model->insert($academic_data); 
				}
				
			}

			return true;
			
		}
	}

    /**
	 * Get Field
	 */
	if (!function_exists('get_field')) {
		function get_field($field_id, $section_id, $item_id, $label, $type, $required, $option_param, $field_value, $help = ''){
            $output ='';
			// get field exit value
			if(empty($field_value)){
				$field_value = get_field_data('data', $field_id, $section_id, $item_id);
			}
			

			if(empty($required)){
				$required = false;
				$required_att = '';
				$required_star = '';
			}else{
				$required = true;
				$required_att = 'required="required"';
				$required_star = '<span class="required">*</span>';
			}
			$field_name ='field_'.$field_id;

			
			
			$label_class = 'col-sm-4';
			$hdiv = '<div class="col-sm-8">';
			$hdiv_close = '</div>';

			// File Field
			if ($type == 'file') {
				
				if($field_value){
					$uploaded_file = '<p>uploaded file is: '.$field_value.'</p>';
				}else{
					$uploaded_file ='';
				}
				$data = [
					'type'     => 'file',
					'name'     => 'document_'.$field_name,
					'id'       => 'document_'.$field_name,
					'class'    => 'form-control '
				];

				if($required){
					$data['required'] = 'required';
				}

				$output .='<div class="row form-group">
					<label class="col-form-label col-sm-4 control-label" for="'.$field_name.'">'.$label.' '.$required_star.'</label>
					<div class="col-sm-8">'.form_upload($data).' <p>'.$help.'</p>
					    <p>'.$uploaded_file.'</p>
					    <input type="hidden" name="old_document_'.$field_name.'"  value="'.$field_value.'" >
					</div>
				</div>';
			}

 
			// Text Field
			if ($type == 1) {
				$data = [
					'type'     => 'text',
					'name'     => $field_name,
					'id'       => $field_name,
					'class'    => 'form-control ',
					'value'    => $field_value
				];

				if($required){
					$data['required'] = 'required';
				}

				$output .='<div class="row form-group">
					<label class="col-form-label col-sm-4 control-label" for="'.$field_name.'">'.$label.' '.$required_star.'</label>
					<div class="col-sm-8">'.form_input($data).'</div>
				</div>';
			}

			// Textarea Field
			if ($type == 2) {
				$output .='<div class="row form-group">';
				$output .='<label for="field_id_'.$field_name.'" class="'.$label_class.' control-label">'.$label.' '.$required_star.'</label>';
				$output .=$hdiv;
				$output .= form_textarea([
					'name'        => $field_name,
					'id'          => $field_name,
					'rows'        => '4',
					'cols'        => '50',
					'class'       => 'form-control',
					'value'       => $field_value,
					'required' => $required
				]);
				$output .=$hdiv_close;
				$output .=$hdiv_close;
			}

			// Check Box Field
			if($type==3){
			
				$output .='<div class=" row form-group">';
				$output .='<label for="'.$field_name.'" class=" '.$label_class.' control-label">'.$label.' '.$required_star.'</label>';
				$output .=$hdiv;

				$check_box_option_values = explode(",",$option_param);
			
					$key =0;
					foreach($check_box_option_values as $option){
					$key++;
					$options = explode("=",$option);
					$option_value = $options[0];
					$option_name = $options[1];
						if (in_array($option_value, explode(",",$field_value))) {
							$checked_code ='checked="checked"';
						}else{
						$checked_code ='';
						}
						
						$output .= '<div class="checkbox"><label class="custom_checkbox" > <input type="checkbox" '.$required_att.'  class=" '.$required.'"  '.$checked_code.' name="'.$field_name.'[]" id="'.$field_id.'_'.$key.'"  value="'.$option_value.'"> ' .$option_name. '  </label></div>';
					}
					$output .=$hdiv_close;
				$output .='</div>';
			}

			// Radio box field
			if($type == 4){
				$output .='<div class=" row form-group">';
				$output .='<label for="'.$field_name.'" class=" '.$label_class.' control-label">'.$label.' '.$required_star.'</label>';
				
				$radio_option_values = explode(",",$option_param);
			    $output .= $hdiv;
				foreach($radio_option_values as $radio_option){
					$radio_options = explode("=",$radio_option);
					$roption_value = $radio_options[0];
					$roption_name = $radio_options[1];
					if (in_array($roption_value, explode(",",$field_value))) {
						$checked_code =true;
					}else{
					$checked_code =false;
					}

					$output .='<div class="radio">';
					$output .= form_radio([
						'name'    => $field_name,
						'id'      => $field_name,
						'value'   => $roption_name,
						'checked' => $checked_code,
						'required' => $required
					]);
					$output .= form_label($roption_name, $field_name);
					$output .='</div>';	
				}
				$output .= $hdiv_close;
				$output .= $hdiv_close;
			}

			/**
			** Select Box
			**/
			if ($type == 5) {
				$output .='<div class=" row form-group">';
				$output .='<label for="'.$field_name.'" class=" '.$label_class.' control-label">'.$label.' '.$required_star.'</label>';
				
				$select_option_values = explode(",",$option_param);

				$output .= $hdiv;
				
				$output .= '<select  id="'.$field_name.'" class="form-control '.$required.'" '.$required_att.' name="'.$field_name.'" >';
				
				$output .='<option value="" >'.$label.'</option>';
				foreach ($select_option_values as $row) {
					$select_options = explode("=",$row);
					$soption_value = $select_options[0];
					$soption_name = $select_options[1];

					if (in_array($soption_value, explode(",",$field_value))) {
						$output .= '<option selected="selected" value="'.$soption_value.'">'.$soption_name.'</option>';
					}else{
					    $output .= '<option value="'.$soption_value.'">'.$soption_name.'</option>';
					}

				}
				$output .= '</select>';
				$output .= $hdiv_close;
				$output .= $hdiv_close;
				
			}

			// Datepicker
			if ($type == 6) {
				$output .='<div class="row form-group">';
				$output .='<label for="field_id_'.$field_name.'" class=" '.$label_class.' control-label">'.$label.' '.$required_star.'</label>';
				
				$output .= $hdiv;
				$output .='<input name="'.$field_name.'" id="'.$field_name.'" value="'.$field_value.'" class="date-picker form-control '.$required.'" '.$required_att.' placeholder="dd-mm-yyyy" type="date" >';
				// $output .="<script>
				// 			$(document).ready(function() {
				// 			    var today = new Date().toISOString().split('T')[0];
                //                 $('.date-picker').val(today);
				// 			});
				// 		    </script>			
				// 			";
				$output .= $hdiv_close;
				$output .= $hdiv_close;

				}


        return $output;
		
		}

	}


	/**
	 * Get Generate a hash from the current time in microseconds
	 */
	if (!function_exists('generate_unique_random_number')) {
		function generate_unique_random_number($numDigits = 10)
		{
			// Generate a hash from the current time in microseconds
			$hash = md5(microtime());
    
			// Calculate the maximum value based on the number of digits (e.g., 9999999999 for 10 digits)
			$maxValue = pow(10, $numDigits) - 1;
			
			// Convert part of the hash to an integer to get a large number
			$uniqueNumber = hexdec(substr($hash, 0, $numDigits)) % $maxValue;
			
			// Ensure the number is exactly the required number of digits
			return str_pad($uniqueNumber, $numDigits, '0', STR_PAD_LEFT);
		}
	}

	
	/**
	 * Get Student Academic data
	 */
	if (!function_exists('get_academic_data')) {
		function get_academic_data($student_id){
            $academic_model = new AcademicsModel();
			$result = $academic_model->where('student_id', $student_id)
                    ->findAll();
			return $result;
		}
	}

	/**
	 * Get distribution mark
	 */
	if (!function_exists('get_distribution_mark')) {
		function get_distribution_mark($field, $exam_id, $school_id, $session_id, $academic_id, $subject_id, $mark_distribution_id){
            $model = new MarkModel();
			$model->where('mark_distribution_id', $mark_distribution_id);
			$model->where('exam_id', $exam_id);
			$model->where('school_id', $school_id);
			$model->where('session_id', $session_id);
			$model->where('academic_id', $academic_id);
			$model->where('subject_id', $subject_id);
			$result = $model->select($field)->first();
			if(empty($result->$field)){
				return '';
			}else{	
			    return $result->$field;
			}
		}
	}

	/**
	 * Get subject mark
	 */
	if (!function_exists('get_subject_mark')) {
		function get_subject_mark($exam_id, $school_id, $session_id, $academic_id, $subject_id){
            $model = new MarkModel();
			$model->select('SUM(mark) as total');
			$model->where('exam_id', $exam_id);
			$model->where('school_id', $school_id);
			$model->where('session_id', $session_id);
			$model->where('academic_id', $academic_id);
			$model->where('subject_id', $subject_id);
			$result = $model->findAll();
			if(empty($result)){
				return '';
			}else{	
			    return $result[0]->total;;
			}
		}
	}

	/**
	 * Get highest mark
	 */
	if (!function_exists('get_highest_mark')) {
		function get_highest_mark($exam_id, $school_id, $session_id, $subject_id){

			$model = new MarkModel();
			$model->select('subject_id, SUM(mark) as total');
			$model->where('exam_id', $exam_id);
			$model->where('school_id', $school_id);
			$model->where('session_id', $session_id);
			$model->where('subject_id', $subject_id);
			$model->groupBy(['subject_id', 'academic_id']);
			$model->orderBy('total', 'DESC');
			$model->limit(1);
			$result = $model->findAll();

			if(empty($result)){
				return '';
			}else{	
			    return $result[0]->total;;
			}
			
		}
	}

	/**
	 * Get highest skill mark
	 */
	if (!function_exists('get_highest_skill_mark')) {
		function get_highest_skill_mark($exam_id, $school_id, $session_id, $skill_id){

			$model = new SkillvalueModel();
			$model->select('skill_id, SUM(mark) as total');
			$model->where('exam_id', $exam_id);
			$model->where('school_id', $school_id);
			$model->where('session_id', $session_id);
			$model->where('skill_id', $skill_id);
			$model->groupBy(['skill_id', 'academic_id']);
			$model->orderBy('total', 'DESC');
			$model->limit(1);
			$result = $model->findAll();

			if(empty($result)){
				return '';
			}else{	
			    return $result[0]->total;;
			}
			
		}
	}

	/**
	 * Get Grading Value
	 */
	if (!function_exists('get_grading_value')) {
		function get_grading_value($category_id, $mark, $school_id){
            $model = new GradingSystemModel();
			$grading_object = $model->where('category_id', $category_id)->where('school_id', $school_id)->findAll();
			
			$grading_point   = 0;
			$letter_grade    = '';
			$grading_remark  = '';
			
			foreach ($grading_object as $key=> $grading_item) {
				if ($mark >= $grading_item->mark_from && $mark <= $grading_item->mark_upto){
					$grading_point    = $grading_item->grade_point;
					$letter_grade     = $grading_item->title;
					$grading_remark   = $grading_item->remarks;
				}
			}
			
			$output = array();
			$output['gp']      = $grading_point;
			$output['gpa']     = $letter_grade;
			$output['remark'] = $grading_remark;
			return $output;
		}
	}

	/**
	 * Get Leter Grade
	 */
	if (!function_exists('get_letter_grade')) {
		function get_letter_grade($grading_system, $grading_point, $school_id){
            $model = new GradingSystemModel();
			$grading_object = $model->where('category_id', $grading_system)->where('school_id', $school_id)->findAll();
			
			$letter_grade    = '';
			foreach ($grading_object as $grading_item) {
				if ($grading_point >= $grading_item->point_from && $grading_point <= $grading_item->point_to){
					$letter_grade = $grading_item->title;
				}
			}
			return $letter_grade;
		}
	}


	/**
	 * Get skill mark
	 */
	if (!function_exists('get_skill_mark')) {
		function get_skill_mark($field, $exam_id, $school_id, $session_id, $academic_id, $skill_id){
            $model = new SkillvalueModel();
			$model->where('skill_id', $skill_id);
			$model->where('exam_id', $exam_id);
			$model->where('school_id', $school_id);
			$model->where('session_id', $session_id);
			$model->where('academic_id', $academic_id);
			$result = $model->select($field)->first();
			if(empty($result->$field)){
				return '';
			}else{	
			    return $result->$field;
			}
		}
	}

	/**
	 * Get Exam Rank
	 */
	if (!function_exists('get_exam_rank')) {
		function get_exam_rank($exam_id, $school_id, $session_id, $student_id, $academic_id, $custom_field, $custom_value){
            $result_model  = new ResultModel();
			$result_data = $result_model->where('exam_id', $exam_id)
                            ->where('school_id', $school_id)
                            ->where('session_id', $session_id);
			if($custom_field){
				$result_data->where($custom_field, $custom_value);
			}
			// Order by total_marks, percentage, grade_point desc
			$result_data = $result_model->orderBy('total_marks', 'desc')->orderBy('percentage', 'desc')->orderBy('grade_point', 'desc')->findAll();
			foreach ($result_data as $key => $result) {
				$rank = $key + 1;
				if($result->student_id == $student_id && $result->academic_id == $academic_id){
					return $rank;
				}
			}

			return '';
		}
	}

	if (!function_exists('generate_ai_remarks')) {
		function generate_ai_remarks($marks, $prompt)
		{
			
			$apiKey = get_setting_value('openai_api_key'); // Replace with your OpenAI API key
			$model  = get_setting_value('open_ai_model');

			$endpoint = 'https://api.openai.com/v1/chat/completions';
	
			$prompt = $prompt. ":\n";
			foreach ($marks as $subject => $score) {
				$prompt .= "$subject: $score\n";
			}
	
			$postData = [
				'model' => $model, // You can use 'gpt-4' if available
				'messages' => [
					['role' => 'system', 'content' => 'You are a helpful assistant.'],
					['role' => 'user', 'content' => $prompt],
				],
				'max_tokens' => 100,
				'temperature' => 0.7,
			];
	
			$headers = [
				'Authorization: Bearer ' . $apiKey,
				'Content-Type: application/json',
			];
	
			$ch = curl_init($endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
			$response = curl_exec($ch);
			curl_close($ch);

			
			if ($response) {
				$result = json_decode($response, true);
				return $result['choices'][0]['message']['content'] ?? 'Could not generate remarks.';
			}
	
			return 'API request failed.';
		}







		
	}
	