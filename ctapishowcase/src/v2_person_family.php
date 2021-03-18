<?php

/*
 *
 * churchdb:view, churchdb:view alldata(-1), churchdb:view memberliste, churchdb:security level person(-1)
 *
 *
 * "Keine ausreichende Berechtigung. Das Recht 'edit relations (\"Verwaltung\")' ist notwendig."
 */

$url = "$ctdomain/api/persons";
$timestamp = date("h:i:sa");
$data = ['page' => 1, 'limit' => 500];
$data_json = <<< EOT
{
  "vater": {
    "firstName": "zz_vater",
    "lastName": "test $timestamp",
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
  "mutter": {
    "firstName": "zz_mutter",
    "lastName": "test $timestamp",
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
  "kind1": {
    "firstName": "zz_kind-1",
    "lastName": "test $timestamp",
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

  "kind2": {
    "firstName": "zz_kind-2",
    "lastName": "test $timestamp",
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
    'data' => json_decode($data_json),
];

$result = [];

$result['vater'] = CT_APITOOLS\CTV2_sendRequest("POST", $report['url'], [], json_decode($data_json, true)['vater']);
$result['mutter'] = CT_APITOOLS\CTV2_sendRequest("POST", $report['url'], [], json_decode($data_json, true)['mutter']);
$result['kind1'] = CT_APITOOLS\CTV2_sendRequest("POST", $report['url'], [], json_decode($data_json, true)['kind1']);
$result['kind2'] = CT_APITOOLS\CTV2_sendRequest("POST", $report['url'], [], json_decode($data_json, true)['kind2']);


$url = $ajax_domain . 'churchdb/ajax';

$data = array(
    'func' => 'add_rel',
    'id' =>  $result['vater']['data']['id'],
    'child_id' => $result['mutter']['data']['id'],
    'rel_id' => "2"  // Ehepartner
);
$result['vater-mutter'] = ['data'=> $data, 'result'=>CT_APITOOLS\CTV1_sendRequest($ajax_domain, $url, $data)];

$data = array(
    'func' => 'add_rel',
    'id' =>  $result['vater']['data']['id'],
    'child_id' => $result['kind1']['data']['id'],
    'rel_id' => "1"  // kind
);
$result['vater-kind1'] = ['data'=> $data, 'result'=>CT_APITOOLS\CTV1_sendRequest($ajax_domain, $url, $data)];

$data = array(
    'func' => 'add_rel',
    'id' =>  $result['vater']['data']['id'],
    'child_id' => $result['kind2']['data']['id'],
    'rel_id' => "1"  // kind
);
$result['vater-kind2'] = ['data'=> $data, 'result'=>CT_APITOOLS\CTV1_sendRequest($ajax_domain, $url, $data)];

$data = array(
    'func' => 'add_rel',
    'id' =>  $result['mutter']['data']['id'],
    'child_id' => $result['kind1']['data']['id'],
    'rel_id' => "1"  // kind
);
$result['mutter-kind1'] = ['data'=> $data, 'result'=>CT_APITOOLS\CTV1_sendRequest($ajax_domain, $url, $data)];


$data = array(
    'func' => 'add_rel',
    'id' =>  $result['mutter']['data']['id'],
    'child_id' => $result['kind2']['data']['id'],
    'rel_id' => "1"  // kind
);
$result['muter-kind2'] = ['data'=> $data, 'result'=>CT_APITOOLS\CTV1_sendRequest($ajax_domain, $url, $data)];


$report['result'] = $result;