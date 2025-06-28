<?php

namespace MaxBrennemann\PhpUtilities;

use PDO;
use PDOException;
use RuntimeException;

class DBAccess
{

	protected static $connection;
	protected static $statement;

	protected static $lastQuery = "";
	protected static $lastParams = [];

	private static function createConnection(): void
	{
		if (self::$connection != null) {
			return;
		}

		try {
			$host = $_ENV["DB_HOST"];
			$database = $_ENV["DB_DATABASE"];
			$username = $_ENV["DB_USERNAME"];
			$password = $_ENV["DB_PASSWORD"];

			self::$connection = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
			self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} catch (PDOException $e) {
			$_ENV["SQL_ERROR"] = true;
			throw new RuntimeException("Database connection failed: " . $e->getMessage());
		}
	}

	public static function getConnection(): PDO
	{
		self::createConnection();
		return self::$connection;
	}

	public static function selectQuery(string $query, $params = NULL): array
	{
		self::createConnection();

		self::$lastQuery = $query;
		self::$lastParams = $params ? $params : [];

		if ($query == "") {
			$_ENV["SQL_ERROR"] = true;
			throw new RuntimeException("Empty query provided.");
		}

		self::$statement = self::$connection->prepare($query);
		self::bindParams($params);

		try {
			self::$statement->execute();
			$result = self::$statement->fetchAll(PDO::FETCH_ASSOC);
		} catch (\Exception $e) {
			$_ENV["SQL_ERROR"] = true;
			throw new RuntimeException("Error executing select query: " . $e->getMessage());
		}

		return $result;
	}

	public static function selectAll(string $table): array
	{
		return self::selectQuery("SELECT * FROM $table");
	}

	public static function selectAllByCondition($table, $condName, $condParam): array
	{
		return self::selectQuery("SELECT * FROM $table WHERE $condName = $condParam");
	}

	public static function selectColumnNames(string $table): array|null
	{
		$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' AND TABLE_SCHEMA = '" . $_ENV["DB_DATABASE"] . "'";
		if ($query == null) {
			return null;
		}

		return self::selectQuery($query);
	}

	public static function updateQuery($query, $params = NULL)
	{
		self::createConnection();

		self::$lastQuery = $query;
		self::$lastParams = $params ? $params : [];

		self::$statement = self::$connection->prepare($query);
		self::bindParams($params);

		try {
			$response = self::$statement->execute();
		} catch (\Exception $e) {
			$_ENV["SQL_ERROR"] = true;
			throw new RuntimeException("Error executing update query: " . $e->getMessage());
		}

		return $response;
	}

	/* exec for queries that don't return a result set */
	public static function updateQueryNonPrepared($query)
	{
		self::createConnection();

		return self::$connection->exec($query);
	}

	public static function deleteQuery($query, $params = NULL)
	{
		self::createConnection();

		self::$lastQuery = $query;
		self::$lastParams = $params ? $params : [];

		self::$statement = self::$connection->prepare($query);
		self::bindParams($params);

		try {
			self::$statement->execute();
		} catch (\Exception $e) {
			$_ENV["SQL_ERROR"] = true;
			throw new RuntimeException("Error executing delete query: " . $e->getMessage());
		}
	}

	public static function insertQuery(string $query, $params = NULL)
	{
		self::createConnection();

		self::$lastQuery = $query;
		self::$lastParams = $params ? $params : [];

		self::$statement = self::$connection->prepare($query);
		self::bindParams($params);

		try {
			self::$statement->execute();
			$lastInsertId = self::$connection->lastInsertId();
		} catch (\Exception $e) {
			$_ENV["SQL_ERROR"] = true;
			throw new RuntimeException("Error executing insert query: " . $e->getMessage());
		}

		return $lastInsertId;
	}

	/**
	 * https://stackoverflow.com/questions/1176352/pdo-prepared-inserts-multiple-rows-in-single-query
	 * Switched to prepared statements to avoid SQL injection and escape errors
	 * 
	 * @param string $queryPart has to look like "INSER INTO tbl (col1, ...) VALUES 
	 * @param array $data
	 * 
	 * @return int
	 */
	public static function insertMultiple($queryPart, $data)
	{
		if ($data == null || !is_array($data) || count($data) == 0) {
			return 0;
		}
		self::$lastQuery = $queryPart;
		self::$lastParams = $data ? $data : [];

		$values = str_repeat('?,', count($data[0]) - 1) . '?';
		$sql = $queryPart .
			str_repeat("($values),", count($data) - 1) . "($values)";

		self::createConnection();
		self::$statement = self::$connection->prepare($sql);
		self::$statement->execute(array_merge(...$data));
		return self::$connection->lastInsertId();
	}

	private static function bindParams(&$params)
	{
		if ($params == NULL) {
			return;
		}

		foreach ($params as $key => &$val) {
			$dataType = getType($val);
			$paramKey = $key;
			if (is_numeric($paramKey)) {
				$paramKey++;
			}

			switch ($dataType) {
				case "integer":
					self::$statement->bindParam($paramKey, $val, PDO::PARAM_INT);
					break;
				case "string":
					self::$statement->bindParam($paramKey, $val, PDO::PARAM_STR);
					break;
				case "array":
					$val = json_encode($val);
					self::$statement->bindParam($paramKey, $val, PDO::PARAM_STR);
					break;
				case "NULL":
					self::$statement->bindParam($paramKey, $val, PDO::PARAM_NULL);
					break;
			}
		}
	}

	public static function executeQuery($query)
	{
		self::createConnection();

		self::$statement = self::$connection->prepare($query);
		self::$statement->execute();
	}

	public static function getLastInsertId()
	{
		return self::$connection->lastInsertId();
	}

	/**
	 * returns the number of affected rows by the last INSERT, UPDATE, DELETE query
	 * @return int
	 */
	public static function getAffectedRows()
	{
		return self::$statement->rowCount();
	}

	public static function getInterpolatedQuery()
	{
		$sql = self::$lastQuery;
		$params = self::$lastParams;

		foreach ($params as $param) {
			$replacement = is_numeric($param) ? $param : "'" . addslashes((string)$param) . "'";
			$sql = preg_replace('/\?/', $replacement, $sql, 1);
		}

		return $sql;
	}

	public static function getLastQuery()
	{
		return self::$lastQuery;
	}

	public static function getLastParams()
	{
		return self::$lastParams;
	}

	/**
	 * by https://github.com/ttodua/useful-php-scripts
	 * ##### EXAMPLE #####
	 * EXPORT_DATABASE("localhost","user","pass","db_name" ); 
	 * 
	 * ##### Notes #####
	 * (optional) 5th parameter: to backup specific tables only,like: array("mytable1","mytable2",...)   
	 * (optional) 6th parameter: backup filename (otherwise, it creates random name)
	 * IMPORTANT NOTE ! Many people replaces strings in SQL file, which is not recommended.
	 * READ THIS:  http://puvox.software/tools/wordpress-migrator
	 * If you need, you can check "import.php" too
	 */
	public static function EXPORT_DATABASE($host, $user, $pass, $name, $tables = false, $backup_name = false, $send_headers = true)
	{
		set_time_limit(3000);
		$mysqli = new \mysqli($host, $user, $pass, $name);
		$mysqli->select_db($name);
		$mysqli->query("SET NAMES 'utf8'");
		$queryTables = $mysqli->query('SHOW TABLES');
		while ($row = $queryTables->fetch_row()) {
			$target_tables[] = $row[0];
		}
		if ($tables !== false) {
			$target_tables = array_intersect($target_tables, $tables);
		}
		$content = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\r\nSET time_zone = \"+00:00\";\r\n\r\n\r\n/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\r\n/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\r\n/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\r\n/*!40101 SET NAMES utf8 */;\r\n--\r\n-- Database: `" . $name . "`\r\n--\r\n\r\n\r\n";
		foreach ($target_tables as $table) {
			if (empty($table)) {
				continue;
			}
			$result	= $mysqli->query('SELECT * FROM `' . $table . '`');
			$fields_amount = $result->field_count;
			$rows_num = $mysqli->affected_rows;
			$res = $mysqli->query('SHOW CREATE TABLE ' . $table);
			$TableMLine = $res->fetch_row();
			$content .= "\n\n" . $TableMLine[1] . ";\n\n";
			$TableMLine[1] = str_ireplace('CREATE TABLE `', 'CREATE TABLE IF NOT EXISTS `', $TableMLine[1]);
			for ($i = 0, $st_counter = 0; $i < $fields_amount; $i++, $st_counter = 0) {
				while ($row = $result->fetch_row()) { //when started (and every after 100 command cycle):
					if ($st_counter % 100 == 0 || $st_counter == 0) {
						$content .= "\nINSERT INTO " . $table . " VALUES";
					}
					$content .= "\n(";
					for ($j = 0; $j < $fields_amount; $j++) {
						$row[$j] = str_replace("\n", "\\n", addslashes($row[$j] ? $row[$j] : ""));
						if (isset($row[$j])) {
							$content .= '"' . $row[$j] . '"';
						} else {
							$content .= '""';
						}
						if ($j < ($fields_amount - 1)) {
							$content .= ',';
						}
					}
					$content .= ")";
					//every after 100 command cycle [or at last line] ....p.s. but should be inserted 1 cycle eariler
					if ((($st_counter + 1) % 100 == 0 && $st_counter != 0) || $st_counter + 1 == $rows_num) {
						$content .= ";";
					} else {
						$content .= ",";
					}
					$st_counter = $st_counter + 1;
				}
			}
			$content .= "\n\n\n";
		}
		$content .= "\r\n\r\n/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\r\n/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\r\n/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;";
		$backup_name = $backup_name ? $backup_name : $name . '___(' . date('H-i-s') . '_' . date('d-m-Y') . ').sql';

		if ($send_headers) {
			ob_get_clean();
			header('Content-Type: application/octet-stream');
			header("Content-Transfer-Encoding: Binary");
			header('Content-Length: ' . (function_exists('mb_strlen') ? mb_strlen($content, '8bit') : strlen($content)));
			header("Content-disposition: attachment; filename=\"" . $backup_name . "\"");
		}

		return $content;
	}

	private static function closeStatement()
	{
		self::$statement = null;
	}

	private static function closeConnection()
	{
		self::$connection = null;
	}

	public static function close()
	{
		self::closeStatement();
		self::closeConnection();
	}
}
