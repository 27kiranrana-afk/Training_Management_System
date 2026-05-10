<?php
// ============================================================
//  Base URL — resolves to the project root automatically.
//  Works on localhost (with or without subfolder) and on any
//  live hosting domain without manual changes after deployment.
// ============================================================
if (!defined('BASE_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // __FILE__ is always this file: <project_root>/includes/base.php
    // So the project root is always one level up from __DIR__
    $project_root = dirname(__DIR__);                        // e.g. C:/xampp/htdocs/training_system
    $doc_root     = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\'); // e.g. C:/xampp/htdocs

    // Get the web path to the project root
    $web_path = str_replace('\\', '/', substr($project_root, strlen($doc_root)));
    $web_path = rtrim($web_path, '/') . '/';

    define('BASE_URL', $scheme . '://' . $host . $web_path);
}
?>
