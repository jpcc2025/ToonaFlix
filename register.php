<?php
require __DIR__ . '/config.php';
require __DIR__ . '/includes/layout.php';

if (!empty($_SESSION['username'])) redirect('home.php');

$error = '';
$v = ['email' => '', 'name' => '', 'username' => '', 'age' => 18, 'gender' => 'M'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $v['email']    = trim($_POST['email'] ?? '');
    $v['name']     = trim($_POST['name'] ?? '');
    $v['username'] = trim($_POST['username'] ?? '');
    $v['age']      = (int)($_POST['age'] ?? 18);
    $v['gender']   = ($_POST['gender'] ?? 'M') === 'F' ? 'F' : 'M';
    $pass = (string)($_POST['password'] ?? '');
    $conf = (string)($_POST['confirm'] ?? '');

    if ($v['email'] === '')                                   $error = 'Please enter your email.';
    elseif (!preg_match('/^[\w.+-]+@[\w-]+(\.[\w-]+)+$/', $v['email'])) $error = "That email doesn't look right.";
    elseif ($v['name'] === '')                                $error = 'Please enter your name.';
    elseif (strlen($v['username']) < 3)                       $error = 'Username must be at least 3 characters.';
    elseif (strlen($pass) < 6)                                $error = 'Password must be at least 6 characters.';
    elseif ($pass !== $conf)                                  $error = "Passwords don't match.";
    elseif ($v['age'] < 10 || $v['age'] > 80)                 $error = 'Please pick a valid age.';

    if ($error === '') {
        try {
            db()->prepare('INSERT INTO users (email, name, username, age, gender, password) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$v['email'], $v['name'], $v['username'], $v['age'], $v['gender'], password_hash($pass, PASSWORD_DEFAULT)]);
            if (!empty($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                save_avatar($v['username'], $_FILES['avatar']['tmp_name']); // optional; ignore failures
            }
            flash('Account created! Sign in to continue.');
            redirect('login.php');
        } catch (PDOException $ex) {
            $error = ($ex->errorInfo[1] ?? 0) === 1062
                ? 'That email or username is already registered.'
                : "Can't reach the database. Is MySQL (XAMPP) running?";
        }
    }
}

page_start('Registration', 'Midnight Purple', 'auth');
?>
<main class="auth-card wide">
  <section class="brand">
    <div class="logo px">TOONAFLIX</div>
    <h2 class="px">Join the fun.</h2>
    <p>Create your free account and start watching in under a minute.</p>
    <ul>
      <li>Personalized picks just for you</li>
      <li>Save shows to your watchlist</li>
      <li>Free to join</li>
    </ul>
  </section>
  <section class="form-side">
    <form method="post" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <div class="reg-head">
        <label class="pfp pick" title="Add a profile photo">
          <img id="pfpPreview" alt="" hidden><span id="pfpGlyph">+</span>
          <input type="file" name="avatar" accept="image/*" id="pfpInput" hidden>
        </label>
        <div><h1 class="px">Create your account</h1>
          <p class="muted">Tap the square to add a profile photo (optional).</p></div>
      </div>
      <div class="grid2">
        <div><label for="email">Email</label>
          <input id="email" name="email" value="<?= e($v['email']) ?>" placeholder="you@example.com" autocomplete="email"></div>
        <div><label for="name">Name</label>
          <input id="name" name="name" value="<?= e($v['name']) ?>" placeholder="Your full name" autocomplete="name"></div>
        <div><label for="username">Username</label>
          <input id="username" name="username" value="<?= e($v['username']) ?>" placeholder="Pick a username" autocomplete="username"></div>
        <div><label for="age">Age</label>
          <select id="age" name="age"><?php for ($i = 10; $i <= 80; $i++): ?>
            <option value="<?= $i ?>" <?= $i === $v['age'] ? 'selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
        <div><label for="password">Password</label>
          <input id="password" type="password" name="password" placeholder="At least 6 characters" autocomplete="new-password"></div>
        <div><label for="confirm">Confirm password</label>
          <input id="confirm" type="password" name="confirm" placeholder="Repeat password" autocomplete="new-password"></div>
      </div>
      <label>Gender</label>
      <div class="segmented">
        <label><input type="radio" name="gender" value="M" <?= $v['gender'] === 'M' ? 'checked' : '' ?>><span>Male</span></label>
        <label><input type="radio" name="gender" value="F" <?= $v['gender'] === 'F' ? 'checked' : '' ?>><span>Female</span></label>
      </div>
      <div class="status err" <?= $error ? '' : 'hidden' ?>><?= e($error) ?></div>
      <button class="btn big" type="submit">Create account</button>
    </form>
    <p class="muted">Already have an account? <a href="login.php">Sign in</a></p>
  </section>
</main>
<?php page_end();
