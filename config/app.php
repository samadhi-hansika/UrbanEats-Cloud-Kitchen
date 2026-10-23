<?php
if (!defined('BASE_URL')) {
    $document_root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $project_root = realpath(dirname(__DIR__));

    if ($document_root === false || $project_root === false) {
        throw new RuntimeException('Unable to determine the application URL path.');
    }

    $document_root = rtrim(str_replace('\\', '/', $document_root), '/');
    $project_root = rtrim(str_replace('\\', '/', $project_root), '/');

    if (strcasecmp($project_root, $document_root) === 0) {
        $base_url = '';
    } else {
        $document_root_prefix = $document_root === '' ? '/' : $document_root . '/';

        if (stripos($project_root, $document_root_prefix) !== 0) {
            throw new RuntimeException('The application must be inside the web server document root.');
        }

        $base_url = '/' . substr($project_root, strlen($document_root_prefix));
    }

    define('BASE_URL', $base_url);
}
