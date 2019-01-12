<?php 
/*
MIT License

Copyright (c) Joachim Meyer 2018 

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/
/*
--- Churchtools API Helper
*/

function &getCookies(){
	static $CT_cookies = array();
	return $CT_cookies;
}

function CT_getCookies($domain) {
	$CT_cookies = &getCookies();
    if(!isset($CT_cookies[$domain])){
        $CT_cookies[$domain] = array();
    }
	$res = "";
	foreach ($CT_cookies[$domain] as $key => $cookie) {
		$res .= "$key=$cookie; ";
	}
	return $res;
}

function CT_saveCookies($domain, $r) {
	$CT_cookies = &getCookies();
	foreach ($r as $hdr) {
		if (preg_match('/^Set-Cookie:\s*([^;]+)/', $hdr, $matches)) {
			parse_str($matches[1], $tmp);
			$CT_cookies[$domain] += $tmp;
		}
	}
}

function CT_sendRequestMultipart($domain, $url, $content, $delimiter) {
	$options = array(
		'http'=>array(
			'header' => "Cookie: ". CT_getCookies($domain) . "\r\nContent-Type: multipart/form-data; boundary=" . $delimiter . "\r\nContent-Length: " . strlen($content)."\r\n",
			'method' => 'POST',
			'content' => $content
		)
	);
	$context = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	$obj = json_decode($result);
	if (isset($obj->status) && $obj->status == 'error') {
		echo "There is an error: $obj->message";
	}
	CT_saveCookies($domain, $http_response_header);
	return $obj;
}

function CT_sendRequest($domain, $url, $data) {
	$options = array(
		'http'=>array(
			'header' => "Cookie: ". CT_getCookies($domain) . "\r\nContent-type: application/x-www-form-urlencoded\r\n",
			'method' => 'POST',
			'content' => http_build_query($data),
		)
	);
	$context = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	$obj = json_decode($result);
	if ($obj->status == 'error') {
		echo "There is an error: $obj->message";
	}
	CT_saveCookies($domain, $http_response_header);
	return $obj;
}

function CT_login($domain, $token, $id){
	$url = $domain . 'login/ajax';

	// Now use token to login 
	$data = array('func' => 'loginWithToken', 
				  'token' => $token,
				  'id' => $id,
				  'directtool' => 'GroupViz'
			);
	$result = CT_sendRequest($domain, $url, $data);
	return ($result->status == "success");
}

function CT_loginAuth($domain, $email, $pw){
	$url = $domain . 'login/ajax';

	// Now use creds to login 
	$data = array('func' => 'login', 
				  'email' => $email,
				  'password' => $pw,
				  'directtool' => 'GroupViz'
			);
	$result = CT_sendRequest($domain, $url, $data);
	return ($result->status == "success");
}

function CT_logout($domain){
	$url = $domain . 'login/ajax';

	$data = array('func' => 'logout');
	$result = CT_sendRequest($domain, $url, $data);
}

function CT_getCTDbMasterData($domain){
	static $CTDbMaster = array();
	if(isset($CTDbMaster[$domain]) && $CTDbMaster[$domain] != null && $CTDbMaster[$domain]->status == "success"){
		return $CTDbMaster[$domain];
	}
	$url = $domain . 'churchdb/ajax';
	$data = array('func' => 'getMasterData'); 
	$CTDbMaster[$domain] = CT_sendRequest($domain, $url, $data);
	return $CTDbMaster[$domain];
}

function CT_getCTAuthMasterData($domain){
	static $CTAuthMaster = array();
	if(isset($CTAuthMaster[$domain]) && $CTAuthMaster[$domain] != null && $CTAuthMaster[$domain]->status == "success"){
		return $CTAuthMaster[$domain];
	}
	$url = $domain . 'churchauth/ajax';
	$data = array('func' => 'getMasterData'); 
	$CTAuthMaster[$domain] = CT_sendRequest($domain, $url, $data);
	return $CTAuthMaster[$domain];
}

function CT_createGroup($domain, $name, $groupTypeId, $groupStatus){
	$url = $domain . 'churchdb/ajax';
	$data = array('func' => 'createGroup',
        'name' => $name,
        'Inputf_grouptype' => $groupTypeId,
        'Inputf_groupstatus' => $groupStatus);
	$ret = CT_sendRequest($domain, $url, $data);
	return $ret;
}

function CT_setParent($domain, $child, $parent){
	$url = $domain . 'churchdb/ajax';
	$data = array('func' => 'addHierachy',
        'childId' => $child,
        'parentId' => $parent);
	$ret = CT_sendRequest($domain, $url, $data);
	return $ret;
}

function CT_addAuthStatus($domain, $id, $data){
	$url = $domain . 'churchauth/ajax';
    
    if(is_object($data)){
        $data = CT_authToData($data);
    }
    
	$data = array('func' => 'saveAuth',
        'domain_type' => 'status',
        'domain_id' => $id,
        'data' => json_encode($data));
	$ret = CT_sendRequest($domain, $url, $data);
	return $ret;
}

function CT_addAuthGroupType($domain, $id, $data){
	$url = $domain . 'churchauth/ajax';
    
    if(is_object($data)){
        $data = CT_authToData($data);
    }
    
	$data = array('func' => 'saveAuth',
        'domain_type' => 'grouptypeMemberstatus',
        'domain_id' => $id,
        'data' => json_encode($data));
	$ret = CT_sendRequest($domain, $url, $data);
	return $ret;
}

function CT_addAuthGroup($domain, $id, $data){
	$url = $domain . 'churchauth/ajax';
    
    if(is_object($data)){
        $data = CT_authToData($data);
    }
    
	$data = array('func' => 'saveAuth',
        'domain_type' => 'groupMemberstatus',
        'domain_id' => $id,
        'data' => json_encode($data));
	$ret = CT_sendRequest($domain, $url, $data);
	return $ret;
}

function CT_authToData($auth){
    $data = array();
    foreach($auth as $key => $value){
        if(is_object($value)){
            foreach($value as $k => $v){
                $data[] = array('auth_id' => $key, 'daten_id' => $v);
            }
        } else {
            $data[] = array('auth_id' => $key);
        }
    }
    return $data;
}

function CT_getSongs($domain) {
	$url = $domain . 'churchservice/ajax';
    $data = array('func' => 'getAllSongs');
	$ret = CT_sendRequest($domain, $url, $data);
	return $ret;
}

function CT_deleteSong($domain, $id) {
	$url = $domain . 'churchservice/ajax';
    $data = array('func' => 'deleteSong', 'id' => $id);
	$ret = CT_sendRequest($domain, $url, $data);
	return $ret;
}

function CT_addSong($domain, $title, $author, $copy, $ccli){
	$url = $domain . 'churchservice/ajax';
    $data = array('func' => 'addNewSong',
		'bezeichnung' => $title,
		'songcategory_id' => 1,
		'author' => $author,
		'copyright' => $copy,
		'ccli' => $ccli,
		'tonality' => '',
		'bpm' => '',
		'beat' => '',
		'comments[domain_type]' => 'arrangement'
		);
	$ret = CT_sendRequest($domain, $url, $data);
	return $ret;
}

function CT_deleteSongFile($domain, $id) {
	$url = $domain . 'churchservice/ajax';
    $data = array('func' => 'delFile', 'id' => $id);
	$ret = CT_sendRequest($domain, $url, $data);
	return $ret;
}

function CT_addSongTag($domain, $id, $tagId) {
	$url = $domain . 'churchservice/ajax';
    $data = array('func' => 'addSongTag', 'id' => $id, 'tag_id' => $tagId);
	$ret = CT_sendRequest($domain, $url, $data);
	return $ret;
}

function CT_deletePersonTag($domain, $id, $tag_id) {
	$url = $domain . 'churchdb/ajax';
    $data = array('func' => 'delPersonTag', 'id' => $id, 'tag_id' => $tag_id);
	$ret = CT_sendRequest($domain, $url, $data);
	return $ret;
}

function CT_getAllPersonData($domain) {
	$url = $domain . 'churchdb/ajax';
    $data = array('func' => 'getAllPersonData');
	$ret = CT_sendRequest($domain, $url, $data);
	return $ret;
}

function CT_addPersonGroupRelation($domain, $id, $g_id, $memberstatus_id, $comment="") {
	$url = $domain . 'churchdb/ajax';
    $data = array('func' => 'addPersonGroupRelation',
		'id' => $id, 'g_id' => $g_id,
		'groupmemberstatus_id' => $memberstatus_id,
		'date' => date('Y-m-d'),
		'comment' => $comment);
	$ret = CT_sendRequest($domain, $url, $data);
	return $ret;
}

function CT_getPersonDetails($domain, $id) {
	$url = $domain . 'churchdb/ajax';
    $data = array('func' => 'getPersonDetails', 'id' => $id);
	$ret = CT_sendRequest($domain, $url, $data);
	return $ret;
}

function build_data_files($boundary, $fields, $filename, $file_content){
    $data = '';
    $eol = "\r\n";

    $delimiter = '-------------' . $boundary;

    foreach ($fields as $name => $content) {
        $data .= "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
            . $content . $eol;
    }


    
	$data .= "--" . $delimiter . $eol
		. 'Content-Disposition: form-data; name="files[]"; filename="' . $filename . '"' . $eol
		. 'Content-Type: text/plain'.$eol
		;

	$data .= $eol;
	$data .= $file_content . $eol;

    $data .= "--" . $delimiter . "--".$eol;


    return $data;
}


function CT_addTextToSong($domain, $song_id, $filepath) {
	if (!file_exists($filepath))
		return FALSE;
	$url = $domain . "churchservice/uploadfile";
	
	$fields = array("domain_type"=>"song_arrangement", "domain_id"=> $song_id);
	$filename = basename($filepath);
	$file_content = file_get_contents($filepath);
	
	$boundary = uniqid();
	$delimiter = '-------------' . $boundary;

	$post_data = build_data_files($boundary, $fields, $filename, $file_content);
	
	$ret = CT_sendRequestMultipart($domain, $url, $post_data, $delimiter);
	return $ret;
}
