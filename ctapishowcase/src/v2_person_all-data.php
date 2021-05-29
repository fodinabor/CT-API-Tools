<?php

/*
 * Mitgliederliste einsehen (view memberliste)
 * "Verwaltung" sehen (view)
 */

$report = [
    'method' => 'GET',
    'url' => "$ctdomain/api/persons",
    'data' => [
        'page' => 1,
        'limit' => 50,
        'is_archived' => false,
        //'lastpage' => 2
    ]
];

$report['response'] = CT_APITOOLS\CTV2_sendRequestWithPagination($report);
