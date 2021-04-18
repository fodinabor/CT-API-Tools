<?php
namespace CT_APITOOLS;
/*
 * this showcase applies access rights
 *
 * examples:
 * php ctcli.php v1_copyauth "group:zz_sub-1:Teilnehmer" "group:zz_sub-1:Teenhelfer"
 * php ctcli.php v1_copyauth "group:zz_sub-1:Teilnehmer" "grouptype:zz_auswahltest:testrolle"
 *
 *
 * Designatore (group|grouptype):zz_sub-1:Teilnehmer
 *
 * todo: improve error handling
 * * catch error 500
 * * catch invalid designators
 * * support auth merging
 * * support auth reset
 * * support multiple targets
 * * support manipulation
 *
 * todo: extract jsonPath helpers
 *
 * © 2021 Bernhard Weichel
 * WTF license
 */


/**
 * @param $masterdata
 * @param $designator
 * @return array
 */
function get_authdomain($masterdata, $designator)
{
    list($type, $name, $role) = explode(":", $designator);

    if ("group" == $type) {

        // find the requested group

        list($authdomain, $auth_id, $auth) = get_authdomain_for_group($masterdata, $name, $role);
    } elseif ("grouptype" == $type) {
        list($authdomain, $auth_id, $auth) = get_authdomain_for_grouptype($masterdata, $name, $role);
    } else {
        // todo handle error
    }

    return [$authdomain, $auth_id, $auth, $designator];
}

/**
 * @param $masterdata
 * @param string $name
 * @param string $role
 * @return array
 */
function get_authdomain_for_group($masterdata, string $name, string $role): array
{
    $group = find_one_in_JSONPath($masterdata, "$.churchauth.cdb_gruppe[?(@.bezeichnung=='$name')]");
    //todo handle group not found
    $group_id = $group['id'];
    $gruppentyp_id = $group['gruppentyp_id'];
    // $grouptype = find_one_in_JSONPath($masterdata, "$.churchauth.grouptype.$gruppentyp_id");

    // find the corresponding grouptype
    $path = "$.churchauth.grouptypeMemberstatus[?(@.bezeichnung == '$role' and @.gruppentyp_id == '$gruppentyp_id')].id";
    $grouptype_memberstatus_id = find_one_in_JSONPath($masterdata, $path);

    // find the groupmemberstatus

    $authdomain = "groupMemberstatus";
    $path = "$.churchauth.{$authdomain}[?(@.group_id == '$group_id')]";

    $x = find_in_JSONPath($masterdata, $path);
    $memberstatus = array_filter($x, function ($y) use ($grouptype_memberstatus_id) {
        return ($y['grouptype_memberstatus_id'] == $grouptype_memberstatus_id);
    });
    $memberstatus = array_values($memberstatus)[0];

    $auth_id = $memberstatus['id'];

    if (key_exists('auth', $memberstatus)) {
        $auth = $memberstatus['auth'];
    } else {
        $auth = [];
    }

    return array($authdomain, $auth_id, $auth);
}

/**
 * @param $masterdata
 * @param string $name
 * @param string $role
 * @return array
 */
function get_authdomain_for_grouptype($masterdata, string $name, string $role): array
{
    $grouptype_id = find_one_in_JSONPath($masterdata, "$.churchauth.cdb_gruppentyp[?(@.bezeichnung=='$name')].id");

    $path = "$.churchauth.grouptypeMemberstatus[?(@.bezeichnung == '$role' and @.gruppentyp_id == '$grouptype_id')]";
    $grouptype_memberstatus = find_one_in_JSONPath($masterdata, $path);

    $authdomain = 'grouptypeMemberstatus';
    $auth_id = $grouptype_memberstatus['id'];

    if (key_exists('auth', $grouptype_memberstatus)) {
        $auth = $grouptype_memberstatus['auth'];
    } else {
        $auth = [];
    }

    return array($authdomain, $auth_id, $auth);
}

/**
 * @param array $source
 * @param array $target
 * @param string $ctdomain
 */
function copy_auth(array $source, array $target, string $ctdomain)
{
    list ($source_authdomain, $source_auth_id, $source_auth, $sourcedesignator) = $source;
    list ($target_authdomain, $target_authdomain_id, $target_auth, $targetdesignator) = $target;

    // copy the source to a new target for reference purpose
    $new_target_auth = $source_auth;

    // serialize the auth data
    $authdata = serialize_auth_data($new_target_auth);

    // prepare the saveAuth request
    $report = [
        'url' => $ctdomain . '/?q=churchauth/ajax',
        'data' => [
            'func' => 'saveAuth',
            'domain_type' => $target_authdomain,
            'domain_id' => $target_authdomain_id,
            'data' => json_encode($authdata)
        ],
        'details' => [  // for reporting only
            'source' => [
                'designator' => $sourcedesignator,
                'auth' => $source_auth
            ],
            'target' => [
                'designator' => $targetdesignator,
                'auth_before' => $target_auth,
                'auth_after' => $new_target_auth
            ]
        ]
    ];

    $report['response'] = CTV1_sendRequest($ctdomain, $report['url'], $report['data']);
    return $report;
}

/**
 * @param $new_target_auth
 * @return array
 */
function serialize_auth_data($new_target_auth): array
{
    $authdata = [];
    foreach (array_keys($new_target_auth) as $auth_id) {
        $authvalues = $new_target_auth[$auth_id];
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
    return $authdata;
}


///// script body starts here
// read auth masterdata
$report = [
    // 'url' => $ctdomain . '/?q=churchauth/ajax' ,
    'url' => $ctdomain . '/?q=churchauth/ajax',

    'method' => "POST",
    'data' => ['func' => 'getMasterData'],
    //'data' => ['func'=>'getAuth'],
    'response' => "???"
];

$authmasterdata = CTV1_sendRequest($ctdomain, $report['url'], $report['data'])['data'];
$authmasterdata_jsonp = create_JSONPath($authmasterdata);

// read Source
if (isset($argv[3])) {
    list($sourcedesignator, $targetdesignator) = [$argv[2], $argv[3]];
} else {
    list($sourcedesignator, $targetdesignator) = ["group:zz_sub-1:Teilnehmer", "grouptype:zz_auswahltest:testrolle"];
}

$source = get_authdomain($authmasterdata_jsonp, $sourcedesignator);
// read target
$target = get_authdomain($authmasterdata_jsonp, $targetdesignator);

$report_1 = copy_auth(["", "", [], "clear"], $target, $ctdomain);
$report_2 = copy_auth($source, $target, $ctdomain);

$report = ['report_1' => $report_1, 'report_2' => $report_2];