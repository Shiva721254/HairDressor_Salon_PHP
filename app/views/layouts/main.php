<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
function navLink(string $href, string $label, string $currentPath): string
{
    $active = ($href === $currentPath) ? ' active' : '';
    $aria = ($href === $currentPath) ? ' aria-current="page"' : '';
    return '<a class="nav-link' . $active . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"' . $aria . '>' .
        htmlspecialchars($label, ENT_QUOTES, 'UTF-8') .
        '</a>';
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Salon App', ENT_QUOTES, 'UTF-8') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <a class="visually-hidden-focusable" href="#main-content">Skip to main content</a>

    <nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom">
        <div class="container">
            <a class="navbar-brand" href="/">Salon</a>

            <div class="navbar-nav">
                <?= navLink('/', 'Home', $currentPath) ?>
                <?= navLink('/hairdressers', 'Hairdressers', $currentPath) ?>
                <?= navLink('/services', 'Services', $currentPath) ?>
                <?= navLink('/appointments/new', 'Book', $currentPath) ?>
                <?= navLink('/appointments', 'Appointments', $currentPath) ?>
                <?= navLink('/contact', 'Contact', $currentPath) ?>
            </div>

        </div>
    </nav>

    <main id="main-content" class="container py-4">

        <?php if (!empty($_SESSION['flash']['success'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['flash']['success'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php unset($_SESSION['flash']['success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash']['error'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['flash']['error'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php unset($_SESSION['flash']['error']); ?>
        <?php endif; ?>

        <?= $content ?? '' ?>
    </main>


    <footer class="border-top py-3">
        <div class="container small text-muted">
            <?= htmlspecialchars('© ' . date('Y') . ' Salon App', ENT_QUOTES, 'UTF-8') ?>
        </div>
    </footer>

    <script src="/assets/js/app.js"></script>
</body>

</html>