<?php

/*
 * Mitgliederliste einsehen (view memberliste)
 * "Verwaltung" sehen (view)
 */

$report=[
    'method' => 'GET',
    'url' => "$ctdomain/api/persons"
];
$report['response'] = CT_APITOOLS\CTV2_sendRequest($report, []);
