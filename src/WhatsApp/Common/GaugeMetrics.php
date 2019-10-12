<?php
namespace WhatsApp\Common; class GaugeMetrics extends BaseMetrics { public function __construct($name, $description, $labels = array()) { parent::__construct(METRICS_TYPE::GAUGE, $name, $description, $labels); } } ?>
