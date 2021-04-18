<?php


$report = [
    'url' => $ctdomain . '/?q=churchdb/ajax' ,
    'method' => "POST",
    'data' => ['func'=>'getPersonGroupRelation', 'data'=> json_encode(['g_id' => 63, 'gp_id'=>812])],
    'response' => "???"
];



$report['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report['url'], $report['data']);
