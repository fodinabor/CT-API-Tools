<?php

/**
 */

namespace CT_APITOOLS;
require 'vendor/autoload.php';

require_once "../ct_apitools--helper.inc.php";
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

$ctdomain = CREDENTIALS['ctdomain'];
$ajax_domain = $ctdomain . "/?q=";
$email = CREDENTIALS['ctusername'];
$password = CREDENTIALS['ctpassword'];

$result = CT_loginAuth($ctdomain, $email, $password);

if (!$result['status'] == 'success') {
    var_dump($result);
    die("Showcase aborted");
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
    fwrite($myfile, json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    fclose($myfile);
}
/**
 * logout
 */


CT_logout($ajax_domain);
echo("logged out");