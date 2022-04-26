<?php


$report = [
    'url' => $ctdomain . '/?q=churchdb/ajax',
    'method' => "POST",
    'data' => [
        'func' => 'editPersonGroupRelation',
        'custom4594' => '01,02',
        'id' => 132,
        'g_id' => 622,
        'groupmemberstatus_id'=> 8
    ]
];


$report['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report['url'], $report['data']);
