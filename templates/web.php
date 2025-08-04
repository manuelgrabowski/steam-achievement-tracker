<div class="main">
    <div class="container">
        <?php if (empty($achievements)): ?>
            <div class="empty-state">
                <h2>No achievements found</h2>
                <p>Check back later or trigger an update to fetch recent achievements.</p>
            </div>
        <?php else: ?>
            <div class="achievements-grid">
                <?php foreach ($achievements as $achievement): ?>
                    <?= ViewRenderer::achievementCard($achievement) ?>
                <?php endforeach; ?>
            </div>
            <?= ViewRenderer::pagination($page, $total_pages) ?>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();

$meta = [
    'title' => 'Steam Achievements - ' . $steam_username,
    'description' => 'Recent Steam achievements from ' . $steam_username . '. ' . ViewRenderer::formatNumber($total_achievements) . ' achievements across ' . ViewRenderer::formatNumber($total_games) . ' games.',
    'type' => 'website',
    'url' => UrlBuilder::getCurrentUrl(),
    'image' => UrlBuilder::buildOgImageUrl()
];

$additionalHead = '<link rel="alternate" type="application/rss+xml" title="Recent Steam Achievements" href="' . UrlBuilder::buildRssUrl() . '">';

$stats = [
    'achievements' => $total_achievements,
    'games' => $total_games,
    'last_update' => $last_update
];

$navigation = [
    ['url' => UrlBuilder::buildSteamProfileUrl($steam_username), 'text' => 'Steam Profile', 'target' => '_blank'],
    ['url' => UrlBuilder::buildRssUrl(), 'text' => 'RSS Feed']
];

include 'layout.php';