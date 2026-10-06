<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/layout.php';
require __DIR__ . '/includes/catalog.php';

$user = require_login();
$name = $_SESSION['name'] ?? $user;
$s = get_settings($user);
$avatar = avatar_url($user);
$boot = [
    'items'      => catalog(),
    'category'   => $s['default_category'],
    'thumbs'     => (bool)$s['show_thumbnails'],
    'csrf'       => csrf_token(),
    'username'   => $user,
];

page_start('Browse', $s['theme'], 'home');
?>
<header class="topbar">
  <div class="user">
    <div class="avatar"><?php if ($avatar): ?><img src="<?= e($avatar) ?>" alt=""><?php else: ?><?= e(mb_strtoupper(mb_substr($name, 0, 1))) ?><?php endif; ?></div>
    <div><small>Welcome,</small><strong><?= e($name) ?></strong></div>
  </div>
  <div class="center">
    <a href="home.php" class="brand-link px" id="brandLink" title="Reset to All content">TOONAFLIX</a>
    <label class="search"><span aria-hidden="true">&#9906;</span>
      <input type="search" id="search" placeholder="Search titles..." autocomplete="off">
      <button type="button" id="clearSearch" aria-label="Clear search">&times;</button></label>
  </div>
  <div class="right">
    <form method="post" action="logout.php" id="logoutForm" data-confirm="<?= $s['confirm_logout'] ? 'Are you sure you want to log out?' : '' ?>">
      <?= csrf_field() ?><button type="submit" class="linkbtn">Logout</button>
    </form>
    <?php hamburger_menu(); ?>
  </div>
</header>

<main class="catalog">
  <?php flash_html(); ?>
  <nav class="tabs" id="tabs">
    <button data-cat="Movie">Movies</button><button data-cat="Manhwa">Manhwa</button>
    <button data-cat="Manga">Manga</button><button data-cat="Anime">Anime</button>
  </nav>
  <div id="shelves"></div>
</main>

<div class="modal-back" id="modal" hidden></div>
<script>window.TOONAFLIX = <?= json_encode($boot, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="assets/home.js"></script>
<?php page_end();
