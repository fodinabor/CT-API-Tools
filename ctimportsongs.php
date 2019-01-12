<?php
require_once("cthelper.inc");


$active_domain = "https://xxx.church.tools/?q="; // TODO: domain anpassen! Sollte https://domain/?q= sein
$login_token = "xxx"; //TODO: mit eigenem Token füllen
$user_id = "20"; //TODO: mit eigener Nutzer ID füllen
if(!CT_login($active_domain, $login_token, $user_id)){
	die("CT login not successful");
}

$songs = CT_getSongs($active_domain);
if($songs->status != "success"){
	CT_logout($active_domain);
	die("songs could not be retrieved");
}

// Lade bereits angelegte songs
$current_songlist = array();
foreach($songs->data->songs as $id => $song){
	$current_songlist[$song->bezeichnung . $song->author] = $id;
}

// lade OpenLyrics xml Dateien aus dem unterordner "songs"
$path = __DIR__ . "/songs/";
$files = array_diff(scandir($path), array('.', '..'));

foreach($files as $file){
	$f_content = file_get_contents($path . $file);
	echo $file . "\n";
	$song = new SimpleXMLElement($f_content);
	
	$title = "";
	foreach($song->properties->titles->title as $ttl){
		$title .= $ttl . ", ";
	}
	$title = substr($title, 0, strlen($title) - 2);
	
	$author = "";
	foreach($song->properties->authors->author as $athr){
		$author .= $athr. ", ";
	}
	$author = substr($author, 0, strlen($author) - 2);
	
	if(array_key_exists($title . $author, $current_songlist))
		continue;
	
	$copy = (string)$song->properties->copyright;
	$ccli = (string)$song->properties->ccliNo;
	
	$start = microtime(true);
	do{
		$res = CT_addSong($active_domain, $title, $author, $copy, $ccli);
		if(!isset($res->status) ||$res->status != "success"){
			echo "new song not added\n";
		}
	} while(!isset($res->status) || $res->status != "success");
	$end = microtime(true);
	echo "adding: " . ($end - $start) . "ms\n";
	
	$start = microtime(true);
	$arrangement_id = 0;
	while($arrangement_id == 0){
		$start_2 = microtime(true);
		$songs = CT_getSongs($active_domain);
		$end_2 = microtime(true);
		echo "getting songs: " . ($end_2 - $start_2) . "ms\n";

		if(!isset($songs->status) || $songs->status != "success"){
			echo "songs not retrieved\n";
			continue;
		}
		$id = $res->data;
		$song = $songs->data->songs->$id;
		
		foreach($song->arrangement as $key => $val){
			$arrangement_id = $key;
			if(!isset($val->files))
				break;
			foreach($val->files as $k => $v){
				CT_deleteSongFile($active_domain, $k);
			}
			break;
		}
	}
	$current_songlist[$title . $author] = intval($res->data);
	
	$end = microtime(true);
	echo "getting song details: " . ($end - $start) . "ms\n";
	
	$start = microtime(true);
	do {
		$obj = CT_addTextToSong($active_domain, intval($arrangement_id), $path . $file);
		if(!isset($obj->files[0])){
			print_r($obj);
			echo "song text not uploaded\n";
		} else {
			break;
		}
	}
	while(true);
	$end = microtime(true);
	echo "upload text: " . ($end - $start) . "ms\n";
}

CT_logout($active_domain);
