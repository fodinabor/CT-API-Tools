<?php

/*
 *
 * churchdb:view, churchdb:view alldata(-1), churchdb:view memberliste, churchdb:security level person(-1)
 *
 */

$url = "$ctdomain/api/persons";
$data = ['page' => 1, 'limit' => 500];
$data_json = <<< EOT
            {
                "firstName": "zz_ct-created-byy",
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
    'method' => "POST",
    'data' => ['id' => 77],
    'body' => json_decode($data_json),
];

$report['response'] = CT_APITOOLS\CTV2_sendRequest($report['method'], $report['url'], $report['data'], $report['body'], true);
