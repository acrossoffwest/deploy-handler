<?php

namespace App\Helpers;

class ArrayMapper
{
    protected $array = [];

    public function __construct($array)
    {
        $this->setArray($array);
    }

    public static function create($array)
    {
        return new static($array);
    }

    public function setArray($array)
    {
        $this->array = $array;
    }

    public function isEmpty()
    {
        return empty($this->array);
    }

    public function toArray()
    {
        return $this->array;
    }

    public function get($way, $default = null, $delimiter = '.')
    {
        if (preg_match('/\|/', $way)) {
            $items = explode('|', $way);
            foreach ($items as $item) {
                if (is_null($value = $this->get($item, $delimiter, $default))) {
                    continue;
                }
                return $value;
            }
            return $default;
        }

        $wayArray = explode($delimiter, $way);
        $result = null;
        $tmp = null;

        foreach ($wayArray as $key) {
            if (!empty($result)) {
                if (is_array($result) && isset($result[$key])) {
                    $result = $result[$key];
                    continue;
                } elseif (empty($result[$key])) {
                    return $default;
                } else {
                    return empty($result) ? $default : $result;
                }
            }
            if (isset($this->array[$key])) {
                $result = $this->array[$key];
            } else if ($wayArray > 1) {
                return $default;
            }
        }

        return $result === null ? $default : $result;
    }

    public function exists($way, $delimiter = '.')
    {
        return !is_null($this->get($way, $delimiter));
    }
}
