<?php

namespace CT_APITOOLS;

$groupname = "zz_sub-1";

$report1 = [
    'url' => "$ctdomain/api/groups",
    'method' => "GET",
    'data' => ['page'=>1],
    'body' => []
];

$report1['response'] = CTV2_sendRequestWithPagination($report1);

$groups = create_JSONPath($report1);
$path = "$.response.data..*[?(@.name == '$groupname')].id";
$group_id = find_one_in_JSONPath($groups, $path);

$report2 = [
    'url' => "$ctdomain/api/groups/$group_id/memberfields",
    'method' => "GET",
    'data' => [],
    'body' => []
];

$report2['response'] = CTV2_sendRequest($report2);

$report = ['report 1' => $report1, 'report 2' => $report2];
