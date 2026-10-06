<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/layout.php';

if (!empty($_SESSION['username'])) redirect('home.php');

$error = ''; $identity = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $identity = trim($_POST['identity'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    if ($identity === '' || $password === '') {
        $error = 'Please enter your email/username and password.';
    } else {
        try {
            $st = db()->prepare('SELECT name, username, password FROM users WHERE email = ? OR username = ? LIMIT 1');
            $st->execute([$identity, $identity]);
            $u = $st->fetch();
            $ok = false;
            if ($u) {
                if (password_verify($password, $u['password'])) {
                    $ok = true;
                } elseif (hash_equals($u['password'], $password)) {
                    // Legacy plain-text password from the Java app: accept once, then upgrade to a hash
                    $ok = true;
                    db()->prepare('UPDATE users SET password = ? WHERE username = ?')
                        ->execute([password_hash($password, PASSWORD_DEFAULT), $u['username']]);
                }
            }
            if ($ok) {
                session_regenerate_id(true);
                $_SESSION['username'] = $u['username'];
                $_SESSION['name'] = $u['name'];
                redirect('home.php');
            }
            $error = 'Incorrect email/username or password.';
        } catch (PDOException $ex) {
            $error = "Can't reach the database. Is MySQL (XAMPP) running?";
        }
    }
}

page_start('Login', 'Midnight Purple', 'auth');
?>
<main class="auth-card">
  <section class="brand">
    <div class="logo px">TOONAFLIX</div>
    <h2 class="px">Your next favorite toon is waiting.</h2>
    <p>Stream, save and binge the best animated shows in one place.</p>
    <ul>
      <li>Thousands of animated titles</li>
      <li>Pick up right where you left off</li>
      <li>Made for every kind of fan</li>
    </ul>
  </section>
  <section class="form-side">
    <div class="pfp" aria-hidden="true">&#9786;</div>
    <h1 class="px">Welcome back</h1>
    <p class="muted">Sign in to continue watching.</p>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <label for="identity">Email or username</label>
      <input id="identity" name="identity" value="<?= e($identity) ?>" placeholder="you@example.com" autocomplete="username" class="<?= $error ? 'invalid' : '' ?>" required>
      <label for="password">Password</label>
      <div class="pw"><input id="password" type="password" name="password" placeholder="Enter your password" autocomplete="current-password" class="<?= $error ? 'invalid' : '' ?>" required>
        <button type="button" class="toggle" data-toggle-pw="password">SHOW</button></div>
      <div class="status err" <?= $error ? '' : 'hidden' ?>><?= e($error) ?></div>
      <button class="btn big" type="submit">Sign in</button>
    </form>
    <p class="muted">New to Toonaflix? <a href="register.php">Create an account</a></p>
  </section>
</main>
<?php page_end();
