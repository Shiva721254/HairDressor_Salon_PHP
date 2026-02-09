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

function isPathActive(string $currentPath, array $prefixes): bool
{
    foreach ($prefixes as $p) {
        if ($p === '/') {
            if ($currentPath === '/') return true;
            continue;
        }
        if (str_starts_with($currentPath, $p)) return true;
    }
    return false;
}

/**
 * Ensure CSRF token exists for layout forms (logout).
 */
if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user = $_SESSION['user'] ?? null;
$isLoggedIn = is_array($user);
$isAdmin = $isLoggedIn && (($user['role'] ?? '') === 'admin');
$csrfToken = (string)$_SESSION['csrf_token'];

$adminActive = isPathActive($currentPath, ['/admin']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'Salon App', ENT_QUOTES, 'UTF-8') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/app.css" rel="stylesheet">
</head>
<body>

<a class="visually-hidden-focusable" href="#main-content">Skip to main content</a>

<nav class="navbar navbar-expand-lg bg-body-tertiary border-bottom">
    <div class="container d-flex align-items-center">

        <div class="navbar-nav">
            <?= navLink('/', 'Home', $currentPath) ?>
            <?= navLink('/hairdressers', 'Hairdressers', $currentPath) ?>
            <?= navLink('/services', 'Services', $currentPath) ?>
            <?= navLink('/appointments/new', 'Book', $currentPath) ?>
            <?= navLink('/appointments', 'Appointments', $currentPath) ?>
           

            <?php if ($isAdmin): ?>
                <?php
                $adminLinkClass = 'nav-link dropdown-toggle' . ($adminActive ? ' active' : '');
                $adminAria = $adminActive ? ' aria-current="page"' : '';
                ?>
                <div class="nav-item dropdown">
                    <a class="<?= $adminLinkClass ?>"
                       href="#"
                       role="button"
                       data-bs-toggle="dropdown"
                       aria-expanded="false"<?= $adminAria ?>>
                        Admin
                    </a>

                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="/admin/services">Manage Services</a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/admin/hairdressers">Manage Hairdressers</a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/admin/availability">Manage Availability</a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="/admin/gdpr-requests">GDPR Requests</a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT SIDE: AUTH -->
        <div class="ms-auto d-flex align-items-center gap-2">
            <?php if ($isLoggedIn): ?>
                <a href="/profile" class="btn btn-outline-secondary btn-sm">Profile</a>
                <span class="small text-muted">
                    <?= htmlspecialchars((string)($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    (<?= htmlspecialchars((string)($user['role'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)
                </span>

                <form method="POST" action="/logout" class="mb-0">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Logout</button>
                </form>
            <?php else: ?>
               <a href="/register" class="btn btn-outline-primary btn-sm">Register</a>
                <a href="/login" class="btn btn-primary btn-sm">Login</a>

            <?php endif; ?>
        </div>
    </div>
</nav>

<main id="main-content" class="container py-4">
    <?php if (!empty($_SESSION['flash']['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars((string)$_SESSION['flash']['success'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['flash']['success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash']['error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars((string)$_SESSION['flash']['error'], ENT_QUOTES, 'UTF-8') ?>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>
</body>
</html>
