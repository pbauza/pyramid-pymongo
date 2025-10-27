<?php

// Load the settings from the central config file
require_once __DIR__.'/config.php';

// Load the CAS lib
require_once __DIR__.'/CAS/CAS.php';

// Enable debugging
phpCAS::setDebug();
// Enable verbose error messages. Disable in production!
phpCAS::setVerbose(true);

// Initialize phpCAS
phpCAS::client(CAS_VERSION_3_0, $cas_host, $cas_port, $cas_context);

// For production use set the CA certificate that is the issuer of the cert
// on the CAS server and uncomment the line below
//phpCAS::setCasServerCACert($cas_server_ca_cert_path);

// For quick testing you can disable SSL validation of the CAS server.
// THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
// VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!
phpCAS::setNoCasServerValidation();

// customize HTML output
phpCAS::setHTMLHeader(
    '<html>
  <head>
    <title>__TITLE__</title>
  </head>
  <body>
  <h1>__TITLE__</h1>'
);
phpCAS::setHTMLFooter(
    '<hr>
    <address>
      phpCAS __PHPCAS_VERSION__,
      CAS __CAS_VERSION__ (__SERVER_BASE_URL__)
    </address>
  </body>
</html>'
);

// force CAS authentication
phpCAS::forceAuthentication();

if (isset($_REQUEST['logout'])) {
    phpCAS::logout();
}

$attr=phpCAS::getAttributes();
#$user_niu=phpCAS::getUser();
$user_niu = substr(phpCAS::getUser(), 0, strrpos(phpCAS::getUser(), '@'));
$user_admin_unit = 0;
$user_admin_db = 0;
$user_modif = 0;
$user_validacio = 0;
$user_unit = '';


#$user_niu = $attr['uid']

// at this step, the user has been authenticated by the CAS server
// and the user's login name can be read with phpCAS::getUser().

// for this test, simply print that the authentication was successfull

// --- Build and set a JWT cookie for Pyramid, then redirect to /app ---
function base64url_encode(string $data): string {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$niu = $user_niu;       // we already trimmed @domain above
$now = time();

$payload = [
  'iss' => 'neas.uab.cat',
  'aud' => 'pyramid-app',   // must match Pyramid if you verify 'aud'
  'iat' => $now,
  'nbf' => $now,
  'exp' => $now + 3600,
  'sub' => $niu,            // << NIU here
];

$header = ['alg' => 'RS256', 'typ' => 'JWT'];

$jwt_header  = base64url_encode(json_encode($header, JSON_UNESCAPED_SLASHES));
$jwt_payload = base64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
$signing_input = $jwt_header . '.' . $jwt_payload;

// Private key mounted at /etc/keys in the container
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

// Send cookie (secure, httpOnly, sameSite=Lax) and go to /app/
setcookie('session_jwt', $jwt, [
  'expires'  => $now + 3600,
  'path'     => '/',
  'secure'   => true,
  'httponly' => true,
  'samesite' => 'Lax',
]);

header('Location: /app/', true, 302);
exit;



?>
