<?php
namespace WhatsApp\Common;

abstract class METRICS_TYPE
{
    const COUNTER = 0;
    const GAUGE = 1;
}

abstract class BaseMetrics
{
    protected $type = null;
    protected $name = null;
    protected $description = null;
    protected $labels = null;
    protected $data = array();
    protected $init = false;

    public function __construct($type, $name, $description, $labels)
    {
        $this->type = $type;
        $this->name = $name;
        $this->description = $description;
        $this->labels = $labels;
    }

    public function collect($hostname)
    {
        if (!$this->init) {
            return null;
        }
        $infoMap = new \stdClass();
        if ($this->type === METRICS_TYPE::COUNTER) {
            $infoMap->type = "counter";
        } else {
            $infoMap->type = "gauge";
        }
        $infoMap->help = $this->description;
        $dataArray = array();
        foreach ($this->data as $key => $value) {
            $labelMap = array();
            $labelMap['node'] = $hostname;
            $count = count($this->labels);
            $labelVals = explode("\n", $key);
            for ($i = 0; $i < $count; $i++) {
                $labelKey = str_replace(" ", "_", $this->labels[$i]);
                $labelVal = str_replace(" ", "_", $labelVals[$i]);
                $labelMap[$labelKey] = $labelVal;
            }
            $singleData = new \stdClass();
            $singleData->labels = $labelMap;
            $singleData->value = $value;
            $dataArray[] = $singleData;
        }
        $infoMap->data = $dataArray;
        return array($this->name, $infoMap);
    }

    public function set($value, $labelVals = array())
    {
        if (count($this->labels) != count($labelVals)) {
            return;
        }
        $this->init = true;
        $this->data[join("\n", $labelVals)] = $value;
    }

    public function data()
    {
        return $this->data;
    }

    public function empty()
    {
        return empty($this->data);
    }

    public function name()
    {
        return $this->name;
    }
} ?>
