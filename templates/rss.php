<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>Recent Steam Achievements</title>
        <description>Recently unlocked Steam achievements</description>
        <link><?= UrlBuilder::getBaseUrl() ?></link>
        <atom:link href="<?= UrlBuilder::buildRssUrl() ?>" rel="self" type="application/rss+xml" />
        <lastBuildDate><?= date('r') ?></lastBuildDate>
        <generator>Steam Achievements RSS Feed v3.0</generator>
        
        <?php foreach ($achievements as $achievement): ?>
        <item>
            <title><?= htmlspecialchars($achievement['achievement_name'] ?: 'Unknown Achievement') ?></title>
            <description><![CDATA[<?= ViewRenderer::buildRssDescription($achievement) ?>]]></description>
            <pubDate><?= date('r', $achievement['unlock_time']) ?></pubDate>
            <guid isPermaLink="true"><?= htmlspecialchars(UrlBuilder::buildSingleAchievementUrl($achievement)) ?></guid>
            <link><?= htmlspecialchars(UrlBuilder::buildSingleAchievementUrl($achievement)) ?></link>
        </item>
        <?php endforeach; ?>
    </channel>
</rss>