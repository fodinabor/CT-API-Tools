<?php

/**
 */

require_once "../cthelper.inc";
require_once('CT-credentialstore.php');

/**
 * find the showcases to be processed
 */

if (count($argv) == 2){
    $showcases = glob("src/*{$argv[1]}*.php");
} else {
    $showcases = glob("src/*.php");
}


/**
 * login
 */

$ctdomain = CT_APITOOLS\CREDENTIALS['ctdomain'];
$active_domain = $ctdomain . "/?q=";
$email = CT_APITOOLS\CREDENTIALS['ctemail'];
$password = CT_APITOOLS\CREDENTIALS['ctpassword'];

$result = CT_loginAuth($active_domain, $email, $password);
if (!$result) {
    echo print_r($result, true);
    die("CT login not successful:");
}

/**
 * execute showcases
 */

foreach ($showcases as $showcase) {

    $url = "undefined";
    $response = "undefined";
    $data = [];
    $body = [];
    $report = [];
    echo "doing $showcase\n";
    require_once($showcase);

    $outfilebase = basename($showcase, ".php");

    $myfile = fopen("responses/$outfilebase.json", "w") or die("Unable to open file!");
    fwrite($myfile, json_encode($report, JSON_PRETTY_PRINT));
    fclose($myfile);
}
/**
 * logout
 */


CT_logout($active_domain);
echo("logged out");