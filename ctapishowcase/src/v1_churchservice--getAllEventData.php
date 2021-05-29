<?php


$report = [
    'url' => $ctdomain . '/?q=churchservice/ajax' ,
    'method' => "POST",
    'data' => ['func'=>'getAllEventData', 'limit' => 1],
    'response' => "???"
];



$report['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report['url'], $report['data']);
