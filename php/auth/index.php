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

// ---- Authenticated here ----
$niu = phpCAS::getUser();
$_SESSION['niu'] = $niu;

// ---- Build and set a JWT cookie (RS256) ----
function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$now = time();
$payload = [
    'iss' => 'neas.uab.cat',       // issuer
    'aud' => 'pyramid-app',        // audience (optional, but nice to have)
    'iat' => $now,
    'nbf' => $now,
    'exp' => $now + 3600,          // 1 hour
    'sub' => $niu,                 // << the NIU goes here
];

$header = ['alg' => 'RS256', 'typ' => 'JWT'];

$jwt_header  = base64url_encode(json_encode($header, JSON_UNESCAPED_SLASHES));
$jwt_payload = base64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
$signing_input = $jwt_header . '.' . $jwt_payload;

// Load private key (mounted read-only in the container)
$private_key_path = '/etc/keys/jwt-private.pem';
$private_key = openssl_pkey_get_private('file://' . $private_key_path);
if ($private_key === false) {
    http_response_code(500);
    echo "Cannot load private key.";
    exit;
}

$signature = '';
if (!openssl_sign($signing_input, $signature, $private_key, OPENSSL_ALGO_SHA256)) {
    http_response_code(500);
    echo "Cannot sign JWT.";
    exit;
}
openssl_free_key($private_key);

$jwt = $signing_input . '.' . base64url_encode($signature);

// Send cookie (secure, httpOnly, sameSite=Lax)
setcookie('session_jwt', $jwt, [
    'expires'  => $now + 3600,
    'path'     => '/',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);

// ---- Redirect to Pyramid app ----
header('Location: /app/', true, 302);
exit;
