<?php
// /var/www/auth/nginx_auth.php

// 1) Asegura sesión PHP (misma save_path que tu index.php)
session_name('PHPSESSID');
session_start();

// 2) Aquí debes adaptar CÓMO guardas el usuario tras CAS.
//    Ejemplos comunes (cambia a tus claves reales):
$logged_in = isset($_SESSION['cas_user']) || isset($_SESSION['user']);
$niu       = $_SESSION['niu']           ?? ($_SESSION['user']['niu'] ?? null);
$uid       = $_SESSION['cas_user']      ?? ($_SESSION['user']['uid'] ?? null);
$email     = $_SESSION['email']         ?? ($_SESSION['user']['mail'] ?? null);

// 3) Si no hay sesión válida, 401 para que Nginx no deje pasar a /app
if (!$logged_in || empty($niu)) {
    http_response_code(401);
    header('Cache-Control: no-store');
    exit;
}

// 4) Emitimos cabeceras que Nginx recogerá con $upstream_http_*
//    (nombres en minúsculas al capturar: x-remote-user, x-ctao-niu, etc.)
header('X-Remote-User: ' . $uid);
header('X-CTAO-NIU: ' . $niu);
if (!empty($email)) {
    header('X-Remote-Email: ' . $email);
}

// Importante: 200 OK y sin cuerpo
http_response_code(200);
header('Content-Length: 0');
exit;
