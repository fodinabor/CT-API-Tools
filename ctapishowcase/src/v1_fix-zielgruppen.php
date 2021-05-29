<?php

// this script illustrates how manipulate a custom field.
// you need to geht the group id and the filename from the masterfile

// collect the results
$report = [];

$group = 622;  // group_id
$customfield = 'custom4594'; // name of the custom field - change the field in the webapp and investigate the network activities
$groupmemberstatus=8;

$result1 = [
    'url' => $ctdomain . '/?q=churchdb/ajax',
    'method' => "POST",
    'data' => ['func' => 'getAdditionalGroupFields', 'g_id' => $group],
    'response' => "???"
];

$result1['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $result1['url'], $result1['data']);
$report['getAdditionalGroupFields'] = $result1;

foreach($result1['response']['data'][$customfield]['data'] as $person_id => $datavalue){
    $report2 = [
        'url' => $ctdomain . '/?q=churchdb/ajax',
        'method' => "POST",
        'data' => [
            'func' => 'editPersonGroupRelation',
             'custom4594' =>  join(",",explode(" ", $datavalue['value'])). ",08 , 09",
            'id' => $person_id,
            'g_id' => $group,
            'groupmemberstatus_id'=> $groupmemberstatus
        ]
    ];
    $report2['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report2['url'], $report2['data']);

    $report[] = $report2;
}
