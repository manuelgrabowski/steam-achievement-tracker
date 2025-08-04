<?php

class AchievementController {
    private DatabaseManager $db;
    private SteamAPIClient $steamAPI;
    private AchievementUpdater $updater;
    private ViewRenderer $renderer;
    private array $config;
    
    public function __construct(DatabaseManager $db, SteamAPIClient $steamAPI, array $config) {
        $this->db = $db;
        $this->steamAPI = $steamAPI;
        $this->config = $config;
        $this->updater = new AchievementUpdater($db, $steamAPI, $config);
        $this->renderer = new ViewRenderer($config);
        
        $userProfile = $this->db->getUserProfile($config['steam_id']);
        $this->renderer->setGlobalData([
            'steam_username' => $userProfile['username'] ?? 'Steam User',
            'steam_avatar_url' => $userProfile['avatar_url'] ?? null
        ]);
    }
    
    public function handleRequest(string $action): void {
        $allowedActions = ['rss', 'update', 'force-update', 'status', 'web', 'ogimage', 'single'];
        
        if (!in_array($action, $allowedActions)) {
            http_response_code(400);
            ErrorHandler::showErrorPage('Invalid action. Allowed: ' . implode(', ', $allowedActions));
            return;
        }
        
        switch($action) {
            case 'rss':
                $this->generateFeed();
                break;
            case 'update':
                $this->handleUpdate(false);
                break;
            case 'force-update':
                $this->handleForceUpdate();
                break;
            case 'status':
                $this->handleStatus();
                break;
            case 'ogimage':
                $this->handleOgImage();
                break;
            case 'single':
                $this->handleSingleAchievement();
                break;
            case 'web':
            default:
                $this->generateWebPage();
                break;
        }
    }
    
    private function handleUpdate(bool $forceUpdate = false): void {
        header('Content-Type: application/json');
        
        try {
            if (!$this->updater->shouldUpdate($forceUpdate)) {
                $lastUpdate = $this->db->getLastUpdateTime();
                echo json_encode([
                    'status' => 'skipped',
                    'message' => 'Update not needed',
                    'last_update' => $lastUpdate ? date('c', $lastUpdate) : null,
                    'next_update' => date('c', $lastUpdate + $this->config['update_interval'])
                ]);
                return;
            }
            
            $result = $this->updater->updateAchievements();
            echo json_encode($result);
            
        } catch (Exception $e) {
            ErrorHandler::handle($e->getMessage());
        }
    }
    
    private function handleForceUpdate(): void {
        $providedSecret = $_GET['secret'] ?? $_POST['secret'] ?? '';
        
        if (empty($this->config['force_secret']) || $providedSecret !== $this->config['force_secret']) {
            http_response_code(403);
            ErrorHandler::handle('Invalid or missing force secret');
            return;
        }
        
        $this->handleUpdate(true);
    }
    
    private function handleStatus(): void {
        header('Content-Type: application/json');
        
        $lastUpdate = $this->db->getLastUpdateTime();
        $totalAchievements = $this->db->getTotalAchievements();
        $totalGames = $this->db->getTotalGames();
        $nextUpdate = $lastUpdate + $this->config['update_interval'];
        $updateNeeded = $this->updater->shouldUpdate();
        
        echo json_encode([
            'status' => 'ok',
            'last_update' => $lastUpdate ? date('c', $lastUpdate) : null,
            'next_update' => date('c', $nextUpdate),
            'update_needed' => $updateNeeded,
            'total_achievements' => $totalAchievements,
            'total_games' => $totalGames,
            'steam_id' => $this->config['steam_id'],
            'update_interval' => $this->config['update_interval']
        ]);
    }
    
    private function handleSingleAchievement(): void {
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: public, max-age=600');
        
        $appId = (int)($_GET['app_id'] ?? 0);
        $achievementKey = $_GET['key'] ?? '';
        
        if (!$appId || !$achievementKey) {
            http_response_code(400);
            ErrorHandler::showErrorPage('Missing required parameters');
            return;
        }
        
        $achievement = $this->db->getSingleAchievement($appId, $achievementKey, $this->config['steam_id']);
        
        if (!$achievement) {
            http_response_code(404);
            ErrorHandler::showErrorPage('Achievement not found');
            return;
        }
        
        echo $this->renderer->render('single', ['achievement' => $achievement]);
    }
    
    private function generateFeed(): void {
        header('Content-Type: application/rss+xml; charset=UTF-8');
        header('Cache-Control: public, max-age=300');
        
        $achievements = $this->db->getAchievements($this->config['rss_item_limit']);
        echo $this->renderer->render('rss', ['achievements' => $achievements]);
    }
    
    private function generateWebPage(): void {
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: public, max-age=300');
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(max((int)($_GET['per_page'] ?? 20), 1), 100);
        $offset = ($page - 1) * $perPage;
        
        $data = [
            'achievements' => $this->db->getAchievements($perPage, $offset),
            'page' => $page,
            'total_pages' => ceil($this->db->getTotalAchievements() / $perPage),
            'total_achievements' => $this->db->getTotalAchievements(),
            'total_games' => $this->db->getTotalGames(true),
            'last_update' => $this->db->getLastUpdateTime()
        ];
        
        echo $this->renderer->render('web', $data);
    }
    
    private function handleOgImage(): void {
        $format = $_GET['format'] ?? 'png';
        
        if ($format === 'html') {
            $this->generateOgImageHTML();
        } else {
            $this->generateOgImagePNG();
        }
    }
    
    private function generateOgImageHTML(): void {
        header('Content-Type: text/html; charset=UTF-8');

        $appId = (int)($_GET['app_id'] ?? 0);
        $achievementKey = $_GET['key'] ?? '';
        
        $userProfile = $this->db->getUserProfile($this->config['steam_id']);
        $totalAchievements = $this->db->getTotalAchievements();
        $totalGames = $this->db->getTotalGames(true);
        $username = $userProfile['username'] ?? 'Steam User';
        $avatarUrl = $userProfile['avatar_url'] ?? null;
        
        if (!empty($appId) && !empty($achievementKey)) {
            $achievement = $this->db->getSingleAchievement($appId, $achievementKey, $this->config['steam_id']);
        } else {
            $achievements = $this->db->getAchievements(1, 0);
            $achievement = $achievements[0] ?? null;
        }
        
        $timeAgo = $achievement ? $this->getTimeAgo($achievement['unlock_time']) : '';
        $rarityInfo = '';
        $rarityClass = '';
        
        if ($achievement && !empty($achievement['global_percentage'])) {
            $percentage = (float)$achievement['global_percentage'];
            $rarityInfo = RaritySystem::getLabel($percentage);
            $rarityClass = 'rarity-' . strtolower(str_replace(' ', '-', $rarityInfo));
        }
        
        echo $this->renderer->render('ogimage', [
            'username' => $username,
            'avatar_url' => $avatarUrl,
            'total_achievements' => $totalAchievements,
            'total_games' => $totalGames,
            'achievement' => $achievement,
            'time_ago' => $timeAgo,
            'rarity_info' => $rarityInfo,
            'rarity_class' => $rarityClass,
        ]);
    }
    
    private function generateOgImagePNG(): void {
        $tempHtml = tempnam(sys_get_temp_dir(), 'og_image_') . '.html';
        $tempPng = tempnam(sys_get_temp_dir(), 'og_image_') . '.png';
        
        ob_start();
        $this->generateOgImageHTML();
        $html = ob_get_clean();
        
        file_put_contents($tempHtml, $html);
        
        $cmd = sprintf(
            'wkhtmltoimage --width 1200 --height 630 --format png --quality 95 %s %s 2>/dev/null',
            escapeshellarg($tempHtml),
            escapeshellarg($tempPng)
        );
        
        exec($cmd, $output, $returnCode);
        
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=300');
        readfile($tempPng);
        
        @unlink($tempHtml);
        @unlink($tempPng);
    }
    
    private function getTimeAgo(int $timestamp): string {
        $now = time();
        $diff = $now - $timestamp;
        
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        
        return date('M j, Y', $timestamp);
    }
}