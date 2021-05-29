<?php
// this demonstrates how to import persons from a json file
// the json-files matches the schema of V2 /peersons/id

use function CT_APITOOLS\create_JSONPath;
use function CT_APITOOLS\CTV2_sendRequest;
use function CT_APITOOLS\CTV2_sendRequestWithPagination;
use function CT_APITOOLS\find_one_in_JSONPath;

/**
 * @param string $url
 * @param $persondata
 * @param array $defaults
 * @return array
 */
function create_person(string $url, $persondata, array $defaults): array
{
    $report1 = [
        'url' => $url,
        'method' => "POST",
        'data' => null,
        'body' => [
            'firstName' => $persondata['firstName'],
            'lastName' => $persondata['lastName'],
            'departmentIds' => $defaults['departmentIds'],
            'statusId' => $defaults['statusId'],
            'privacyPolicyAgreement' => $defaults['privacyPolicyAgreement'],
            'campusId' => $defaults['campusId']
        ]
    ];

    $report1['response'] = CT_APITOOLS\CTV2_sendRequest($report1);
    $person_id = $report1['response']['data']['id'];
    return array($report1, $person_id);
}

/**
 * @param string $url
 * @param $person_id
 * @param $persondata
 * @return array
 */
function update_person(string $url, $person_id, $persondata): array
{
// update person record

    $report2 = [
        'url' => "$url/$person_id",
        'method' => "PATCH",
        'data' => null,
        'body' => $persondata
    ];

    $report2['response'] = CT_APITOOLS\CTV2_sendRequest($report2);
    return $report2;
}


/**
 *
 * retu9rn an index of all groupfields steming
 * from a list of groups
 *
 * todo maybe get more information if required
 *
 * @param $groups   result of "$ctdomain/api/groups"['data']
 * @param $ctdomain the ctdomain
 * @return array [groupname/fieldname => ['grouppId' => .., 'fieldId' => .. ]]
 */
function extractgroupfieldindex($groups, $ctdomain): array
{
    $allgroupfields = [];
    foreach ($groups as $group) {
        $groupId = $group['id'];
        $groupname = $group['name'];
        $report2 = [
            'url' => "$ctdomain/api/groups/$groupId/memberfields",
            'method' => "GET",
            'body' => []
        ];
        $report2['response'] = CTV2_sendRequest($report2);
        // collect fields of grup
        foreach ($report2['response']['data'] as $fieldentry) {
            if ($fieldentry['type'] == 'person') {
                continue;
            }
            $field = $fieldentry['field'];
            $fullfieldname = "$groupname/${field['fieldName']}";
            $allgroupfields[$fullfieldname] = [
                'groupId' => $groupId,
                'fieldId' => $field['id']
            ];
        }
    }
    return $allgroupfields;
}

/**
 *
 * retrieve all groupfields from ct
 *
 * @param string $ctdomain
 * @return array
 */
function getallgroupfields(string $ctdomain): array
{
    $report3 = [
        'url' => "$ctdomain/api/groups",
        'method' => "GET",
        'data' => ['page' => 1],
        'body' => []
    ];

    $report3['response'] = CTV2_sendRequestWithPagination($report3);

// creating index for groupfields
// collect from all groups
    $groups = $report3['response']['data'];
    $allgroupfields = extractgroupfieldindex($groups, $ctdomain);
    return $allgroupfields;
}

/**
 * @param $persondata
 * @param string $url
 * @param array $defaults
 * @param array $groupfieldindex
 * @param string $ctdomain
 * @return array
 */
function processoneperson($persondata, string $url, array $defaults, array $groupfieldindex, string $ctdomain): array
{
    $personfields = $persondata[""];

// retrieve or create the person if the person is not yet in CT

    $person_id = null;
    $report1 = null;
    if ("update" == $persondata["VM ZZ_Importinfo"]["VM Davipimport Status"]) {
        $person_id = $persondata["VM ZZ_Importinfo"]["VM Davipimport Id"];
    } else {
        // create new person
        list($report1, $person_id) = create_person($url, $personfields, $defaults);
    }

    echo("\n processing person: $person_id");

    $report2 = update_person($url, $person_id, $personfields);

// update groupmembership and fields

    $report3 = [];
    foreach (array_keys($persondata) as $groupname) {
        echo("\n processing group: '$groupname'");
        if ($groupname == "") {
            continue;
        }

        $groupfields = [];
        $groupId = null;
        $comment = "";

        foreach ($persondata[$groupname] as $fieldname => $fieldvalue) {
            if ($fieldname == 'Bemerkungen') {
                $comment = join("\n", $fieldvalue);
                continue;
            }
            //echo ("\n processing field '$groupname/$fieldname'");
            $fieldindex = $groupfieldindex["$groupname/$fieldname"];
            $groupId = $fieldindex['groupId'];   // todo: this is not very elegant to get the group ID !!
            $groupfields[$fieldindex['fieldId']] = $fieldvalue;
        }

        echo("\n processing groupmembership for '$person_id': '$groupname'");
        $theurl = "$ctdomain/api/groups/{$groupId}/members/{$person_id}";

        $report5 = [
            'url' => $ctdomain . '/?q=churchdb/ajax',
            'method' => "POST",
            'data' => [
                'func' => 'addPersonGroupRelation',
                'comment' => $comment,
                'id' => $person_id,
                'g_id' => $groupId,
                'groupmemberstatus_id' => 0
            ]
        ];
        foreach ($groupfields as $fieldid => $fieldvalue) {
            $report5['data']["custom$fieldid"] = $fieldvalue;
        }
        $report5['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report5['url'], $report5['data']);
        if ('fail' == $report5['response']['status']) {
            $report5['data']['func'] = 'editPersonGroupRelation';
            $report5['data']['comment'] = $comment . "\nfelder aktualisiert";
            $report5['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report5['url'], $report5['data']);
        }
        $report3[$groupname] = $report5;
    }

    $result = ['1' => $report1, '2' => $report2, "3" => $report3, "groupfieldindex" => $groupfieldindex];
    return array($person_id, $result);
}


/*
 * the main part of th scrpt
 */


$defaults = [
    'departmentIds' => [1],
    'privacyPolicyAgreement' => [
        "date" => "2019-05-02",
        "typeId" => 1,
        "whoId" => 1
    ],
    'campusId' => 0,
    'statusId' => 0
];

$url = "$ctdomain/api/persons";

echo("\n reading groupfielddindex");
$groupfieldindex = getallgroupfields($ctdomain);


// read inputfile

echo("\n reading source data");
$sourcefile = __DIR__ . "/../inputs/02_person-import_source.json";  // todo argument!
$allpersons = json_decode(file_get_contents($sourcefile), true);

// todo loop over input
$persondata = $allpersons[0];

$report = [];
foreach ($allpersons as $persondata) {
    list($person_id, $result) = processoneperson($persondata, $url, $defaults, $groupfieldindex, $ctdomain);
    $report[$person_id] = $result;
}
