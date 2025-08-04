<?php

class ErrorHandler {
    public static function handle(Exception $e, string $context): void {
        error_log('Steam RSS Feed Error: ' . $e->getMessage());
        http_response_code(500);
        
        if (in_array($context, ['web', 'single'])) {
            self::showErrorPage($e->getMessage());
        } else {
            self::showJsonError($e->getMessage());
        }
    }
    
    public static function showErrorPage(string $errorMessage): void {
        header('Content-Type: text/html; charset=UTF-8');
        $renderer = new ViewRenderer([]);
        die($renderer->render('error', ['error_message' => $errorMessage]));
    }
    
    public static function showJsonError(string $errorMessage): void {
        header('Content-Type: application/json');
        die(json_encode(['error' => 'Internal server error', 'details' => $errorMessage]));
    }
}