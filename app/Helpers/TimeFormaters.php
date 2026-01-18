<?php



if (!function_exists('debugHalt')) {
    function debugHalt($message)
    {
$message = str_replace('-', '_', json_encode($message));
throw new Exception(json_encode($message), 400);
    }
}
