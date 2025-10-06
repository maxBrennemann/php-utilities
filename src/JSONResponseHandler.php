<?php

namespace MaxBrennemann\PhpUtilities;

class JSONResponseHandler
{

    /**
     * @param int $httpStatusCode
     * @param string|array<mixed> $message
     * @return never
     */
    public static function throwError(int $httpStatusCode, string|array $message): never
    {
        if (!headers_sent()) {
            http_response_code($httpStatusCode);
        }

        echo json_encode([
            "message" => $message,
        ]);

        if (function_exists("fastcgi_finish_request")) {
            fastcgi_finish_request();
        }

        die();
    }

    public static function sendErrorResponse(int $httpStatusCode, string $message): void 
    {
        if (!headers_sent()) {
            http_response_code($httpStatusCode);
        }

        echo json_encode([
            "message" => $message,
        ]);

        if (function_exists("fastcgi_finish_request")) {
            fastcgi_finish_request();
        }
    }

    /**
     * @param array<mixed, mixed> $data
     * @return void
     */
    public static function sendResponse(array $data): void
    {
        if (!headers_sent()) {
            http_response_code(200);
        }

        echo json_encode($data);
    }

    public static function returnOK(): void
    {
        if (!headers_sent()) {
            http_response_code(200);
        }

        echo json_encode([
            "message" => "OK",
        ]);
    }

    public static function returnNotFound(?string $additionalMessage = null): never
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

    public static function outputHeaderJSON(): void
    {
        session_start();

        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Origin: https://localhost:5173");
        header("Content-Type: application/json; charset=utf-8");
    }
}
