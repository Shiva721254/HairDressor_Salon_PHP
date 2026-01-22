<h1 class="mb-3">Hello</h1>

<p class="lead">
    Hello, <?= htmlspecialchars($name ?? 'Guest', ENT_QUOTES, 'UTF-8') ?>.
</p>
