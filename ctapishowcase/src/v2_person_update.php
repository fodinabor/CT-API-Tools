<?php

/*
 *
 * churchdb:security level person(-1)
 * churchdb:view memberliste,
 * churchdb:view, churchdb:view alldata(-1),
 * churchdb:write access,
 * churchdb:create person,
 */

$url = "$ctdomain/api/persons/93";
$data = ['page' => 1, 'limit' => 500];
$data_json = <<< EOT
            {
                "firstName": "zz_ct-created-byx",
                "lastName": "__FILE__x",
                "departmentIds": [
                    1
                ],
                
                "privacyPolicyAgreement": {
                    "date": "2019-05-02",
                    "typeId": 1,
                    "whoId": 1
                },
                                
                "campusId": 0,
                "statusId": 0
           }
EOT;


$report = [
    'url' => $url ,
    'method' => "PATCH",
    'data' => [],
    'body' => json_decode($data_json),
];

//$report['response'] = CTV2_sendRequest($report['method'], $report['url'], $report['data'], $report['body'], true);
$report['response'] = CT_APITOOLS\CTV2_sendREquest($report);
;