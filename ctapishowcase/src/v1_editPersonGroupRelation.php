<?php


$report = [
    'url' => $ctdomain . '/?q=churchdb/ajax',
    'method' => "POST",
    'data' => [
        'func' => 'addPersonGroupRelation',
        'comment' => "das ist importiert,",
        'custom44' => 1,
        'custom38' => 'das ist das textfeld importiert',
        'custom41' => 'o1',
        'custom35' => '10,11,test13',
        'id' => 815,
        'g_id' => 63,
        'groupmemberstatus_id'=> 8
    ]
];


$report['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report['url'], $report['data']);
