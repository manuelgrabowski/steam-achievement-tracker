<div class="header">
    <div class="container">
        <div class="header-content">
            <div class="logo">🏆 <a href="<?= htmlspecialchars(UrlBuilder::getBaseUrl()) ?>">Steam Achievements</a></div>
            
            <?php if (isset($stats)): ?>
            <div class="stats">
                <div class="stat">📊 <?= ViewRenderer::formatNumber($stats['achievements']) ?> achievements</div>
                <div class="stat">🎮 <?= ViewRenderer::formatNumber($stats['games']) ?> games</div>
                <div class="stat">🕒 Updated <?= $stats['last_update'] ? date('M j, Y', $stats['last_update']) : 'never' ?></div>
            </div>
            <?php endif; ?>
            
            <div class="nav-links">
                <?php foreach ($navigation ?? [] as $link): ?>
                    <a href="<?= htmlspecialchars($link['url']) ?>" <?= isset($link['target']) ? 'target="' . htmlspecialchars($link['target']) . '"' : '' ?>>
                        <?= htmlspecialchars($link['text']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>