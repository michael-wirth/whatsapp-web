<?php
namespace WhatsApp\Common;

class CounterMetrics extends BaseMetrics
{
    public function __construct($name, $description, $labels = array())
    {
        parent::__construct(METRICS_TYPE::COUNTER, $name, $description, $labels);
    }

    public function incr($delta, $labelVals = array())
    {
        if (count($this->labels) != count($labelVals)) {
            return;
        }
        if ($delta < 1e-9) {
            return;
        }
        $this->init = true;
        $valStr = join("\n", $labelVals);
        if (array_key_exists($valStr, $this->data)) {
            $this->data[$valStr] += $delta;
        } else {
            $this->data[$valStr] = $delta;
        }
    }
} ?>
