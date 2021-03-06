<?php
namespace WhatsApp\Metrics;

use WhatsApp\Common\CounterMetrics;

class MetricsStorage
{
    private $apiRequestsLatencySum;
    private $apiRequestsCount;
    private $apiRequestsDbLatencySum;
    private $apiRequestsDbCount;
    private $apiRequestsCoreAppLatencySum;
    private $apiRequestsCoreAppCount;
    private $metricsList;

    public function __construct()
    {
        $this->metricsList = array();
        $this->metricsList[] = $this->apiRequestsLatencySum = new CounterMetrics("api_requests_duration_ms_sum", "Total number of durations of API requests", array("result", "method"));
        $this->metricsList[] = $this->apiRequestsCount = new CounterMetrics("api_requests_duration_ms_count", "Total number of API requests", array("result", "method"));
        $this->metricsList[] = $this->apiRequestsDbLatencySum = new CounterMetrics("api_requests_db_duration_ms_sum", "Total time spent in making DB calls", array("query", "result", "method"));
        $this->metricsList[] = $this->apiRequestsDbCount = new CounterMetrics("api_requests_db_duration_ms_count", "Total number of calls to the DB", array("query", "result", "method"));
        $this->metricsList[] = $this->apiRequestsCoreAppLatencySum = new CounterMetrics("api_requests_coreapp_duration_ms_sum", "Total time spent in making CoreApp calls", array("coreapp", "result", "method"));
        $this->metricsList[] = $this->apiRequestsCoreAppCount = new CounterMetrics("api_requests_coreapp_duration_ms_count", "Total number of calls to the CoreApp", array("coreapp", "result", "method"));
    }

    public function incDuration($type, $latency, $labelVals)
    {
        $labelVals[] = "unknown";
        if ($type === GlobalMetricsType::API_REQUESTS) {
            $this->apiRequestsLatencySum->incr($latency, $labelVals);
            $this->apiRequestsCount->incr(1, $labelVals);
        } else if ($type === GlobalMetricsType::API_REQUESTS_DB) {
            $this->apiRequestsDbLatencySum->incr($latency, $labelVals);
            $this->apiRequestsDbCount->incr(1, $labelVals);
        } else if ($type === GlobalMetricsType::API_REQUESTS_COREAPP) {
            $this->apiRequestsCoreAppLatencySum->incr($latency, $labelVals);
            $this->apiRequestsCoreAppCount->incr(1, $labelVals);
        }
    }

    public function persist($reqPath)
    {
        foreach ($this->metricsList as $metric) {
            $data = $metric->data();
            foreach ($data as $key => $value) {
                $finalLabelVals = array();
                $labelVals = explode("\n", $key);
                $count = count($labelVals);
                for ($i = 0; $i < $count - 1; $i++) {
                    $finalLabelVals[] = str_replace(",", "_", str_replace(" ", "_", $labelVals[$i]));
                }
                $finalLabelVals[] = $reqPath;
                $key = $metric->name() . "," . implode(",", $finalLabelVals);
                apcu_inc($key, $value);
            }
        }
    }

    public function load()
    {
        $result = array();
        foreach ($this->metricsList as $metric) {
            $regex = '/^' . $metric->name() . ',.*/';
            foreach (new \APCUIterator($regex) as $counter) {
                $elements = explode(",", $counter['key']);
                $metric->set($counter['value'], array_slice($elements, 1));
            }
            if (!$metric->empty()) {
                $result[] = $metric;
            }
        }
        return $result;
    }
} ?>
