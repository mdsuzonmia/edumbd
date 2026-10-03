<?php

use App\Models\SettingModel;

if (! function_exists('setting')) {
    function setting(string $group, string $key, $default = null)
    {
        static $cache = [];

        $index = $group.'.'.$key;

        if (! array_key_exists($index, $cache)) {
            $model = new SettingModel();
            $cache[$index] = $model->getValue($group, $key, $default);
        }

        return $cache[$index];
    }
}