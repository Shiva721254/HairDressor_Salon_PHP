<?php
$title = $title ?? 'Not Found';
?>
<div class="container">
    <h1 class="h3 mb-3">404 - <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    <p>The page or resource you requested could not be found.</p>
    <a class="btn btn-primary" href="/">Go back home</a>
</div>
