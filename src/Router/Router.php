<?php

namespace MaxBrennemann\PhpUtilities\Router;

use MaxBrennemann\PhpUtilities\Tools;

class Router
{

    public static function getParameters(): void
    {
        $PHPInput = file_get_contents("php://input");

        if ($PHPInput !== "" && $PHPInput !== false) {
            $parsedPHPInput = json_decode($PHPInput, true);

            if ($parsedPHPInput != null) {
                Tools::$data = array_merge(Tools::$data, $parsedPHPInput);
                $_POST = array_merge($_POST, $parsedPHPInput);
            }
        }

        switch ($_SERVER["REQUEST_METHOD"]) {
            case "POST":
                Tools::$data = array_merge(Tools::$data, $_POST);
                break;
            case "GET":
                Tools::$data = array_merge(Tools::$data, $_GET);
                break;
            case "PUT":
            case "DELETE":
                if ($PHPInput !== "" && $PHPInput !== false) {
                    parse_str($PHPInput, $_PUT);
                    Tools::$data = array_merge(Tools::$data, $_PUT);
                }
                break;
        }
    }
}
