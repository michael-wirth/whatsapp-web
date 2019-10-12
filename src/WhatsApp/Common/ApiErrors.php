<?php

namespace WhatsApp\Common;

class ApiErrors
{
    private $apiErrors = array();

    public function add($code, $details = "", $href = "")
    {
        $apiError = new ApiError($code, $details, $href);
        array_push($this->apiErrors, $apiError);
    }

    public function hasError()
    {
        return ($this->count() != 0);
    }

    public function count()
    {
        return \count($this->apiErrors);
    }

    public function toJson()
    {
        if (!$this->count()) {
            return null;
        }
        return json_encode($this->get());
    }

    public function get()
    {
        if (!$this->count()) {
            return array();
        }
        $errors = array();
        foreach ($this->apiErrors as $err) {
            array_push($errors, $err->get());
        }
        return array('errors' => $errors);
    }
}