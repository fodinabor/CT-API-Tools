<?php

# curl -X GET "https://bgkorntal.church.tools/api/persons/876/relationships" -H "accept: application/json"

$report=[
    'url' => "$ctdomain/api/person/masterdata",
    'method' => 'GET'
];
$report['response']= CT_APITOOLS\CTV2_sendRequest($report);


