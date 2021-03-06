<?php
namespace WhatsApp\Common;

class ConnectorConfig
{
    public $use_tcp;
    public $hostname;
    public $baseport;
    public $portMap = array();
    public $containerName = null;

    public function getPort($offset)
    {
        if (empty($this->portMap)) {
            return $this->baseport + $offset;
        } else {
            return $this->portMap[$offset];
        }
    }

    public function getNodeId()
    {
        if (!$this->containerName) {
            return $this->hostname;
        } else {
            return $this->hostname . ":" . $this->containerName;
        }
    }
} ?>
