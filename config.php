<?php
// Replaces DBConnection.java + the static helpers of SettingsFrame.java
declare(strict_types=1);
session_start();

// XAMPP defaults: host localhost, user root, empty password
const DB_HOST = 'localhost';
const DB_NAME = 'toonaflix_db';
const DB_USER = 'root';
const DB_PASS = '';

const THEMES = [
    //                       bg        headerA   headerB   accent    tab       tabBorder panel
    'Midnight Purple' => ['#0E0B1A', '#28144D', '#4A156D', '#7353BA', '#231A48', '#563E88', '#1F1638'],
    'Ocean Blue'      => ['#08121F', '#0F2A4D', '#14507A', '#2F80ED', '#12284A', '#2A5A94', '#0F2038'],
    'Crimson Night'   => ['#160A0E', '#4D1423', '#7A1530', '#D6335C', '#3A1220', '#8A2E4A', '#2A1019'],
    'Forest Green'    => ['#08150F', '#123D26', '#1B5E3A', '#2FA866', '#123423', '#2D7A52', '#0F2619'],
    'Sunset Orange'   => ['#17100A', '#4D2A12', '#7A3A15', '#F28C28', '#3A2412', '#8A5A2E', '#2A1B0F'],
    'Graphite'        => ['#0F0F12', '#25262B', '#3A3B42', '#8E94A8', '#202127', '#4A4C57', '#1A1B20'],
];
const CATEGORIES = ['ALL' => 'All', 'Movie' => 'Movies', 'Manhwa' => 'Manhwa', 'Manga' => 'Manga', 'Anime' => 'Anime'];

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header('Location: ' . $url); exit; }

// ---- CSRF ----
function csrf_token(): string {
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Invalid request token. Go back and reload the page.');
    }
}

// ---- Flash messages ----
function flash(string $msg, bool $error = false): void { $_SESSION['flash'] = [$msg, $error]; }
function take_flash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }

// ---- Auth ----
function require_login(): string {
    if (empty($_SESSION['username'])) redirect('login.php');
    return $_SESSION['username'];
}

// ---- Settings (was SettingsFrame prefs) ----
function get_settings(string $username): array {
    $st = db()->prepare('SELECT * FROM user_settings WHERE username = ?');
    $st->execute([$username]);
    $row = $st->fetch();
    if (!$row) {
        db()->prepare('INSERT IGNORE INTO user_settings (username) VALUES (?)')->execute([$username]);
        $st->execute([$username]);
        $row = $st->fetch();
    }
    return $row;
}

function theme_css(string $name): string {
    $t = THEMES[$name] ?? THEMES['Midnight Purple'];
    $keys = ['bg', 'head-a', 'head-b', 'accent', 'tab', 'tab-border', 'panel'];
    $css = '';
    foreach ($keys as $i => $k) $css .= "--$k:{$t[$i]};";
    return ':root{' . $css . '}';
}

function plan_label(string $plan): string {
    return ['monthly' => 'Premium Monthly', 'yearly' => 'Premium Yearly'][$plan] ?? 'Free';
}

// ---- Profile pictures (was ~/.toonaflix/avatars) ----
function avatar_path(string $username): string {
    return __DIR__ . '/uploads/avatars/' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $username) . '.png';
}
function avatar_url(string $username): ?string {
    $p = avatar_path($username);
    return is_file($p) ? 'uploads/avatars/' . basename($p) . '?v=' . filemtime($p) : null;
}
/** Center-crops to a square, scales to 256px, saves as PNG. Returns an error string or null. */
function save_avatar(string $username, string $tmpFile): ?string {
    if (!function_exists('imagecreatefromstring')) return 'PHP GD extension is not enabled (php.ini: extension=gd).';
    if (filesize($tmpFile) > 5 * 1024 * 1024) return 'Image is too large (max 5 MB).';
    $info = @getimagesize($tmpFile);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_BMP, IMAGETYPE_WEBP], true)) {
        return 'That file is not a valid image.';
    }
    $src = @imagecreatefromstring((string)file_get_contents($tmpFile));
    if (!$src) return 'Could not read that image.';
    $w = imagesx($src); $h = imagesy($src); $side = min($w, $h);
    $out = imagecreatetruecolor(256, 256);
    imagealphablending($out, false); imagesavealpha($out, true);
    imagecopyresampled($out, $src, 0, 0, intdiv($w - $side, 2), intdiv($h - $side, 2), 256, 256, $side, $side);
    $path = avatar_path($username);
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0775, true);
    imagepng($out, $path);
    return null;
}
