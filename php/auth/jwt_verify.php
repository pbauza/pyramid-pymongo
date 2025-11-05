<?php
declare(strict_types=1);

// Ruta a la clave pública (la pareja del jwt-private.pem)
$pubkey_path = '/etc/keys/jwt-public.pem';
$pubkey = openssl_pkey_get_public('file://' . $pubkey_path);
if ($pubkey === false) {
    http_response_code(500);
    exit('Public key not found');
}

// Leer cookie
if (empty($_COOKIE['session_jwt'])) {
    http_response_code(401);
    exit;
}

list($header64, $payload64, $sig64) = explode('.', $_COOKIE['session_jwt']);
$header  = json_decode(base64_decode(strtr($header64, '-_', '+/')), true);
$payload = json_decode(base64_decode(strtr($payload64, '-_', '+/')), true);
$sig     = base64_decode(strtr($sig64, '-_', '+/'));

$verified = openssl_verify("$header64.$payload64", $sig, $pubkey, OPENSSL_ALGO_SHA256);
openssl_free_key($pubkey);

if ($verified !== 1) {
    http_response_code(401);
    exit;
}

// Ver expiración
if (time() > ($payload['exp'] ?? 0)) {
    http_response_code(401);
    exit;
}

// OK: devolvemos el NIU
header('NIU: ' . $payload['sub']);
http_response_code(200);
exit;
