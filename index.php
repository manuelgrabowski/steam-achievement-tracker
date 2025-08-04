<?php
/**
 * Steam Achievement Tracker
 * Track your Steam achievements to serve them as a website and feed.
 */

error_reporting(E_ERROR | E_PARSE);

require_once __DIR__ . '/ConfigValidator.php';
require_once __DIR__ . '/ErrorHandler.php';
require_once __DIR__ . '/SteamAPIClient.php';
require_once __DIR__ . '/RaritySystem.php';
require_once __DIR__ . '/UrlBuilder.php';
require_once __DIR__ . '/DatabaseManager.php';
require_once __DIR__ . '/AchievementUpdater.php';
require_once __DIR__ . '/ViewRenderer.php';
require_once __DIR__ . '/AchievementController.php';

$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    ErrorHandler::handle('Missing config.php file');
}

$config = require $configFile;

try {
    ConfigValidator::validate($config);
    
    $db = new DatabaseManager($config['db_path']);
    $steamAPI = new SteamAPIClient($config['steam_api_key']);
    $controller = new AchievementController($db, $steamAPI, $config);
    
    $action = $_GET['action'] ?? 'web';
    $controller->handleRequest($action);
    
} catch (Exception $e) {
    ErrorHandler::handle($e, $_GET['action'] ?? 'web');
}