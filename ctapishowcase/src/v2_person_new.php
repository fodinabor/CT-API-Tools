<?php

/*
 *
 * churchdb:view, churchdb:view alldata(-1), churchdb:view memberliste, churchdb:security level person(-1)
 *
 */
$timestamp = date("h:i:sa");
$url = "$ctdomain/api/persons";
$data = ['page' => 1, 'limit' => 500];
$data_json = <<< EOT
{

    "title": "",
    "firstName": "zz_new_allfields",
    "lastName": "test $timestamp",
    "nickname": "",
    "job": "",
    "street": "",
    "addressAddition": "",
    "zip": "",
    "city": "",
    "country": "",

    "phonePrivate": "",
    "phoneWork": "",
    "mobile": "",
    "fax": "",
    "birthName": "",
    "birthday": null,
    "imageUrl": "",
    "familyImageUrl": "https:\/\/arpke.church.tools\/?q=churchdb\/filedownload&filename=&type=image",
    "sexId": 0,
    "email": "",
    "emails": [],
    "cmsUserId": "",
    "privacyPolicyAgreement": {
        "date": "2019-05-02",
        "typeId": 1,
        "whoId": 1
    },
    "nationalityId": 0,
    "familyStatusId": 0,
    "campusId": 0,
    "statusId": 0,
    "departmentIds": [
        1
    ],
    "firstContact": "2021-04-01",
    "growPathId": null,



    "handy_eltern": "",
    "allergic": "",
    "swimmer": false
}

EOT;

$body = json_decode($data_json, JSON_OBJECT_AS_ARRAY);

// person anlegen

$report1 = [
    'url' => $url,
    'method' => "POST",
    'data' => null,
    'body' => [
        'firstName' => $body['firstName'],
        'lastName' => $body['lastName'],
        'departmentIds' => $body['departmentIds'],
        'statusId' => $body['statusId'],
        'privacyPolicyAgreement' => $body['privacyPolicyAgreement'],
        'campusId' => $body['campusId']
    ]
];

$report1['response'] = CT_APITOOLS\CTV2_sendRequest($report1);

// Personendaten auffüllen

$person_id = $report1['response']['data']['id'];

$report2 = [
    'url' => "$url/$person_id",
    'method' => "PATCH",
    'data' => null,
    'body' => $body
];

$report2['response'] = CT_APITOOLS\CTV2_sendRequest($report2);

// gruppen und Felder

// beziehungen


$report = ['create person' => $report1, 'update person' => $report2];#

