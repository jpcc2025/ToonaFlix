<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/layout.php';

$user = require_login();
$PAGES = ['account' => 'Account', 'settings' => 'Settings', 'subscriptions' => 'Subscriptions'];
$page = $_GET['page'] ?? 'account';
if (!isset($PAGES[$page])) $page = 'account';

function verify_pw(string $input, string $stored): bool {
    return password_verify($input, $stored) || hash_equals($stored, $input); // hash_equals = legacy plain text
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    try {
        switch ($action) {
            case 'avatar_upload':
                if (empty($_FILES['avatar']['tmp_name']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                    flash('Choose an image first.', true);
                } elseif ($err = save_avatar($user, $_FILES['avatar']['tmp_name'])) {
                    flash($err, true);
                } else flash('Profile picture updated.');
                break;

            case 'avatar_remove':
                if (is_file(avatar_path($user))) unlink(avatar_path($user));
                flash('Profile picture removed.');
                break;

            case 'change_username':
                $next = trim($_POST['username'] ?? '');
                if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $next)) { flash('Use 3-20 letters, numbers or underscores.', true); break; }
                if ($next === $user) break;
                $st = db()->prepare('SELECT 1 FROM users WHERE username = ?');
                $st->execute([$next]);
                if ($st->fetch()) { flash('That username is already taken.', true); break; }
                $pdo = db();
                $pdo->beginTransaction();
                foreach (['users', 'user_settings', 'history'] as $table) {
                    $pdo->prepare("UPDATE $table SET username = ? WHERE username = ?")->execute([$next, $user]);
                }
                $pdo->commit();
                if (is_file(avatar_path($user))) rename(avatar_path($user), avatar_path($next));
                $_SESSION['username'] = $user = $next;
                flash("Username changed to \"$next\".");
                break;

            case 'change_email':
                $email = trim($_POST['email'] ?? '');
                if (!preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email)) { flash('Please enter a valid email address.', true); break; }
                db()->prepare('UPDATE users SET email = ? WHERE username = ?')->execute([$email, $user]);
                flash('Email updated.');
                break;

            case 'change_password':
                $cur = (string)($_POST['current'] ?? ''); $new = (string)($_POST['new'] ?? ''); $conf = (string)($_POST['confirm'] ?? '');
                $st = db()->prepare('SELECT password FROM users WHERE username = ?');
                $st->execute([$user]);
                $row = $st->fetch();
                if (!$row || !verify_pw($cur, $row['password'])) { flash('Your current password is incorrect.', true); break; }
                if (strlen($new) < 6) { flash('The new password must be at least 6 characters.', true); break; }
                if ($new !== $conf) { flash('The new passwords do not match.', true); break; }
                db()->prepare('UPDATE users SET password = ? WHERE username = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user]);
                flash('Password updated.');
                break;

            case 'save_prefs':
                $theme = $_POST['theme'] ?? '';
                $cat = $_POST['default_category'] ?? 'ALL';
                if (!isset(THEMES[$theme])) $theme = 'Midnight Purple';
                if (!isset(CATEGORIES[$cat])) $cat = 'ALL';
                get_settings($user);
                db()->prepare('UPDATE user_settings SET theme=?, default_category=?, show_thumbnails=?, confirm_logout=? WHERE username=?')
                    ->execute([$theme, $cat, isset($_POST['show_thumbnails']) ? 1 : 0, isset($_POST['confirm_logout']) ? 1 : 0, $user]);
                flash('Settings saved.');
                break;

            case 'reset_prefs':
                get_settings($user);
                db()->prepare("UPDATE user_settings SET theme='Midnight Purple', default_category='ALL', show_thumbnails=1, confirm_logout=1 WHERE username=?")->execute([$user]);
                flash('Settings reset to defaults.');
                break;

            case 'clear_history':
                db()->prepare('DELETE FROM history WHERE username = ?')->execute([$user]);
                flash('History cleared.');
                break;

            case 'subscribe':
            case 'unsubscribe':
                $plan = $action === 'unsubscribe' ? 'none' : ($_POST['plan'] ?? '');
                if (!in_array($plan, ['none', 'monthly', 'yearly'], true)) break;
                get_settings($user);
                db()->prepare('UPDATE user_settings SET plan = ? WHERE username = ?')->execute([$plan, $user]);
                flash($plan === 'none' ? 'You unsubscribed. (Demo only)' : 'You subscribed to ' . plan_label($plan) . '! (Demo only - no payment taken)');
                break;
        }
    } catch (PDOException $ex) {
        if (db()->inTransaction()) db()->rollBack();
        flash(($ex->errorInfo[1] ?? 0) === 1062 ? 'That value is already in use.' : 'Database error. Is MySQL (XAMPP) running?', true);
    }
    redirect('settings.php?page=' . $page . (isset($_GET['hf']) ? '&hf=' . urlencode($_GET['hf']) : ''));
}

// ---- Data for rendering ----
$s = get_settings($user);
$st = db()->prepare('SELECT email FROM users WHERE username = ?');
$st->execute([$user]);
$email = trim((string)($st->fetchColumn() ?: '')) ?: 'Not set';
$st = db()->prepare('SELECT COUNT(*) FROM history WHERE username = ?');
$st->execute([$user]);
$histCount = (int)$st->fetchColumn();
$avatar = avatar_url($user);

$hf = $_GET['hf'] ?? 'all';
$where = match ($hf) { 'reading' => "AND category IN ('Manhwa','Manga')", 'watching' => "AND category NOT IN ('Manhwa','Manga')", default => '' };
$history = [];
if ($page === 'settings') {
    $st = db()->prepare("SELECT category, title, viewed_at FROM history WHERE username = ? $where ORDER BY viewed_at DESC, id DESC LIMIT 100");
    $st->execute([$user]);
    $history = $st->fetchAll();
}

page_start($PAGES[$page], $s['theme'], 'settings');
?>
<main class="settings-wrap">
  <header class="settings-head">
    <?php hamburger_menu($page); ?>
    <h1 class="px"><?= e(strtoupper($PAGES[$page])) ?></h1>
    <a class="btn ghost" href="home.php">&larr; Back to browse</a>
  </header>
  <?php flash_html(); ?>

<?php if ($page === 'account'): ?>
  <section class="panel">
    <h2>Account Summary</h2>
    <div class="summary">
      <div class="avatar big"><?php if ($avatar): ?><img src="<?= e($avatar) ?>" alt=""><?php else: ?><?= e(mb_strtoupper(mb_substr($user, 0, 1))) ?><?php endif; ?></div>
      <dl>
        <dt>Username</dt><dd><?= e($user) ?></dd>
        <dt>Email</dt><dd><?= e($email) ?></dd>
        <dt>Password</dt><dd>&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</dd>
        <dt>Plan</dt><dd><?= e(plan_label($s['plan'])) ?></dd>
        <dt>History</dt><dd><?= $histCount ?> titles</dd>
      </dl>
    </div>
  </section>

  <section class="panel">
    <h2>Edit Account</h2>
    <div class="row"><span>Profile picture</span>
      <form method="post" enctype="multipart/form-data" class="inline">
        <?= csrf_field() ?><input type="hidden" name="action" value="avatar_upload">
        <input type="file" name="avatar" accept="image/*" required>
        <button class="btn sm">Change</button>
      </form>
      <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="avatar_remove"><button class="btn sm dark">Remove</button></form>
    </div>
    <form method="post" class="row"><?= csrf_field() ?><input type="hidden" name="action" value="change_username">
      <label for="u">Username</label><input id="u" name="username" placeholder="3-20 letters, numbers or _" required><button class="btn sm">Change</button></form>
    <form method="post" class="row"><?= csrf_field() ?><input type="hidden" name="action" value="change_email">
      <label for="em">Email</label><input id="em" type="email" name="email" placeholder="New email address" required><button class="btn sm">Change</button></form>
    <form method="post" class="row stack"><?= csrf_field() ?><input type="hidden" name="action" value="change_password">
      <label>Password</label>
      <input type="password" name="current" placeholder="Current password" autocomplete="current-password" required>
      <input type="password" name="new" placeholder="New password (6+ characters)" autocomplete="new-password" required>
      <input type="password" name="confirm" placeholder="Confirm new password" autocomplete="new-password" required>
      <button class="btn sm">Change</button></form>
    <form method="post" action="logout.php" class="row" data-confirm="Log out of &quot;<?= e($user) ?>&quot; and switch to another account?">
      <?= csrf_field() ?><span>Use a different account</span><button class="btn sm dark">Switch</button></form>
  </section>

<?php elseif ($page === 'settings'): ?>
  <form method="post" id="prefs">
    <?= csrf_field() ?><input type="hidden" name="action" value="save_prefs">
    <section class="panel">
      <h2>Theme</h2>
      <div class="swatches">
        <?php foreach (THEMES as $tn => $c): ?>
          <label class="swatch" style="--a:<?= $c[1] ?>;--b:<?= $c[2] ?>;--ac:<?= $c[3] ?>;--bgc:<?= $c[0] ?>">
            <input type="radio" name="theme" value="<?= e($tn) ?>" <?= $tn === $s['theme'] ? 'checked' : '' ?>>
            <span class="sw-body"><b><?= e($tn) ?></b><i></i><i></i></span>
          </label>
        <?php endforeach; ?>
      </div>
    </section>
    <section class="panel">
      <h2>Browsing</h2>
      <div class="row"><label for="dc">Default category</label>
        <select id="dc" name="default_category"><?php foreach (CATEGORIES as $k => $l): ?>
          <option value="<?= e($k) ?>" <?= $k === $s['default_category'] ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
      <div class="row"><label><input type="checkbox" name="show_thumbnails" <?= $s['show_thumbnails'] ? 'checked' : '' ?>> Show thumbnail images on cards</label></div>
    </section>
    <section class="panel">
      <h2>Session</h2>
      <div class="row"><label><input type="checkbox" name="confirm_logout" <?= $s['confirm_logout'] ? 'checked' : '' ?>> Ask before logging out</label></div>
    </section>
    <div class="actions">
      <button type="submit" form="resetForm" class="btn dark">Reset</button>
      <a class="btn dark" href="home.php">Cancel</a>
      <button class="btn">Save</button>
    </div>
  </form>
  <form method="post" id="resetForm"><?= csrf_field() ?><input type="hidden" name="action" value="reset_prefs"></form>

  <section class="panel">
    <h2>History</h2>
    <div class="row"><span>Show</span>
      <span class="chips">
        <?php foreach (['all' => 'All', 'reading' => 'Reading', 'watching' => 'Watching'] as $k => $l): ?>
          <a href="settings.php?page=settings&hf=<?= $k ?>" class="<?= $hf === $k || ($k === 'all' && !in_array($hf, ['reading', 'watching'])) ? 'on' : '' ?>"><?= $l ?></a>
        <?php endforeach; ?></span></div>
    <ul class="history">
      <?php foreach ($history as $h): $reading = in_array($h['category'], ['Manhwa', 'Manga'], true); ?>
        <li><?= $reading ? 'Read' : 'Watched' ?> &bull; <b><?= e($h['title']) ?></b> (<?= e($h['category']) ?>) &bull; <time><?= e(substr($h['viewed_at'], 0, 16)) ?></time></li>
      <?php endforeach; ?>
      <?php if (!$history): ?><li class="muted">Nothing here yet.</li><?php endif; ?>
    </ul>
    <form method="post" class="right" data-confirm="Clear your whole read/watch history?"><?= csrf_field() ?><input type="hidden" name="action" value="clear_history"><button class="btn dark">Clear History</button></form>
  </section>

<?php else: ?>
  <section class="panel">
    <h2>Premium Plans</h2>
    <p class="muted">Demo only - subscribing does not charge you or unlock anything.</p>
    <?php $plans = [
        'monthly' => ['Premium Monthly', '$4.99 / month', ['My List access', 'Ad-free reading and watching', 'Early access to new chapters']],
        'yearly'  => ['Premium Yearly', '$49.99 / year', ['Everything in Monthly', 'Two months free', 'Exclusive yearly badge']],
    ];
    foreach ($plans as $id => [$pn, $price, $perks]): $active = $s['plan'] === $id; ?>
      <article class="plan <?= $active ? 'active' : '' ?>">
        <h3><?= e($pn) ?> - <?= e($price) ?></h3>
        <ul><?php foreach ($perks as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul>
        <form method="post" class="plan-foot"><?= csrf_field() ?>
          <input type="hidden" name="action" value="<?= $active ? 'unsubscribe' : 'subscribe' ?>"><input type="hidden" name="plan" value="<?= $id ?>">
          <span class="<?= $active ? 'good' : 'muted' ?>"><?= $active ? '&#9679; Subscribed' : 'Not subscribed' ?></span>
          <button class="btn <?= $active ? 'dark' : '' ?>"><?= $active ? 'Unsubscribe' : 'Subscribe' ?></button>
        </form>
      </article>
    <?php endforeach; ?>
  </section>
<?php endif; ?>
</main>
<?php page_end();
