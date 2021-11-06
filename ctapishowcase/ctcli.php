<?php

/**
 */

namespace CT_APITOOLS;

use Cassandra\Exception\RangeException;

ini_set('memory_limit', "256M");

require 'vendor/autoload.php';

require_once __DIR__ . "/../ct_apitools--helper.inc.php";
require_once('CT-credentialstore.php');

/**
 * find the showcases to be processed
 */
if (count($argv) > 1) {
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

// if no ctiinstance is provided, we extract it from the ctdomain
if (!array_key_exists('ctinstance', CREDENTIALS)) {
    $x = preg_match("/https:\/\/([^\.]+)/", $ctdomain, $matches);
    $ctinstance = "{$matches[1]}_";
} else {
    $ctinstance = CREDENTIALS['ctinstance'];
}

$result = CT_loginAuth($ctdomain, $email, $password);

if (!$result['status'] == 'success') {
    var_dump($result);
    die("Showcase aborted / login failed");
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

    $showcasebase = basename($showcase, ".php");
    $outfolder = array_key_exists('outfolder', CREDENTIALS) ? CREDENTIALS['outfolder'] : __DIR__ . "/responses";

    if (!is_dir($outfolder)) {
        echo ("\ncreating $outfolder");
        mkdir($outfolder, 0777 ,true);
    }

    // note there is no separator between ctinstance and showcasebase
    // to support filename built of showcasebase only (without mentioning the ctinstance)
    $outfilebase = "$outfolder/{$ctinstance}$showcasebase";
    require_once($showcase);

    $myfile = fopen("$outfilebase.json", "w") or die("Unable to open file!");
    fwrite($myfile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fclose($myfile);
}
/**
 * logout
 */


CT_logout($ajax_domain);
echo("logged out\n");