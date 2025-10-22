<?php

final class PDOConnection
{
    private static ?PDOConnection $_instance = null;

    /** @var ?PDO */
    private ?PDO $_db = null;
    /** @var array */
    private array $_config = [];

    private function __construct()
    {
        $cfg = parse_ini_file(ROOT . '/../config/trackdirect.ini', true);
        if (!is_array($cfg) || !isset($cfg['database'])) {
            throw new RuntimeException('Failed to parse database ini file.');
        }
        $this->_config = $cfg['database'];
        if (!isset($this->_config['username'])) {
            $this->_config['username'] = get_current_user();
        }
        // Valeurs par défaut sûres
        $this->_config += [
            'host'     => 'localhost',
            'port'     => '5432',
            'password' => '',
            'database' => '',
        ];
    }

    public static function getInstance(): PDOConnection
    {
        // Suffisant en PHP-FPM/Apache (par requête/processus)
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    // Interdire clone/unserialize
    private function __clone() {}
    public function __wakeup() { throw new RuntimeException('Cannot unserialize singleton'); }

    private function createConnection(): void
    {
        if ($this->_db !== null) {
            return;
        }

        $dsn = sprintf(
            'pgsql:dbname=%s;host=%s;port=%s;application_name=%s',
            $this->_config['database'],
            $this->_config['host'],
            $this->_config['port'],
            rawurlencode($this->_config['application_name'] ?? 'trackdirect-php')
        );

        try {
            $this->_db = new PDO(
                $dsn,
                $this->_config['username'],
                $this->_config['password'],
                [
                    PDO::ATTR_PERSISTENT         => false, // pas de connexions persistantes
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            // Log détaillé côté serveur, message générique côté appli
            error_log('DB connect error: ' . $e->getMessage());
            throw new RuntimeException('Failed to connect to database.');
        }
    }

    private function getConnection(): PDO
    {
        if ($this->_db === null) {
            $this->createConnection();
        }
        return $this->_db;
    }

    public function query(string $sql): PDOStatement
    {
        return $this->getConnection()->query($sql);
    }

    public function prepare(string $sql): PDOStatement
    {
        return $this->getConnection()->prepare($sql);
    }

    public function prepareAndExec(string $sql, array $arguments = []): PDOStatement
    {
        $stmt = $this->prepare($sql);
        $stmt->execute($arguments);
        return $stmt;
    }

    public function beginTransaction(): bool
    {
        return $this->getConnection()->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->getConnection()->commit();
    }

    public function rollBack(): bool
    {
        return $this->getConnection()->rollBack();
    }

    public function lastInsertId(string $table, string $column): string|false
    {
        $suffix = '_' . $column . '_seq';
        $table  = substr($table, 0, 63 - strlen($suffix));
        $seq    = $table . $suffix;
        return $this->getConnection()->lastInsertId($seq);
    }

    /** Fermer explicitement la connexion */
    public function close(): void
    {
        $this->_db = null; // libère la connexion PDO
    }

    public function __destruct()
    {
        // Sécurité : s’assure que la connexion est fermée en fin de vie
        $this->close();
    }
}
