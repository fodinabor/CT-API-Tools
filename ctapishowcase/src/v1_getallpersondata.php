<?php


$report = [
    'url' => $ajax_domain . 'churchdb/ajax' ,
    'method' => "POST",
    'data' => ['func'=>'getAllPersonData'],
    'response' => "???"
];



$report['response'] = CTV1_sendRequest($ajax_domain, $report['url'], $report['data']);
