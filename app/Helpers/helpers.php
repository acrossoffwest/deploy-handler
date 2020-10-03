<?php

if (!function_exists('prepare_ssh_creds')) {
    function prepare_ssh_creds_config(string $credsString): array
    {
        $creds = array_filter(array_map('trim', explode(',', $credsString)), fn ($v) => !empty($v));

        $result = [];
        foreach ($creds as $var) {
            $cred = explode(':', $var);
            throw_if(count($cred) < 2, new \Exception('Test'));
            $result[$cred[0]] = $cred[1];
        }
        return $result;
    }
}
if (!function_exists('array_mapper')) {
    function array_mapper (array $array)
    {
        return \App\Helpers\ArrayMapper::create($array);
    }
}
