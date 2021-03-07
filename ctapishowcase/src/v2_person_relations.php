<?php

# curl -X GET "https://bgkorntal.church.tools/api/persons/876/relationships" -H "accept: application/json"

$report=[
    'url' => "$ctdomain/api/persons/90/relationships",
    'method' => 'GET'
];
$report['response']= CTV2_sendRequest($report);


