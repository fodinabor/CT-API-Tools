<?php
require_once("cthelper.inc");



$active_domain = "https://xxx.church.tools/?q="; // TODO: domain anpassen! Sollte https://domain/?q= sein
$login_token = "xxx"; //TODO: mit eigenem Token füllen
$user_id = "20"; //TODO: mit eigener Nutzer ID füllen
if(!CT_login($active_domain, $login_token, $user_id)){
	die("CT login not successful");
}

$allPersons = CT_getAllPersonData($active_domain);
if($allPersons->status != "success"){
	CT_logout($active_domain);
	die("person data could not be loaded.");
}

$allPersons = $allPersons->data;

/**
 Checks if any attachment to any person is below a certain securitylevel (e.g. DSGVO relevant)
*/
// foreach($allPersons as $person) {
	// $status = CT_getPersonDetails($active_domain, $person->p_id);
	// if($status->status != "success"){
		// CT_logout($active_domain);
		// die("person details could not be retrieved.");
	// }
	// if(isset($status->data->files)){
		// foreach($status->data->files as $file){
			// if($file->securitylevel_id < 5)
				// echo $person->vorname . " " . $person->name . "\n";
		// }
	// } else {
		// echo $person->vorname . " " . $person->name . " no files\n";
	// }
// }

// replace group and tag ids...
foreach($allPersons as $person){
	$groupmembership = array();
	foreach($person->groupmembers as $member){
		$groupmembership[$member->id] = $member->id;
	}
	echo $person->vorname . " " . $person->name . "\n";
	$id = $person->p_id;
	if(in_array(12, $person->tags) && !isset($groupmembership[697])){ //DSGVO
		echo "\t" . "DSGVO" . "\n";
		$status = CT_addPersonGroupRelation($active_domain, $id, 697, 29);
		if($status->status != "success"){
			CT_logout($active_domain);
			die("group membership could not be set.");
		}
		$status = CT_deletePersonTag($active_domain, $id, 12);
		if($status->status != "success"){
			CT_logout($active_domain);
			die("tag could not be removed.");
		}
	}
	if(!in_array(17, $person->tags) && !isset($groupmembership[688])){ //!BD
		echo "\t" . "!BD" . "\n";
		$status = CT_addPersonGroupRelation($active_domain, $id, 688, 29);
		if($status->status != "success"){
			CT_logout($active_domain);
			die("group membership could not be set.");
		}
	} else {
		$status = CT_deletePersonTag($active_domain, $id, 17);
		if($status->status != "success"){
			CT_logout($active_domain);
			die("tag could not be removed.");
		}
	}
	if(!in_array(20, $person->tags) && !isset($groupmembership[691])){ //!FOTOCT
		echo "\t" . "!FOTOCT" . "\n";
		$status = CT_addPersonGroupRelation($active_domain, $id, 691, 29);
		if($status->status != "success"){
			CT_logout($active_domain);
			die("group membership could not be set.");
		}
	} else {
		$status = CT_deletePersonTag($active_domain, $id, 20);
		if($status->status != "success"){
			CT_logout($active_domain);
			die("tag could not be removed.");
		}
	}
	if(!in_array(23, $person->tags) && !isset($groupmembership[701])){ //!Newsletter
		echo "\t" . "!Newsletter" . "\n";
		$status = CT_addPersonGroupRelation($active_domain, $id, 701, 29);
		if($status->status != "success"){
			CT_logout($active_domain);
			die("group membership could not be set.");
		}
	} else {
		$status = CT_deletePersonTag($active_domain, $id, 23);
		if($status->status != "success"){
			CT_logout($active_domain);
			die("tag could not be removed.");
		}
	}
	if(!in_array(26, $person->tags) && !isset($groupmembership[694])){ //!Foto Veranstaltungen
		echo "\t" . "!Foto Veranstaltungen" . "\n";
		$status = CT_addPersonGroupRelation($active_domain, $id, 694, 29);
		if($status->status != "success"){
			CT_logout($active_domain);
			die("group membership could not be set.");
		}
	} else {
		$status = CT_deletePersonTag($active_domain, $id, 26);
		if($status->status != "success"){
			CT_logout($active_domain);
			die("tag could not be removed.");
		}
	}
}


CT_logout($active_domain);
