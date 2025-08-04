<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1200, height=630">
    <title>Steam Achievement Tracker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            width: 1200px;
            height: 630px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            font-family: Arial, sans-serif;
            display: block;
            overflow: hidden;
            padding: 30px;
            margin: 0;
        }
        
        .achievement-card {
            width: 1140px;
            height: 570px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
            padding: 40px;
            border-left: 8px solid #1e3c72;
            display: block;
        }
        
        .pattern-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.02;
            background-image: 
                linear-gradient(45deg, transparent 40%, rgba(0,0,0,0.1) 50%, transparent 60%),
                linear-gradient(-45deg, transparent 40%, rgba(0,0,0,0.1) 50%, transparent 60%);
            background-size: 30px 30px;
            pointer-events: none;
        }
        
        .header-section {
            display: block;
            overflow: hidden;
            margin-bottom: 30px;
            height: 60px;
        }
        
        .site-branding {
            float: left;
            display: block;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #1e3c72;
            line-height: 60px;
        }
        
        .user-info {
            float: right;
            display: block;
            height: 60px;
        }
        
        .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid #1e3c72;
            object-fit: cover;
            float: right;
            margin-top: 5px;
        }
        
        .avatar-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            display: block;
            text-align: center;
            line-height: 44px;
            font-size: 20px;
            color: white;
            float: right;
            margin-top: 5px;
        }
        
        .user-details {
            float: right;
            text-align: right;
            margin-right: 15px;
            margin-top: 8px;
        }
        
        .user-details .username {
            font-size: 18px;
            font-weight: 600;
            color: #1e3c72;
            margin-bottom: 2px;
        }
        
        .user-stats {
            font-size: 14px;
            color: #666;
        }
        
        .achievement-content {
            display: block;
            overflow: hidden;
            margin-bottom: 30px;
            min-height: 150px;
        }
        
        .achievement-icon-container {
            float: left;
            margin-right: 30px;
        }
        
        .achievement-icon {
            width: 130px;
            height: 130px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            object-fit: cover;
        }
        
        .achievement-icon.placeholder {
            background: linear-gradient(135deg, #f5f5f5, #e0e0e0);
            display: block;
            text-align: center;
            line-height: 130px;
            font-size: 50px;
            color: #999;
        }
        
        .achievement-info {
            overflow: hidden;
            padding-top: 10px;
        }
        
        .achievement-name {
            font-size: 36px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 12px;
            line-height: 1.2;
            word-wrap: break-word;
        }
        
        .game-name {
            font-size: 20px;
            color: #666;
            margin-bottom: 12px;
            font-weight: 500;
        }
        
        .achievement-description {
            font-size: 16px;
            color: #555;
            line-height: 1.4;
            max-height: 60px;
            overflow: hidden;
        }
        
        .meta-section {
            display: block;
            overflow: hidden;
            padding-top: 25px;
            border-top: 2px solid #f0f0f0;
            clear: both;
        }
        
        .meta-item {
            float: left;
            width: 33.33%;
            padding-right: 20px;
            box-sizing: border-box;
        }
        
        .meta-label {
            font-size: 14px;
            font-weight: 600;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .meta-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        
        .rarity-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .rarity-common { background: linear-gradient(135deg, #616161, #424242); }
        .rarity-very-common { background: linear-gradient(135deg, #757575, #616161); }
        .rarity-uncommon { background: linear-gradient(135deg, #f9a825, #f57c00); }
        .rarity-rare { background: linear-gradient(135deg, #1976d2, #1565c0); }
        .rarity-very-rare { background: linear-gradient(135deg, #388e3c, #2e7d32); }
        .rarity-ultra-rare { background: linear-gradient(135deg, #7b1fa2, #6a1b9a); }
        .rarity-legendary { background: linear-gradient(135deg, #f57c00, #ef6c00); }
        
        .hidden-badge {
            background: linear-gradient(135deg, #424242, #212121);
            color: white;
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .no-achievement {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            text-align: center;
            gap: 20px;
        }
        
        .no-achievement .icon {
            font-size: 80px;
            opacity: 0.3;
        }
        
        .no-achievement .message {
            font-size: 28px;
            color: #666;
            font-weight: 500;
        }
        
        .no-achievement .subtitle {
            font-size: 18px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="achievement-card">
        <div class="pattern-overlay"></div>
        
        <div class="header-section">
            <div class="site-branding">
                <div class="logo">🏆 Steam Achievement</div>
            </div>
            
            <div class="user-info">
                <div class="user-details">
                    <div class="username"><?= htmlspecialchars($username) ?></div>
                    <div class="user-stats"><?= number_format($total_achievements) ?> achievements • <?= number_format($total_games) ?> games</div>
                </div>
                
                <?php if ($avatar_url): ?>
                    <img src="<?= htmlspecialchars($avatar_url) ?>" alt="Avatar" class="avatar">
                <?php else: ?>
                    <div class="avatar-placeholder">👤</div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($achievement): ?>
            <div class="achievement-content">
                <?php if (!empty($achievement['icon'])): ?>
                    <div class="achievement-icon-container">
                        <img src="<?= htmlspecialchars($achievement['icon']) ?>" alt="Achievement" class="achievement-icon">
                    </div>
                <?php else: ?>
                    <div class="achievement-icon-container">
                        <div class="achievement-icon placeholder">🏆</div>
                    </div>
                <?php endif; ?>
                
                <div class="achievement-info">
                    <div class="achievement-name"><?= htmlspecialchars($achievement['achievement_name'] ?? 'Unknown Achievement') ?></div>
                    <div class="game-name">🎮 <?= htmlspecialchars($achievement['game_name'] ?? 'Unknown Game') ?></div>
                    
                    <?php if (!empty($achievement['description'])): ?>
                        <div class="achievement-description"><?= htmlspecialchars($achievement['description']) ?></div>
                    <?php else: ?>
                        <div class="achievement-description"><div class="hidden-badge">🔒 Hidden Achievement</div></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="meta-section">
                <div class="meta-item">
                    <div class="meta-label">📅 Unlocked</div>
                    <div class="meta-value"><?= htmlspecialchars($time_ago) ?></div>
                </div>
                
                <?php if ($rarity_info): ?>
                <div class="meta-item">
                    <div class="meta-label">🏆 Rarity</div>
                    <div class="meta-value">
                        <div class="rarity-badge <?= htmlspecialchars($rarity_class) ?>">🏆 <?= htmlspecialchars($rarity_info) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="no-achievement">
                <div class="icon">🏆</div>
                <div class="message">No recent achievements found</div>
                <div class="subtitle">Try again later.</div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>