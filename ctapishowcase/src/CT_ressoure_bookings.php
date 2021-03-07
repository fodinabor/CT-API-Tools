<?php
$url = "CT_getRessourceBookings($active_domain)";
$report = [
    'function' => "CT_getRessourceBookings($active_domain)",
];
$allRessources = CT_getRessourceBookings($active_domain);

$report['response'] = $allRessources;

