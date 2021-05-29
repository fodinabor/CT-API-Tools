<?php

require 'vendor/autoload.php';

use JsonPath\JsonObject;


$a = <<< EOT

{
  "grouptypeMemberstatus": {
    "8": {
      "id": "8",
      "gruppentyp_id": "1",
      "bezeichnung": "Teilnehmer"
    },
    "9": {
      "id": "9",
      "gruppentyp_id": "3",
      "bezeichnung": "Teilnehmer"
    }
  }
}

EOT;

$b = json_decode($a);

$c = new JsonObject($b);

$path = "$..*[?(@.bezeichnung == 'Teilnehmer' and @.gruppentyp_id == '1')].id";
$d = $c->get($path)[0];
var_dump("$path: $d");

$path = "$.grouptypeMemberstatus[?(@.gruppentyp_id == '1')].id";
$e = $c->get($path)[0];
var_dump("$path: $e");

$path = "$.grouptypeMemberstatus[?(@.bezeichnung == 'Teilnehmer')].id";
$f = $c->get($path)[0];
var_dump("$path: $f");

