<?php



if (!function_exists('convertFloatHoursToTime')) {
    function convertFloatHoursToTime($input) {
        // Ensure the input is a number
        $float_hours = floatval($input);

        if (!is_numeric($float_hours)) {
            return;
        }

        // Extract hours and minutes
        $hours = floor($float_hours);
        $minutes = round(($float_hours - $hours) * 60);

        // Format hours
        $string_h = $hours > 0 ? "{$hours}h" : "";

        // Format minutes
        $string_m = $minutes > 0 ? "{$minutes}min" : "";

        // If both hours and minutes are zero, return "0h"
        return $string_h || $string_m ? trim("{$string_h} {$string_m}") : "0h";
    }
}
