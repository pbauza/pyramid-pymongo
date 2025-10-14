<?php
// Turn on errors for debugging (remove in production)
ini_set('display_errors', 'On');
error_reporting(E_ALL);

// Include your CAS connection (this should set $_SESSION['niu'] or similar)
require_once './init_cas_connect.php';

// Grab the NIU from session or CAS attributes
session_start();
if (isset($_SESSION['niu'])) {
    $niu = $_SESSION['niu'];
} elseif (isset($_SERVER['REMOTE_USER'])) {
    $niu = $_SERVER['REMOTE_USER']; // depends on how init_cas_connect.php works
} else {
    http_response_code(401);
    echo "Authentication failed (no NIU)";
    exit;
}

// Option A: Send NIU as a JWT cookie
require_once 'vendor/autoload.php'; // firebase/php-jwt
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$privateKey = file_get_contents('/etc/keys/jwt-private.pem');
$now = time();
$payload = [
    'sub' => $niu,
    'iat' => $now,
    'exp' => $now + 3600,
    'iss' => 'php-auth',
    'aud' => 'pyramid'
];

$jwt = JWT::encode($payload, $privateKey, 'RS256');
setcookie('session_jwt', $jwt, [
    'expires' => $now + 3600,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Redirect to Pyramid backend
header("Location: /app/");
exit;
