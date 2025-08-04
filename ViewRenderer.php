<?php

class ViewRenderer {
    private array $config;
    private array $globalData = [];
    
    public function __construct(array $config) {
        $this->config = $config;
    }
    
    public function setGlobalData(array $data): void {
        $this->globalData = array_merge($this->globalData, $data);
    }
    
    public function render(string $view, array $data = []): string {
        $allData = array_merge($this->globalData, $data);
        return $this->renderTemplate($view, $allData);
    }
    
    private function renderTemplate(string $template, array $data): string {
        extract($data);
        ob_start();
        include $this->getTemplatePath($template);
        return ob_get_clean();
    }
    
    private function getTemplatePath(string $template): string {
        $path = __DIR__ . '/templates/' . $template . '.php';
        if (!file_exists($path)) {
            throw new Exception('Template not found: ' . $template);
        }
        return $path;
    }
    
    public static function timeAgo(int $timestamp): string {
        $diff = time() - $timestamp;
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date('M j, Y', $timestamp);
    }
    
    public static function rarity(float $percentage): array {
        return RaritySystem::getRarity($percentage);
    }
    
    public static function formatNumber(int $number): string {
        return number_format($number);
    }
    
    public static function metaTags(array $meta): string {
        $tags = [];
        
        if (isset($meta['title'])) {
            $tags[] = '<title>' . htmlspecialchars($meta['title']) . '</title>';
        }
        if (isset($meta['description'])) {
            $tags[] = '<meta name="description" content="' . htmlspecialchars($meta['description']) . '">';
        }
        
        $ogProperties = ['title', 'description', 'type', 'url', 'image'];
        foreach ($ogProperties as $property) {
            if (isset($meta[$property])) {
                $tags[] = '<meta property="og:' . $property . '" content="' . htmlspecialchars($meta[$property]) . '">';
            }
        }
        
        $tags[] = '<meta name="twitter:card" content="summary">';
        if (isset($meta['title'])) {
            $tags[] = '<meta name="twitter:title" content="' . htmlspecialchars($meta['title']) . '">';
        }
        if (isset($meta['description'])) {
            $tags[] = '<meta name="twitter:description" content="' . htmlspecialchars($meta['description']) . '">';
        }
        if (isset($meta['image'])) {
            $tags[] = '<meta name="twitter:image" content="' . htmlspecialchars($meta['image']) . '">';
        }
        
        return implode("\n", $tags);
    }
    
    public static function achievementCard(array $achievement, bool $large = false): string {
        $cardClass = $large ? 'achievement-card large' : 'achievement-card';
        $iconClass = $large ? 'achievement-icon large' : 'achievement-icon';
        $titleTag = $large ? 'h1' : 'h3';
        
        ob_start();
        ?>
        <div class="<?= $cardClass ?>">
            <div class="achievement-header">
                <?php if (!empty($achievement['icon'])): ?>
                    <div class="<?= $iconClass ?>">
                        <img src="<?= htmlspecialchars($achievement['icon']) ?>" alt="Achievement Icon">
                    </div>
                <?php else: ?>
                    <div class="<?= $iconClass ?>">🏆</div>
                <?php endif; ?>
                
                <div class="achievement-info">
                    <div class="achievement-content">
                        <a href="<?= htmlspecialchars(UrlBuilder::buildSingleAchievementUrl($achievement)) ?>" >
                            <<?= $titleTag ?>><?= htmlspecialchars($achievement['achievement_name'] ?? 'Unknown Achievement') ?></<?= $titleTag ?>>
                        </a>
                        
                        <?php if (($achievement['hidden'] ?? 0) == 1): ?>
                            <div class="achievement-description">
                                <span class="hidden-badge">🔒 Hidden Achievement</span>
                            </div>
                        <?php elseif (!empty($achievement['description'])): ?>
                            <div class="achievement-description"><?= htmlspecialchars($achievement['description']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="game-name">
                        🎮 <a href="<?= htmlspecialchars(UrlBuilder::buildSteamStoreUrl($achievement['app_id'])) ?>" target="_blank">
                            <?= htmlspecialchars($achievement['game_name']) ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if (!$large): ?>
                <div class="achievement-meta">
                    <div class="left-meta">
                        <?= self::rarityBadge($achievement) ?>
                    </div>
                    <div class="unlock-date">
                        <span class="unlock-span tooltip" 
                           data-tooltip="<?= date('Y-m-d H:i:s T', $achievement['unlock_time']) ?>">
                            🕒 <?= self::timeAgo($achievement['unlock_time']) ?>
                        </span>
                    </div>
                </div>
            <?php else: ?>
                <div class="achievement-meta">
                    <div class="meta-grid">
                        <div class="meta-item">
                            <div class="meta-label">📅 Unlocked</div>
                            <div class="meta-value"><?= date('M j, Y g:i A', $achievement['unlock_time']) ?></div>
                        </div>
                        
                        <?php if ($achievement['global_percentage'] !== null): ?>
                        <div class="meta-item">
                            <div class="meta-label">🏆 Rarity</div>
                            <div class="meta-value"><?= self::rarityBadge($achievement, true) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function rarityBadge(array $achievement, bool $showPercentage = false): string {
        if ($achievement['global_percentage'] === null) return '';
        
        $percentage = round((float)$achievement['global_percentage'], 2);
        $rarity = self::rarity($percentage);
        $text = $showPercentage ? $rarity['label'] . ' (' . $percentage . '%)' : $rarity['label'];
        $tooltipText = $percentage . '% of players';
        
        return '<div class="rarity ' . htmlspecialchars($rarity['class']) . ' tooltip" data-tooltip="' . htmlspecialchars($tooltipText) . '">🏆 ' . htmlspecialchars($text) . '</div>';
    }
    
    public static function pagination(int $currentPage, int $totalPages): string {
        if ($totalPages <= 1) return '';
        
        ob_start();
        ?>
        <div class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="<?= htmlspecialchars(UrlBuilder::buildPaginationUrl($currentPage - 1)) ?>">« Previous</a>
            <?php endif; ?>
            
            <?php
            $start = max(1, $currentPage - 2);
            $end = min($totalPages, $currentPage + 2);
            
            if ($start > 1):
            ?>
                <a href="<?= htmlspecialchars(UrlBuilder::buildPaginationUrl(1)) ?>">1</a>
                <?php if ($start > 2): ?><span>...</span><?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $start; $i <= $end; $i++): ?>
                <?php if ($i == $currentPage): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= htmlspecialchars(UrlBuilder::buildPaginationUrl($i)) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($end < $totalPages): ?>
                <?php if ($end < $totalPages - 1): ?><span>...</span><?php endif; ?>
                <a href="<?= htmlspecialchars(UrlBuilder::buildPaginationUrl($totalPages)) ?>"><?= $totalPages ?></a>
            <?php endif; ?>
            
            <?php if ($currentPage < $totalPages): ?>
                <a href="<?= htmlspecialchars(UrlBuilder::buildPaginationUrl($currentPage + 1)) ?>">Next »</a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function buildRssDescription(array $achievement): string {
        $html = '';
        
        if (!empty($achievement['icon'])) {
            $html .= '<img src="' . htmlspecialchars($achievement['icon']) . '" alt="Achievement Icon" style="float: left; margin-right: 10px; width: 64px; height: 64px;" />';
        }
        
        if (($achievement['hidden'] ?? 0) == 1) {
            $html .= '<p><strong>🔒 Hidden Achievement</strong></p>';
        } elseif (!empty($achievement['description'])) {
            $html .= '<p>' . htmlspecialchars($achievement['description']) . '</p>';
        }
        
        $rarityMeta = '';
        if ($achievement['global_percentage'] !== null) {
            $percentage = round((float)$achievement['global_percentage'], 2);
            $rarity = self::rarity($percentage);
            $rarityMeta = '<strong>🏆 Rarity:</strong> ' . htmlspecialchars($rarity['label']) . ' (' . $percentage . '% of players)';
        }

        $metadata = [
            '<strong>🎮 Game:</strong> <a href="' . htmlspecialchars(UrlBuilder::buildSteamStoreUrl($achievement['app_id'])) . '">' . htmlspecialchars($achievement['game_name']) . '</a>',
            $rarityMeta,
            '<strong>📅 Unlocked:</strong> ' . date('Y-m-d H:i:s T', $achievement['unlock_time'])
        ];
        
        $html .= '<br><hr><div style="margin-top: 10px; padding: 10px; background-color: #f5f5f5; border-radius: 4px; font-size: 0.9em;">';
        $html .= implode('<br/>', array_filter($metadata));
        $html .= '</div><div style="clear: both;"></div>';
        
        return $html;
    }
}