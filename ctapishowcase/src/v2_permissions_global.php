<?php

$report = [
'url' => "$ctdomain/api/permissions/global",
'method' => "GET",
'data' => [],
'body' => []
];

$report['response'] = CTV2_sendRequest($report);

