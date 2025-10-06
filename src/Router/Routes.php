<?php

namespace MaxBrennemann\PhpUtilities\Router;

use MaxBrennemann\PhpUtilities\JSONResponseHandler;
use MaxBrennemann\PhpUtilities\Tools;

class Routes
{

    /** @var array<string, array{class-string, string}> */
    protected static $getRoutes = [];

    /** @var array<string, array{class-string, string}> */
    protected static $postRoutes = [];

    /** @var array<string, array{class-string, string}> */
    protected static $putRoutes = [];

    /** @var array<string, array{class-string, string}> */
    protected static $deleteRoutes = [];

    protected static function get(string $route): void
    {
        if (self::checkUrlPatterns($route, static::$getRoutes)) {
            return;
        }

        if (!isset(static::$getRoutes[$route])) {
            JSONResponseHandler::throwError(404, "Path not found");
        }

        $callback = static::$getRoutes[$route];
        self::callCallback($callback);
    }

    protected static function post(string $route): void
    {
        if (self::checkUrlPatterns($route, static::$postRoutes)) {
            return;
        }

        if (!isset(static::$postRoutes[$route])) {
            JSONResponseHandler::throwError(404, "Path not found");
        }

        $callback = static::$postRoutes[$route];
        self::callCallback($callback);
    }

    protected static function put(string $route): void
    {
        if (self::checkUrlPatterns($route, static::$putRoutes)) {
            return;
        }

        if (!isset(static::$putRoutes[$route])) {
            JSONResponseHandler::throwError(404, "Path not found");
        }

        $callback = static::$putRoutes[$route];
        self::callCallback($callback);
    }

    protected static function delete(string $route): void
    {
        if (self::checkUrlPatterns($route, static::$deleteRoutes)) {
            return;
        }

        if (!isset(static::$deleteRoutes[$route])) {
            JSONResponseHandler::throwError(404, "Path not found");
        }

        $callback = static::$deleteRoutes[$route];
        self::callCallback($callback);
    }

    /**
     * @param string $url
     * @param array<string, array{class-string, string}> $routes
     * @return bool
     */
    private static function checkUrlPatterns(string $url, array $routes): bool
    {
        foreach ($routes as $route => $callback) {
            if (self::matchUrlPattern($url, $route)) {
                self::callCallback($callback);
                return true;
            }
        }

        return false;
    }

    private static function matchUrlPattern(string $url, string $route): bool
    {
        $urlParts = explode("/", $url);
        $routeParts = explode("/", $route);

        if (count($urlParts) != count($routeParts)) {
            return false;
        }

        for ($i = 0; $i < count($urlParts); $i++) {
            if ($routeParts[$i] == $urlParts[$i]) {
                continue;
            }

            if (substr($routeParts[$i], 0, 1) == "{" && substr($routeParts[$i], -1) == "}") {
                self::setUrlParameter(substr($routeParts[$i], 1, -1), $urlParts[$i]);
                continue;
            }

            return false;
        }

        return true;
    }

    private static function setUrlParameter(string $key, string $value): void
    {
        Tools::add($key, $value);
    }

    public static function handleRequest(string $route): void
    {
        $method = $_SERVER["REQUEST_METHOD"];
        switch ($method) {
            case "GET":
                static::get($route);
                break;
            case "POST":
                static::post($route);
                break;
            case "PUT":
                static::put($route);
                break;
            case "DELETE":
                static::delete($route);
                break;
            default:
                JSONResponseHandler::throwError(405, "Method not allowed");
        }
    }

    /**
     * @param callable|array{class-string, string} $callback
     * @return void
     */
    private static function callCallback(callable|array $callback): void
    {
        if (!is_callable($callback)) {
            if ($_ENV["DEV_MODE"] === "true") {
                JSONResponseHandler::throwError(500, "Internal server error, callback function not found");
            }

            JSONResponseHandler::throwError(500, "Internal server error");
        }
        
        call_user_func($callback);
    }
}
