<?php

namespace MaxBrennemann\PhpUtilities;

class Tools
{

    public static $data = [];

    public static function get($key)
    {
        if (isset(self::$data[$key])) {
            return self::$data[$key];
        }

        return null;
    }

    public static function add($key, $value)
    {
        self::$data[$key] = $value;
    }

    /**
     * puts the data into the output buffer,
     * used to show data in eventsources
     * 
     * @param int $id
     * @param array $data
     */
    public static function output($id, $data)
    {
        echo "id: $id" . PHP_EOL;
        echo "data: " . json_encode($data) . PHP_EOL;
        echo PHP_EOL;
        if (ob_get_level() > 0) {
            ob_flush();
        }
        if (connection_aborted()) {
            exit();
        }
        flush();
    }

    public static function normalizeString($term)
    {
        $term = iconv("utf-8", "ascii//TRANSLIT", $term);
        return $term;
    }

    public static function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * deletes a running task from the database by key
     * 
     * @param string $key
     * 
     * @return void
     */
    static function delete($key)
    {
        $query = "DELETE FROM running_tasks WHERE data_key = :dataKey;";
        DBAccess::deleteQuery($query, [
            "dataKey" => $key,
        ]);
    }

    /**
     * reads runnings tasks from the database by key
     * 
     * @param string $key
     * 
     * @return array|null
     */
    public static function read($key)
    {
        $query = "SELECT data_value FROM running_tasks WHERE data_key = :dataKey;";
        $result = DBAccess::selectQuery($query, [
            "dataKey" => $key,
        ]);

        if (count($result) == 0) {
            return null;
        }

        return json_decode($result[0]["data_value"], true);
    }

    /**
     * writes or updates running tasks to the database
     * 
     * @param string $key
     * 
     * @param int $id is 0 if the task exists or was not found, otherwise the id of the task
     */
    public static function write($key, $value)
    {
        if ($key == null || $value == null) {
            return 0;
        }

        $id = 0;

        if (self::read($key) == null) {
            $query = "INSERT INTO running_tasks (data_key, data_value) VALUES (:dataKey, :dataValue);";
            $id = (int) DBAccess::insertQuery($query, [
                "dataKey" => $key,
                "dataValue" => json_encode($value, JSON_UNESCAPED_UNICODE),
            ]);
        } else {
            $query = "UPDATE running_tasks SET data_value = :dataValue WHERE data_key = :dataKey;";

            DBAccess::updateQuery($query, [
                "dataKey" => $key,
                "dataValue" => json_encode($value, JSON_UNESCAPED_UNICODE),
            ]);
        }


        return $id;
    }

    public static function outputLog($message, $tag = null, $type = null)
    {
        if ($tag == null) {
            $tag = "";
        }

        if ($type == null) {
            $type = "regular";
        }

        if (!$_ENV["DEV_MODE"] || $_ENV["DEV_MODE"] == false) {
            return;
        }

        if (php_sapi_name() != 'cli') {
            return;
        }

        $timestamp = date("Y-m-d H:i:s");
        $timestamp = "[" . $timestamp . "]";

        if ($tag != "") {
            $tag = "\e[1;32m" . $tag . "\e[0m" . " ";
        }

        switch ($type) {
            case "warning":
                echo $timestamp . ": " . $tag . "\e[1;33m" . $message . "\e[0m" . PHP_EOL;
                break;
            case "error":
                echo $timestamp . ": " . $tag . "\e[0;31m" . $message . "\e[0m" . PHP_EOL;
                break;
            case "regular":
                echo $timestamp . ": " . $tag . $message . PHP_EOL;
                break;
            default:
                echo $timestamp . ": " . $tag . $message . PHP_EOL;
                break;
        }
    }

    /**
     * logs an action to the database
     * 
     * @param string $logAction
     * @param string $logComment
     * @param array $additionalInfo
     * @param string $status
     * @param string $initiator
     */
    public static function log($logAction, $logComment = null, $additionalInfo = null, $status = null, $initiator = null)
    {
        if ($logComment == null) {
            $logComment = "";
        }

        if ($additionalInfo == null) {
            $additionalInfo = [];
        }

        if ($status == null) {
            $status = "";
        }

        if ($initiator == null) {
            $initiator = "";
        }

        if (strlen($logAction) > 32 || strlen($logComment) > 128 || strlen($status) > 32 || strlen($initiator) > 32) {
            return;
        }

        $query = "INSERT INTO logs (log_action, log_comment, additional_info, `status`, initiator) VALUES (:logAction, :logComment, :logAdditionalInfo, :logStatus, :logInitiator);";
        DBAccess::insertQuery($query, [
            "logAction" => $logAction,
            "logComment" => $logComment,
            "logAdditionalInfo" => json_encode($additionalInfo, JSON_UNESCAPED_UNICODE),
            "logStatus" => $status,
            "logInitiator" => $initiator,
        ]);
    }

    public static function getLogs()
    {
        $limit = (int) self::get("limit");
        $query = "SELECT * FROM logs ORDER BY id DESC LIMIT :limit;";
        $data = DBAccess::selectQuery($query, [
            "limit" => $limit,
        ]);

        JSONResponseHandler::sendResponse($data);
    }

    public static function joinDataJson($results, $column = null)
    {
        if ($column == null) {
            $column = "data";
        }
        
        foreach ($results as &$result) {
            $data = json_decode($result[$column], true);

            foreach ($data as $key => $value) {
                if (isset($result[$key])) {
                    continue;
                }

                $result[$key] = $value;
            }
            unset($result[$column]);
        }

        return $results;
    }

    public static function parseDataJson($results, $column = null)
    {
        if ($column == null) {
            $column = "data";
        }

        foreach ($results as &$row) {
            $dataValue = $row[$column];
            $dataValue = json_decode($dataValue, true);
            $row[$column] = $dataValue;
        }

        return $results;
    }

    public static function formatDate($data, $column = null, $format = null)
    {
        if ($column == null) {
            $column = "date";
        }

        if ($format == null) {
            $format = "Y-m-d h:i:s";
        }

        foreach ($data as &$row) {
            $dataValue = $row[$column];
            $dataValue = date($format, strtotime($dataValue));
            $row[$column] = $dataValue;
        }

        return $data;
    }
    
}
