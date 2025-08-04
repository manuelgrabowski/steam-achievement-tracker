<?php

class DatabaseManager {
    private PDO $db;
    
    public function __construct(string $dbPath) {
        try {
            $this->db = new PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            $this->db->exec('PRAGMA journal_mode=WAL');
            $this->db->exec('PRAGMA synchronous=NORMAL');
            $this->db->exec('PRAGMA cache_size=10000');
            
            $this->createTables();
        } catch (PDOException $e) {
            throw new Exception('Database initialization failed: ' . $e->getMessage());
        }
    }
    
    private function createTables(): void {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS achievements (
                steam_id TEXT NOT NULL,
                app_id INTEGER NOT NULL,
                achievement_key TEXT NOT NULL,
                unlock_time INTEGER NOT NULL,
                discovered_at INTEGER NOT NULL DEFAULT (strftime("%s", "now")),
                PRIMARY KEY (steam_id, app_id, achievement_key)
            )
        ');
        
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS games (
                app_id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                last_checked INTEGER NOT NULL DEFAULT (strftime("%s", "now"))
            )
        ');
        
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS achievement_schemas (
                app_id INTEGER NOT NULL,
                achievement_key TEXT NOT NULL,
                display_name TEXT,
                description TEXT,
                icon TEXT,
                hidden INTEGER DEFAULT 0,
                global_percentage REAL,
                last_updated INTEGER NOT NULL DEFAULT (strftime("%s", "now")),
                PRIMARY KEY (app_id, achievement_key)
            )
        ');
        
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS update_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                last_update INTEGER NOT NULL DEFAULT (strftime("%s", "now")),
                achievements_found INTEGER NOT NULL DEFAULT 0,
                games_checked INTEGER NOT NULL DEFAULT 0,
                execution_time REAL,
                status TEXT DEFAULT "success"
            )
        ');
        
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS user_profiles (
                steam_id TEXT PRIMARY KEY,
                username TEXT,
                avatar_url TEXT,
                profile_url TEXT,
                last_updated INTEGER NOT NULL DEFAULT (strftime("%s", "now"))
            )
        ');
        
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_achievements_unlock_time ON achievements(unlock_time DESC)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_schema_lookup ON achievement_schemas(app_id, achievement_key)');
        $this->db->exec('CREATE INDEX IF NOT EXISTS idx_update_log_time ON update_log(last_update DESC)');
    }
    
    public function getAchievements(int $limit, int $offset = 0): array {
        $stmt = $this->db->prepare('
            SELECT 
                a.*,
                s.display_name as achievement_name,
                s.description,
                s.icon,
                s.hidden,
                s.global_percentage
            FROM achievements a
            LEFT JOIN achievement_schemas s ON (a.app_id = s.app_id AND a.achievement_key = s.achievement_key)
            ORDER BY a.unlock_time DESC 
            LIMIT ? OFFSET ?
        ');
        $stmt->execute([$limit, $offset]);
        $achievements = $stmt->fetchAll();
        
        $gameNames = $this->getGameNameCache();
        array_walk($achievements, function (&$achievement) use ($gameNames) {
            $achievement['game_name'] = $gameNames[$achievement['app_id']] ?? 'Unknown Game';
        });
        
        return $achievements;
    }
    
    public function getSingleAchievement(int $appId, string $achievementKey, string $steamId): ?array {
        $stmt = $this->db->prepare('
            SELECT 
                a.*,
                s.display_name as achievement_name,
                s.description,
                s.icon,
                s.hidden,
                s.global_percentage
            FROM achievements a
            LEFT JOIN achievement_schemas s ON (a.app_id = s.app_id AND a.achievement_key = s.achievement_key)
            WHERE a.steam_id = ? AND a.app_id = ? AND a.achievement_key = ?
        ');
        $stmt->execute([$steamId, $appId, $achievementKey]);
        $achievement = $stmt->fetch();
        
        if ($achievement) {
            $gameNames = $this->getGameNameCache();
            $achievement['game_name'] = $gameNames[$achievement['app_id']] ?? 'Unknown Game';
            return $achievement;
        }
        
        return null;
    }
    
    public function saveAchievement(string $steamId, int $appId, string $achievementKey, int $unlockTime): bool {
        $stmt = $this->db->prepare('
            INSERT OR IGNORE INTO achievements 
            (steam_id, app_id, achievement_key, unlock_time, discovered_at) 
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$steamId, $appId, $achievementKey, $unlockTime, time()]);
        
        return $stmt->rowCount() > 0;
    }
    
    public function updateGameName(int $appId, string $gameName): void {
        $stmt = $this->db->prepare('INSERT OR REPLACE INTO games (app_id, name, last_checked) VALUES (?, ?, ?)');
        $stmt->execute([$appId, $gameName, time()]);
    }
    
    public function getGameNameCache(): array {
        $stmt = $this->db->query('SELECT app_id, name FROM games');
        $games = $stmt->fetchAll();
        
        $lookup = [];
        foreach ($games as $game) {
            $lookup[(int)$game['app_id']] = $game['name'];
        }
        
        return $lookup;
    }
    
    public function getCachedAchievementSchema(int $appId): array {
        $stmt = $this->db->prepare('SELECT * FROM achievement_schemas WHERE app_id = ? AND last_updated > ?');
        $stmt->execute([$appId, time() - (7 * 24 * 3600)]);
        $cached = $stmt->fetchAll();
        
        if (empty($cached)) {
            return [];
        }
        
        $schema = [];
        foreach ($cached as $item) {
            $schema[$item['achievement_key']] = [
                'displayName' => $item['display_name'],
                'description' => $item['description'],
                'icon' => $item['icon'],
                'hidden' => (bool)$item['hidden'],
                'globalPercentage' => $item['global_percentage']
            ];
        }
        
        return $schema;
    }
    
    public function saveAchievementSchema(int $appId, array $achievements): void {
        $stmt = $this->db->prepare('INSERT OR REPLACE INTO achievement_schemas (app_id, achievement_key, display_name, description, icon, hidden, last_updated) VALUES (?, ?, ?, ?, ?, ?, ?)');
        
        foreach ($achievements as $achievement) {
            $key = $achievement['name'];
            $displayName = $achievement['displayName'] ?? '';
            $description = $achievement['description'] ?? '';
            $icon = $achievement['icon'] ?? '';
            $hidden = isset($achievement['hidden']) ? (int)$achievement['hidden'] : 0;
            
            $stmt->execute([$appId, $key, $displayName, $description, $icon, $hidden, time()]);
        }
    }
    
    public function updateSchemaWithGlobalStats(int $appId, array $globalStats): void {
        if (empty($globalStats)) {
            return;
        }
        
        $stmt = $this->db->prepare('UPDATE achievement_schemas SET global_percentage = ? WHERE app_id = ? AND achievement_key = ?');
        
        foreach ($globalStats as $achievementKey => $percentage) {
            $stmt->execute([$percentage, $appId, $achievementKey]);
        }
    }
    
    public function getUserProfile(string $steamId): ?array {
        $stmt = $this->db->prepare('SELECT * FROM user_profiles WHERE steam_id = ?');
        $stmt->execute([$steamId]);
        return $stmt->fetch() ?: null;
    }
    
    public function saveUserProfile(string $steamId, array $player): void {
        $stmt = $this->db->prepare('INSERT OR REPLACE INTO user_profiles (steam_id, username, avatar_url, profile_url, last_updated) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $steamId,
            $player['personaname'] ?? null,
            $player['avatarfull'] ?? null,
            $player['profileurl'] ?? null,
            time()
        ]);
    }
    
    public function logUpdate(int $achievementsFound, int $gamesChecked, float $executionTime = 0, string $status = 'success'): void {
        $stmt = $this->db->prepare('INSERT INTO update_log (last_update, achievements_found, games_checked, execution_time, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([time(), $achievementsFound, $gamesChecked, $executionTime, $status]);
    }
    
    public function getLastUpdateTime(): int {
        $stmt = $this->db->query('SELECT MAX(last_update) as last_update FROM update_log');
        $result = $stmt->fetch();
        return (int)($result['last_update'] ?? 0);
    }
    
    public function getTotalAchievements(): int {
        $stmt = $this->db->query('SELECT COUNT(*) as count FROM achievements');
        return (int)$stmt->fetchColumn();
    }
    
    public function getTotalGames(bool $onlyWithAchievements = false): int {
        if ($onlyWithAchievements) {
            $stmt = $this->db->query('
                SELECT COUNT(DISTINCT app_id) as count 
                FROM achievements
            ');
        } else {
            $stmt = $this->db->query('SELECT COUNT(*) as count FROM games');
        }
        
        return (int)$stmt->fetchColumn();
    }
}