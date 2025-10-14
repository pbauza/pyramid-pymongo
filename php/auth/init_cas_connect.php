<?php

require_once __DIR__.'/funcions.php';
// Load the settings from the central config file
require_once __DIR__.'/config.php';

// Load the CAS lib
require_once __DIR__.'/CAS/CAS.php';

// Enable debugging
phpCAS::setDebug();
// Enable verbose error messages. Disable in production!
phpCAS::setVerbose(true);

$serviceBaseUrl = 'https://neas.uab.cat:8443';

// Initialize phpCAS
phpCAS::client(CAS_VERSION_3_0, $cas_host, $cas_port, $cas_context, $serviceBaseUrl);

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


?>
