<?php


$report = [
    'url' => $ajax_domain . 'churchdb/ajax' ,
    'method' => "POST",
    'data' => ['func'=>'getMasterData'],
    'response' => "???"
];



$report['response'] = CT_APITOOLS\CTV1_sendRequest($ajax_domain, $report['url'], $report['data']);
