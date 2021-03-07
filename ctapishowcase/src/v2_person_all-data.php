<?php

/*
 * Mitgliederliste einsehen (view memberliste)
 * "Verwaltung" sehen (view)
 */

$report=[
    'method' => 'GET',
    'url' => "$ctdomain/api/persons"
];
$report['response'] = CTV2_sendRequest($report, []);
