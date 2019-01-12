<?php
function getICS($url){
	$options = array(
		'http'=>array(
			'method' => 'GET'
		)
	);
	$context = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	return $result;
}

$ferien = getICS("http://i.cal.to/ical/81/bayern/ferien/51dff671.2e043941-e6413524.ics"); // TODO: URL ersetzen.. :)
$feiertage = getICS("http://i.cal.to/ical/65/bayern/feiertage/51dff671.2e043941-4aa31ff2.ics"); // TODO: URL ersetzen.. :)
$pos = stripos($ferien, "BEGIN:VEVENT");
$rpos = strripos($feiertage, "END:VEVENT");
$merged = substr($feiertage, 0, $rpos + strlen("END:VEVENT")) . "\n" . substr($ferien, $pos);

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=cal.ics'); 
header('Content-Transfer-Encoding: binary');
header('Connection: Keep-Alive');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . strlen($merged));

echo $merged;