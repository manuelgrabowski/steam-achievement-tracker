<?php

class UrlBuilder {
    public static function getBaseUrl(): string {
        return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    }
    
    public static function getCurrentUrl(): string {
            return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . ($_SERVER['REQUEST_URI'] ?? '');
    }
    
    public static function buildSingleAchievementUrl(array $achievement): string {
        return self::buildSingleAchievementUrlFromParams(
            $achievement['app_id'],
            $achievement['achievement_key']
        );
    }
    
    public static function buildSingleAchievementUrlFromParams(int $appId, string $achievementKey): string {
        return self::getBaseUrl() . '?' . http_build_query([
            'action' => 'single',
            'app_id' => $appId,
            'key' => $achievementKey
        ]);
    }
    
    public static function buildPaginationUrl(int $pageNumber, ?int $perPage = null): string {
        $baseUrl = self::getBaseUrl();
        
        $params = ['page' => $pageNumber];
        if ($perPage !== null) {
            $params['per_page'] = $perPage;
        }
        
        return $baseUrl . '?' . http_build_query($params);
    }
    
    public static function buildRssUrl(): string {
        return self::getBaseUrl() . '?action=rss';
    }
    
    public static function buildOgImageUrl(?array $achievement = null, string $format = 'png'): string {
        $params = ['action' => 'ogimage', 'format' => $format];
        
        if ($achievement) {
            $params['app_id'] = $achievement['app_id'];
            $params['key'] = $achievement['achievement_key'];
        }
        
        return self::getBaseUrl() . '?' . http_build_query($params);
    }
    
    public static function buildSteamProfileUrl(string $steamUsername): string {
        return 'https://steamcommunity.com/id/' . $steamUsername;
    }
    
    public static function buildSteamStatsUrl(string $steamUsername, int $appId): string {
        return self::buildSteamProfileUrl($steamUsername) . '/stats/' . $appId;
    }
    
    public static function buildSteamStoreUrl(int $appId): string {
        return 'https://store.steampowered.com/app/' . $appId . '/';
    }
}