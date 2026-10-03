<?php

if (!function_exists('get_custom_fields_for_form')) {
    /**
     * Get custom fields for a specific entity, group, and school
     * 
     * @param int $schoolId
     * @param string $entitySlug  e.g. 'student'
     * @param string|null $groupSlug e.g. 'personal_information' (optional)
     * @return array
     */
    function get_custom_fields_for_form(int $schoolId, string $entitySlug, ?string $groupSlug = null): array
    {
        $db = \Config\Database::connect();

        $cfTable  = $db->prefixTable('custom_fields');
        $cfgTable = $db->prefixTable('custom_field_groups');
        $cftTable = $db->prefixTable('custom_field_types');
        $cfeTable = $db->prefixTable('custom_field_entities');

        $builder = $db->table($cfTable)
            ->select("
                {$cfTable}.*,
                {$cfeTable}.title AS entity_name,
                {$cfeTable}.slug AS entity_slug,
                {$cfgTable}.title AS group_name,
                {$cfgTable}.slug AS group_slug,
                {$cftTable}.title AS field_type,
                {$cftTable}.slug AS field_type_slug
            ")
            ->join($cfeTable, "{$cfeTable}.id = {$cfTable}.entity_id", 'left')
            ->join($cfgTable, "{$cfgTable}.id = {$cfTable}.group_id", 'left')
            ->join($cftTable, "{$cftTable}.id = {$cfTable}.field_type_id", 'left')
            ->where("{$cfTable}.school_id", $schoolId)
            ->where("{$cfeTable}.slug", $entitySlug)
            ->where("{$cfTable}.status", 1)
            ->orderBy("{$cfgTable}.sort_order")
            ->orderBy("{$cfTable}.sort_order");

        if ($groupSlug) {
            $builder->where("{$cfgTable}.slug", $groupSlug);
        }

        $query = $builder->get();
        return $query->getResult();
    }
}

if (!function_exists('get_custom_field_value')) {
    /**
     * Get saved value for a custom field
     * 
     * @param int $schoolId
     * @param int $entityId
     * @param int $recordId   e.g. student id
     * @param int $fieldId
     * @return string|null
     */
    function get_custom_field_value(int $schoolId, int $entityId, int $recordId, int $fieldId): ?string
    {
        $db = \Config\Database::connect();
        $cfvTable = $db->prefixTable('custom_field_values');

        $row = $db->table($cfvTable)
            ->where('school_id', $schoolId)
            ->where('entity_id', $entityId)
            ->where('record_id', $recordId)
            ->where('field_id', $fieldId)
            ->get()->getFirstRow();

        return $row ? $row->value : null;
    }
}

if (!function_exists('debug_custom_field_values')) {
    /**
     * Debug helper to check what values exist for a record
     */
    function debug_custom_field_values(int $schoolId, int $entityId, int $recordId): array
    {
        $db = \Config\Database::connect();
        $cfvTable = $db->prefixTable('custom_field_values');
        $cfTable = $db->prefixTable('custom_fields');

        $query = $db->table($cfvTable)
            ->select("{$cfvTable}.*, {$cfTable}.label, {$cfTable}.field_key")
            ->join($cfTable, "{$cfTable}.id = {$cfvTable}.field_id", 'left')
            ->where("{$cfvTable}.school_id", $schoolId)
            ->where("{$cfvTable}.entity_id", $entityId)
            ->where("{$cfvTable}.record_id", $recordId)
            ->get();

        $results = [];
        foreach ($query->getResult() as $row) {
            $results[] = [
                'field_id' => $row->field_id,
                'label' => $row->label,
                'field_key' => $row->field_key,
                'value' => $row->value
            ];
        }
        return $results;
    }
}

if (!function_exists('render_custom_field_input')) {
    /**
     * Render HTML input for a custom field based on its type
     * 
     * @param object $field
     * @param mixed $value
     * @param string $namePrefix e.g. 'custom_fields['
     * @return string
     */
    function render_custom_field_input(object $field, $value = null, string $namePrefix = 'custom_fields['): string
    {
        $name = $namePrefix . $field->id . ']';
        $fieldType = strtolower($field->field_type_slug ?? $field->field_type ?? 'text');
        $placeholder = esc($field->placeholder ?? '');
        $required = ($field->is_required ?? 0) ? 'required' : '';
        $html = '';

        switch ($fieldType) {
            case 'text':
            case 'email':
            case 'url':
            case 'number':
            case 'date':
                $html = '<input type="' . $fieldType . '" name="' . $name . '" class="form-control" value="' . esc($value ?? $field->default_value ?? '') . '" placeholder="' . $placeholder . '" ' . $required . '>';
                break;

            case 'textarea':
                $html = '<textarea name="' . $name . '" class="form-control" rows="3" placeholder="' . $placeholder . '" ' . $required . '>' . esc($value ?? $field->default_value ?? '') . '</textarea>';
                break;

            case 'select':
                $options = get_custom_field_options($field->id);
                $html = '<select name="' . $name . '" class="form-select" ' . $required . '>';
                $html .= '<option value="">-- Select --</option>';
                foreach ($options as $opt) {
                    $selected = ((string)($value ?? $field->default_value ?? '') === (string)$opt->option_value) ? 'selected' : '';
                    $html .= '<option value="' . esc($opt->option_value) . '" ' . $selected . '>' . esc($opt->option_label) . '</option>';
                }
                $html .= '</select>';
                break;

            case 'radio':
                $options = get_custom_field_options($field->id);
                $html = '<div class="d-flex flex-wrap gap-3">';
                foreach ($options as $opt) {
                    $checked = ((string)($value ?? $field->default_value ?? '') === (string)$opt->option_value) ? 'checked' : '';
                    $html .= '<div class="form-check">';
                    $html .= '<input class="form-check-input" type="radio" name="' . $name . '" value="' . esc($opt->option_value) . '" ' . $checked . ' ' . $required . '>';
                    $html .= '<label class="form-check-label">' . esc($opt->option_label) . '</label>';
                    $html .= '</div>';
                }
                $html .= '</div>';
                break;

            case 'checkbox':
                $options = get_custom_field_options($field->id);
                $savedValues = $value ?? $field->default_value ?? '';
                if (is_string($savedValues)) {
                    $savedValues = explode(',', $savedValues);
                }
                $html = '<div class="d-flex flex-wrap gap-3">';
                foreach ($options as $opt) {
                    $checked = in_array($opt->option_value, (array)$savedValues) ? 'checked' : '';
                    $html .= '<div class="form-check">';
                    $html .= '<input class="form-check-input" type="checkbox" name="' . $name . '[]" value="' . esc($opt->option_value) . '" ' . $checked . '>';
                    $html .= '<label class="form-check-label">' . esc($opt->option_label) . '</label>';
                    $html .= '</div>';
                }
                $html .= '</div>';
                break;

            case 'multiselect':
            case 'multi_select':
                $options = get_custom_field_options($field->id);
                $savedValues = $value ?? $field->default_value ?? '';
                if (is_string($savedValues)) {
                    $savedValues = explode(',', $savedValues);
                }
                $html = '<select name="' . $name . '[]" class="form-select" multiple ' . $required . '>';
                foreach ($options as $opt) {
                    $selected = in_array($opt->option_value, (array)$savedValues) ? 'selected' : '';
                    $html .= '<option value="' . esc($opt->option_value) . '" ' . $selected . '>' . esc($opt->option_label) . '</option>';
                }
                $html .= '</select>';
                break;

            default:
                $html = '<input type="text" name="' . $name . '" class="form-control" value="' . esc($value ?? $field->default_value ?? '') . '" placeholder="' . $placeholder . '" ' . $required . '>';
                break;
        }

        if (!empty($field->help_text)) {
            $html .= '<small class="text-muted">' . esc($field->help_text) . '</small>';
        }

        return $html;
    }
}

if (!function_exists('get_custom_field_options')) {
    /**
     * Get options for a custom field (for select, radio, checkbox, multiselect)
     * 
     * @param int $fieldId
     * @return array
     */
    function get_custom_field_options(int $fieldId): array
    {
        $db = \Config\Database::connect();
        $cfoTable = $db->prefixTable('custom_field_options');

        $query = $db->table($cfoTable)
            ->where('field_id', $fieldId)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->get();

        return $query->getResult();
    }
}

if (!function_exists('render_custom_fields_section')) {
    /**
     * Render a complete custom fields section grouped by group_name
     * 
     * @param array $fields
     * @param int $recordId   e.g. student id (for loading saved values)
     * @param int $schoolId
     * @param int $entityId
     * @param string $namePrefix
     * @return string
     */
    function render_custom_fields_section(array $fields, int $recordId, int $schoolId, int $entityId, string $namePrefix = 'custom_fields['): string
    {
        if (empty($fields)) {
            return '';
        }

        $html = '';
        $currentGroup = '';
        $groupFields = [];

        // Group fields by group_name
        foreach ($fields as $field) {
            $groupName = $field->group_name ?? 'Ungrouped';
            $groupFields[$groupName][] = $field;
        }

        // Render each group
        foreach ($groupFields as $groupName => $groupFieldList) {
            $html .= '<div class="card mb-3">';
            $html .= '<div class="card-header"><strong>' . esc($groupName) . '</strong></div>';
            $html .= '<div class="card-body">';
            $html .= '<div class="row">';

            foreach ($groupFieldList as $field) {
                $value = get_custom_field_value($schoolId, $entityId, $recordId, $field->id);
                $colClass = 'col-md-6 mb-3';
                $html .= '<div class="' . $colClass . '">';
                $html .= '<label class="form-label">' . esc($field->label);
                if ($field->is_required) {
                    $html .= ' <span class="text-danger">*</span>';
                }
                $html .= '</label>';
                $html .= render_custom_field_input($field, $value, $namePrefix);
                $html .= '</div>';
            }

            $html .= '</div></div></div>';
        }

        return $html;
    }
}
