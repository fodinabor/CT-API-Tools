<?php

use Flow\JSONPath\JSONPath;

/**
 * @param JSONPath $masterdata_jsonpath
 * @param $auth_entry
 * @return string[]
 * @throws \Flow\JSONPath\JSONPathException
 */
function resolve_auth_entry($auth_entry, JSONPath $masterdata_jsonpath, $datafield = null)
{
    return array_map(function ($_auth_key) use ($masterdata_jsonpath, $auth_entry, $datafield) {
        $_authvalue = $auth_entry[$_auth_key];
        // todo fix handling of missing targets and auth parameters.
        // todo fix handling of auth for subgroups
        // todo fix handling of auth parameters

        $lookuptable = isset($datafield) ? $datafield : "auth_table";

        if ($_auth_key == -1){
           $__modulename = "alle";
        }
        else {
            $__authrecord = $masterdata_jsonpath->find("$..{$lookuptable}..[?(@.id=='$_auth_key')]")->getData()[0];
            $__authname = key_exists("auth", $__authrecord ) ? " [{$__authrecord['auth']}]" : "";
            $__modulename = "{$__authrecord['bezeichnung']}{$__authname}";
        }

        $_authvalue_resolved = is_array($_authvalue) ? resolve_auth_entry($_authvalue, $masterdata_jsonpath,
            $__authrecord['datenfeld']) : null;

        return ["{$__modulename} (id: $_auth_key)", $_authvalue_resolved];
    },
        array_keys($auth_entry));
}


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

// handle status

$statuus = $masterdata_jsonpath->find('$..churchauth.status.*')->getData();
$statusauth = [];  // here we collect the groptype auths

foreach ($statuus as $status) {
    $statusid = $status['id'];
    $statusname = $status['bezeichnung'];

    $statusauth[$statusname]['auth'] = resolve_auth_entry($status['auth'], $masterdata_jsonpath);
}


// handle grouptypes

$grouptypes = $masterdata_jsonpath->find('$..cdb_gruppentyp.*')->getData();

$grouptypeauth = [];  // here we collect the groptype auths

foreach ($grouptypes as $grouptype) {
    $grouptypeid = $grouptype['id'];
    $grouptypename = $grouptype['bezeichnung'];

    // get membertype
    $q = "$..grouptypeMemberstatus[?(@.gruppentyp_id == '$grouptypeid')]";
    $membertypes = $masterdata_jsonpath->find($q)->getData();
    $r = array_map(function ($auth_entry) use ($grouptypename, $masterdata_jsonpath) {

        if (isset($auth_entry['auth'])) {
            $auth = resolve_auth_entry($auth_entry['auth'], $masterdata_jsonpath);
        } else {
            $auth = [];
        }

        return [
            'grouptype' => $grouptypename,
            'membertype' => $auth_entry['bezeichnung'],
            "auth_hash" => hash('md5', json_encode($auth)),
            'auth' => $auth
        ];
    }, $membertypes);

    $grouptypeauth[$grouptypename] = $r;
}

// handle groups

$groups = $masterdata_jsonpath->find('$..groups.*')->getData();

$groupmemberauth = $masterdata_jsonpath->find('$..groupMemberstatus[?(@.auth)]')->getData();;

$groupmissing = [];
$authdefinitions = [];
$groupauth = [];

foreach ($groupmemberauth as &$i) {
    $hash = hash('md5', json_encode($i['auth']));

    $j = [
        'group' => $masterdata_jsonpath->find("$..group.{$i['group_id']}.bezeichnung")->getData()[0],
        'role' => $masterdata_jsonpath->find("$..grouptypeMemberstatus.[?(@.id=={$i['grouptype_memberstatus_id']})].bezeichnung")->getData()[0],
        'group_id' => $i['group_id'],
        'groupMemberstatus_id' => $i['id'],
        'auth_hash' => $hash,
        'auth' => resolve_auth_entry($i['auth'], $masterdata_jsonpath)
    ];


    if ($j['group'] == null) {
        $groupmissing[$i['id']] = $i;
    }


    $role = "{$j['group_id']} {$j['group']} ({$j['role']})}";
    if (key_exists($hash, $authdefinitions)) {
        $authdefinitions[$hash]['applied'][] = $role;
    } else {
        $authdefinitions[$hash] = ['applied' => [$role], 'auth' => $i['auth']];
    }

    $i = $j;

    $groupauth[$j['group']][$j['role']] = $j;
}

// handle status


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

