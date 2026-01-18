<?php

namespace App\Http\Utils;


use Exception;
use Illuminate\Http\Request;

trait ErrorUtil
{
    // this function do all the task and returns transaction id or -1

    private function getHttpStatusCode($code) {
        if (is_numeric($code) && $code >= 400 && $code < 600) {
            // If it's a valid HTTP status code, return it
            return (int) $code;
        } else {
            // Otherwise, default to 500 Internal Server Error
            return 500;
        }
    }
    public function sendError(Exception $e)
    {
        $errorData = [
            "message" => $e->getMessage(),
            "line" => $e->getLine(),
            "file" => $e->getFile(),
        ];

        return response()->json($errorData, $this->getHttpStatusCode($e->getCode()));

    }

}
