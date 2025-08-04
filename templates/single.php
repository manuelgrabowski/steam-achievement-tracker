<div class="main">
    <div class="container">
        <div class="single-achievement">
            <?= ViewRenderer::achievementCard($achievement, true) ?>
            
            <div class="actions">
                <a href="<?= htmlspecialchars(UrlBuilder::getBaseUrl()) ?>" class="btn btn-primary">View All Achievements</a>
                <a href="<?= htmlspecialchars(UrlBuilder::buildSteamStatsUrl($steam_username, $achievement['app_id'])) ?>" target="_blank" class="btn btn-secondary">View Game Stats</a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$achievementName = htmlspecialchars($achievement['achievement_name'] ?: 'Unknown Achievement');
$gameName = htmlspecialchars($achievement['game_name']);
$description = htmlspecialchars($achievement['description'] ?: '');
$unlockDate = date('M j, Y – g:i A', $achievement['unlock_time']);

$meta = [
    'title' => $achievementName . ' - ' . $gameName,
    'description' => 'Achievement unlocked on ' . $unlockDate . '.' . "\n" . ($description ? ' ' . $description : ''),
    'type' => 'website',
    'url' => UrlBuilder::getCurrentUrl(),
    'image' => UrlBuilder::buildOgImageUrl($achievement)
];

$navigation = [
    ['url' => UrlBuilder::getBaseUrl(), 'text' => 'All Achievements'],
    ['url' => UrlBuilder::buildSteamProfileUrl($steam_username), 'text' => 'Steam Profile', 'target' => '_blank']
];

include 'layout.php';