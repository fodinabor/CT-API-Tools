<?php

use Flow\JSONPath\JSONPath;


/*
 * this showcase applies access rights from Group "BRV vorlage' to group "ZZ Authtest"
 *
 * final objecti e
 *
 * php showcase.php copyauth "Teilnehmer:zz_sub-1" "Teilnehmer:zz_root"
 *
 * (group|grouptype):zz:sub-1:Teilnehmer
 *
 * use Flow\JSONPath\JSONPat
 *
 *
 * * read auth in "ZZ Authtest"
 * * merge with auth from"BRV vorlage"
 * * push result to "ZZ Authtest"
 *
 * to investigate the overall approach of access rights
 *
 * * baseline in git to track changes
 * * checking plausibility
 * * identify similar aceess reight settings.
 *
 * © 2021 Bernhard Weichel
 * WTF license
 */


/**
 * find a set in a JSONPath
 *
 * @param $masterdata  the Jason Path objct
 * @param $jsonpath    the path to search for
 * @return mixed
 */
function find_in_JSONPath(&$masterdata, $jsonpath)
{
    return ($masterdata->find($jsonpath)->getData());
}

/**
 *
 * find one in a JSONPath
 *
 * @param $masterdata  the Jason Path objct
 * @param $jsonpath    the path to search for
 * @return mixed | null
 */
function find_one_in_JSONPath(&$masterdata, $jsonpath)
{
    $result = find_in_JSONPath($masterdata, $jsonpath);
    $result = empty($result) ? null : $result[0];
    return ($result);
}


function get_authdomain($masterdata, $designator)
{
    list($type, $name, $role) = explode(":", $designator);

    if ("group" == $type) {

        // find the requested group

        $group = find_one_in_JSONPath($masterdata, "$.churchauth.cdb_gruppe[?(@.bezeichnung=='$name')]");
        //todo handle group not found
        $group_id = $group['id'];
        $gruppentyp_id = $group['gruppentyp_id'];
        $grouptype = find_one_in_JSONPath($masterdata, "$.churchauth.grouptype.$gruppentyp_id]");

        // find the corresponding grouptyp
        // todo this does not work https://github.com/Galbar/JsonPath-PHP/issues/43
        // $path = "$.churchauth.grouptypeMemberstatus[?(@.bezeichnung == '$role' && @.gruppentyp_id == '$gruppentyp_id')].id";
        // $grouptypememberstatus_id = find_one_in_JSONPath($masterdata, $path);

        $path = "$.churchauth.grouptypeMemberstatus[?(@.bezeichnung == '$role')]";
        $x = find_in_JSONPath($masterdata, $path);
        $grouptype_memberstatus_id = array_filter($x, function($y) use ($gruppentyp_id) {return($y['gruppentyp_id'] == $gruppentyp_id);});
        $grouptype_memberstatus_id = array_values($grouptype_memberstatus_id)[0]['id'];

        // find the groupmemberstatus

        $authdomain = "groupMemberstatus";
        $path = "$.churchauth.{$authdomain}[?(@.group_id == '$group_id')]";

        $x = find_in_JSONPath($masterdata, $path);
        $memberstatus = array_filter($x, function($y) use ($grouptype_memberstatus_id) {return($y['grouptype_memberstatus_id'] == $grouptype_memberstatus_id);});
        $memberstatus = array_values($memberstatus)[0];

        $auth_id = $memberstatus['id'];
        $auth = $memberstatus['auth'];
    } else {
    }

    return [$authdomain, $auth_id, $auth];
}



///// script body starts here
// read masterdata
$report = [
    // 'url' => $ctdomain . '/?q=churchauth/ajax' ,
    'url' => $ctdomain . '/?q=churchauth/ajax',

    'method' => "POST",
    'data' => ['func' => 'getMasterData'],
    //'data' => ['func'=>'getAuth'],
    'response' => "???"
];

$authmasterdata = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report['url'], $report['data'])['data'];
$authmasterdata_jsonp = new JSONPath($authmasterdata);
// read Source
list ($source_authdomain, $source_auth_id, $source_auth) = get_authdomain($authmasterdata_jsonp, $argv[2]);
// read target
list ($target_authdomain, $target_authdomain_id, $target_auth) = get_authdomain($authmasterdata_jsonp, $argv[3]);

// now we flatten the source auth

$authdata = [];
foreach (array_keys($source_auth) as $auth_id) {
    $authvalues = $target_auth[$auth_id];
    if (is_array($authvalues)) {
        foreach ($authvalues as $authvalue) {
            $authdata[] = [
                'auth_id' => $auth_id,
                'daten_id' => $authvalue
            ];
        }
    } elseif (empty($authvalues)) {
        $authdata[] = ['auth_id' => $auth_id];
    } else {
        $authdata[] = [
            'auth_id' => "$auth_id"
        ];
    }
}


/*
 *
func: saveAuth
domain_type: groupMemberstatus
domain_id: 786
data: [{"auth_id":"1"},{"auth_id":"2"},{"auth_id":"3"},{"auth_id":"4"},{"auth_id":"5"},{"auth_id":"6"},{"auth_id":"7"}]
browsertabId: 1989686060
 */

$report = [
    'url' => $ctdomain . '/?q=churchauth/ajax',
    'data' => [
        'func' => 'saveAuth',
        'domain_type' => $target_authdomain,
        'browsertabId' => 14908869,
        'domain_id' => $target_authdomain_id,
        'data' => json_encode($authdata)
    ]
];

$report['response'] = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report['url'], $report['data']);




