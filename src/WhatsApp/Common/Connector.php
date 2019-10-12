<?php
namespace WhatsApp\Common;
use WhatsApp\Constants;
use WhatsApp\Common\ApiError;
use WhatsApp\Common\ApiErrors;
use WhatsApp\Common\ConnectorConfig;
use WhatsApp\Common\Cluster;
use WhatsApp\Common\ClusterInfo;
use WhatsApp\Common\Util;
use WhatsApp\Security\SecurePayload;
use WhatsApp\Security\CryptoUtils;
use WhatsApp\Metrics\GlobalMetricsType;
use WhatsApp\Metrics\StoreTime;
abstract class PORT_OFFSET {
    const MESSAGE_PORT = 0;
    const CONTACT_PORT = 1;
    const CONTROL_PORT = 2;
};
class Connector {
    const PORT_OFFSET_MAP = array('message' => PORT_OFFSET::MESSAGE_PORT, 'contact' => PORT_OFFSET::CONTACT_PORT, 'control' => PORT_OFFSET::CONTROL_PORT,);
    const MASTER_ONLY_COMMANDS = array("set_settings", "get_settings", "delete_settings", "import", "export", "code_request", "register", "get_shards", "set_shards", "delete_messages", "get_webhook_ca_certs", "set_webhook_ca_certs", "delete_webhook_ca_certs", "delete_content_provider_configs", "get_content_provider_configs", "set_content_provider_configs");
    public static function full_write($sock, $buf, $size = - 1) {
        if ($size == - 1) {
            $size = strlen($buf);
        }
        $zero_len_retries = 0;
        for ($written = 0;$written < $size;$written+= $fwrite) {
            $fwrite = fwrite($sock, substr($buf, $written), $size - $written);
            if ($fwrite === false) {
                return false;
            } else if ($fwrite === 0) {
                if ($zero_len_retries++ > 5) {
                    return false;
                }
            } else {
                $zero_len_retries = 0;
            }
        }
        return true;
    }
    public static function send_receive($app, $obj, $endpoint, $errors, $logger = null, $version = 3) {
        try {
            $config_object = self::get_node_config($app, $obj, $endpoint);
        }
        catch(\Exception $exception) {
            $errors->add(ApiError::INTERNAL_ERROR, $exception->getMessage());
            $app["monolog"]->error($exception->getMessage());
            return null;
        }
        return self::send_receive_with_config($obj, $config_object, $endpoint, $errors, $logger, $version, $app);
    }
    public static function send_receive_with_config($obj, $config_object, $endpoint, $errors, $logger, $version, $app) {
        if ($config_object->use_tcp) {
            $hostStr = $config_object->hostname . ":" . $config_object->getPort(self::PORT_OFFSET_MAP[$endpoint]);
        } else {
            $hostStr = $unix_socket_name = $endpoint . '_socket';
        }       
        $storeTime = new StoreTime($app, GlobalMetricsType::API_REQUESTS_COREAPP, array($hostStr));
        $raw_json_without_meta = json_encode($obj);
        $json_request = json_decode($raw_json_without_meta, true);
        $json_request['meta'] = array(Constants::REQUEST_ID_FIELD => $app[Constants::REQUEST_ID_FIELD]);
        $raw_json = json_encode($json_request);
        $cryptoUtils = new CryptoUtils($logger);
        $secretKey = $cryptoUtils->getKey('WA_SECRET');
        if ($secretKey) {
            $version = $version + 1;
            $securePayload = new SecurePayload($logger, $secretKey);
            $encPayload = $securePayload->encrypt($raw_json);
            if ($encPayload === null) {
                $storeTime->setResult("failed_to_encrypt");
                $errors->add(ApiError::INTERNAL_ERROR, 'Unable to encrypt payload');
                return null;
            }
            $nonce = $securePayload->nonce();
            $socketData = "$nonce;$encPayload";
        } else {
            $socketData = $raw_json;
        }
        $dataLen = strlen($socketData);
        $socket_payload = "{$version};$dataLen;$socketData";

        if ($config_object->use_tcp) {
            $sock = @fsockopen($config_object->hostname, $config_object->getPort(self::PORT_OFFSET_MAP[$endpoint]), $errno, $errstr, 10);
        } else {
            $sock = @fsockopen("unix:///tmp/{$unix_socket_name}", -1, $errno, $errstr, 10);
        }
        if (!$sock) {
            $storeTime->setResult("failed_to_connect");
            $errors->add(ApiError::INTERNAL_ERROR, $errstr . '. Please check if wacore is running: ' . $hostStr);
            return null;
        }
        $res = self::full_write($sock, $socket_payload);
        if (!$res) {
            $storeTime->setResult("failed_to_send");
            $errors->add(ApiError::INTERNAL_ERROR, "Communication failure: write. Please check if wacore is running: " . $hostStr);
            fclose($sock);
            return null;
        }
        $buf = "";
        stream_set_timeout($sock, 45);
        while (!feof($sock)) {
            if (stream_get_meta_data($sock) ['timed_out']) {
                $storeTime->setResult("timed_out");
                $errors->add(ApiError::INTERNAL_ERROR, "Communication failure: timeout. Please check if wacore is running: " . $hostStr);
                fclose($sock);
                return null;
            }
            $buf.= fread($sock, 1024);
        }
        fclose($sock);
        if ($secretKey) {
            $nonceB64 = substr($buf, 0, strpos($buf, ";"));
            $encPayload = substr($buf, strpos($buf, ";") + 1);
            $securePayload = new SecurePayload($logger, $secretKey, $nonceB64);
            $payload = $securePayload->decrypt($encPayload);
            if ($payload === null) {
                $storeTime->setResult("failed_to_decrypt");
                $errors->add(ApiError::INTERNAL_ERROR, "Communication failure: Unable to decrypt payload.");
                return null;
            }
            $storeTime->setResult("ok");
            return json_decode($payload);
        }
        $storeTime->setResult("ok");
        return json_decode($buf);
    }
    public static function check_contacts($json_query) {
        $out_jq = new JSON_Query();
        if (!$json_query->payload || !$json_query->payload->users) {
            $out_jq->set_error("missing params payload|users", 400);
            return $out_jq;
        }
        return self::socket_io($json_query, 1, "contact_socket");
    }
    public static function control($json_query, $version = "2") {
        $out_jq = new JSON_Query();
        return self::socket_io($json_query, 2, "control_socket", $version);
    }
    public static function set_settings($json_query) {
        return WA_Connector::control($json_query, "1");
    }
    public static function check_health_multiconnect($app, $stats_type, $errors) {
        try {
            $cluster = new Cluster();
            $cluster->initDatabase($app);
            $nodes = $cluster->getNodes();
        }
        catch(\Exception $exception) {
            $errors->add(ApiError::INTERNAL_ERROR, $exception->getMessage());
            $app["monolog"]->error($exception->getMessage());
            return null;
        }
        $json_query = array($stats_type => Null);
        $res_payload = array();
        foreach ($nodes as $node) {
            $node_errors = new ApiErrors();
            $node_response = self::send_receive_with_config($json_query, $node, 'control', $node_errors, null, 3, $app);
            $node_str = $node->getNodeId();
            if (!$node_errors->hasError()) {
                $rawPayload = Util::getPayload($node_response, $meta, $node_errors, $respCode);
            }
            if (!$node_errors->hasError()) {
                $payload = get_object_vars($rawPayload);
                if ($stats_type === "gateway_status") {
                    $res_payload["health"][$node_str] = $payload["health"];
                } else if ($stats_type === "support_info") {
                    $res_payload["support"][$node_str] = $payload["support"];
                } else if ($stats_type === "db_stats") {
                    $res_payload["stats"]["db"][$node_str] = $payload["stats"]->db;
                } else if ($stats_type === "app_stats") {
                    $res_payload["stats"]["app"][$node_str] = $payload["stats"]->app;
                } else if ($stats_type === "internal_app_stats") {
                    $res_payload["stats"]["app"]["internal"][$node_str] = $payload["stats"]->app->internal;
                } else if ($stats_type === "internal_db_stats") {
                    $res_payload["stats"]["db"]["internal"][$node_str] = $payload["stats"]->db->internal;
                }
            } else {
                if ($stats_type === "gateway_status") {
                    $res_payload["health"][$node_str] = $node_errors->get();
                } else if ($stats_type === "support_info") {
                    $res_payload["support"][$node_str] = $node_errors->get();
                }
            }
        }
        $result = new \stdClass;
        $result->payload = $res_payload;
        return $result;
    }
    public static function get_node_config($app, $obj, $endpoint) {
        if (Util::isMultiConnect()) {
            $cluster = new Cluster();
            $cluster->initDatabase($app);
            $clusterInfo = $cluster->getClusterInfo();
            $portOffset = self::PORT_OFFSET_MAP[$endpoint];
            if ($portOffset === PORT_OFFSET::MESSAGE_PORT) {
                if (!isset($obj->to) || $obj->to === "") {
                    return $clusterInfo->getRandomCoreApp();
                }
                return $clusterInfo->getCoreApp(Util::getShardId($obj->to, $clusterInfo->getServerShards()));
            } else if ($portOffset === PORT_OFFSET::CONTACT_PORT) {
                return $clusterInfo->getRandomCoreApp();
            } else if ($portOffset === PORT_OFFSET::CONTROL_PORT) {
                $payload = $obj;
                reset($payload);
                $command = key($payload);
                if (in_array($command, self::MASTER_ONLY_COMMANDS)) {
                    return $clusterInfo->getMaster();
                } else {
                    return $clusterInfo->getRandomCoreApp();
                }
            } else {
                return $clusterInfo->getMaster();
            }
        } else {
            return self::read_config();
        }
    }
    public static function read_config() {
        $res = new ConnectorConfig();
        $json_str = null;
        $conf_file = "/etc/wa_config.json";
        if (file_exists($conf_file)) {
            $json_str = file_get_contents($conf_file);
        }
        if (!$json_str) {
            $res->use_tcp = false;
        } else {
            $fileobj = json_decode($json_str);
            if (isset($fileobj->use_tcp)) {
                $res->use_tcp = $fileobj->use_tcp;
            } else {
                $res->use_tcp = false;
            }
            if (isset($fileobj->hostname)) {
                $res->hostname = $fileobj->hostname;
            } else {
                $res->hostname = "127.0.0.1";
            }
            if (isset($fileobj->baseport)) {
                $res->baseport = $fileobj->baseport;
            } else {
                $res->baseport = 6250;
            }
        }
        return $res;
    }
}; ?>
