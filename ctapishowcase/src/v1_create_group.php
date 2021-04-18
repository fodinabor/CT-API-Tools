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

$groups = <<< EOT
{
    
    "MG Davip Sonst": {
        "Davip Vornamen": "textfeld",
        "Davip Pfarrbezirksnr": "textfeld",
        "Davip Briefanrede": "textfeld",
        "Davip Kirchengemeinde": "textfeld",
        "Davip Wohnungsstatus": "textfeld",
        "Davip Sperr allgemein": "textfeld"
    },
    "MG Davip Haushalt": {
        "Davip HH stif": "textfeld",
        "Davip HH Ordn": "textfeld"
    },
    "MG Davip Trauung": {
        "DaviP Trauung Datum": "datum",
        "Davip Trauung Ort": "textfeld",
        "Davip Trauung Kirche": "textfeld",
        "Davip Trauung Gemeinde": "textfeld",
        "Davip Trauung Konfess bet.": "auswahl",
        "Davip Traunug_Bibelstelle": "textfeld",
        "Davip Trauung Konfess": "auswahl",
        "Davip Trauung Bibelstelle-1": "textfeld"
    },
    "MG Davip Konfirm": {
        "Davip Konfess": "auswahl",
        "Davip Konfi Bibelstelle": "textfeld",
        "Davip Konfi Gemeinde": "textfeld",
        "Davip Konfi Konfess": "auswahl",
        "Davip Konfi Kirche": "textfeld",
        "Davip Konfi Datum": "datum",
        "Davip Konfi Ort": "textfeld",
        "Davip Erstkomm Bibelstelle": "textfeld",
        "Davip Konfess bish": "auswahl"
    },
    "MG Davip_zielgruppen": {
        "Davip zielgruppen": "mehrfachauswahl"
    },
    "MG Davip Taufe": {
        "Davip Taufe Datum": "datum",
        "Davip Taufe Konfess": "auswahl",
        "Davip Taufe Ort": "textfeld",
        "Davip Taufe Gemeinde": "textfeld",
        "Davip Taufe Kirche": "textfeld",
        "Davip Taufe Segnung Bibelstelle": "textfeld"
    },
    "MG Davip Bestattung": {
        "Davip Bestattung Ort": "textfeld",
        "Davip Bestattung Datum": "textfeld"
    }
}
EOT;

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
function create_field(string $ctdomain, $group_id, string $fieldname): array
{
    $report1 = [
        'url' => $ctdomain . '/?q=churchdb/ajax',
        'method' => "POST",
        'data' => [
            'func' => 'editAdditionalGroupField',
            'type' => 'custom',
            'fieldname' => $fieldname,
            'defaultvalue' => "default",
            'feldtyp_id' => 1,  // todo
            'sortkey' => 0,
            'useinregistrationform_yn' => 0,
            'securitylevel_id' => 1,
            'gruppe_id' => $group_id,
            'mandatory_yn' => 0
        ],
        'response' => "???"
    ];
    $report1['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report1['url'], $report1['data']);
    return $report1;
}

$requested_groups = json_decode($groups);

// now generate the groups
foreach ($requested_groups as $groupname => $groupfields) {
    $result = create_group($ctdomain, $groupname, 71);
    $group_id = $result['response']['data']['id'];
    $report[] = $result;

    foreach ($groupfields as $fieldname => $fielddef) {
        $report[] = create_field( $ctdomain, $group_id,  $fieldname);
    }
}

