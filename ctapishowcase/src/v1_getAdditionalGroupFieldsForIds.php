<?php


// collec the results
$report = [];

// retrieve all groups
$result = [
    'url' => "$ctdomain" . "/api/groups",
    'method' => "GET",
    'data' => [
        'page' => 1,
        'limit' => 50,
        'is_archived' => false,
        //'lastpage' => 2
    ]
];

$result['response'] = CT_APITOOLS\CTV2_sendRequestWithPagination($result);

$report['/api/groups'] = $result['response'];


// get groupname => key
$groups = [];
foreach ($result['response']['data'] as $entry) {
    $groups[$entry['name']] = $entry['id'];
}
$result['groups'] = $groups;
$report['groupname => key'] = $result;

// now get groupfields such that we have
//
// ['group' => ['field' => [...]]]

$groupfields = [];
foreach ($groups as $groupname => $groupid) {
    $result = [
        'url' => $ctdomain . "/api/groups/$groupid/memberfields",
        'method' => "GET",
        'response' => "???"
    ];

    $responsedata = CT_APITOOLS\CTV2_sendRequest($result);
    $thefields = [];
    foreach ($responsedata['data'] as $field) {
        if ($field['type'] == 'group') {
            $thefields[$field['field']['fieldName']] = [
                'id' => $field['field']['id'],
                'groupId' => $field['field']['groupId']
            ];
        }
    }
    // $groupfields[$groupname] = $responsedata;
    if (count($thefields) > 0){
        $groupfields[$groupname] = $thefields;
    }
}

$report['groupfields lookup'] = ['groupfields' => $groupfields];

// curl -X GET "https://arpke.church.tools/api/groups/178/memberfields" -H "accept: application/json"

$result = [
    'url' => $ctdomain . '/?q=churchdb/ajax',
    'method' => "POST",
    'data' => ['func' => 'getAdditionalGroupFieldsForIds', 'ids' => array_values($groups)],
    'response' => "???"
];

$result['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $result['url'], $result['data']);

$report['getAdditionalGroupFieldsForIds'] = $result;