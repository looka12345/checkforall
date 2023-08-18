<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// auto load classes
spl_autoload_register(function ($class_name) {
    $a = str_replace('\\', '/', $class_name);
    $b = str_replace('com/octorate/', '', $a);
    @include $b . '.php';
});

// utility function to check if a variable is not null
function is_not_null($var) {
    if (is_null($var)) {
        return false;
    }
    if (is_array($var) && count($var) <= 0) {
        return false;
    }
    return true;
}

// utility function to filter not null values in json
function filter_json($result) {
    if (!$result) {
        return null;
    }
    $arr = (array) $result;
    foreach ($arr as $k => $v) {
        if (is_object($v) || is_array($v)) {
            $arr[$k] = filter_json($v);
        }
    }
    return array_filter($arr, 'is_not_null');
}

// utility function to print not null values in json
function print_json($result) {
    echo json_encode(filter_json($result));
}

// global variable
$test = filter_input(INPUT_GET, 'test', FILTER_VALIDATE_BOOLEAN);
