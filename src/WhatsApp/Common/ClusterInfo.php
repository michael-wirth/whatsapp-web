<?php
namespace WhatsApp\Common;

class ClusterInfo
{
    private $master;
    private $shards;
    private $serverShards;
    private $rangeSize;
    private $shardMap = array();
    private $shardKeys;

    public function __construct($master, $shards, $serverShards, $rangeSize, $shardMap)
    {
        $this->master = $master;
        $this->shards = $shards;
        $this->serverShards = $serverShards;
        $this->rangeSize = $rangeSize;
        $this->shardMap = $shardMap;
        $this->shardKeys = array_keys($this->shardMap);
    }

    public function getMaster()
    {
        return $this->master;
    }

    public function getServerShards()
    {
        return $this->serverShards;
    }

    public function getRandomCoreApp()
    {
        if (empty($this->shardMap)) {
            throw new \Exception("No coreapp node available");
        }
        $key = $this->shardKeys[mt_rand(0, count($this->shardKeys) - 1)];
        return $this->shardMap[$key];
    }

    public function getCoreApp($shardId)
    {
        $rangeShardId = (int)($shardId / $this->rangeSize);
        if (isset($this->shardMap[$rangeShardId])) {
            return $this->shardMap[$rangeShardId];
        } else {
            throw new \Exception("No coreapp node available for shard {$rangeShardId}");
        }
    }
} ?>
