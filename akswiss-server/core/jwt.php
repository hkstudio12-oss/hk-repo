<?php
/** Minimal JWT (HS256) — no external dependencies, works on shared hosting. */

function b64url_encode($data) {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function b64url_decode($data) {
  $pad = strlen($data) % 4;
  if ($pad) $data .= str_repeat('=', 4 - $pad);
  return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_sign($payload) {
  $header = ['alg' => 'HS256', 'typ' => 'JWT'];
  $payload['iat'] = time();
  $payload['exp'] = time() + (JWT_TTL_DAYS * 86400);
  $h = b64url_encode(json_encode($header));
  $p = b64url_encode(json_encode($payload));
  $sig = hash_hmac('sha256', $h . '.' . $p, JWT_SECRET, true);
  return $h . '.' . $p . '.' . b64url_encode($sig);
}

/** Returns the payload array on success, or null if invalid/expired. */
function jwt_verify($token) {
  $parts = explode('.', $token);
  if (count($parts) !== 3) return null;
  list($h, $p, $s) = $parts;
  $expected = b64url_encode(hash_hmac('sha256', $h . '.' . $p, JWT_SECRET, true));
  if (!hash_equals($expected, $s)) return null;
  $payload = json_decode(b64url_decode($p), true);
  if (!is_array($payload)) return null;
  if (isset($payload['exp']) && time() >= $payload['exp']) return null;
  return $payload;
}
