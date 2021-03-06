<?php
if ($argc <= 1) {
    echo "Usage: {$argv[0]} <use_tcp> <hostname> <baseport>";
    exit(1);
}
$obj = new stdClass();
if ($argv[1] && strtolower($argv[1]) != "false") {
    $obj->use_tcp = true;
} else {
    $obj->use_tcp = false;
}
if (isset($argv[2])) {
    $obj->hostname = $argv[2];
}
if (isset($argv[3])) {
    $obj->baseport = $argv[3];
}
$res = json_encode($obj, JSON_PRETTY_PRINT);
file_put_contents("/etc/wa_config.json", $res); ?>
