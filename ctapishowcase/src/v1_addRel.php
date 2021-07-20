<?php

/**
 * this script illustrats how to add relationshiops
 */

/**
 * @param $ctdomain
 * @param $id
 * @param $child_id
 * @param $rel_id
 * @return array
 */
function addRel($ctdomain, $id, $child_id, $rel_id)
{
    $report1 = [
        'url' => $ctdomain . '/?q=churchdb/ajax',
        'method' => "POST",
        'data' => [
            'func' => 'add_rel',
            'id' => $id,
            'child_id' => $child_id,  //       id     -> child_id   id         -> child_id
            'rel_id' => $rel_id       // 1 für Eltern -> Kind 2 für Ehepartner -> Ehepartner
        ],
        'response' => "???"
    ];

    $report1['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report1['url'], $report1['data']);
    return $report1;
}

function delRel($ctdomain, $id, $rel_id){
    $report1 = [
        'url' => $ctdomain . '/?q=churchdb/ajax',
        'method' => "POST",
        'data' => [
            'func' => 'del_rel',
            'id' => $id,
            'rel_id' => $rel_id
        ],
        'response' => "???"
    ];

    $report1['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report1['url'], $report1['data']);
    return $report1;
}


$report = [];

//return;


//$report[] = delrel($ctdomain, "1265", 917 );


$report[] = addRel($ctdomain, "1265", "1268", "1");
$report[] = addRel($ctdomain, "1265", "1271", "1");
$report[] = addRel($ctdomain, "1265", "1262", "2");
$report[] = addRel($ctdomain, "1262", "1265", "2");


$report0 = [
    'url' => $ctdomain . '/?q=churchdb/ajax',
    'method' => "POST",
    'data' => ['func' => 'getAllPersonData'],
    'response' => "???"
];
$report['persons'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report0['url'], $report0['data']);
