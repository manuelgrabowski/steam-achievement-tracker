<?php

class AchievementUpdater {
    private DatabaseManager $db;
    private SteamAPIClient $steamAPI;
    private array $config;
    
    public function __construct(DatabaseManager $db, SteamAPIClient $steamAPI, array $config) {
        $this->db = $db;
        $this->steamAPI = $steamAPI;
        $this->config = $config;
    }
    
    public function shouldUpdate(bool $forceUpdate = false): bool {
        if ($forceUpdate) {
            return true;
        }
        
        $lastUpdate = $this->db->getLastUpdateTime();
        return (time() - $lastUpdate) >= $this->config['update_interval'];
    }
    
    public function updateAchievements(): array {
        $startTime = microtime(true);
        $steamId = $this->config['steam_id'];
        
        $this->updateUserProfile($steamId);
        
        $games = $this->getGamesToCheck($steamId);
        
        if (empty($games)) {
            throw new Exception('No games found. Check Steam API key and Steam ID.');
        }
        
        $totalAchievements = 0;
        $gamesChecked = 0;
        
        foreach ($games as $game) {
            try {
                $gameAchievements = $this->processGame($steamId, $game);
                $totalAchievements += $gameAchievements;
                $gamesChecked++;
                
                usleep($this->config['api_delay']);
            } catch (Exception $e) {
                error_log('Error processing game ' . $game['appid'] . ': ' . $e->getMessage());
            }
        }
        
        $executionTime = round(microtime(true) - $startTime, 2);
        $this->db->logUpdate($totalAchievements, $gamesChecked, $executionTime);
        
        return [
            'status' => 'success',
            'achievements_found' => $totalAchievements,
            'games_checked' => $gamesChecked,
            'execution_time' => $executionTime,
            'timestamp' => date('c')
        ];
    }
    
    private function updateUserProfile(string $steamId): void {
        $cached = $this->db->getUserProfile($steamId);
        if ($cached && (time() - $cached['last_updated']) < (7 * 24 * 3600)) {
            return;
        }
        
        $player = $this->steamAPI->getUserProfile($steamId);
        
        if ($player) {
            $this->db->saveUserProfile($steamId, $player);
        } else {
            error_log('Failed to fetch Steam user profile for ' . $steamId);
        }
    }
    
    private function getGamesToCheck(string $steamId): array {
        $games = $this->steamAPI->getRecentlyPlayedGames($steamId, $this->config['recent_games_limit']);
        
        if (empty($games)) {
            $games = $this->steamAPI->getOwnedGames($steamId, $this->config['recent_games_limit']);
        }
        
        return $games;
    }
    
    private function processGame(string $steamId, array $game): int {
        $appId = (int)$game['appid'];
        $gameName = $game['name'] ?? 'Unknown Game';
        
        $this->db->updateGameName($appId, $gameName);
        
        $playerAchievements = $this->steamAPI->getPlayerAchievements($steamId, $appId);
        
        if ($playerAchievements === null) {
            return 0;
        }
        
        $schema = $this->getAchievementSchema($appId);
        $globalStats = $this->steamAPI->getGlobalAchievementStats($appId);
        
        if (!empty($globalStats)) {
            $this->db->updateSchemaWithGlobalStats($appId, $globalStats);
        }
        
        return $this->processGameAchievements($steamId, $appId, $playerAchievements, $schema);
    }
    
    private function getAchievementSchema(int $appId): array {
        $cached = $this->db->getCachedAchievementSchema($appId);
        
        if (!empty($cached)) {
            return $cached;
        }
        
        $achievements = $this->steamAPI->getAchievementSchema($appId);
        
        if (!empty($achievements)) {
            $this->db->saveAchievementSchema($appId, $achievements);
        }
        
        $schema = [];
        foreach ($achievements as $achievement) {
            $key = $achievement['name'];
            $schema[$key] = [
                'displayName' => $achievement['displayName'] ?? '',
                'description' => $achievement['description'] ?? '',
                'icon' => $achievement['icon'] ?? '',
                'hidden' => (bool)($achievement['hidden'] ?? false),
                'globalPercentage' => null
            ];
        }
        
        return $schema;
    }
    
    private function processGameAchievements(string $steamId, int $appId, array $playerAchievements, array $schema): int {
        $count = 0;
        
        foreach ($playerAchievements as $achievement) {
            if (($achievement['achieved'] ?? 0) == 1) {
                $key = $achievement['apiname'];
                $unlockTime = (int)($achievement['unlocktime'] ?? time());
                
                if ($this->db->saveAchievement($steamId, $appId, $key, $unlockTime)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
}