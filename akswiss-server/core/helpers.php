<?php
/** Shared helpers for the JSON API. */

/** Send a success JSON envelope the app understands: { code:0, data } */
function ok($data = null) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['code' => 0, 'message' => 'ok', 'data' => $data]);
  exit;
}

/** Send an error envelope with an HTTP status. 401 makes the app sign out. */
function fail($message, $http = 400) {
  http_response_code($http);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['code' => 1, 'message' => $message]);
  exit;
}

/** Parsed JSON request body as an array. */
function body() {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function field($arr, $key, $default = null) {
  return isset($arr[$key]) ? $arr[$key] : $default;
}

/** Read the Authorization: Bearer <jwt> header across server setups. */
function bearer_token() {
  $headers = [];
  if (function_exists('getallheaders')) $headers = getallheaders();
  $auth = '';
  foreach ($headers as $k => $v) {
    if (strtolower($k) === 'authorization') { $auth = $v; break; }
  }
  if (!$auth && isset($_SERVER['HTTP_AUTHORIZATION'])) $auth = $_SERVER['HTTP_AUTHORIZATION'];
  if (!$auth && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
  if (preg_match('/Bearer\s+(.+)/i', $auth, $m)) return trim($m[1]);
  return '';
}

/** Require a valid token; returns the user id or sends 401. */
function require_user() {
  $token = bearer_token();
  if (!$token) fail('Please sign in', 401);
  $payload = jwt_verify($token);
  if (!$payload || empty($payload['uid'])) fail('Please sign in again', 401);
  return (int) $payload['uid'];
}

function valid_email($e) {
  return filter_var($e, FILTER_VALIDATE_EMAIL) !== false;
}

/** Send a verification code by email. Returns true on success. */
function send_code_email($to, $code) {
  $subject = 'Your AK SWISS verification code';
  $body = "Your verification code is: $code\n\nIt expires in 10 minutes.\nIf you didn't request this, you can ignore this email.";
  $headers = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . ">\r\n";
  $headers .= 'Reply-To: ' . MAIL_FROM . "\r\n";
  $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
  return @mail($to, $subject, $body, $headers);
}

function gen_code() {
  return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}
