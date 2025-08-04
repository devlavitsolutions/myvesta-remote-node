<?php

class Database
{
    private $db_host;
    private $db_user;
    private $db_password;
    private $db_name;
    private $db_charset;
    private $pdo;
    private $db_port;

    // Constructor to initialize the database connection parameters
    public function __construct($configFile)
    {
        // Read configuration from the provided file
        $config = $this->parse_mysql_config($configFile);

        // Set the connection parameters using the config file
        // $this->db_host = $config['host'] ?? $config['HOST'] ?? 'localhost';
        // $this->db_user = $config['user'] ?? $config['USER'] ?? 'root';
        // $this->db_password = $config['password'] ?? $config['PASSWORD'] ?? '';
        // $this->db_name = $config['hchq_dbname'] ?? $config['HCHQ_DBNAME'] ?? 'myvesta_newdb';
        // $this->db_charset = $config['hchq_charset'] ?? $config['HCHQ_CHARSET'] ?? 'utf8';
        // $this->db_port = $config['hchq_port'] ?? $config['HCHQ_PORT'] ?? '3306';
    }

    // Function to parse the config files and return values as an associative array
    private function parse_mysql_config($file)
    {
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
        try {
            // Set the DSN (Data Source Name) for PDO
            $dsn = "mysql:host=$this->db_host";
            if ($db_already_created) {
                $dsn .= ";dbname=$this->db_name;charset=$this->db_charset";
            }

            // Create a PDO instance
            $this->pdo = new PDO($dsn, $this->db_user, $this->db_password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $this->pdo;
        } catch (PDOException $e) {
            print_r($e);
            exit;
        }
    }

    public function createDatabaseIfNotExists()
    {
        try {
            // First, connect to MySQL without specifying a database
            $this->connect(false); // This connects to MySQL server without any specific database

            // Check if the database already exists
            $stmt = $this->pdo->prepare("SHOW DATABASES LIKE :db_name");
            $stmt->bindParam(':db_name', $this->db_name);
            $stmt->execute();

            // If the database doesn't exist, create it
            if ($stmt->rowCount() === 0) {
                $stmt = $this->pdo->prepare("CREATE DATABASE IF NOT EXISTS `$this->db_name` CHARACTER SET $this->db_charset COLLATE utf8_general_ci");
                $stmt->execute();
            }
        } catch (PDOException $e) {
            exit;
        }
    }

    // Getter for PDO instance
    public function getConnection()
    {
        return $this->pdo;
    }

    public function hasTable(string $tableName)
    {
        try {
            // Prepare and execute the SQL to check if the table exists
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE :table_name");
            $stmt->bindParam(':table_name', $tableName);
            $stmt->execute();

            // If a row is returned, the table exists
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function syncDomainsFromScan(array $scanned)
    {
        $pdo = $this->connect(); // Ensure DB is connected

        // Fetch current DB domains
        $stmt = $pdo->query("SELECT name FROM domains");
        $existingDomains = $stmt->fetchAll(PDO::FETCH_COLUMN);

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
            $stmt = $pdo->prepare("DELETE FROM domains WHERE name IN ($placeholders)");
            $stmt->execute(array_values($toDelete));
        }

        // Insert new domains
        $toInsert = array_diff($scannedNames, $existingDomains);
        if (!empty($toInsert)) {
            $stmt = $pdo->prepare("
                INSERT INTO domains (name, hasWordPress, isCacheEnabled, isFlagged, created_at, updated_at)
                VALUES (:name, :hasWordPress, 0, 0, :created_at, :updated_at)
            ");
            $now = date('Y-m-d H:i:s');
            foreach ($toInsert as $name) {
                $stmt->execute([
                    ':name' => $name,
                    ':hasWordPress' => $scannedMap[$name] ? 1 : 0,
                    ':created_at' => $now,
                    ':updated_at' => $now,
                ]);
            }
        }

        // Update hasWordPress for all
        $stmt = $pdo->prepare("
            UPDATE domains SET hasWordPress = :hasWordPress, updated_at = :updated_at WHERE name = :name
        ");
        $now = date('Y-m-d H:i:s');
        foreach ($scannedMap as $name => $hasWP) {
            $stmt->execute([
                ':name' => $name,
                ':hasWordPress' => $hasWP ? 1 : 0,
                ':updated_at' => $now,
            ]);
        }

        return true;
    }
}