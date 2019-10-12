<?php

namespace WhatsApp\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\CounterMetrics;
use WhatsApp\Common\GaugeMetrics;
use WhatsApp\Common\Util;
use WhatsApp\Constants;
use WhatsApp\Metrics\MetricsStorage;

class MetricsController
{
    private $STATE_MAP = array('_' => "idle", '.' => "connect", 'C' => "close", 'E' => "error", 'k' => "keep-alive", 'r' => "read", 'R' => "read-POST", 'W' => "write", 'h' => "handle-request", 'q' => "request-start", 'Q' => "request-end", 's' => "response-start", 'S' => "response-end");
    private $metricsList;

    public function __construct()
    {
        $this->metricsList = array();
        $this->metricsList[] = $this->upTime = new GaugeMetrics("web_uptime", "Number of seconds since web server has started");
        $this->metricsList[] = $this->connections = new CounterMetrics("web_connections", "Total number of connections accepted");
        $this->metricsList[] = $this->pendingConnections = new GaugeMetrics("web_pending_connections", "Current number of pending connections in the queue");
        $this->metricsList[] = $this->maxPendingConnections = new GaugeMetrics("web_max_pending_connections", "Maximum number of pending connections in the queue");
        $this->metricsList[] = $this->queueLen = new GaugeMetrics("web_queue_len", "Size of queue for pending connections");
        $this->metricsList[] = $this->idleProcesses = new GaugeMetrics("web_idle_processes", "Current number of idle processes");
        $this->metricsList[] = $this->totalProcesses = new GaugeMetrics("web_total_processes", "Total process limit");
        $this->metricsList[] = $this->processLimitHit = new CounterMetrics("web_process_limit_hit", "Total number of times reached process limit");
        $this->metricsList[] = $this->handledRequests = new CounterMetrics("web_handled_requests", "Total number of requests handled");
        $this->metricsList[] = $this->outKBytes = new CounterMetrics("web_out_kbytes", "Total outgoing traffic in kbytes");
        $this->metricsList[] = $this->requests = new GaugeMetrics("web_requests", "Number of ongoing requests", array("state"));
        $this->hostName = Util::getHostName();
    }

    public function getMetrics(Request $request, Application $app)
    {
        $metrics = self::collectServerMetrics($app);
        if ($request->query->get('format') === 'prometheus') {
            self::convertToPromFormat($metrics);
            return '';
        } else {
            $payload = array();
            $payload["metrics"] = $metrics;
            $post = Util::genResponse(null, $payload, new ApiErrors(), $app);
            return $app->json($post, Response::HTTP_OK);
        }
    }

    public function collectServerMetrics(Application $app)
    {
        $upTimeSeconds = -1;
        $response = self::sendGetRequest($app, Constants::FPM_STATUS_ENDPOINT);
        if ($response && json_decode($response, true) !== null) {
            $json = json_decode($response, true);
            if (array_key_exists("start since", $json)) {
                $upTimeSeconds = $json["start since"];
            }
            if (array_key_exists("accepted conn", $json)) {
                $this->connections->set($json["accepted conn"]);
            }
            if (array_key_exists("listen queue", $json)) {
                $this->pendingConnections->set($json["listen queue"]);
            }
            if (array_key_exists("max listen queue", $json)) {
                $this->maxPendingConnections->set($json["max listen queue"]);
            }
            if (array_key_exists("listen queue len", $json)) {
                $this->queueLen->set($json["listen queue len"]);
            }
            if (array_key_exists("idle processes", $json)) {
                $this->idleProcesses->set($json["idle processes"]);
            }
            if (array_key_exists("total processes", $json)) {
                $this->totalProcesses->set($json["total processes"]);
            }
            if (array_key_exists("max children reached", $json)) {
                $this->processLimitHit->set($json["max children reached"]);
            }
        }
        $response = self::sendGetRequest($app, Constants::SERVER_STATUS_ENDPOINT);
        if ($response) {
            $lines = explode("\n", $response);
            foreach ($lines as $line) {
                $pairs = explode(": ", $line);
                if (count($pairs) === 2) {
                    $key = $pairs[0];
                    if ($key === "Total Accesses") {
                        $this->handledRequests->set((int)$pairs[1]);
                    } elseif ($key === "Total kBytes") {
                        $this->outKBytes->set((int)$pairs[1]);
                    } elseif ($key === "Uptime") {
                        $value = (int)$pairs[1];
                        if ($value > 0) {
                            if ($upTimeSeconds < 0 || $value < $upTimeSeconds) {
                                $upTimeSeconds = $value;
                            }
                        }
                    } elseif ($key === "Scoreboard") {
                        $value = $pairs[1];
                        $len = strlen($value);
                        $requestMap = array();
                        for ($i = 0; $i < $len; $i++) {
                            $cVal = $value[$i];
                            if (!array_key_exists($cVal, $requestMap)) {
                                $requestMap[$cVal] = 0;
                            }
                            $requestMap[$cVal] += 1;
                        }
                        foreach ($requestMap as $state => $req_count) {
                            if (array_key_exists($state, $this->STATE_MAP)) {
                                $this->requests->set($req_count, array($this->STATE_MAP[$state]));
                            }
                        }
                    }
                }
            }
        }
        if ($upTimeSeconds > 0) {
            $this->upTime->set($upTimeSeconds);
        }
        $metricsStorage = new MetricsStorage();
        $this->metricsList = array_merge($this->metricsList, $metricsStorage->load());
        return self::collectAvailableMetrics();
    }

    public function sendGetRequest(Application $app, $url)
    {
        $ch = \curl_init();
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        \curl_setopt($ch, CURLOPT_URL, $url);
        \curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        \curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        \curl_setopt($ch, CURLOPT_TIMEOUT, Constants::DEFAULT_CURL_TIMEOUT);
        \curl_setopt($ch, CURLOPT_HEADER, 0);
        $response = \curl_exec($ch);
        if ($response === false) {
            $app['monolog']->info("CURL to " . $url . " got error:" . curl_error($ch) . " code:" . curl_errno($ch));
        }
        \curl_close($ch);
        return $response;
    }

    public function collectAvailableMetrics()
    {
        $result = array();
        foreach ($this->metricsList as $metric) {
            $pair = ($metric->collect($this->hostName));
            if (!is_null($pair)) {
                $result[$pair[0]] = $pair[1];
            }
        }
        return $result;
    }

    public function convertToPromFormat($stat)
    {
        foreach ($stat as $name => $info) {
            if (empty($info->data) || !property_exists($info, 'help') || !property_exists($info, 'type')) {
                continue;
            }
            print "# HELP $name $info->help\n";
            print "# TYPE $name $info->type\n";
            foreach ($info->data as $sample) {
                StatsController::printOneSample(false, $sample, $name, $info->type);
            }
        }
    }
}