<?php

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

function app_base_url(): string
{
    $scriptDirectory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
    $logicDirectory = '/assets/logic';

    if (substr($scriptDirectory, -strlen($logicDirectory)) === $logicDirectory) {
        $scriptDirectory = substr($scriptDirectory, 0, -strlen($logicDirectory));
    }

    $base = rtrim($scriptDirectory, '/');
    return $base === '' ? '/' : $base . '/';
}
