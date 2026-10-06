<?php
// Records "opened this title" in history (was SettingsFrame.recordHistory)
require __DIR__ . '/config.php';
$user = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
csrf_check();
$cat = mb_substr(trim((string)($_POST['category'] ?? '')), 0, 20);
$title = mb_substr(trim((string)($_POST['title'] ?? '')), 0, 150);
if ($cat !== '' && $title !== '') {
    db()->prepare('INSERT INTO history (username, category, title) VALUES (?, ?, ?)')->execute([$user, $cat, $title]);
}
http_response_code(204);
