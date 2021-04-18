<?php

namespace CT_APITOOLS;

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


// helpers for CT autho

/**
 *
 * this resolves an authentry to a text description readable by humans
 *
 * @param JSONPath $masterdata_jsonpath the loaded jason path
 * @param $auth_entry  the auth entra as it comes from CT
 * @return string[]  athe result of the evaluation
 * @throws \Flow\JSONPath\JSONPathException
 */
function resolve_auth_entry($auth_entry, $masterdata_jsonpath, $datafield = null)
{
    if (!isset($auth_entry)) {
        return null;
    }
    return array_map(function ($_auth_key) use ($masterdata_jsonpath, $auth_entry, $datafield) {
        $_authvalue = $auth_entry[$_auth_key];
        // todo fix handling of missing targets and auth parameters.
        // todo fix handling of auth for subgroups
        // todo fix handling of auth parameters

        // find in which table to resolve the authkey
        // by default it is auth_table
        // note that "auth_table" uses integer ids
        // churchauth uses string ids
        $lookuptable = isset($datafield) ? $datafield : "auth_table";

        // auth_key = -1 resolves to all (groups etc.)
        if ($_auth_key == -1) {
            $__modulename = "alle";
        } else {
            // workaround the type-mess of ids in CT
            $__auth_key = $_auth_key;
            // if we have subgroup stuff such as "10001D" - lookup fore 10001
            if ((substr($__auth_key, -1) == 'D')) {
                $__auth_key = (int)$_auth_key;
            }

            // there are sill some id which are strings
            if ($lookuptable == 'auth_table') {
                $__auth_key = $__auth_key;
            } else {
                $__auth_key = "'$__auth_key'";
            }

            $path = "$..{$lookuptable}..*[?(@.id == $__auth_key )]";

            $__authrecord = find_one_in_JSONPath($masterdata_jsonpath, $path);
            if (isset($__authrecord)) {
                $__authname = key_exists("auth", $__authrecord) ? " [{$__authrecord['auth']}]" : "";
                $__modulename = "{$__authrecord['bezeichnung']}{$__authname}";
            } else {
                // todo improve error handling
                $__modulename = "$_auth_key undefined in $lookuptable ??";
            }
        }

// if resolved value is a nested auth record, we have to resolved this again.
// this means we have an auth entry with parameters.
// otherwise wer return the key as dummy parameter.
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


// read auth record

/**
 *
 * Read auth defined at the level of status (Member etc.)
 *
 * @param JSONPath $masterdata_jsonpath
 * @param array $authdefinitions
 * @return array
 * @throws \Flow\JSONPath\JSONPathException
 */
function read_auth_by_status($masterdata_jsonpath, array &$authdefinitions)
{
    $statuus = find_in_JSONPath($masterdata_jsonpath, '$..churchauth.status.*');
    $statusauth = [];  // here we collect the groptype auths

    foreach ($statuus as $status) {
        $statusid = $status['id'];
        $statusname = $status['bezeichnung'];

        $auth = isset($status['auth']) ? resolve_auth_entry($status['auth'], $masterdata_jsonpath) : [];

        $hash = hash('md5', json_encode($auth));
        pushauthdef($hash, "ST $statusname", $auth, $authdefinitions);

        $result = [
            "auth_hash" => $hash,
            'auth' => $auth
        ];

        $statusauth[$statusname] = $result;
    }
    return array($statusauth);
}


/**
 *
 *  Read auth defined at the level of Grouptype
 *  note this walks along grouatypes
 *
 * @param JSONPath $masterdata_jsonpath
 * @param $authdefinitions
 * @return array
 * @throws \Flow\JSONPath\JSONPathException
 */
function read_auth_by_grouptypes($masterdata_jsonpath, &$authdefinitions)
{
    $grouptypes = find_in_JSONPath($masterdata_jsonpath, '$..cdb_gruppentyp.*');

    $grouptypeauth = [];  // here we collect the groptype auths

    foreach ($grouptypes as $grouptype) {
        $grouptypeid = $grouptype['id'];
        $grouptypename = $grouptype['bezeichnung'];

        // get membertype
        $membertypes = find_in_JSONPath($masterdata_jsonpath,
            "$..grouptypeMemberstatus[?(@.gruppentyp_id == '$grouptypeid')]");
        $r = array_map(function ($authentry) use ($grouptypename, $masterdata_jsonpath, &$authdefinitions) {

            if (isset($authentry['auth'])) {
                $auth = resolve_auth_entry($authentry['auth'], $masterdata_jsonpath);
            } else {
                $auth = [];
            }

            $hash = hash('md5', json_encode($auth));

            $gtabbreviation = explode(" ", $grouptypename)[0];
            pushauthdef($hash, "GTRL $gtabbreviation {$authentry['bezeichnung']}", $auth, $authdefinitions);

            return [
                'grouptypeMemberstatus_id' => $authentry['id'],
                'grouptype' => $grouptypename,
                'membertype' => $authentry['bezeichnung'],
                "auth_hash" => $hash,
                'auth' => $auth
            ];
        }, $membertypes);

        $grouptypeauth[$grouptypename] = $r;
    }
    return array($grouptypeauth);
}


/**
 * This reads authentification by groups. Note that it walks along
 * groupmemberstatus.
 *
 *
 * @param JSONPath $masterdata_jsonpath
 * @param $authdefinitions
 * @return array
 * @throws \Flow\JSONPath\JSONPathException
 */
function read_auth_by_groups($masterdata_jsonpath, &$authdefinitions)
{
    //$groups = find_in_JSONPath($masterdata_jsonpath,'$..groups.*');

    $groupmemberauth = find_in_JSONPath($masterdata_jsonpath, '$..groupMemberstatus[?(@.auth)]');

    $groupmissing = [];
    $groupauth = [];

    foreach ($groupmemberauth as &$authentry) {
        $hash = hash('md5', json_encode($authentry['auth']));

        $resolved_authentry = [
            'group' => find_one_in_JSONPath($masterdata_jsonpath, "$..group.{$authentry['group_id']}.bezeichnung"),
            'role' => find_one_in_JSONPath($masterdata_jsonpath,
                "$..grouptypeMemberstatus[?(@.id == '{$authentry['grouptype_memberstatus_id']}')].bezeichnung"),
            'group_id' => $authentry['group_id'],
            'groupMemberstatus_id' => $authentry['id'],
            'auth_hash' => $hash,
            'auth' => $authentry['auth'],
            'resolved_auth' => resolve_auth_entry($authentry['auth'], $masterdata_jsonpath)
        ];


        if ($resolved_authentry['group'] == null) {
            $groupmissing[$authentry['id']] = $authentry;
        } else {

            $role = "GRRL {$resolved_authentry['group']} {$resolved_authentry['role']}";

            $authentry = $resolved_authentry;
            pushauthdef($hash, $role, $authentry['resolved_auth'], $authdefinitions);

            $groupauth[$resolved_authentry['group']][$resolved_authentry['role']] = $resolved_authentry;
        }
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

$masterdata = CTV1_sendRequest($ctdomain, $report['url'], $report['data']);
$masterdata_jsonpath = create_JSONPath($masterdata);
$authdefinitions = [];  // here we collect auth definietions

// reading auth by status
list($statusauth) = read_auth_by_status($masterdata_jsonpath, $authdefinitions);

// handle grouptypes
list($grouptypeauth) = read_auth_by_grouptypes($masterdata_jsonpath, $authdefinitions);

// handle groups
list($groupmissing, $groupauth) = read_auth_by_groups($masterdata_jsonpath, $authdefinitions);

// write file for simulation

$rubyfile = fopen(__DIR__ . "/../responses/v1_churchauth--all.rb", "w");

foreach ($authdefinitions as $authdefinition => $value) {
    $defs = json_encode($value, JSON_UNESCAPED_UNICODE);
    $record = "  plan.add('$authdefinition', [], $defs)\n";
    fwrite($rubyfile, $record);
}

fclose($rubyfile);

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


