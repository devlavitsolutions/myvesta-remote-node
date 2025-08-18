<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

class Database
{
    const DEFAULT_HOST     = 'localhost';
    const DEFAULT_USER     = 'root';
    const DEFAULT_PASSWORD = '';
    const DEFAULT_DB_NAME  = 'myvesta_newdb';
    const DEFAULT_CHARSET  = 'utf8';
    const DEFAULT_PORT     = '3306';

    private $db_host;
    private $db_user;
    private $db_password;
    private $db_name;
    private $db_charset;
    private $mysqli;
    private $db_port;

    // Constructor to initialize the database connection parameters
    public function __construct($configFile)
    {
        // Read configuration from the provided file
        try {
            $config = $this->parse_mysql_config($configFile);
        } catch (Exception $e) {
            die('Database config error: ' . $e->getMessage());
        }

        // Set the connection parameters using the config file
        $this->db_host     = isset($config['host']) ? $config['host'] : (isset($config['HOST']) ? $config['HOST'] : self::DEFAULT_HOST);
        $this->db_user     = isset($config['user']) ? $config['user'] : (isset($config['USER']) ? $config['USER'] : self::DEFAULT_USER);
        $this->db_password = isset($config['password']) ? $config['password'] : (isset($config['PASSWORD']) ? $config['PASSWORD'] : self::DEFAULT_PASSWORD);
        $this->db_name     = isset($config['hchq_dbname']) ? $config['hchq_dbname'] : (isset($config['HCHQ_DBNAME']) ? $config['HCHQ_DBNAME'] : self::DEFAULT_DB_NAME);
        $this->db_charset  = isset($config['hchq_charset']) ? $config['hchq_charset'] : (isset($config['HCHQ_CHARSET']) ? $config['HCHQ_CHARSET'] : self::DEFAULT_CHARSET);
        $this->db_port     = isset($config['hchq_port']) ? $config['hchq_port'] : (isset($config['HCHQ_PORT']) ? $config['HCHQ_PORT'] : self::DEFAULT_PORT);
    }

    // Function to parse the config files and return values as an associative array
    private function parse_mysql_config($file)
    {
        if (!file_exists($file)) {
            throw new Exception("Configuration file not found: $file");
        }

        $config = [];
        if (file_exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); // Read the file line by line
            foreach ($lines as $line) {
                if (strpos($line, '#') === 0) continue; // Ignore comments
                if (strpos($line, '=') !== false) {  // Ignore lines without an '=' sign
                    list($key, $value) = explode('=', $line, 2);
                    $config[trim($key)] = preg_replace('~^[\'"]?(.*?)[\'"]?$~', '$1', $value);
                }
            }
        }

        if (empty($config)) {
            throw new Exception("Configuration file is empty or invalid: $file");
        }

        return $config;
    }

    public function getHost() {
        return $this->db_host;
    }

    public function getUser() {
        return $this->db_user;
    }

    public function getPassword() {
        return $this->db_password;
    }

    public function getDbName() {
        return $this->db_name;
    }

    public function getCharset() {
        return $this->db_charset;
    }

    public function getPort() {
        return $this->db_port;
    }

    // Function to establish the database connection
    public function connect($db_already_created = true)
    {
        // Connect either to specific database or just to the server
        if ($db_already_created) {
            $this->mysqli = mysqli_connect('localhost', 'root', $this->db_password, $this->db_name, 3306, '/var/run/mysqld/mysqld.sock');
        } else {
            $this->mysqli = mysqli_connect('localhost', 'root', $this->db_password, '', 3306, '/var/run/mysqld/mysqld.sock');
        }

        if ($this->mysqli->connect_error) {
            die("Connection failed: " . $this->mysqli->connect_error);
        }

        // Set charset
        $this->mysqli->set_charset($this->db_charset);

        return $this->mysqli;
    }

    public function createDatabaseIfNotExists()
    {
        $this->connect(false); // connect without db

        $dbNameEscaped = $this->mysqli->real_escape_string($this->db_name);

        $sql = "SHOW DATABASES LIKE '$dbNameEscaped'";
        $result = $this->mysqli->query($sql);

        if ($result && $result->num_rows === 0) {
            $sqlCreate = "CREATE DATABASE `$dbNameEscaped` CHARACTER SET $this->db_charset COLLATE utf8_general_ci";
            $this->mysqli->query($sqlCreate);
        }

        $result->free();
    }

    public function getConnection()
    {
        return $this->mysqli;
    }

    public function hasTable(string $tableName)
    {
        $tableNameEscaped = $this->mysqli->real_escape_string($tableName);

        $sql = "SHOW TABLES LIKE '$tableNameEscaped'";
        $result = $this->mysqli->query($sql);

        if ($result) {
            $exists = $result->num_rows > 0;
            $result->free();
            return $exists;
        }

        return false;
    }

    public function syncDomainsFromScan(array $scanned)
    {
        $mysqli = $this->connect(); // Connect using MySQLi instead of PDO

        // Fetch current DB domains
        $result = $mysqli->query("SELECT name FROM domains");
        $existingDomains = [];
        while ($row = $result->fetch_assoc()) {
            $existingDomains[] = $row['name'];
        }

        // Create scanned name => hasWordPress map
        $scannedMap = [];
        foreach ($scanned as $entry) {
            $scannedMap[$entry['domain']] = (bool)$entry['wordpress'];
        }

        $scannedNames = array_keys($scannedMap);

        // Delete domains that no longer exist
        $toDelete = array_diff($existingDomains, $scannedNames);
        if (!empty($toDelete)) {
            $placeholders = implode(',', array_fill(0, count($toDelete), '?'));
            $stmt = $mysqli->prepare("DELETE FROM domains WHERE name IN ($placeholders)");
            $types = str_repeat('s', count($toDelete));
            $stmt->bind_param($types, ...$toDelete);
            $stmt->execute();
        }

        // Insert new domains
        $toInsert = array_diff($scannedNames, $existingDomains);
        if (!empty($toInsert)) {
            $stmt = $mysqli->prepare("
                INSERT INTO domains (name, hasWordPress, isCacheEnabled, isFlagged, created_at, updated_at)
                VALUES (?, ?, 0, 0, ?, ?)
            ");
            $now = date('Y-m-d H:i:s');
            foreach ($toInsert as $name) {
                $hasWP = $scannedMap[$name] ? 1 : 0;
                $stmt->bind_param("siss", $name, $hasWP, $now, $now);
                $stmt->execute();
            }
        }

        // Update hasWordPress for all
        $stmt = $mysqli->prepare("
            UPDATE domains SET hasWordPress = ?, updated_at = ? WHERE name = ?
        ");
        $now = date('Y-m-d H:i:s');
        foreach ($scannedMap as $name => $hasWP) {
            $stmt->bind_param("iss", $hasWP, $now, $name);
            $stmt->execute();
        }

        return true;
    }

    public function select($table, $columns = '*', $limit = 0, $where = '', $extra = '')
    {
        // Make sure $columns is a string or an array
        if (is_array($columns)) {
            $columns = implode(', ', array_map(function ($col) {
                return "`" . trim($col, "` ") . "`";
            }, $columns));
        } elseif (empty($columns)) {
            $columns = '*';
        }

        $sql = "SELECT $columns FROM `$table`";

        if (!empty($where)) {
            $sql .= " WHERE $where";
        }

        if (!empty($extra)) {
            $sql .= " $extra";
        }

        if ($limit > 0) {
            $sql .= " LIMIT $limit";
        }

        $result = $this->mysqli->query($sql);

        if (!$result) {
            throw new Exception("Query failed: " . $this->mysqli->error);
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function update($table, $set, $where = '')
    {
        $setClauses = [];
        foreach ($set as $column => $value) {
            $escapedValue = $this->mysqli->real_escape_string($value);
            $setClauses[] = "`$column` = '$escapedValue'";
        }
        $sql = "UPDATE `$table` SET " . implode(', ', $setClauses);
        if (!empty($where)) {
            $sql .= " WHERE $where";
        }
        $result = $this->mysqli->query($sql);
        if (!$result) {
            throw new Exception("Update failed: " . $this->mysqli->error);
        }
        return $result;
    }

    public function getConfig($key, $default = null)
    {
        $mysqli = $this->connect(); // ensure connection

        $stmt = $mysqli->prepare("SELECT value FROM config WHERE name = ? LIMIT 1");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $mysqli->error);
        }

        $stmt->bind_param('s', $key);

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->bind_result($value);
        if ($stmt->fetch()) {
            $stmt->close();
            return $value;
        }

        $stmt->close();
        return $default;
    }

    public function setConfig($key, $value)
    {
        $mysqli = $this->connect(); // ensure connection
        $now = date('Y-m-d H:i:s');

        // Prepare statement
        $stmt = $mysqli->prepare("
            INSERT INTO config (name, value, created_at, updated_at)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE value = ?, updated_at = ?
        ");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $mysqli->error);
        }

        // Bind parameters (6 values: key, value, created_at, updated_at, value, updated_at)
        $stmt->bind_param('ssssss', $key, $value, $now, $now, $value, $now);

        // Execute
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();
        return true;
    }

}