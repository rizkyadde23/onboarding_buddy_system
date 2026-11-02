<?php
/**
 * Database connection singleton
 */
class Database
{
    private static ?PDO $instance = null;

    private const HOST   = 'localhost';
    private const DBNAME = 'onboarding_buddy';
    private const USER   = 'root';
    private const PASS   = '';       // change for production
    private const CHARSET = 'utf8mb4';

    public static function connect(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::HOST, self::DBNAME, self::CHARSET
            );
            self::$instance = new PDO($dsn, self::USER, self::PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$instance;
    }
}
