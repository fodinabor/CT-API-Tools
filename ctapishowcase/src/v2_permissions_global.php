<?php

$report = [
'url' => "$ctdomain/api/permissions/global",
'method' => "GET",
'data' => [],
'body' => []
];

$report['response'] = CT_APITOOLS\CTV2_sendRequest($report);

