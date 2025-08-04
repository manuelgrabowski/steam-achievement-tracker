<?php

class SteamAPIClient {
    private string $apiKey;
    
    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;
    }
    
    private function callAPI(string $url): ?array {
        $response = @file_get_contents($url);
        
        if ($response === false) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        
        return $data;
    }
    
    public function getRecentlyPlayedGames(string $steamId, int $limit): array {
        $url = 'https://api.steampowered.com/IPlayerService/GetRecentlyPlayedGames/v1/?key=' . $this->apiKey . '&steamid=' . $steamId . '&count=' . $limit;
        
        $data = $this->callAPI($url);
        return $data['response']['games'] ?? [];
    }
    
    public function getOwnedGames(string $steamId, int $limit): array {
        $url = 'https://api.steampowered.com/IPlayerService/GetOwnedGames/v1/?key=' . $this->apiKey . '&steamid=' . $steamId . '&include_appinfo=1&include_played_free_games=1';
        
        $data = $this->callAPI($url);
        
        if (!$data) {
            return [];
        }
        
        $games = $data['response']['games'] ?? [];
        
        usort($games, function($a, $b) {
            return ($b['playtime_forever'] ?? 0) <=> ($a['playtime_forever'] ?? 0);
        });
        
        return array_slice($games, 0, $limit);
    }
    
    public function getPlayerAchievements(string $steamId, int $appId): ?array {
        $url = 'https://api.steampowered.com/ISteamUserStats/GetPlayerAchievements/v1/?key=' . $this->apiKey . '&steamid=' . $steamId . '&appid=' . $appId;
        
        $data = $this->callAPI($url);
        
        if (!$data || !isset($data['playerstats']['success']) || !$data['playerstats']['success']) {
            return null;
        }
        
        return $data['playerstats']['achievements'] ?? [];
    }
    
    public function getAchievementSchema(int $appId): array {
        $url = 'https://api.steampowered.com/ISteamUserStats/GetSchemaForGame/v2/?key=' . $this->apiKey . '&appid=' . $appId;
        
        $data = $this->callAPI($url);
        
        if (!$data) {
            return [];
        }
        
        return $data['game']['availableGameStats']['achievements'] ?? [];
    }
    
    public function getGlobalAchievementStats(int $appId): array {
        $url = 'https://api.steampowered.com/ISteamUserStats/GetGlobalAchievementPercentagesForApp/v0002/?gameid=' . $appId . '&format=json';
        
        $data = $this->callAPI($url);
        
        if (!$data) {
            return [];
        }
        
        $achievements = $data['achievementpercentages']['achievements'] ?? [];
        
        $stats = [];
        foreach ($achievements as $achievement) {
            $stats[$achievement['name']] = (float)$achievement['percent'];
        }
        
        return $stats;
    }
    
    public function getUserProfile(string $steamId): ?array {
        $url = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . $this->apiKey . '&steamids=' . $steamId;
        
        $data = $this->callAPI($url);
        
        if (!$data) {
            return null;
        }
        
        $players = $data['response']['players'] ?? [];
        return empty($players) ? null : $players[0];
    }
}