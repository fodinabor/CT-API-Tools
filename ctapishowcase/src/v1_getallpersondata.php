<?php


$report = [
    'url' => $ctdomain . '/?q=churchdb/ajax' ,
    'method' => "POST",
    'data' => ['func'=>'getAllPersonData'],
    'response' => "???"
];



$report['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report['url'], $report['data']);
