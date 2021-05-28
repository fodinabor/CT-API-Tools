<?php


// collec the results
$report = [];

$result = [
    'url' => $ctdomain . '/?q=churchdb/ajax',
    'method' => "POST",
    'data' => [
        'func' => 'editAdditionalGroupField',
        'id' => 4537,
        'type' => 'custom',
        'fieldname' => 'VM Pfarrbezirksnr',
        'defaultvalue' => '1201520098',
        'feldtyp_id' => 2,
        'securitylevel_id' => '4',
        'sortkey' => 1,
        'useinregistrationform_yn' => 0,
        'gruppe_id' => 613,
        'mandatory_yn' => 0,
        'options' => '1201520098, 1201520090, 1201520100, 1201520101'
    ],
    'response' => "???"
];

$result['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $result['url'], $result['data']);

$report['getAdditionalGroupFieldsForIds'] = $result;