<?php


// collec the results
$report = [];

$result = [
    'url' => $ctdomain . '/?q=churchdb/ajax',
    'method' => "POST",
    'data' => ['func' => 'getAdditionalGroupFields', 'g_id' => 622],
    'response' => "???"
];

$result['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $result['url'], $result['data']);

$report['getAdditionalGroupFieldsForIds'] = $result;