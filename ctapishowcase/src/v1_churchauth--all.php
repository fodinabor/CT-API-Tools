<?php

/*
 * this showcase generats an json file which can b uses
 * to investigate the overall approach of access rights
 *
 * * baseline in git to trach changes
 * * checking plausibility
 * * identify similar aceess reight settings.
 *
 * © 2021 Bernhard Weichel
 * WTF license
 */

use Flow\JSONPath\JSONPath;

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

/**
 * @param JSONPath $masterdata_jsonpath the loaded jason path
 * @param $auth_entry  the auth entra as it comes from CT
 * @return string[]  athe result of the evaluation
 * @throws \Flow\JSONPath\JSONPathException
 */
function resolve_auth_entry($auth_entry, JSONPath $masterdata_jsonpath, $datafield = null)
{
    if (!isset($auth_entry)) {
        return null;
    }
    return array_map(function ($_auth_key) use ($masterdata_jsonpath, $auth_entry, $datafield) {
        $_authvalue = $auth_entry[$_auth_key];
        // todo fix handling of missing targets and auth parameters.
        // todo fix handling of auth for subgroups
        // todo fix handling of auth parameters

        $lookuptable = isset($datafield) ? $datafield : "auth_table";

        if ($_auth_key == -1) {
            $__modulename = "alle";
        } else {
            $__authrecord = find_one_in_JSONPath($masterdata_jsonpath, "$..{$lookuptable}..[?(@.id=='$_auth_key')]");
            if (isset($__authrecord)) {
                $__authname = key_exists("auth", $__authrecord) ? " [{$__authrecord['auth']}]" : "";
                $__modulename = "{$__authrecord['bezeichnung']}{$__authname}";
            } else {
                $__modulename = "?? undefined in $lookuptable ??";
            }
        }

        $_authvalue_resolved = is_array($_authvalue) ?
            resolve_auth_entry($_authvalue, $masterdata_jsonpath, $__authrecord['datenfeld']) : $_auth_key;

        return ["{$__modulename} (id: $_auth_key)" => $_authvalue_resolved];
    },
        array_keys($auth_entry));
}

/**
 * push identified hash and usage for further investigation
 *
 * @param $hash  String hash of the auth definitions
 * @param $role  String role where the auth is applied
 * @param $definition Array the auth definition
 * @param $authdefinitions Array here we collect the auth definitions and usages
 */
function pushauthdef($hash, $role, $definition, &$authdefinitions)
{
    if (key_exists($hash, $authdefinitions)) {
        $authdefinitions[$hash][] = $role;
    } else {
        // $authdefinitions[$hash] = ['applied' => [$role], 'auth' => $definition];
        $authdefinitions[$hash] = [$role];
    }
}


// handle status

/**
 * @param JSONPath $masterdata_jsonpath
 * @param array $authdefinitions
 * @return array
 * @throws \Flow\JSONPath\JSONPathException
 */
function read_auth_by_status(JSONPath $masterdata_jsonpath, array &$authdefinitions)
{
    $statuus = find_in_JSONPath($masterdata_jsonpath, '$..churchauth.status.*');
    $statusauth = [];  // here we collect the groptype auths

    foreach ($statuus as $status) {
        $statusid = $status['id'];
        $statusname = $status['bezeichnung'];

        $auth = isset($status['auth']) ? resolve_auth_entry($status['auth'], $masterdata_jsonpath) : [];

        $hash = hash('md5', json_encode($auth));
        pushauthdef($hash, "Status: $statusname", $auth, $authdefinitions);

        $result = [
            "auth_hash" => $hash,
            'auth' => $auth
        ];

        $statusauth[$statusname] = $result;
    }
    return array($statusauth);
}


/**
 * @param JSONPath $masterdata_jsonpath
 * @param $authdefinitions
 * @return array
 * @throws \Flow\JSONPath\JSONPathException
 */
function read_auth_by_grouptypes(JSONPath $masterdata_jsonpath, &$authdefinitions)
{
    $grouptypes = find_in_JSONPath($masterdata_jsonpath, '$..cdb_gruppentyp.*');

    $grouptypeauth = [];  // here we collect the groptype auths

    foreach ($grouptypes as $grouptype) {
        $grouptypeid = $grouptype['id'];
        $grouptypename = $grouptype['bezeichnung'];

        // get membertype
        $membertypes = find_in_JSONPath($masterdata_jsonpath,
            "$..grouptypeMemberstatus[?(@.gruppentyp_id == '$grouptypeid')]");
        $r = array_map(function ($auth_entry) use ($grouptypename, $masterdata_jsonpath, &$authdefinitions) {

            if (isset($auth_entry['auth'])) {
                $auth = resolve_auth_entry($auth_entry['auth'], $masterdata_jsonpath);
            } else {
                $auth = [];
            }

            $hash = hash('md5', json_encode($auth));

            pushauthdef($hash, "Gruppentyp: $grouptypename {$auth_entry['bezeichnung']}", $auth, $authdefinitions);

            return [
                'grouptype' => $grouptypename,
                'membertype' => $auth_entry['bezeichnung'],
                "auth_hash" => $hash,
                'auth' => $auth
            ];
        }, $membertypes);

        $grouptypeauth[$grouptypename] = $r;
    }
    return array($grouptypeauth);
}


/**
 * @param JSONPath $masterdata_jsonpath
 * @param $authdefinitions
 * @return array
 * @throws \Flow\JSONPath\JSONPathException
 */
function read_auth_by_group(JSONPath $masterdata_jsonpath, &$authdefinitions)
{
    //$groups = find_in_JSONPath($masterdata_jsonpath,'$..groups.*');

    $groupmemberauth = find_in_JSONPath($masterdata_jsonpath, '$..groupMemberstatus[?(@.auth)]');

    $groupmissing = [];
    $groupauth = [];

    foreach ($groupmemberauth as &$i) {
        $hash = hash('md5', json_encode($i['auth']));

        $j = [
            'group' => find_one_in_JSONPath($masterdata_jsonpath, "$..group.{$i['group_id']}.bezeichnung"),
            'role' => find_one_in_JSONPath($masterdata_jsonpath,
                "$..grouptypeMemberstatus.[?(@.id=={$i['grouptype_memberstatus_id']})].bezeichnung"),
            'group_id' => $i['group_id'],
            'groupMemberstatus_id' => $i['id'],
            'auth_hash' => $hash,
            'auth' => resolve_auth_entry($i['auth'], $masterdata_jsonpath)
        ];


        if ($j['group'] == null) {
            $groupmissing[$i['id']] = $i;
        }


        $role = "Gruppe: {$j['group_id']} {$j['group']} ({$j['role']})}";

        $i = $j;
        pushauthdef($hash, $role, $i['auth'], $authdefinitions);

        $groupauth[$j['group']][$j['role']] = $j;
    }
    return array($groupmissing, $groupauth);
}

/////////////////////////////////////////////////////////////
// reading masterdata

$report = [
    'url' => $ctdomain . '/?q=churchauth/ajax',
    // this was wrong 'url' => $ctdomain . '/?q=churchdb/ajax',

    'method' => "POST",
    'data' => ['func' => 'getMasterData'],
    //'data' => ['func'=>'getAuth'],
    'response' => "???"
];

$masterdata = CT_APITOOLS\CTV1_sendRequest($ctdomain, $report['url'], $report['data']);
$masterdata_jsonpath = new JSONPath($masterdata);
$authdefinitions = [];  // here we collect auth definietions

// reading auth by status
list($statusauth) = read_auth_by_status($masterdata_jsonpath, $authdefinitions);

// handle grouptypes
list($grouptypeauth) = read_auth_by_grouptypes($masterdata_jsonpath, $authdefinitions);

// handle groups
list($groupmissing, $groupauth) = read_auth_by_group($masterdata_jsonpath, $authdefinitions);

// report results

$report['response'] = [
    'auth_by_status' => $statusauth,
    'auth_by_grouptypes' => $grouptypeauth,
    'auth_by_groups' => $groupauth,
    'debug' => [
        'groupMemberstatus--withUndefined--group_id' => $groupmissing,
        'authdefintions' => $authdefinitions
    ]
];


