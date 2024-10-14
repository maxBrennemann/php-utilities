<?php

namespace MaxBrennemann\PhpUtilities;

class JSONResponseHandler
{

    public static function throwError(int $httpStatusCode, $message)
    {
        if (!headers_sent()) {
            http_response_code($httpStatusCode);
        }

        echo json_encode(array("message" => $message));

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        
        die();
    }

    public static function sendResponse($data)
    {
        if (!headers_sent()) {
            http_response_code(200);
        }

        echo json_encode($data);
    }

    public static function returnOK()
    {
        if (!headers_sent()) {
            http_response_code(200);
        }

        echo json_encode(array("message" => "OK"));
    }

    public static function returnNotFound(string $additionalMessage = null)
    {
        if ($additionalMessage == null) {
            $additionalMessage = "";
        }

        if (!headers_sent()) {
            http_response_code(404);
        }

        echo json_encode([
            "message" => "Not found",
            "details" => $additionalMessage
        ]);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        die();
    }

    public static function outputHeaderJSON()
    {
        session_start();

        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header('Access-Control-Allow-Origin: http://localhost:5173');
        header('Content-Type: application/json; charset=utf-8');
    }
}
