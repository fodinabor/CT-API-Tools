<?php

use function CT_APITOOLS\CTV1_sendRequest;

$report = [
    'url' => $ctdomain . '/?q=churchresource/ajax',
    'method' => "POST",
    'data' => ['func' => 'getBookings'],
    'response' => "???"
];

$report['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report['url'], $report['data']);
