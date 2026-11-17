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
  // Prefer authenticated SMTP (reliable delivery to Gmail); fall back to mail().
  if (defined('SMTP_HOST') && SMTP_HOST) {
    $ok = smtp_send($to, $subject, $body);
    if ($ok) return true;
  }
  $headers = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . ">\r\n";
  $headers .= 'Reply-To: ' . MAIL_FROM . "\r\n";
  $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
  return @mail($to, $subject, $body, $headers);
}

/** Minimal dependency-free SMTP sender (AUTH LOGIN). Returns true on success. */
function smtp_send($to, $subject, $body) {
  $host = SMTP_HOST;
  $port = defined('SMTP_PORT') ? SMTP_PORT : 465;
  $secure = defined('SMTP_SECURE') ? SMTP_SECURE : 'ssl';
  $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
  $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
  $fp = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
  if (!$fp) return false;
  $helo = substr(strrchr(MAIL_FROM, '@'), 1) ?: 'localhost';
  $read = function () use ($fp) {
    $data = '';
    while ($line = fgets($fp, 515)) { $data .= $line; if (isset($line[3]) && $line[3] === ' ') break; }
    return $data;
  };
  $cmd = function ($c) use ($fp) { fwrite($fp, $c . "\r\n"); };
  $read();
  $cmd('EHLO ' . $helo); $read();
  if ($secure === 'tls') {
    $cmd('STARTTLS'); $read();
    stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
    $cmd('EHLO ' . $helo); $read();
  }
  $cmd('AUTH LOGIN'); $read();
  $cmd(base64_encode(SMTP_USER)); $read();
  $cmd(base64_encode(SMTP_PASS)); $r = $read();
  if (strpos($r, '235') === false) { fclose($fp); return false; }
  $cmd('MAIL FROM:<' . MAIL_FROM . '>'); $read();
  $cmd('RCPT TO:<' . $to . '>'); $read();
  $cmd('DATA'); $read();
  $msg = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . ">\r\n";
  $msg .= 'To: <' . $to . ">\r\n";
  $msg .= 'Subject: ' . $subject . "\r\n";
  $msg .= "MIME-Version: 1.0\r\n";
  $msg .= "Content-Type: text/plain; charset=utf-8\r\n\r\n";
  $msg .= str_replace("\r\n.", "\r\n..", $body) . "\r\n.\r\n";
  fwrite($fp, $msg);
  $r = $read();
  $cmd('QUIT'); fclose($fp);
  return strpos($r, '250') !== false;
}

function gen_code() {
  return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}
