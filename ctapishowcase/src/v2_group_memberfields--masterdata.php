<?php

namespace CT_APITOOLS;

// reading all groups


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
function extracted($groups, $ctdomain): array
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

$report1 = [
    'url' => "$ctdomain/api/groups",
    'method' => "GET",
    'data' => ['page' => 1],
    'body' => []
];

$report1['response'] = CTV2_sendRequestWithPagination($report1);

// just create a sample for "$ctdomain/api/groups/$group_id/memberfields"

$groups = create_JSONPath($report1);
$path = "$.response.data..*[?(@.name == '$groupnamex')].id";
$group_id = find_one_in_JSONPath($groups, $path);

$report2 = [
    'url' => "$ctdomain/api/groups/$group_id/memberfields",
    'method' => "GET",
    'data' => [],
    'body' => []
];

$report3['response'] = CTV2_sendRequest($report3);

// creating index for groupfields
// collect from all groups
$groups = $report1['response']['data'];
$allgroupfields = extracted($groups, $ctdomain);

// finalize the result

$report = [
    'report 1' => $report1,
    'report 2' => $report3,
    'groupfields' => $allgroupfields
];
