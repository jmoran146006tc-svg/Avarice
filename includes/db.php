<?php
define('DB_HOST',    $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST'));
define('DB_PORT',    $_ENV['MYSQL_PORT'] ?? getenv('MYSQL_PORT') ?: '3306');
define('DB_NAME',    $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE'));
define('DB_USER',    $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER'));
define('DB_PASS',    $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD'));
define('DB_CHARSET', 'utf8mb4');

/**
 * Get PDO database connection instance
 */
function getDB(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
    }
    
    return $pdo;
}