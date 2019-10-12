<?php
namespace WhatsApp\Common;

use WhatsApp\Constants;
use WhatsApp\Database\DBUtil;

class Cluster
{
    private $initialized = false;
    private $conn;

    public function __construct()
    {
    }

    public function initDatabase($app)
    {
        if ($this->initialized) {
            return;
        }
        $dbOptions = DBUtil::getDatabaseConfigFromFile($app['config.run.db_config_file'], Constants::CLUSTER_DB_NAME);
        $config = new \Doctrine\DBAL\Configuration();
        $dbOptions['wrapperClass'] = 'WhatsApp\Database\DBConnection';
        $dbOptions['pdo'] = DBUtil::getPDO($dbOptions);
        $this->conn = \Doctrine\DBAL\DriverManager::getConnection($dbOptions, $config);
        $this->initialized = true;
    }

    public function getNodes()
    {
        $nodes = array();
        $sth = $this->conn->executeQuery('SELECT hostname, port, container_name FROM nodes');
        $results = $sth->fetchAll();
        if (empty($results)) {
            throw new \Exception("No nodes in clusterStore.nodes");
        }
        foreach ($results as $result) {
            $node = new ConnectorConfig();
            $node->use_tcp = true;
            $node->hostname = $result['hostname'];
            $node->portMap = self::toPortMap($result['port']);
            $node->containerName = $result['container_name'];
            $nodes[] = $node;
        }
        return $nodes;
    }

    public static function toPortMap($portStr)
    {
        $portArray = explode(",", $portStr);
        $ports = array();
        for ($offset = 0; $offset < Constants::NUM_PORTS; $offset++) {
            $ports[$offset] = (int)$portArray[$offset];
        }
        return $ports;
    }

    public function getClusterInfo()
    {
        if (!$this->initialized) {
            throw new \Exception("DB connection is not init");
        }
        $sth = $this->conn->executeQuery("SELECT hostname, port, state, schema_version FROM cluster_info " . "WHERE ckey = 'cluster_info'");
        $clusterData = $sth->fetch(\PDO::FETCH_OBJ);
        if (empty($clusterData)) {
            throw new \Exception("No information in clusterStore.cluster_info");
        }
        $master = new ConnectorConfig();
        $master->use_tcp = true;
        $master->hostname = $clusterData->hostname;
        $master->portMap = self::toPortMap($clusterData->port);
        if ($clusterData->state == "0") {
            if (DBUtil::getDBDriver($this->conn) === Constants::DB_DRIVER_PGSQL) {
                $schemaVersion = json_decode(stream_get_contents($clusterData->schema_version));
            } else {
                $schemaVersion = json_decode($clusterData->schema_version);
            }
            if (is_null($schemaVersion)) {
                throw new \Exception("Schema version {$clusterData->schema_version} in clusterStore.cluster_info is not in json format");
            }
            $shards = (int)$schemaVersion->shards;
            if ($shards <= 0) {
                throw new \Exception("shards {$schemaVersion->shards} in clusterStore.cluster_info is not positive number");
            }
            $serverShards = (int)$schemaVersion->server_shards;
            if ($serverShards <= 0) {
                throw new \Exception("server shards {$schemaVersion->server_shard} in clusterStore.cluster_info is not positive number");
            }
        } else {
            $shards = 1;
            $serverShards = 1024;
        }
        $rangeSize = (int)($serverShards / $shards) + ($serverShards % $shards == 0 ? 0 : 1);
        $shardMap = $this->getShardMap($rangeSize, $serverShards);
        return new ClusterInfo($master, $shards, $serverShards, $rangeSize, $shardMap);
    }

    private function getShardMap($rangeSize, $serverShards)
    {
        $shardMap = array();
        $sth = $this->conn->executeQuery('SELECT lower_bound, upper_bound, type, hostname, port FROM shard_map WHERE type = 1');
        $results = $sth->fetchAll();
        foreach ($results as $result) {
            $lowerBound = (int)$result['lower_bound'];
            $upperBound = (int)$result['upper_bound'];
            if ($lowerBound % $rangeSize !== 0) {
                throw new \Exception("lower_bound in clusterStore.shard_map {$lowerBound} is not multiple of range size {$rangeSize}");
            }
            if (min($lowerBound + $rangeSize, $serverShards) - 1 !== $upperBound) {
                throw new \Exception("shard range [{$lowerBound},{$upperBound}] in clusterStore.shard_map is not valid, expect range size {$rangeSize}");
            }
            $primary = new ConnectorConfig();
            $primary->use_tcp = true;
            $primary->hostname = $result['hostname'];
            $primary->portMap = self::toPortMap($result['port']);
            $shardMap[(int)($lowerBound / $rangeSize)] = $primary;
        }
        return $shardMap;
    }
} ?>
