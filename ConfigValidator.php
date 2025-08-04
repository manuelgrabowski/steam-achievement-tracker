<?php

class ConfigValidator {
    public static function validate(array $config): void {
        if (!isset($config['steam_api_key'], $config['steam_id'])) {
            throw new InvalidArgumentException('Missing required config: steam_api_key and steam_id');
        }

        if (!is_string($config['steam_api_key']) || trim($config['steam_api_key']) === '') {
            throw new InvalidArgumentException('steam_api_key must be a non-empty string.');
        }

        if (!is_string($config['steam_id']) || !preg_match('/^\d{17}$/', $config['steam_id'])) {
            throw new InvalidArgumentException('Invalid steam_id format. Must be a 17-digit Steam ID64 string.');
        }

        if (!isset($config['update_interval']) || !is_int($config['update_interval']) || $config['update_interval'] < 60) {
            throw new InvalidArgumentException('Invalid update_interval. Must be an integer >= 60.');
        }
    }
}
