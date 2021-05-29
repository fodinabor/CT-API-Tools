<?php


$report = [
   // 'url' => $ctdomain . '/?q=churchauth/ajax' ,
    'url' => $ctdomain . '/?q=churchauth/ajax' ,

    'method' => "POST",
    'data' => ['func'=>'getMasterData'],
    //'data' => ['func'=>'getAuth'],
    'response' => "???"
];


$report['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report['url'], $report['data']);
