<?php

namespace WhatsApp\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\Connector;
use WhatsApp\Common\Util;

class StatsController
{
    public function getDBStats(Request $request, Application $app)
    {
        return $this->getStats($request, $app, 'db_stats');
    }

    public function getStats(Request $request, Application $app, $statsType)
    {
        $errors = new ApiErrors();
        $payload = new \stdClass;
        $meta = null;
        $respCode = Response::HTTP_OK;
        do {
            if (Util::isMultiConnect()) {
                $response = Connector::check_health_multiconnect($app, $statsType, $errors);
            } else {
                $response = Connector::send_receive($app, array($statsType => Null), 'control', $errors, $app['monolog']);
            }
            if ($errors->hasError()) {
                $respCode = Response::HTTP_INTERNAL_SERVER_ERROR;
                break;
            }
            $payload = Util::getPayload($response, $meta, $errors, $respCode);
        } while (false);
        if ($request->query->get('format') === 'prometheus') {
            if ($errors->hasError()) {
                return '';
            }
            if (Util::isMultiConnect()) {
                if ($statsType === 'db_stats') {
                    $this->convertToPromFormat($payload['stats']['db']);
                } else if ($statsType === 'internal_db_stats') {
                    $this->convertToPromFormat($payload['stats']['db']['internal']);
                } else if ($statsType === 'app_stats') {
                    $this->convertToPromFormat($payload['stats']['app']);
                } else if ($statsType === 'internal_app_stats') {
                    $this->convertToPromFormat($payload['stats']['app']['internal']);
                }
            } else {
                if ($statsType === 'db_stats') {
                    $this->convertToPromFormat($payload->stats->db);
                } else if ($statsType === 'internal_db_stats') {
                    $this->convertToPromFormat($payload->stats->db->internal);
                } else if ($statsType === 'app_stats') {
                    $this->convertToPromFormat($payload->stats->app);
                } else if ($statsType === 'internal_app_stats') {
                    $this->convertToPromFormat($payload->stats->app->internal);
                }
            }
            Util::extractRequestId($meta, $app);
            return '';
        } else {
            $post = Util::genResponse($meta, $payload, $errors, $app);
            return $app->json($post, $respCode);
        }
    }

    private function convertToPromFormat($stat)
    {
        if (Util::isMultiConnect()) {
            $mergeStats = array();
            foreach ($stat as $node => $per_node_stat) {
                foreach ($per_node_stat as $name => $info) {
                    if (array_key_exists($name, $mergeStats)) {
                        $mergeStats[$name]->data[$node] = $info->data;
                    } else {
                        $newInfo = new \stdClass();
                        $newInfo->help = $info->help;
                        $newInfo->type = $info->type;
                        $newInfo->data = array();
                        $newInfo->data[$node] = $info->data;
                        $mergeStats[$name] = $newInfo;
                    }
                }
            }
            $this->printStat($mergeStats);
        } else {
            $this->printStat($stat);
        }
    }

    private function printStat($stat)
    {
        foreach ($stat as $name => $info) {
            if (empty($info->data) || !property_exists($info, 'help') || !property_exists($info, 'type')) {
                continue;
            }
            print "# HELP $name $info->help\n";
            print "# TYPE $name $info->type\n";
            if (Util::isMultiConnect()) {
                foreach ($info->data as $node => $samples) {
                    foreach ($samples as $sample) {
                        StatsController::printOneSample($node, $sample, $name, $info->type);
                    }
                }
            } else {
                foreach ($info->data as $sample) {
                    StatsController::printOneSample(false, $sample, $name, $info->type);
                }
            }
        }
    }

    public static function printOneSample($node, $sample, $name, $type)
    {
        $labelStr = "";
        if ($node) {
            $hasComma = true;
            $labels = 'node="' . $node . '"';
        } else {
            $hasComma = false;
            $labels = "";
        }
        if (!empty($sample->labels)) {
            foreach ($sample->labels as $key => $value) {
                if ($hasComma) {
                    $labels .= ",";
                } else {
                    $hasComma = true;
                }
                $labels .= $key . '="' . $value . '"';
            }
        }
        if (!empty($labels)) {
            $labelStr = "{" . $labels . "}";
        }
        if ($type === "histogram") {
            if ($hasComma) {
                $labels .= ",";
            }
            if (!empty($sample->bucket)) {
                foreach ($sample->bucket as $key => $value) {
                    print "$name" . "_bucket{" . $labels . 'le="' . $key . '"}' . " $value\n";
                }
            }
            print "$name" . "_bucket{" . $labels . 'le="+Inf"}' . " $sample->count\n";
            print "$name" . "_sum" . $labelStr . " $sample->sum\n";
            print "$name" . "_count" . $labelStr . " $sample->count\n";
        } else {
            print "$name" . $labelStr . " $sample->value\n";
        }
    }

    public function getDBInternalStats(Request $request, Application $app)
    {
        return $this->getStats($request, $app, 'internal_db_stats');
    }

    public function getAppStats(Request $request, Application $app)
    {
        return $this->getStats($request, $app, 'app_stats');
    }

    public function getAppInternalStats(Request $request, Application $app)
    {
        return $this->getStats($request, $app, 'internal_app_stats');
    }
} 