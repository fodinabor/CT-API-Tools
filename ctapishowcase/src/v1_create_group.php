<?php
/**
 * func: createGroup
 * name: zz_test3
 * Inputf_grouptype: 4
 * Inputf_groupstatus: 1
 * Inputf_superiorgroup: 71
 * browsertabId: 301726627
 *
 *
 * func: editAdditionalGroupField
 * type: custom
 * fieldname: testfeld1
 * defaultvalue:
 * feldtyp_id: 1
 * securitylevel_id: 1
 * sortkey: 0
 * useinregistrationform_yn: 0
 * gruppe_id: 76
 * mandatory_yn: 0
 * browsertabId: 301726627
 **/


$report = [];

function create_group($ctdomain, $groupname, $parent_id)
{
    $report1 = [
        'url' => $ctdomain . '/?q=churchdb/ajax',
        'method' => "POST",
        'data' => [
            'func' => 'createGroup',
            'name' => $groupname,
            'Inputf_grouptype' => 4,  // ToDo achtung
            'Inputf_groupstatus' => 1,
            'Inputf_superiorgroup' => $parent_id
        ],
        'response' => "???"
    ];

    $report1['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report1['url'], $report1['data']);
    return $report1;
}


/**
 * @param string $ctdomain
 * @param $group_id
 * @param string $fieldname
 * @return array
 *
 * todo: more details ...
 *
 */
function create_field(string $ctdomain, $group_id, string $fieldname, array $fielddef): array
{
    $lut = [
        'text' => 1,
        'select' => 1,
        'date' => 1, // handle date as textfield
        'multiselect' => 7,
    ];

    $options = array_key_exists('options', $fielddef) ? $fielddef['options'] : "";

    $report1 = [
        'url' => $ctdomain . '/?q=churchdb/ajax',
        'method' => "POST",
        'data' => [
            'func' => 'editAdditionalGroupField',
            'type' => 'custom',
            'fieldname' => $fieldname,
            'defaultvalue' => "",
            'feldtyp_id' => $lut[$fielddef['type']],
            'options' => $options,   //wir stellen multiselct nun doch als Textfeld dar
            'sortkey' => 0,
            'useinregistrationform_yn' => 0,
            'securitylevel_id' => 4,  // todo
            'gruppe_id' => $group_id,
            'mandatory_yn' => 0
        ],
        'response' => "???"
    ];
    $report1['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report1['url'], $report1['data']);
    return $report1;
}


//************************

$groupfile = "inputs/01_mediator--real.json";
$parentgroupid = 217;
if (count($argv) >= 4) {
    $groupfile = $argv[2];
    $parentgroupid = $argv[3];
}

echo "\n crating groups from $groupfile under $parentgroupid";

$requested_groups = json_decode(file_get_contents($groupfile), JSON_OBJECT_AS_ARRAY);

// now generate the groups
foreach ($requested_groups as $groupname => $groupfields) {
    if ($groupname == "") {
        continue;
    }
    $result = create_group($ctdomain, $groupname, $parentgroupid); // 237
    $group_id = $result['response']['data']['id'];
    $report[] = $result;

    foreach ($groupfields as $fieldname => $fielddef) {
        $report[] = create_field($ctdomain, $group_id, $fieldname, $fielddef);
    }
}

