<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= ViewRenderer::metaTags($meta ?? []) ?>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🏆</text></svg>">
    <meta name="theme-color" content="#1e3a8a">
    <link rel="stylesheet" href="style.css">
    <?= $additionalHead ?? '' ?>
</head>
<body>
    <?php include 'header.php'; ?>
    <?= $content ?>
</body>
</html>