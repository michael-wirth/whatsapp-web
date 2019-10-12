<?php
namespace WhatsApp\Database;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use Symfony\Component\Yaml\Yaml;
use WhatsApp\Common\ApiError;
use WhatsApp\Common\Util;
use WhatsApp\Constants;

class DBUtil
{
    const TABLES = array(Constants::TABLE_CONTACTS, Constants::TABLE_USERS, Constants::TABLE_TOKENS, Constants::TABLE_CERTS);
    private $logger = null;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public static function getPDO($dbOpts)
    {
        if (array_key_exists('user', $dbOpts)) {
            $user = $dbOpts['user'];
        } else {
            $user = null;
        }
        if (array_key_exists('password', $dbOpts)) {
            $password = $dbOpts['password'];
        } else {
            $password = null;
        }
        $dbDriverOpts = $dbOpts['driverOptions'];
        $dbDriverOpts[\PDO::ATTR_PERSISTENT] = DBUtil::attrPersistent();
        $dbh = new \PDO(DBUtil::getPDODSN($dbOpts), $user, $password, $dbDriverOpts);
        return $dbh;
    }

    private static function attrPersistent()
    {
        $dbPersistentConn = getenv('WA_DB_PERSISTENT');
        if ($dbPersistentConn) {
            return $dbPersistentConn;
        }
        return false;
    }

    private static function getPDODSN($dbOpts)
    {
        if ($dbOpts['driver'] === Constants::DB_DRIVER_MYSQL) {
            $host = 'host=' . $dbOpts['host'];
            $port = 'port=' . $dbOpts['port'];
            $charset = 'charset=' . $dbOpts['charset'];
            $dbname = 'dbname=' . $dbOpts['dbname'];
            $dsn = "mysql:$host;$port;$charset;$dbname";
        } else if ($dbOpts['driver'] === Constants::DB_DRIVER_PGSQL) {
            $host = 'host=' . $dbOpts['host'];
            $port = 'port=' . $dbOpts['port'];
            $charset = "options='--client_encoding=" . $dbOpts['charset'] . "'";
            $dbname = 'dbname=' . $dbOpts['dbname'];
            $dsn = "pgsql:$host;$port;$charset;$dbname";
        } else {
            $dsn = 'sqlite:' . $dbOpts['path'];
        }
        return $dsn;
    }

    public static function getDatabaseConfigFromFile($cfgfile, $dbname)
    {
        $cfg_data = file_get_contents($cfgfile);
        if (!$cfg_data) {
            throw new \Exception("Fail to read " . $cfgfile);
        }
        $dbConfig = Yaml::parse($cfg_data);
        if (!isset($dbConfig['dbname_prefix'])) {
            $dbConfig['dbname_prefix'] = '';
        }
        $dbConfig['dbname'] = $dbConfig['dbname_prefix'] . $dbname;
        return $dbConfig;
    }

    public static function getDatabaseConfigFromEnv($dbname)
    {
        $dbConfig = array();
        $dbConfig['dbname'] = $dbname;
        $dbType = getenv('WA_DB_ENGINE');
        if ($dbType) {
            $dbConfig['engine'] = $dbType;
        }
        $dbHostname = getenv('WA_DB_HOSTNAME');
        if ($dbHostname) {
            if (filter_var($dbHostname, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $dbHostname = '[' . $dbHostname . ']';
            }
            $dbConfig['hostname'] = $dbHostname;
        }
        $dbPort = getenv('WA_DB_PORT');
        if ($dbPort) {
            $dbConfig['port'] = $dbPort;
        }
        $dbUsername = getenv('WA_DB_USERNAME');
        if ($dbUsername) {
            $dbConfig['username'] = $dbUsername;
        }
        $dbPassword = getenv('WA_DB_PASSWORD');
        if ($dbPassword) {
            $dbConfig['password'] = $dbPassword;
        }
        $dbPrefix = getenv('WA_DB_NAME_PREFIX');
        if ($dbPrefix) {
            $dbConfig['dbname_prefix'] = $dbPrefix;
        }
        $dbSslKey = getenv('WA_DB_SSL_KEY');
        if ($dbSslKey) {
            $dbConfig['ssl_key'] = $dbSslKey;
        }
        $dbSslCert = getenv('WA_DB_SSL_CERT');
        if ($dbSslCert) {
            $dbConfig['ssl_cert'] = $dbSslCert;
        }
        $dbSslCa = getenv('WA_DB_SSL_CA');
        if ($dbSslCa) {
            $dbConfig['ssl_ca'] = $dbSslCa;
        }
        $dbSslVerify = getenv('WA_DB_SSL_VERIFY');
        if ($dbSslVerify !== false) {
            $dbConfig['ssl_verify'] = $dbSslVerify;
        }
        $dbTimeout = getenv('WA_DB_READ_TIMEOUT');
        if ($dbTimeout) {
            $dbConfig['db_timeout'] = $dbTimeout;
        }
        return self::getDatabaseConfig($dbConfig);
    }

    public static function getDatabaseConfig($dbSettings)
    {
        $dbname = isset($dbSettings['dbname']) ? $dbSettings['dbname'] : Constants::WEB_DB_NAME;
        $dbType = isset($dbSettings['engine']) ? strtolower($dbSettings['engine']) : Constants::DB_SQLITE;
        if ($dbType !== Constants::DB_MYSQL && $dbType !== Constants::DB_SQLITE && $dbType !== Constants::DB_PGSQL) {
            throw new \Exception("Invalid database_engine " . $dbType, ApiError::PARAMETER_INVALID);
        }
        if ($dbType === Constants::DB_SQLITE) {
            if (Util::isMultiConnect()) {
                throw new \Exception("database engine sqlite is not supported for multiconnect mode", ApiError::PARAMETER_INVALID);
            }
            return array('driver' => Constants::DB_DRIVER_SQLITE, 'path' => Constants::LOCAL_DB_DIR . $dbname . '.db');
        }
        if (!isset($dbSettings['hostname'], $dbSettings['username'], $dbSettings['password'])) {
            throw new \Exception("db hostname, db username and db password are required", ApiError::PARAMETER_ABSENT);
        }
        if (!isset($dbSettings['port'])) {
            $dbSettings['port'] = Constants::DEFAULT_DB_PORT;
        }
        if (!isset($dbSettings['dbname_prefix'])) {
            $dbSettings['dbname_prefix'] = '';
        }
        $dbDriverOpts = DBUtil::getDatabaseDriverOptions($dbSettings);
        if ($dbType === Constants::DB_PGSQL) {
            $dbDriver = Constants::DB_DRIVER_PGSQL;
        } else {
            $dbDriver = Constants::DB_DRIVER_MYSQL;
        }
        if ($dbType === Constants::DB_MYSQL) {
            $dbCharSet = 'utf8mb4';
        } else {
            $dbCharSet = 'utf8';
        }
        $dbConfig = array('driver' => $dbDriver, 'host' => $dbSettings['hostname'], 'user' => $dbSettings['username'], 'password' => $dbSettings['password'], 'port' => $dbSettings['port'], 'dbname_prefix' => $dbSettings['dbname_prefix'], 'dbname' => $dbSettings['dbname_prefix'] . $dbname, 'charset' => $dbCharSet);
        if (count($dbDriverOpts)) {
            $dbConfig['driverOptions'] = $dbDriverOpts;
        }
        return $dbConfig;
    }

    public static function getDatabaseDriverOptions($dbSettings)
    {
        $dbDriverOpts = array();
        if ($dbSettings['engine'] === Constants::DB_MYSQL) {
            if (isset($dbSettings['ssl_ca'])) {
                $dbDriverOpts[\PDO::MYSQL_ATTR_SSL_CA] = $dbSettings['ssl_ca'];
            }
            if (isset($dbSettings['ssl_cert'])) {
                $dbDriverOpts[\PDO::MYSQL_ATTR_SSL_CERT] = $dbSettings['ssl_cert'];
            }
            if (isset($dbSettings['ssl_key'])) {
                $dbDriverOpts[\PDO::MYSQL_ATTR_SSL_KEY] = $dbSettings['ssl_key'];
            }
            if (isset($dbSettings['ssl_verify'])) {
                $dbDriverOpts[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = (bool)$dbSettings['ssl_verify'];
            }
        }
        if (isset($dbSettings['db_timeout'])) {
            $dbDriverOpts[\PDO::ATTR_TIMEOUT] = $dbSettings['db_timeout'];
        } else {
            $dbDriverOpts[\PDO::ATTR_TIMEOUT] = Constants::DEFAULT_DB_TIMEOUT;
        }
        return $dbDriverOpts;
    }

    public function migrateDatabase(Connection $conn, $newDBOptions)
    {
        $retval = true;
        do {
            if ($newDBOptions['driver'] === self::getDBDriver($conn)) {
                $this->logger->info("Database type hasn't changed.");
                $retval = false;
                break;
            }
            $newConn = $this->createDBConnection($newDBOptions);
            if (!$newConn) {
                $this->logger->info("Unable to create connection to new Database");
                $retval = false;
                break;
            }
            $this->initDatabase($newConn, false);
            $this->wipeTables($newConn);
            foreach (self::TABLES as $table) {
                $this->copyTable($conn, $newConn, $table);
            }
            $this->logger->info("Database migrated successfully");
            $this->dropDatabase($conn);
        } while (false);
        return $retval;
    }

    public static function getDBDriver(Connection $conn)
    {
        $dbParams = $conn->getParams();
        return $dbParams['driver'];
    }

    public function createDBConnection($dbOptions)
    {
        do {
            $dbname = isset($dbOptions['dbname']) ? $dbOptions['dbname'] : Constants::WEB_DB_NAME;
            unset($dbOptions['dbname']);
            $config = new \Doctrine\DBAL\Configuration();
            $dbOptions['wrapperClass'] = 'WhatsApp\Database\DBConnection';
            $conn = \Doctrine\DBAL\DriverManager::getConnection($dbOptions, $config);
            if ($this->isDBSQLITE($conn)) {
                break;
            }
            $this->createDatabase($conn, $dbname);
            $dbOptions['dbname'] = $dbname;
            $conn = \Doctrine\DBAL\DriverManager::getConnection($dbOptions, $config);
        } while (false);
        return $conn;
    }

    private function isDBSQLITE(Connection $conn)
    {
        return (self::getDBDriver($conn) === Constants::DB_DRIVER_SQLITE);
    }

    private function createDatabase(Connection $conn, $dbname)
    {
        $schema = $conn->getSchemaManager();
        $databases = $schema->listDatabases();
        if (!in_array($dbname, $databases)) {
            $schema->createDatabase($dbname);
        }
    }

    public function initDatabase(Connection $conn, $createAdmin = true)
    {
        $this->createTables($conn, $createAdmin);
    }

    private function createTables(Connection $conn, $createAdmin)
    {
        $driver = DBUtil::getDBDriver($conn);
        $schema = $conn->getSchemaManager();
        if (!$schema->tablesExist(Constants::TABLE_CONTACTS)) {
            $contacts = new Table(Constants::TABLE_CONTACTS);
            $contacts->addColumn(Constants::KEY_WA_ID, 'string', array('length' => 256));
            $contacts->setPrimaryKey(array(Constants::KEY_WA_ID));
            $contacts->addUniqueIndex(array(Constants::KEY_WA_ID));
            if ($driver === Constants::DB_DRIVER_MYSQL) {
                $contacts->addColumn(Constants::KEY_NAME, 'text', array('customSchemaOptions' => array('charset' => 'utf8mb4', 'collation' => 'utf8mb4_bin')));
            } else {
                $contacts->addColumn(Constants::KEY_NAME, 'text');
            }
            $contacts->addColumn(Constants::KEY_JOINED, 'boolean');
            $contacts->addColumn(Constants::KEY_TIMESTAMP, 'bigint');
            $schema->createTable($contacts);
        }
        if (!$schema->tablesExist(Constants::TABLE_USERS)) {
            $users = new Table(Constants::TABLE_USERS);
            $users->addColumn(Constants::KEY_ID, 'integer', array('unsigned' => true, 'autoincrement' => true));
            $users->setPrimaryKey(array(Constants::KEY_ID));
            $users->addColumn(Constants::KEY_USERNAME, 'string', array('length' => 32));
            $users->addUniqueIndex(array(Constants::KEY_USERNAME));
            $users->addColumn(Constants::KEY_PASSWORD, 'string', array('length' => 255));
            $users->addColumn(Constants::KEY_ROLES, 'string', array('length' => 255));
            $schema->createTable($users);
            if ($createAdmin) {
                $conn->insert(Constants::TABLE_USERS, array(Constants::KEY_USERNAME => Constants::ADMIN_NAME, Constants::KEY_PASSWORD => Constants::ADMIN_PASSWORD, Constants::KEY_ROLES => Constants::ADMIN_ROLE));
                if (Util::isSandboxMode()) {
                    $conn->insert(Constants::TABLE_USERS, array(Constants::KEY_USERNAME => Constants::SB_ADMIN_NAME, Constants::KEY_PASSWORD => Constants::ADMIN_PASSWORD, Constants::KEY_ROLES => Constants::ADMIN_ROLE));
                }
            }
        }
        if (!$schema->tablesExist(Constants::TABLE_TOKENS)) {
            $tokens = new Table(Constants::TABLE_TOKENS);
            $tokens->addColumn(Constants::KEY_TOKEN, 'string', array('length' => 1024));
            $tokens->addUniqueIndex(array(Constants::KEY_TOKEN));
            $tokens->addColumn(Constants::KEY_USERNAME, 'string', array('length' => 32));
            $schema->createTable($tokens);
        }
        $sqlText = 'CREATE TABLE IF NOT EXISTS ' . Constants::TABLE_CERTS . '(' . Constants::KEY_HOSTNAME . ' varchar(256), ' . Constants::KEY_CERT . ' TEXT, ' . Constants::KEY_CA_CERT . ' TEXT, ' . Constants::KEY_CERT_TYPE . ' varchar(256), PRIMARY KEY (' . Constants::KEY_HOSTNAME . ', ' . Constants::KEY_CERT_TYPE . '))';
        $sql = $conn->prepare($sqlText);
        $sql->execute();
    }

    public function wipeTables(Connection $conn)
    {
        foreach (self::TABLES as $table) {
            $this->logger->info("Wiping entries from table " . $table);
            $this->wipeTable($conn, $table);
        }
    }

    private function wipeTable(Connection $conn, $table)
    {
        if ($this->checkTableName($table) === true) {
            $sql = $conn->prepare('DELETE FROM ' . $table);
            $sql->execute();
        }
    }

    private function copyTable(Connection $srcConn, Connection $dstConn, $table)
    {
        if ($this->checkTableName($table) === true) {
            $this->logger->info("Copying table " . $table);
            $sql = 'SELECT * FROM ' . $table;
            $stmt = $srcConn->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();
            foreach ($results as $result) {
                Util::printArray($this->logger, $result);
                $dstConn->insert($table, $result);
            }
        }
    }

    public function dropDatabase(Connection $conn)
    {
        foreach (self::TABLES as $table) {
            $this->logger->info("Dropping table " . $table);
            $this->dropTable($conn, $table);
        }
    }

    private function dropTable(Connection $conn, $table)
    {
        if ($this->checkTableName($table) === true) {
            $sql = $conn->prepare('DROP TABLE IF EXISTS ' . $table);
            $sql->execute();
        }
    }

    private function checkTableName($table)
    {
        if (preg_match("/^[\w]+$/", $table) === 1) {
            return true;
        }
        throw new \Exception("Invalid table name", ApiError::PARAMETER_INVALID);
    }
} ?>
