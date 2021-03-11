<?php

/*
 *
 * churchdb:view, churchdb:view alldata(-1), churchdb:view memberliste, churchdb:security level person(-1)
 *
 *
 * "Keine ausreichende Berechtigung. Das Recht 'edit relations (\"Verwaltung\")' ist notwendig."
 * "Keine ausreichende Berechtigung. Das Recht 'view person (\"Verwaltung\")' ist notwendig.",
 */

$url = "$ctdomain/api/persons";
$data = ['page' => 1, 'limit' => 500];
$data_json = <<< EOT
{
  "p1": {
    "firstName": "zz_vater",
    "lastName": "testfamilie",
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
  },
  "p2": {
    "firstName": "zz_mutter",
    "lastName": "testfamilie",
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
  },
  "p3": {
    "firstName": "zz_kind-1",
    "lastName": "testfamilie",
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
  },

  "p4": {
    "firstName": "zz_kind-1",
    "lastName": "testfamilie",
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
}
EOT;

$report = [
    'url' => "$ctdomain/api/persons" ,
    'method' => "POST",
    'data' => ['id' => 77],
    'body' => json_decode($data_json),
];

$result = [];

//$result['p1'] = CTV2_sendRequest("POST", $report['url'], [], json_decode($data_json, true)['p1']);
//$result['p2'] = CTV2_sendRequest("POST", $report['url'], [], json_decode($data_json, true)['p2']);
//$result['p2'] = CTV2_sendRequest("POST", $report['url'], [], json_decode($data_json)['p2']);
//$result['p2'] = CTV2_sendRequest("POST", $report['url'], [], json_decode($data_json)['p2']);


$url = $ajax_domain . 'churchdb/ajax';
$data = array(
    'func' => 'add_rel',
    'parent_id' =>  147, //$result['p1']['data']['id'],
    'child_id' => 150, //$result['p2']['data']['id'],
    'relation_id' => 2
);
$result['p1-p2'] = ['data'=> $data, 'result'=>CT_sendRequest($ajax_domain, $url, $data)];

$report['result'] = $result;