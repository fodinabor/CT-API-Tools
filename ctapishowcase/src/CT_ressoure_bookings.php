<?php
$url = "CT_getRessourceBookings($ajax_domain)";
$report = [
    'function' => "CT_getRessourceBookings($ajax_domain)",
];
$allRessources = CT_APITOOLS\CT_getRessourceBookings($ajax_domain);

$report['response'] = $allRessources;

