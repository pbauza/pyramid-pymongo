<?php
declare(strict_types=1);
session_start();

// ---- Load config + CAS ----
require_once __DIR__ . '/config.php';
require_once $phpcas_path . '/CAS.php';

// Service URL base (no trailing slash)
$client_service_name = $client_service_name ?? 'https://neas.uab.cat:8443';

// Initialize phpCAS client (this phpCAS needs service base url)
phpCAS::client(
    CAS_VERSION_3_0,
    $cas_host,
    (int)$cas_port,
    $cas_context,
    $client_service_name,
    true // changeSessionID
);

// IMPORTANT: fix the service URL to the root (or to /auth/ if you prefer)
phpCAS::setFixedServiceURL($client_service_name . '/');

// In production: validate CAS server certificate
// phpCAS::setCasServerCACert('/etc/ssl/certs/geant-ca.pem');
phpCAS::setNoCasServerValidation(); // dev only

// Support CAS single logout if you use it
phpCAS::handleLogoutRequests();

// Force authentication (redirects to CAS if needed)
phpCAS::forceAuthentication();

// Compute NIU (strip domain if present)
$user = phpCAS::getUser();
$niu  = strpos($user, '@') !== false ? substr($user, 0, strrpos($user, '@')) : $user;

// --- Build JWT (RS256) ---
function b64url(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }

$now = time();
$payload = [
  'iss' => 'neas.uab.cat',
  'aud' => 'pyramid-app',   // must match Pyramid audience if you verify it
  'iat' => $now,
  'nbf' => $now,
  'exp' => $now + 3600,
  'sub' => $niu,
];
$header = ['alg' => 'RS256', 'typ' => 'JWT'];

$jwt_header  = b64url(json_encode($header, JSON_UNESCAPED_SLASHES));
$jwt_payload = b64url(json_encode($payload, JSON_UNESCAPED_SLASHES));
$sign_input  = $jwt_header . '.' . $jwt_payload;

$pk_path = '/etc/keys/jwt-private.pem';               // mounted in the container
$pk      = openssl_pkey_get_private('file://' . $pk_path);
if ($pk === false) { http_response_code(500); exit('Private key not found'); }

if (!openssl_sign($sign_input, $sig, $pk, OPENSSL_ALGO_SHA256)) {
  http_response_code(500); exit('Cannot sign JWT');
}
openssl_free_key($pk);

$jwt = $sign_input . '.' . b64url($sig);

// --- Set cookie and bounce to /app ---
setcookie('session_jwt', $jwt, [
  'expires'  => $now + 3600,
  'path'     => '/',
  'secure'   => true,
  'httponly' => true,
  'samesite' => 'Lax',
]);

header('Location: /app/', true, 302);
exit;