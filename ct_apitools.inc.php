<?php

namespace CT_APITOOLS;

/*
 * This file holds helper functions to access Churcthools
 * the helper function hide the API-levels
 *
 * todo: improve the documentation
 *
 * you can use this file to inspect the usage of ct_apitools--helper.inc.php
 *
 */

require_once __DIR__ . "/ct_apitools--helper.inc.php";

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
    $url = $domain . '/?q=churchdb/ajax';
    $data = array('func' => 'getMasterData');
    $CTDbMaster[$domain] = CTV1_sendRequest($domain, $url, $data);
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
    $url = $domain . '/?q=churchauth/ajax';
    $data = array('func' => 'getMasterData');
    $CTAuthMaster[$domain] = CTV1_sendRequest($domain, $url, $data);
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
    $url = $domain . '/?q=churchdb/ajax';
    $data = array(
        'func' => 'createGroup',
        'name' => $name,
        'Inputf_grouptype' => $groupTypeId,
        'Inputf_groupstatus' => $groupStatus
    );
    $ret = CTV1_sendRequest($domain, $url, $data);
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
    $url = $domain . '/?q=churchdb/ajax';
    $data = array(
        'func' => 'addHierachy',
        'childId' => $child,
        'parentId' => $parent
    );
    $ret = CTV1_sendRequest($domain, $url, $data);
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
    $url = $domain . '/?q=churchauth/ajax';

    if (is_object($data)) {
        $data = CT_authToData($data);
    }

    $data = array(
        'func' => 'saveAuth',
        'domain_type' => 'status',
        'domain_id' => $id,
        'data' => json_encode($data)
    );
    $ret = CTV1_sendRequest($domain, $url, $data);
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
    $url = $domain . '/?q=churchauth/ajax';

    if (is_object($data)) {
        $data = CT_authToData($data);
    }

    $data = array(
        'func' => 'saveAuth',
        'domain_type' => 'grouptypeMemberstatus',
        'domain_id' => $id,
        'data' => json_encode($data)
    );
    $ret = CTV1_sendRequest($domain, $url, $data);
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
    $url = $domain . '/?q=churchauth/ajax';

    if (is_object($data)) {
        $data = CT_authToData($data);
    }

    $data = array(
        'func' => 'saveAuth',
        'domain_type' => 'groupMemberstatus',
        'domain_id' => $id,
        'data' => json_encode($data)
    );
    $ret = CTV1_sendRequest($domain, $url, $data);
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
    $url = $domain . '/?q=churchservice/ajax';
    $data = array('func' => 'getAllSongs');
    $ret = CTV1_sendRequest($domain, $url, $data);
    return $ret;
}

/**
 * @param $domain
 * @param $id
 * @return mixed
 */
function CT_deleteSong($domain, $id)
{
    $url = $domain . '/?q=churchservice/ajax';
    $data = array('func' => 'deleteSong', 'id' => $id);
    $ret = CTV1_sendRequest($domain, $url, $data);
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
    $url = $domain . '/?q=churchservice/ajax';
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
    $ret = CTV1_sendRequest($domain, $url, $data);
    return $ret;
}

/**
 * @param $domain
 * @param $id
 * @return mixed
 */
function CT_deleteSongFile($domain, $id)
{
    $url = $domain . '/?q=churchservice/ajax';
    $data = array('func' => 'delFile', 'id' => $id);
    $ret = CTV1_sendRequest($domain, $url, $data);
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
    $url = $domain . '/?q=churchservice/ajax';
    $data = array('func' => 'addSongTag', 'id' => $id, 'tag_id' => $tagId);
    $ret = CTV1_sendRequest($domain, $url, $data);
    return $ret;
}

function CT_deletePersonTag($domain, $id, $tag_id)
{
    $url = $domain . '/?q=churchdb/ajax';
    $data = array('func' => 'delPersonTag', 'id' => $id, 'tag_id' => $tag_id);
    $ret = CTV1_sendRequest($domain, $url, $data);
    return $ret;
}

/**
 * @param $domain
 * @return mixed
 */
function CT_getAllPersonData($domain)
{
    $url = $domain . '/?q=churchdb/ajax';
    $data = array('func' => 'getAllPersonData');
    $ret = CTV1_sendRequest($domain, $url, $data);
    return $ret;
}

/**
 * @param $domain
 * @return mixed
 */
function CT_getRessourceBookings($domain)
{
    $url = $domain . '/?q=churchresource/ajax';
    $data = array('func' => 'getBookings');
    $ret = CTV1_sendRequest($domain, $url, $data);
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
    $url = $domain . '/?q=churchdb/ajax';
    $data = array(
        'func' => 'addPersonGroupRelation',
        'id' => $id,
        'g_id' => $g_id,
        'groupmemberstatus_id' => $memberstatus_id,
        'date' => date('Y-m-d'),
        'comment' => $comment
    );
    $ret = CTV1_sendRequest($domain, $url, $data);
    return $ret;
}

/**
 * @param $domain
 * @param $id
 * @return mixed
 */
function CT_getPersonDetails($domain, $id)
{
    $url = $domain . '/?q=churchdb/ajax';
    $data = array('func' => 'getPersonDetails', 'id' => $id);
    $ret = CTV1_sendRequest($domain, $url, $data);
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
    $ret = CTV2_sendRequest("GET", $url, $data, []);
    return $ret;
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
    $url = $domain . "/?q=churchservice/uploadfile";

    $fields = array("domain_type" => "song_arrangement", "domain_id" => $song_id);
    $filename = basename($filepath);
    $file_content = file_get_contents($filepath);

    $boundary = uniqid();
    $delimiter = '-------------' . $boundary;

    $post_data = CT_build_data_files($boundary, $fields, $filename, $file_content);

    $ret = CTV1_sendRequestMultipart($domain, $url, $post_data, $delimiter);
    return $ret;
}
