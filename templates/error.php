<div class="error-page">
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <h1>Steam Achievements</h1>
        <div class="error-message">
            <strong>Error:</strong> <?= htmlspecialchars($error_message) ?>
        </div>
        <div class="actions">
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
            <a href="<?= htmlspecialchars(UrlBuilder::getCurrentUrl()) ?>" class="btn btn-primary">Try Again</a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$meta = [
    'title' => 'Error - Steam Achievements',
    'description' => 'An error occurred while loading Steam achievements.'
];

include 'layout.php';