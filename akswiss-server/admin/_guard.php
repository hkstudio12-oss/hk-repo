<?php
/** Session guard + shared utilities for every admin page except login. */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';

function csrf_token() {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  return $_SESSION['csrf'];
}
function csrf_check() {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $t = isset($_POST['csrf']) ? $_POST['csrf'] : '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $t)) { http_response_code(400); exit('Bad request'); }
  }
}
function e($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['ak_admin'])) { header('Location: login.php'); exit; }
