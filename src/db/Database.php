<?php

namespace Php22\Db;

use PDO;

class Database
{
    private static $connection = null;

    /**
     * Get the database connection.
     *
     * @return PDO
     * @throws \Exception
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::initializeConnection();
        }

        return self::$connection;
    }

    /**
     * Initialize the database connection.
     *
     * @throws \Exception
     */
    private static function initializeConnection()
    {
        $env = self::loadEnv();

        $connection = $env['DB_CONNECTION'] ?? 'mysql';
        $host = $env['DB_HOST'] ?? '127.0.0.1';
        $port = $env['DB_PORT'] ?? '3306';
        $database = $env['DB_DATABASE'] ?? '';
        $username = $env['DB_USERNAME'] ?? '';
        $password = $env['DB_PASSWORD'] ?? '';

        $dsn = match ($connection) {
            'mysql' => "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4",
            'pgsql' => "pgsql:host=$host;port=$port;dbname=$database",
            'sqlite' => "sqlite:$database",
            default => throw new \Exception("Unsupported database connection: $connection"),
        };

        self::$connection = new PDO($dsn, $username, $password);
        self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Load environment variables from the .env file.
     *
     * @return array
     */
    private static function loadEnv(): array
    {
        $envFile = __DIR__ . '/../../.env';

        if (!file_exists($envFile)) {
            throw new \Exception(".env file not found.");
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $env = [];

        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $env[trim($key)] = trim($value);
        }

        return $env;
    }

    /**
     * Initialize a new QueryBuilder instance.
     *
     * @param string $table
     * @return QueryBuilder
     */
    public static function table(string $table): QueryBuilder
    {
        $pdo = self::getConnection();
        $queryBuilder = new QueryBuilder($pdo);
        return $queryBuilder->table($table);
    }
}
