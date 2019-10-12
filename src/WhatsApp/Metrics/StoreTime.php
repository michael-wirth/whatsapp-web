<?php
namespace WhatsApp\Metrics;

use WhatsApp\Constants;

class StoreTime
{
    private $app;
    private $globalMetricsType;
    private $startTime;
    private $labelVals;
    private $result = "unknown";
    private $latency;

    public function __construct($app, $globalMetricsType, $labelVals)
    {
        $this->startTime = microtime(true);
        $this->app = $app;
        $this->globalMetricsType = $globalMetricsType;
        $this->labelVals = $labelVals;
    }

    public function setResult($result)
    {
        $this->result = $result;
    }

    public function __destruct()
    {
        $this->labelVals[] = $this->result;
        $this->latency = (microtime(true) - $this->startTime) * 1000;
        if ($this->app) {
            $this->app[Constants::API_METRICS]->incDuration($this->globalMetricsType, $this->latency, $this->labelVals);
        }
    }
} ?>
