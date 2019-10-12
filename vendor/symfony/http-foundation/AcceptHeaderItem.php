<?php
 namespace Symfony\Component\HttpFoundation; class AcceptHeaderItem { private $value; private $quality = 1.0; private $index = 0; private $attributes = array(); public function __construct($value, array $attributes = array()) { $this->value = $value; foreach ($attributes as $name => $value) { $this->setAttribute($name, $value); } } public static function fromString($itemValue) { $bits = preg_split('/\s*(?:;*("[^"]+");*|;*(\'[^\']+\');*|;+)\s*/', $itemValue, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE); $value = array_shift($bits); $attributes = array(); $lastNullAttribute = null; foreach ($bits as $bit) { if (($start = substr($bit, 0, 1)) === ($end = substr($bit, -1)) && ($start === '"' || $start === '\'')) { $attributes[$lastNullAttribute] = substr($bit, 1, -1); } elseif ('=' === $end) { $lastNullAttribute = $bit = substr($bit, 0, -1); $attributes[$bit] = null; } else { $parts = explode('=', $bit); $attributes[$parts[0]] = isset($parts[1]) && strlen($parts[1]) > 0 ? $parts[1] : ''; } } return new self(($start = substr($value, 0, 1)) === ($end = substr($value, -1)) && ($start === '"' || $start === '\'') ? substr($value, 1, -1) : $value, $attributes); } public function __toString() { $string = $this->value.($this->quality < 1 ? ';q='.$this->quality : ''); if (count($this->attributes) > 0) { $string .= ';'.implode(';', array_map(function ($name, $value) { return sprintf(preg_match('/[,;=]/', $value) ? '%s="%s"' : '%s=%s', $name, $value); }, array_keys($this->attributes), $this->attributes)); } return $string; } public function setValue($value) { $this->value = $value; return $this; } public function getValue() { return $this->value; } public function setQuality($quality) { $this->quality = $quality; return $this; } public function getQuality() { return $this->quality; } public function setIndex($index) { $this->index = $index; return $this; } public function getIndex() { return $this->index; } public function hasAttribute($name) { return isset($this->attributes[$name]); } public function getAttribute($name, $default = null) { return isset($this->attributes[$name]) ? $this->attributes[$name] : $default; } public function getAttributes() { return $this->attributes; } public function setAttribute($name, $value) { if ('q' === $name) { $this->quality = (float) $value; } else { $this->attributes[$name] = (string) $value; } return $this; } } 