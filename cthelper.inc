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

https://api.church.tools/index.html
*/

/**
 * Extract key for cokie store etc.
 *
 * @param $domain the url as to be specified in the api calls e.g. https://mychurch.church.tools/?q=api/ajax
 * @return false|mixed|string the domain e.g. 'mychurch.church.tools'
 *
 * for internal use
 */
function getCoreDomain($domain)
{
    $pos = strpos($domain, "://");
    if ($pos !== false) {
        $domain = substr($domain, $pos + 3);
    }

    $pos = strpos($domain, "/");
    if ($pos !== false) {
        $domain = substr($domain, 0, $pos);
    }

    $pos = strpos($domain, "?");
    if ($pos !== false) {
        $domain = substr($domain, 0, $pos);
    }

    return $domain;
}

/**
 * return a pointer to the cookies array
 * @return array
 *
 * for internal use
 */
function &getCookies()
{
    static $CT_cookies = array();
    return $CT_cookies;
}


/**
 *
 * Return the cookies for a given ct url
 *
 * @param $domain the url as to be specified in the api calls e.g. https://mychurch.church.tools/?q=api/ajax
 * @return string the cookie string
 */
function CT_getCookies($domain)
{
    $domain = getCoreDomain($domain);
    $CT_cookies = &getCookies();
    if (!isset($CT_cookies[$domain])) {
        $CT_cookies[$domain] = array();
    }
    $res = "";
    foreach ($CT_cookies[$domain] as $key => $cookie) {
        $res .= "$key=$cookie; ";
    }
    return $res;
}

/**
 *
 * save recceived cookies
 *
 * @param $domain the url as to be specified in the api calls e.g. https://mychurch.church.tools/?q=api/ajax
 * @param $r  the result header of the most recent call
 *
 * for internal use
 */
function CT_saveCookies($domain, $r)
{
    $domain = getCoreDomain($domain);
    $CT_cookies = &getCookies();
    foreach ($r as $hdr) {
        if (preg_match('/^Set-Cookie:\s*([^;]+)/i', $hdr, $matches)) {
            parse_str($matches[1], $tmp);
            $CT_cookies[$domain] += $tmp;
        }
    }
}

/**
 * getht eh CSRFToken for a givn url
 *
 * @param $url  the url as to be specified in the api calls e.g. https://mychurch.church.tools/?q=api/ajax
 * @return mixed th CSRF Token
 *
 * for internal use
 */
function CT_getCSRFToken($url)
{
    static $token = array();

    $domain = getCoreDomain($url);

    if (isset($token[$domain]) && !empty($token[$domain])) {
        return $token[$domain];
    }

    $result = CTNew_getRequest("https://$domain/api/csrftoken", array());
    $token[$domain] = $result->data;
    return $token[$domain];
}


/**
 * Submit apost with multipe parts
 *
 * @param $domain    the url as to be specified in the api calls e.g. https://mychurch.church.tools/?q=api/ajax
 * @param $url       the url as to be specified in the api calls e.g. https://mychurch.church.tools/?q=api/ajax
 * @param $content   the content to be sent
 * @param $delimiter the delimiter for th parts
 * @return mixed     todo
 */

function CT_sendRequestMultipart($domain, $url, $content, $delimiter)
{
    $options = array(
        'http' => array(
            'header' => "Cookie: " . CT_getCookies($domain) . "\r\nContent-Type: multipart/form-data; boundary=" . $delimiter . "\r\nContent-Length: " . strlen($content) . "\r\n",
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


/**
 * send a request
 *
 * @param $domain
 * @param $url
 * @param $data
 * @param bool $csrf_token_required
 * @return mixed
 *
 *
 *
 */
function CT_sendRequest($domain, $url, $data, $csrf_token_required = true)
{
    $header = "Cookie: " . CT_getCookies($domain) . "\r\nContent-type: application/x-www-form-urlencoded\r\n";
    if ($csrf_token_required) {
        $header .= "csrf-token: " . CT_getCSRFToken($domain) . "\r\n";
    }

    $options = array(
        'http' => array(
            'header' => $header,
            'method' => 'POST',
            'content' => http_build_query($data),
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    $obj = json_decode($result);
    if (!isset($obj)) {
        echo("could noth handle result");
        var_dump($http_response_header);
        var_dump($url);
        var_dump($result);
        echo("end could noth handle result");
    }
    CT_saveCookies($domain, $http_response_header);
    return $obj;
}


/**
 * @param $method HTTP-Method  or Array with parameters
 * @param $url the url
 * @param $data array data to be encodd in the url
 * @param $body array to be sent as JSON in the body
 * @return Array the respons
 *
 * note you can use the explicit parametrers or an Array with the Arguments
 *
 * $report = [
 *   'url' => $url ,
 *   'method' => "PATCH",
 *   'data' => [],
 *   'body' => json_decode($data_json),
 * ];
 *
 */
function CTV2_sendRequest($method, $url = null, $data = null, $body = null)
{
    if (is_array($method)) {
        $_method = $method['method'];
        $_url = $method['url'];
        $_data = array_key_exists('data', $method)? $method['data']: null;
        $_body = array_key_exists('body', $method)? $method['body']: null;
    } else {
        $_method = $method;
        $_url = $url;
        $_data = $data;
        $_body = $body;
    }

    if (isset ($_data)) {
        $_url = $_url . "?" . http_build_query($_data);
    }

    $options = array(
        'http' => array(
            'header' => "Cookie: " . CT_getCookies($_url)
                . "\r\nContent-type: application/json"
                . "\r\naccept: application/json",
            'method' => $_method,
            'content' => json_encode($_body),
            'ignore_errors' => true,
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($_url, false, $context);
    $obj = json_decode($result);
    if (!isset($obj)) {
        echo("could noth handle result");
        var_dump($http_response_header);
        var_dump($_url);
        var_dump($result);
        echo("end could noth handle result");
    }
    CT_saveCookies($_url, $http_response_header);
    return $obj;
}


/**
 * @param $url
 * @param $data
 * @return mixed
 */
function CTNew_getRequest($url, $data)
{
    $options = array(
        'http' => array(
            'header' => "Cookie: " . CT_getCookies($url) . "\r\nContent-type: application/x-www-form-urlencoded\r\n",
            'method' => 'GET',
            'content' => http_build_query($data),
            'ignore_errors' => true
        )
    );
    $finalurl = $url . "?" . http_build_query($data);
    $context = stream_context_create($options);
    $result = file_get_contents($finalurl, false, $context);
    $obj = json_decode($result);
    if (!isset($obj)) {
        echo("could noth handle result");
        var_dump($http_response_header);
        var_dump($url);
        var_dump($result);
        echo("end could noth handle result");
    }
    CT_saveCookies($url, $http_response_header);
    return $obj;
}


/**
 * @param $domain
 * @param $token
 * @param $id
 * @return bool
 */
function CT_login($domain, $token, $id)
{
    $url = $domain . 'login/ajax';

    // Now use token to login
    $data = array(
        'func' => 'loginWithToken',
        'token' => $token,
        'id' => $id,
        'directtool' => 'API Tools'
    );
    $result = CT_sendRequest($domain, $url, $data, false);
    return ($result->status == "success");
}


/**
 * @param $domain
 * @param $email
 * @param $pw
 * @return bool
 */
function CT_loginAuth($domain, $email, $pw)
{
    $url = $domain . 'login/ajax';

    // Now use creds to login
    $data = array(
        'func' => 'login',
        'email' => $email,
        'password' => $pw,
        'directtool' => 'API Tools'
    );
    $result = CT_sendRequest($domain, $url, $data, false);
    return ($result->status == "success");
}

/**
 * @param $domain
 */
function CT_logout($domain)
{
    $url = $domain . 'login/ajax';

    $data = array('func' => 'logout');
    $result = CT_sendRequest($domain, $url, $data);
}

/**
 * @param $domain
 * @return mixed
 */
function CT_getCTDbMasterData($domain)
{
    static $CTDbMaster = array();
    if (isset($CTDbMaster[$domain]) && $CTDbMaster[$domain] != null && $CTDbMaster[$domain]->status == "success") {
        return $CTDbMaster[$domain];
    }
    $url = $domain . 'churchdb/ajax';
    $data = array('func' => 'getMasterData');
    $CTDbMaster[$domain] = CT_sendRequest($domain, $url, $data);
    return $CTDbMaster[$domain];
}

/**
 * @param $domain
 * @return mixed
 */
function CT_getCTAuthMasterData($domain)
{
    static $CTAuthMaster = array();
    if (isset($CTAuthMaster[$domain]) && $CTAuthMaster[$domain] != null && $CTAuthMaster[$domain]->status == "success") {
        return $CTAuthMaster[$domain];
    }
    $url = $domain . 'churchauth/ajax';
    $data = array('func' => 'getMasterData');
    $CTAuthMaster[$domain] = CT_sendRequest($domain, $url, $data);
    return $CTAuthMaster[$domain];
}

/**
 * @param $domain
 * @param $name
 * @param $groupTypeId
 * @param $groupStatus
 * @return mixed
 */
function CT_createGroup($domain, $name, $groupTypeId, $groupStatus)
{
    $url = $domain . 'churchdb/ajax';
    $data = array(
        'func' => 'createGroup',
        'name' => $name,
        'Inputf_grouptype' => $groupTypeId,
        'Inputf_groupstatus' => $groupStatus
    );
    $ret = CT_sendRequest($domain, $url, $data);
    return $ret;
}


/**
 * @param $domain
 * @param $child
 * @param $parent
 * @return mixed
 */
function CT_setParent($domain, $child, $parent)
{
    $url = $domain . 'churchdb/ajax';
    $data = array(
        'func' => 'addHierachy',
        'childId' => $child,
        'parentId' => $parent
    );
    $ret = CT_sendRequest($domain, $url, $data);
    return $ret;
}

/**
 * @param $domain
 * @param $id
 * @param $data
 * @return mixed
 */
function CT_addAuthStatus($domain, $id, $data)
{
    $url = $domain . 'churchauth/ajax';

    if (is_object($data)) {
        $data = CT_authToData($data);
    }

    $data = array(
        'func' => 'saveAuth',
        'domain_type' => 'status',
        'domain_id' => $id,
        'data' => json_encode($data)
    );
    $ret = CT_sendRequest($domain, $url, $data);
    return $ret;
}

/**
 * @param $domain
 * @param $id
 * @param $data
 * @return mixed
 */
function CT_addAuthGroupType($domain, $id, $data)
{
    $url = $domain . 'churchauth/ajax';

    if (is_object($data)) {
        $data = CT_authToData($data);
    }

    $data = array(
        'func' => 'saveAuth',
        'domain_type' => 'grouptypeMemberstatus',
        'domain_id' => $id,
        'data' => json_encode($data)
    );
    $ret = CT_sendRequest($domain, $url, $data);
    return $ret;
}


/**
 * @param $domain
 * @param $id
 * @param $data
 * @return mixed
 */
function CT_addAuthGroup($domain, $id, $data)
{
    $url = $domain . 'churchauth/ajax';

    if (is_object($data)) {
        $data = CT_authToData($data);
    }

    $data = array(
        'func' => 'saveAuth',
        'domain_type' => 'groupMemberstatus',
        'domain_id' => $id,
        'data' => json_encode($data)
    );
    $ret = CT_sendRequest($domain, $url, $data);
    return $ret;
}

/**
 * @param $auth
 * @return array
 */
function CT_authToData($auth)
{
    $data = array();
    foreach ($auth as $key => $value) {
        if (is_object($value)) {
            foreach ($value as $k => $v) {
                $data[] = array('auth_id' => $key, 'daten_id' => $v);
            }
        } else {
            $data[] = array('auth_id' => $key);
        }
    }
    return $data;
}


/**
 * @param $domain
 * @return mixed
 */
function CT_getSongs($domain)
{
    $url = $domain . 'churchservice/ajax';
    $data = array('func' => 'getAllSongs');
    $ret = CT_sendRequest($domain, $url, $data);
    return $ret;
}

/**
 * @param $domain
 * @param $id
 * @return mixed
 */
function CT_deleteSong($domain, $id)
{
    $url = $domain . 'churchservice/ajax';
    $data = array('func' => 'deleteSong', 'id' => $id);
    $ret = CT_sendRequest($domain, $url, $data);
    return $ret;
}


/**
 * @param $domain
 * @param $title
 * @param $author
 * @param $copy
 * @param $ccli
 * @return mixed
 */
function CT_addSong($domain, $title, $author, $copy, $ccli)
{
    $url = $domain . 'churchservice/ajax';
    $data = array(
        'func' => 'addNewSong',
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

/**
 * @param $domain
 * @param $id
 * @return mixed
 */
function CT_deleteSongFile($domain, $id)
{
    $url = $domain . 'churchservice/ajax';
    $data = array('func' => 'delFile', 'id' => $id);
    $ret = CT_sendRequest($domain, $url, $data);
    return $ret;
}

/**
 * @param $domain
 * @param $id
 * @param $tagId
 * @return mixed
 */
function CT_addSongTag($domain, $id, $tagId)
{
    $url = $domain . 'churchservice/ajax';
    $data = array('func' => 'addSongTag', 'id' => $id, 'tag_id' => $tagId);
    $ret = CT_sendRequest($domain, $url, $data);
    return $ret;
}

function CT_deletePersonTag($domain, $id, $tag_id)
{
    $url = $domain . 'churchdb/ajax';
    $data = array('func' => 'delPersonTag', 'id' => $id, 'tag_id' => $tag_id);
    $ret = CT_sendRequest($domain, $url, $data);
    return $ret;
}

/**
 * @param $domain
 * @return mixed
 */
function CT_getAllPersonData($domain)
{
    $url = $domain . 'churchdb/ajax';
    $data = array('func' => 'getAllPersonData');
    $ret = CT_sendRequest($domain, $url, $data);
    return $ret;
}

/**
 * @param $domain
 * @return mixed
 */
function CT_getRessourceBookings($domain)
{
    $url = $domain . 'churchresource/ajax';
    $data = array('func' => 'getBookings', 'from' => "0", 'to' => "1");
    $ret = CT_sendRequest($domain, $url, $data);
    return $ret;
}

/**
 * @param $domain
 * @param $id
 * @param $g_id
 * @param $memberstatus_id
 * @param string $comment
 * @return mixed
 */
function CT_addPersonGroupRelation($domain, $id, $g_id, $memberstatus_id, $comment = "")
{
    $url = $domain . 'churchdb/ajax';
    $data = array(
        'func' => 'addPersonGroupRelation',
        'id' => $id,
        'g_id' => $g_id,
        'groupmemberstatus_id' => $memberstatus_id,
        'date' => date('Y-m-d'),
        'comment' => $comment
    );
    $ret = CT_sendRequest($domain, $url, $data);
    return $ret;
}

/**
 * @param $domain
 * @param $id
 * @return mixed
 */
function CT_getPersonDetails($domain, $id)
{
    $url = $domain . 'churchdb/ajax';
    $data = array('func' => 'getPersonDetails', 'id' => $id);
    $ret = CT_sendRequest($domain, $url, $data);
    return $ret;
}

/**
 * @param $domain
 * @param $id
 * @return mixed
 */
function CTNew_getEventAgenda($domain, $id)
{
    $url = $domain . '/api/events/' . $id . '/agenda';
    $data = array();
    $ret = CTNew_getRequest($url, $data);
    return $ret;
}

/**
 * @param $boundary
 * @param $fields
 * @param $filename
 * @param $file_content
 * @return string
 */
function build_data_files($boundary, $fields, $filename, $file_content)
{
    $data = '';
    $eol = "\r\n";

    $delimiter = '-------------' . $boundary;

    foreach ($fields as $name => $content) {
        $data .= "--" . $delimiter . $eol
            . 'Content-Disposition: form-data; name="' . $name . "\"" . $eol . $eol
            . $content . $eol;
    }


    $data .= "--" . $delimiter . $eol
        . 'Content-Disposition: form-data; name="files[]"; filename="' . $filename . '"' . $eol
        . 'Content-Type: text/plain' . $eol;

    $data .= $eol;
    $data .= $file_content . $eol;

    $data .= "--" . $delimiter . "--" . $eol;


    return $data;
}

/**
 * @param $domain
 * @param $song_id
 * @param $filepath
 * @return false|mixed
 */
function CT_addTextToSong($domain, $song_id, $filepath)
{
    if (!file_exists($filepath)) {
        return FALSE;
    }
    $url = $domain . "churchservice/uploadfile";

    $fields = array("domain_type" => "song_arrangement", "domain_id" => $song_id);
    $filename = basename($filepath);
    $file_content = file_get_contents($filepath);

    $boundary = uniqid();
    $delimiter = '-------------' . $boundary;

    $post_data = build_data_files($boundary, $fields, $filename, $file_content);

    $ret = CT_sendRequestMultipart($domain, $url, $post_data, $delimiter);
    return $ret;
}
