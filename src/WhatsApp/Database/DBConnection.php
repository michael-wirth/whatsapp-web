<?php
namespace WhatsApp\Database;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver;
use Silex\Application;
use WhatsApp\Metrics\GlobalMetricsType;
use WhatsApp\Metrics\StoreTime;

class DBConnection extends Connection
{
    const SUCCESS = 'ok';
    private $app;

    public function __construct(array $params, Driver $driver, Configuration $config = null, EventManager $eventManager = null)
    {
        parent::__construct($params, $driver, $config, $eventManager);
    }

    public function setApplication(Application $app)
    {
        $this->app = $app;
    }

    public function connect()
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::connect();
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function fetchAssoc($statement, array $params = [], array $types = [])
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::fetchAssoc($statement, $params, $types);
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function fetchArray($statement, array $params = [], array $types = [])
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::fetchArray($statement, $params, $types);
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function fetchColumn($statement, array $params = [], $column = 0, array $types = [])
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::fetchColumn($statement, $params, $column, $types);
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function delete($tableExpression, array $identifier, array $types = [])
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::delete($tableExpression, $identifier, $types);
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function update($tableExpression, array $data, array $identifier, array $types = [])
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::update($tableExpression, $data, $identifier, $types);
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function executeQuery($query, array $params = [], $types = [], QueryCacheProfile $qcp = null)
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::executeQuery($query, $params, $types, $qcp);
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function insert($tableExpression, array $data, array $types = [])
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::insert($tableExpression, $data, $types);
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function fetchAll($sql, array $params = [], $types = [])
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::fetchAll($sql, $params, $types);
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function executeCacheQuery($query, $params, $types, QueryCacheProfile $qcp)
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::executeCacheQuery($quert, $params, $types, $qcp);
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function query()
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::query();
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function executeUpdate($query, array $params = [], array $types = [])
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::executeUpdate($query, $params, $types);
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function exec($statement)
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::exec($statement);
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $e) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $e;
        }
    }

    public function beginTransaction()
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::beginTransaction();
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function commit()
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::commit();
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function rollback()
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::rollback();
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function createSavepoint($savepoint)
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::createSavepoint();
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function rollbackSavepoint($savepoint)
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::rollbackSavepoint();
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function getWrappedConnection()
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::getWrappedConnection();
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }

    public function ping()
    {
        $storeTime = new StoreTime($this->app, GlobalMetricsType::API_REQUESTS_DB, array(__FUNCTION__));
        try {
            $result = parent::ping();
            $storeTime->setResult(self::SUCCESS);
            return $result;
        } catch (DBALException $err) {
            $previous = $err->getPrevious();
            $storeTime->setResult($previous->errorInfo[0] . "/" . (isset($previous->errorInfo[1]) ? $previous->errorInfo[1] : ""));
            throw $err;
        }
    }
} ?>
