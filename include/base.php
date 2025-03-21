<?php

// ============================================================================
// PHP Setups
// ============================================================================

date_default_timezone_set('Asia/Kuala_Lumpur');

// ============================================================================
// General Page Functions
// ============================================================================

// Is GET request?
// TODO
function isGet(){
    return $_SERVER['REQUEST_METHOD'] == "GET";
}

// Is POST request?
// TODO
function isPost(){
    return $_SERVER['REQUEST_METHOD'] == "POST";
}
// Obtain GET parameter
// TODO
function get($key, $value = null){
    $value = $_GET[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);

}
// Obtain POST parameter
// TODO
function post($key, $value = null){
    $value = $_POST[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}
// Obtain REQUEST (GET and POST) parameter
// TODO
function req($key, $value = null){
    $value = $_REQUEST[$key] ?? $value;
    return is_array($value) ? array_map('trim', $value) : trim($value);
}