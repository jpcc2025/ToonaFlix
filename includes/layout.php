<?php
function page_start(string $title, string $themeName = 'Midnight Purple', string $bodyClass = ''): void { ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Toonaflix - <?= e($title) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=VT323&display=swap" rel="stylesheet">
<style><?= theme_css($themeName) ?></style>
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= e($bodyClass) ?>">
<?php }

function page_end(): void { ?>
<script src="assets/app.js"></script>
</body>
</html>
<?php }

function flash_html(): void {
    if ($f = take_flash()) {
        echo '<div class="status ' . ($f[1] ? 'err' : 'ok') . '" role="alert">' . e($f[0]) . '</div>';
    }
}

/** The three-line menu: Account / Settings / Subscriptions */
function hamburger_menu(string $active = ''): void {
    $pages = ['account' => 'Account', 'settings' => 'Settings', 'subscriptions' => 'Subscriptions']; ?>
    <div class="menu">
      <button type="button" class="hamburger" aria-label="Menu" aria-haspopup="true" aria-expanded="false" data-menu-toggle>
        <span></span><span></span><span></span>
      </button>
      <ul class="menu-list" hidden>
        <?php foreach ($pages as $k => $label): ?>
          <li><a href="settings.php?page=<?= $k ?>" class="<?= $k === $active ? 'active' : '' ?>"><?= $label ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
<?php }
