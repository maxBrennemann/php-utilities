<?php

namespace MaxBrennemann\PhpUtilities\Router;

use MaxBrennemann\PhpUtilities\Tools;

class Router
{

    public static function getParameters()
    {
        if (file_get_contents("php://input") != "") {
            $PHP_INPUT = json_decode(file_get_contents("php://input"), true);

            if ($PHP_INPUT != null) {
                Tools::$data = array_merge(Tools::$data, $PHP_INPUT);
                $_POST = array_merge($_POST, $PHP_INPUT);
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
                parse_str(file_get_contents("php://input"), $_PUT);
                Tools::$data = array_merge(Tools::$data, $_PUT);
                break;
        }
    }
}
