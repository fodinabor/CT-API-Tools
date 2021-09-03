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

//use Flow\JSONPath\JSONPathException;
use JsonPath\InvalidJsonException;
use JsonPath\InvalidJsonPathException;
use JsonPath\JsonObject;
use JsonSerializable;

/**
 * Class GroupHierarchy
 * @package CT_APITOOLS
 *
 * collect and export group hierarchy and decriptions
 */
class GroupHierarchy implements JsonSerializable
{
    private $groups = [];    // details of groups index = group id
    private $hierarchy = [];  // hierarchy index = group id
    private $hierarchyvalid = false;  // indcate if
    private $groupids = [];   // index = group name
    private $grouptypeids = [];  // index = grouptype name
    private $oldnames = [];  // index = groupname -
    private $lastid = 1000000;  // used for pseudoids
    private $grouptypedefs = []; // used for grouptype metadata

    private function getgrouptypebyname($groupname)
    {
        if (array_key_exists($groupname, $this->grouptypedefs)) {
            return ($this->grouptypedefs[$groupname]);
        }

        $prefix = $this->getprefix($groupname);
        if (array_key_exists($prefix, $this->grouptypedefs)) {
            return ($this->grouptypedefs[$prefix]['id']);
        }
        return (0);
    }

    /**
     * @param $name string
     * @return mixed|string
     *
     * extract the prefix from a name
     */
    function getprefix($name)
    {
        return explode(" ", $name)[0];
    }

    /**
     * @param $name // group name
     * @param array $parents // list of parent group names
     * @param array $extra : 'desc' => "...", 'type'=> 4, 'id' => 23, 'oldname' => "oldname"
     *
     */
    function add($name, $parents = [], $extra = [])
    {
        $this->hierarchyvalid = false;  // invalidate hierarchy

        $id = array_key_exists("id", $extra) ? $extra["id"] : $this->lastid++;
        $desc = array_key_exists("desc", $extra) ? $extra["desc"] : "";
        $type = array_key_exists("type", $extra) ?
            $extra["type"] : $this->getgrouptypebyname($name);  // todo proper handling of group types

        if (array_key_exists($name, $this->groupids)) {
            // todo error mehtdeutige Gruppenname
            echo "ERROR: Gruppenname '$name' ist mehfach verwendet";
        }

        $this->groups[$id] = [
            'id' => $id,
            'name' => $name,
            'parents' => $parents,
            'type' => $type,
            'desc' => $desc
        ];

        $this->groupids[$name] = $id;
    }

    /**
     * rebuild the hierarchy of groups to prepare for output
     */
    function buildhierarchy()
    {
        $this->hierarchy = [];  // hierarchy index = group id
        // populat hierarchy with parent ids
        foreach ($this->groups as $group) {
            $parents = array_key_exists('parents', $group) ? $group['parents'] : [];
            $parents = empty($parents) ? [] : $parents;

            // todo create the missing pseudogroups

            $this->hierarchy[$group['id']] = array_map(function ($groupname) {
                // todo: unify ct returns group ids as parents while simulation comes with group names as parents
                if (array_key_exists($groupname, $this->groups)) {
                    $result = $groupname;
                } else {
                    if (array_key_exists("$groupname", $this->groupids)) {
                        $result = $this->groupids["$groupname"];
                    } else {
                        echo("ERROR missing group: $groupname\n");
                        $result = "$groupname";
                    }
                }
                return $result;
            }, $parents);
        }
        $this->hierarchyvalid = true;
    }

    function setgrouptypedefs($grouptypedefs)
    {
        $this->grouptypedefs = $grouptypedefs;
        $keys = array_keys($grouptypedefs);
        foreach ($keys as $key) {
            $this->grouptypedefs[$key]['id'] = "$key";
            $prefix = $this->grouptypedefs[$key]['prefix'];
            $this->grouptypedefs[$prefix] = $this->grouptypedefs[$key];
        }
    }

    /**
     * @return string
     *
     * export hierarchy as plantuml
     */
    function toplantuml()
    {
        $result = ['@startuml'];
        // write groups
        // write edges

        $arrows = ["-up-|>", "-up---|>", "-up----|>", "-up-----|>"];
        $this->buildhierarchy();
        $i = 0;
        foreach ($this->groups as $group) {
            $parents = $this->hierarchy["{$group['id']}"];
            $parents = empty($parents) ? [0] : $parents;
            $me = $group['name'];

            foreach ($parents as $parent) {
                $arrow = $arrows[$i++];
                $i = ($i > 3) ? 0 : $i;
                if (array_key_exists("$parent", $this->groups)) {
                    $result[] = "\"$me\" $arrow \"{$this->groups["$parent"]["name"]}\"";
                }
            }
        }

        $result[] = "@enduml";

        return join("\n", $result);

        /*
  result = groups_hash.map do |id, group|
    parents = hierarchy_hash[group['id']]
    parents = [0] if parents.nil?
        parents = [0] if parents.empty?
    parents = parents.map do |i|
    thegroup = groups_hash[i]
      if group
      "#{i}:#{thegroup['name']}" rescue nil
      else
        $log.log("ERROR", "Gruppe #{i} nicht definiert")
      end
    end.compact
    require 'pry'
    me = %{#{group['id']}:#{groups_hash[group['id']]['name']}} rescue binding.pry;
        parents.map { |parentname| %Q{"#{me}" -up-|> "#{parentname}" } }
  end
end*/
    }

    /**
     * export hierarhy as graphml
     */
    function tographml()
    {

        // the template
        $template = <<< EOT
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<graphml xmlns="http://graphml.graphdrawing.org/xmlns"  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:y="http://www.yworks.com/xml/graphml" xmlns:yed="http://www.yworks.com/xml/yed/3" xsi:schemaLocation="http://graphml.graphdrawing.org/xmlns http://www.yworks.com/xml/schema/graphml/1.1/ygraphml.xsd">
  <!--Created by yFiles for Java 2.9-->
  <key for="graphml" id="d0" yfiles.type="resources"/>
  <key for="port" id="d1" yfiles.type="portgraphics"/>
  <key for="port" id="d2" yfiles.type="portgeometry"/>
  <key for="port" id="d3" yfiles.type="portuserdata"/>
  <key attr.name="url" attr.type="string" for="node" id="d4"/>
  <key attr.name="description" attr.type="string" for="node" id="d5"/>
  <key for="node" id="d6" yfiles.type="nodegraphics"/>
  <key attr.name="Beschreibung" attr.type="string" for="graph" id="d7"/>
  <key attr.name="url" attr.type="string" for="edge" id="d8"/>
  <key attr.name="description" attr.type="string" for="edge" id="d9"/>
  <key for="edge" id="d10" yfiles.type="edgegraphics"/>
  <graph edgedefault="directed" id="G">
  </graph>
</graphml>
EOT;

        $doc = simplexml_load_string($template);

//
//  groups_hash, hierarchy_hash = groupdatatohash(groups, hierarchy)
//  #groups_hash[0]              = { 'id' => 0, 'name' => "ROOT" }
//
//  graph = doc.xpath("//xmlns:graph").first
//
        $graph = $doc->graph[0];

//  # generate all nodes
//  groups_hash.each do |id, group|

        foreach ($this->groups as $group) {

//        prefix     = group['name'].split(" ").first
//    group_type = TYPECOLORS[get_groupType_in_group(group)] ||
//        TYPECOLORS[prefix] ||
//        TYPECOLORS[0]
//

            // find grouptype def by id
            $grouptype = array_key_exists($group['type'],
                $this->grouptypedefs) ? $this->grouptypedefs[$group['type']] : null;

            // find grouptype def by prefix
            $prefix = $this->getprefix($group['name']);
            if (!isset($grouptype)) {
                $grouptype = array_key_exists($prefix,
                    $this->grouptypedefs) ? $this->grouptypedefs[$prefix] : null;
            }
            if (!isset($grouptype)) {
                $grouptype = $this->grouptypedefs[0];
            }

//    n_node       = Nokogiri::XML::Node.new "node", doc
//    n_node["id"] = id
            $n_node = $graph->addChild("node");
            $n_node['id'] = $group['id'];

//
//    n_data        = Nokogiri::XML::Node.new "data", doc
//    n_data["key"] = "d6"
//


            $n_data = $n_node->addChild('data');
            $n_data['key'] = $group['id'];
            $n_data['key'] = 'd6';


//    n_data_4              = Nokogiri::XML::Node.new "data", doc
//    n_data_4["key"]       = "d4"
//    n_data_4["xml:space"] = "preserve"
//    n_data_4 << "https://bgkorntal.church.tools/?q=churchdb#/GroupView/searchEntry:#{URI.escape(group['name'])}"
//

            $content = urlencode($group['name']);
            $content = str_replace("+", "%20", $content);  // replace "+" for churchtools url
            $n_data_4 = $n_node->addChild('data',
                "https://bgkorntal.church.tools/?q=churchdb#/GroupView/searchEntry:$content");
            $n_data_4['key'] = 'd4';
            $n_data_4["xml:space"] = "preserve";


//    n_data_5              = Nokogiri::XML::Node.new "data", doc
//    n_data_5["key"]       = "d5"
//    n_data_5["xml:space"] = "preserve"
//    n_data_5 << "#{group['desc']}"
//
//
            $content = str_replace("&", "&amp;", $group['desc']);
            $n_data_5 = $n_node->addChild('data', $content);
            $n_data_5['key'] = 'd5';
            $n_data_5["xml:space"] = "preserve";


//    n_ShapeNode = Nokogiri::XML::Node.new "y:ShapeNode", doc
//    n_NodeLabel = Nokogiri::XML::Node.new "y:NodeLabel", doc


            $n_shapenode = $n_data->addChild('y:ShapeNode', null, "http://www.yworks.com/xml/graphml");

//    n_NodeLabel.content = "[#{group_type[:prefix]}] #{group['name']} (#{id})"
//    n_NodeLabel.content = "#{group['name']} (#{id})"
//
            $content = str_replace("&", "&amp;", "{$group['name']} ({$group['id']})");
            $n_shapenode->addChild('NodeLabel', $content, "http://www.yworks.com/xml/graphml");

//    n_Geometry          = Nokogiri::XML::Node.new "y:Geometry", doc
//    n_Geometry['width'] = "#{40 * 7}"  # estimated length of title
//

            $n_geometry = $n_shapenode->addChild('Geometry', "", "http://www.yworks.com/xml/graphml");
            $n_geometry['width'] = "280";  // 40 * 7

//    n_Shape         = Nokogiri::XML::Node.new "y:Shape", doc
//    n_Shape["type"] = group_type[:shape] || TYPECOLORS[0][:shape]

            $n_shape = $n_shapenode->addChild('Shape', null, "http://www.yworks.com/xml/graphml");
            $n_shape['type'] = $grouptype['shape'];
            //var_dump($grouptype);die;

//
//    n_Fill          = Nokogiri::XML::Node.new "y:Fill", doc
//    n_Fill['color'] = group_type[:color] || TYPECOLORS[0][:color]

            $n_fill = $n_shapenode->addChild("Fill", null, "http://www.yworks.com/xml/graphml");
            $n_fill['color'] = $grouptype['color'];

//
//    n_ShapeNode << n_NodeLabel
//    n_ShapeNode << n_Geometry
//    n_ShapeNode << n_Fill
//    n_ShapeNode << n_Shape
//
//    n_data << n_ShapeNode
//    n_node << n_data
//    n_node << n_data_4
//    n_node << n_data_5
//    graph << n_node
//
        }

///// expose edges
///

        foreach ($this->hierarchy as $meid => $parentids) {
            $parents = empty($parentids) ? ['0'] : $parentids;

            foreach ($parents as $parent) {
                if ($parent == '0') {
                    continue;
                }
                $n_edge = $graph->addChild('edge');
                $n_edge['source'] = $meid;
                $n_edge['target'] = $parent;
                $n_edge['id'] = "{$meid}_$parent";
            }
        }

//    parents = hierarchy_hash[id]
//    parents = [0] if parents.nil?
//            parents = [0] if parents.empty?
//    parents.each do |up|
//        n_edge           = Nokogiri::XML::Node.new "edge", doc
//      n_edge["source"] = id
//      n_edge["target"] = up
//      n_edge["id"]     = "#{up}_#{id}"
//      if (!up.nil?) && (up > 0)
//        graph << n_edge
//      end
//    end
//  end
//  xp(doc)
        return ($doc->asXML());
    }

    /**
     * @return array|mixed
     *
     * implement for JsonSerializeable
     *
     */
    function jsonSerialize()
    {
        $this->buildhierarchy();
        return get_object_vars($this);
    }
}

// helpers for CT auth

/**
 * @param $grouptypename
 * @return mixed|string
 *
 * construct the abbreviation for a grouptype
 * used to build the name of the pseudogrup
 */
function getgrouptypeabbreviation($grouptypename)
{
    return explode(" ", $grouptypename)[0];
}


/**
 *
 * this resolves an authentry to a text description readable by humans
 *
 * @param $masterdata_jsonpath JsonObject  the loaded jason path
 * @param $auth_entry array the auth entra as it comes from CT
 * @return string[]  the result of the evaluation
 * @return array|array[]|null
 * @throws InvalidJsonPathException InvalidJsonException
 */
function resolve_auth_entry($auth_entry, &$masterdata_jsonpath, $datafield = null, $permission_deep_no = null)
{
    if (!isset($auth_entry)) {
        return null;
    }

    $result = array_map(function ($_auth_key) use (
        &$masterdata_jsonpath,
        $auth_entry,
        $datafield,
        $permission_deep_no
    ) {
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
            // if we have subgroup stuff such as "10001D" - lookup for 10001
            if ((substr($__auth_key, -1) == 'D')) {
                $deep = " in Untergruppen ($permission_deep_no)";
                $__auth_key = (int)$_auth_key;
            } else {
                $deep = "";
            }

            // there are sill some id which are strings
            // the ones with deep
            if ($lookuptable == 'auth_table') {
                // $__auth_key = $__auth_key;
                $path = "$.data.$lookuptable.*[?(@.id == $__auth_key)]";
            } else {
                // $__auth_key = $__auth_key;
                $path = "$.data.churchauth.$lookuptable.$__auth_key";
            }

            // todo this is a full table scan wich is done for every entry
            //
            //$path = "$.*.{$lookuptable}..*[?(@.id == $__auth_key )]";
            //echo ($path."\n");
            $__authrecord = find_one_in_JSONPath($masterdata_jsonpath, $path);
            if (isset($__authrecord)) {
                // see if there is a symbolic name for the auth record (e.g. (+edit info)
                $__authname = key_exists("auth", $__authrecord) ? " [{$__authrecord['auth']}]" : "";
                $__modulename = "{$__authrecord['bezeichnung']}$deep$__authname";
            } else {
                // todo improve error handling
                $__modulename = "auth_key '$_auth_key' undefined in '$lookuptable' ??";
            }
        }

// if resolved value is a nested auth record, we have to resolved this again.
// this means we have an auth entry with parameters.
// otherwise wer return the key as dummy parameter.
        $_authvalue_resolved = is_array($_authvalue) ?
            resolve_auth_entry($_authvalue, $masterdata_jsonpath, $__authrecord['datenfeld']) : $_auth_key;

        return ["$__modulename ($lookuptable: $_auth_key)" => $_authvalue_resolved];
    },
        array_keys($auth_entry));
    return $result;
}

/**
 * push identified hash and usage for further investigation
 *
 * @param $hash  string hash of the auth definitions
 * @param $role  string role within a group where the auth is applied - the parent of hash
 * @param $groupname string Group for which role the auth is applied to - the parent of $role
 * @param $definition array the auth definition
 * @param $authdefinitions array here we collect the auth definitions and usages
 */
function pushauthdef($hash, $role, $groupname, $definition, &$authdefinitions)
{
    $desc = reportauthasmd($definition, "");
    if (key_exists($hash, $authdefinitions)) {
        $authdefinitions[$hash]['roles'][] = $role;
    } else {
        // $authdefinitions[$hash] = ['applied' => [$role], 'auth' => $definition];
        $authdefinitions[$hash]['roles'] = [$role];
    }
    $authdefinitions[$hash]['desc'] = $desc;
    $authdefinitions[$hash]['groupname'] = $groupname;

}

/**
 * @param $definition array
 * @param $indent string
 * @return string
 */
function reportauthasmd($definition, $indent = "")
{
    if (!isset($definition)) {
        return "";
    }
    $result = [];
    //echo ".";
    foreach ($definition as $key => $defnitionentry) {
        if (is_numeric($key)) {
            $result[] = reportauthasmd($defnitionentry, "$indent");
        } elseif (is_array($defnitionentry)) {
            $result[] = "{$indent}* $key";
            $result[] = reportauthasmd($defnitionentry, "$indent    ");
        } else {
            $result[] = "{$indent}* $key [$defnitionentry]";
        }
    }
    return join("\n", $result);
}

// read auth record

/**
 *
 * Read auth defined at the level of status (Member etc.)
 *
 * @param  $masterdata_jsonpath JsonObject
 * @param  $authdefinitions array
 * @param array $pseudogroups
 * @return array
 * @throws InvalidJsonPathException
 */
function read_auth_by_status($masterdata_jsonpath, array &$authdefinitions, array &$pseudogroups): array
{
    $statuus = find_in_JSONPath($masterdata_jsonpath, '$..churchauth.status.*');
    $statusauth = [];  // here we collect the groptype auths

    foreach ($statuus as $status) {
        // $statusid = $status['id'];
        $statusname = $status['bezeichnung'];

        // not there might be as status without an auth entry
        $auth = array_key_exists('auth', $status) ? $status['auth'] : [];
        $resolved_auth = resolve_auth_entry($auth, $masterdata_jsonpath);

        $hash = hash('md5', json_encode($resolved_auth));
        pushauthdef($hash, "ST $statusname", null, $resolved_auth, $authdefinitions);
        pushauthdef("ST $statusname", "ST Status", null, null, $pseudogroups);

        $result = [
            "auth_hash" => $hash,
            'auth' => $auth,
            'resolved_auth' => $resolved_auth
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
 * @param $masterdata_jsonpath JsonObject
 * @param $authdefinitions array
 * @param array $pseudogroups
 * @return array
 * @throws InvalidJsonPathException
 */

function read_auth_by_grouptypes(JsonObject $masterdata_jsonpath, array &$authdefinitions, array &$pseudogroups): array
{
    $grouptypes = find_in_JSONPath($masterdata_jsonpath, '$.data.churchauth.cdb_gruppentyp.*');

    $grouptypeauth = [];  // here we collect the groptype auths

    foreach ($grouptypes as $grouptype) {
        $grouptypeid = $grouptype['id'];
        $grouptypename = $grouptype['bezeichnung'];
        $permission_deep_no = $grouptype['permission_deep_no'];

        // get membertype
        // todo remove full scan in loop
        $membertypes = find_in_JSONPath($masterdata_jsonpath,
            "$..grouptypeMemberstatus[?(@.gruppentyp_id == '$grouptypeid')]");
        $r = array_map(function ($authentry) use (
            $grouptypename,
            $masterdata_jsonpath,
            $permission_deep_no,
            &$authdefinitions
        ) {

            // there might be grouptypes without auth enty
            $auth = (array_key_exists('auth', $authentry)) ? $authentry['auth'] : [];
            $resolved_auth = resolve_auth_entry($auth, $masterdata_jsonpath, null, $permission_deep_no);

            $hash = hash('md5', json_encode($resolved_auth));

            $gtabbreviation = getgrouptypeabbreviation($grouptypename);
            pushauthdef($hash, "GTRL $gtabbreviation {$authentry['bezeichnung']}", null, $resolved_auth,
                $authdefinitions);

            return [
                'grouptypeMemberstatus_id' => $authentry['id'],
                'grouptype' => $grouptypename,
                'membertype' => $authentry['bezeichnung'],
                "auth_hash" => $hash,
                'auth' => $auth,
                'resolved_auth' => $resolved_auth
            ];
        }, $membertypes);

        $grouptypeauth[$grouptypename] = $r;
    }
    return array($grouptypeauth);
}


/**
 * @param $masterdata_jsonpath
 * @param $authdefinitions
 */
function read_auth_by_person($masterdata_jsonpath, array &$authdefinitions, array &$pseudogroups)
{
    $personmissing = [];
    $personauth = [];

    $personauths = find_in_JSONPath($masterdata_jsonpath, '$..person[?(@.auth)]');

    foreach ($personauths as $authentry) {
        $authdefinition = $authentry['auth'];
        $hash = hash('md5', json_encode($authdefinition));
        $personname = $authentry['bezeichnung'];
        $role = "$personname";
        $rolen = "Personen";

        // note that if there is a pesonauths entry, we can be sure taht there is an auth entry
        $resolved_authentry = [
            'person' => $personname,
            'person_id' => $authentry['id'],
            'auth_hash' => $hash,
            'auth' => $authentry['auth'],
            'resolved_auth' => resolve_auth_entry($authdefinition, $masterdata_jsonpath)
        ];

        $personnamewithabbr = "PRS $personname";
        pushauthdef($hash, $personnamewithabbr, null, $resolved_authentry['resolved_auth'], $authdefinitions);
        pushauthdef($personnamewithabbr, "PRS Persons", null, null, $pseudogroups);

        $personauth[$personname] = $resolved_authentry;
    }

    return [$personmissing, $personauth];
}

/**
 * This reads authentification by groups. Note that it walks along
 * groupmemberstatus.
 *
 *
 * @param $masterdata_jsonpath JsonObject
 * @param $authdefinitions
 * @return array
 * @throws InvalidJsonPathException
 */
function read_auth_by_groups(JsonObject $masterdata_jsonpath, array &$authdefinitions, array &$pseudogroups): array
{
    //$groups = find_in_JSONPath($masterdata_jsonpath,'$..groups.*');

    $groupmemberauth = find_in_JSONPath($masterdata_jsonpath, '$.data.churchauth.groupMemberstatus[?(@.auth)]');

    $groupmissing = [];
    $groupauth = [];

    foreach ($groupmemberauth as &$authentry) {
        $hash = hash('md5', json_encode($authentry['auth']));

        // ase we iterate through groupmemberauth we can be sure there is an 'auth' Property
        $auth = $authentry['auth'];
        $group_id = $authentry['group_id'];
        $group = find_one_in_JSONPath($masterdata_jsonpath, "$.data.churchauth.group.{$group_id}");

        // there are some zombie - entries in groupmemberauth table
        // which refer to non existing (maybe deleted groups)
        // confirmed by support
        if (empty($group)) {
            $groupmissing[$authentry['id']] = $authentry;
            continue;
        }

        $permission_deep_no = $group['permission_deep_no'];

        // echo ("p: $permission_deep_no");
        $resolved_authentry = [
            'group' => $group['bezeichnung'],
            'role' => find_one_in_JSONPath($masterdata_jsonpath,
                "$..grouptypeMemberstatus[?(@.id == '{$authentry['grouptype_memberstatus_id']}')].bezeichnung"),
            'group_id' => $authentry['group_id'],
            'groupMemberstatus_id' => $authentry['id'],
            'auth_hash' => $hash,
            'auth' => $auth,
            'permission_deep_no' => $permission_deep_no,
            'resolved_auth' => resolve_auth_entry($auth, $masterdata_jsonpath, null, $permission_deep_no)
        ];


        $groupname = $resolved_authentry['group'];
        $role = "GRRL {$groupname} {$resolved_authentry['role']}";
        $rolen = "GRRLN {$groupname}";

        $authentry = $resolved_authentry;
        pushauthdef($hash, $role, $rolen, $authentry['resolved_auth'], $authdefinitions);
        pushauthdef($role, $rolen, $groupname, null, $pseudogroups);

        $groupauth[$resolved_authentry['group']][$resolved_authentry['role']] = $resolved_authentry;

    }
    return array($groupmissing, $groupauth);
}


// write file for simulation

/**
 * @param array $authdefinitions
 * @param $rubyfilename
 * @param $grouphierarchy GroupHierarchy
 * @return false|resource|null
 *
 * create rubysimulation for the authdefinitions
 * also preserves group hierarchy
 */
function create_rubysimulationfile(array $authdefinitions, $rubyfilename, &$grouphierarchy = [])
{
    $rubyfile = fopen($rubyfilename, "w");

    $handledgroups = [];

    foreach ($authdefinitions as $authdefinition => $value) {
        $desc = $value['desc'];
        $groupname = $value['groupname'];

        fwrite($rubyfile, "### auth definitions for $groupname\n");
        $rolesjson = json_encode($value['roles'], JSON_UNESCAPED_UNICODE);
        $record = "  plan.add('$authdefinition', [], $rolesjson, desc:%Q{{$desc}})\n";
        if (!array_key_exists($authdefinition, $handledgroups)) {
            fwrite($rubyfile, $record);
            // preserve group hierarchy
            $grouphierarchy->add($authdefinition, $value['roles'], ['desc' => $desc]);
        }
        $handledgroups[$authdefinition] = $authdefinition;

        if (!empty($groupname)) { // write role definitions
            {
                fwrite($rubyfile, "### role definitions for $groupname\n");
            }
            foreach ($value['roles'] as $role) {
                $record = "  plan.add('$role', [], ['$groupname'], desc:%Q{{$desc}})\n";
                if (!array_key_exists($role, $handledgroups)) {
                    fwrite($rubyfile, $record);
                    //preserve group hierarchy
                    $grouphierarchy->add($role, [$groupname], ['desc' => $desc]);
                }
                $handledgroups[$role] = $authdefinition;
            }
        }
    }
    fclose($rubyfile);
    return null;
}

/**
 * @param $authdefinitions
 * @param $filename
 */
function create_markdownreport($authdefinitions, $filename)
{
    $rubyfile = fopen($filename, "w");
    foreach ($authdefinitions as $authdefinition => $value) {
        fwrite($rubyfile, "\n\n# $authdefinition");
        fwrite($rubyfile, "\n## " . join("\n## ", $value['roles']));
        fwrite($rubyfile, "\n\n" . $value['desc']);
    }
    fclose($rubyfile);

}

/**
 * @param $authdefinitions
 * @param $filename
 */
function create_jsonreport($authdefinitions, $filename)
{
    $jsonfile = fopen($filename, "w");
    fwrite($jsonfile, json_encode($authdefinitions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fclose($jsonfile);
}

/**
 * @param $response
 * @param $filename
 */
function create_whocanwhatreport($response, $filename)
{

    $sectionnames = [
        'auth_by_person' => '4 Person     ',
        'auth_by_status' => '1 Status     ',
        'auth_by_grouptypes' => '2 Gruppentyp ',
        'auth_by_groups' => '3 Gruppe     ',
    ];

    $whocanwhat = [];

    echo "create_whocanwahtreport\n";
    foreach ($response as $section => $value) {
        // if (in_array($section, ["debug", 'auth_by_grouptypes', "auth_by_groups", 'auth_by_person'])) {
        if (in_array($section, ["debug"])) {
            continue;
        }
        echo "handling $section\n";

        $currenthead = $sectionnames[$section];


        // now
        foreach ($value as $grantee => $grants) {
            $role = array_key_exists('membertype', $grants) ? $grants['membertype'] : $grantee;

            // this is a grant for person / status (it has no roles)
            if (array_key_exists('resolved_auth', $grants)) {
                push_authcollection($grants['resolved_auth'], $whocanwhat, "$currenthead: $role");
            } else {
                // for grouptypes, groups which have roles
                foreach ($grants as $role => $rolegrants) {
                    if (!is_array($rolegrants)) {
                        continue;
                    }

                    // see if there is a designator for the role
                    $role = array_key_exists('membertype', $rolegrants) ? $rolegrants['membertype'] : $role;

                    if (array_key_exists('resolved_auth', $rolegrants)) {
                        push_authcollection($rolegrants['resolved_auth'], $whocanwhat,
                            "$currenthead: $grantee: [$role]");
                    } else {
                        // todo for pseudo groups like GRRLLN
                    }
                }
            }
        }
    }

    // maybe there might be a more elegant approach to sort
    // the result.

    // sort the grants
    $grants = array_keys($whocanwhat);
    sort($grants);

    // generate the result, thereby sort the grantees within the grants
    $result = [];
    foreach ($grants as $grant) {
        $grantees = $whocanwhat[$grant];
        sort($grantees);
        $result[$grant] = $grantees;
    }

    // save jason version
    $outfile = fopen("$filename.json", "w");
    fwrite($outfile, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    fclose($outfile);

    // save markdown version
    $outfile = fopen("$filename.md", "w");
    foreach (array_keys($result) as $grant) {
        fwrite($outfile, "\n## $grant\n\n");

        foreach ($result[$grant] as $grantee) {
            fwrite($outfile, "    $grantee\n");
        }
    }
    fclose($outfile);

    return $whocanwhat;
}

/**
 * inveerts whocanwhat such that we get whatcanwho
 *
 * @param $whocanwhat
 * @return array
 */
function invert_whocanwhat($whocanwhat)
{
    $result = [];

    foreach ($whocanwhat as $grant => $grantees) {
        foreach ($grantees as $grantee) {
            if (!array_key_exists($grantee, $result)) {
                $result[$grantee] = [];
            }
            $result[$grantee][] = $grant;
        }
    }

    return $result;
}

function find_roles_in_whatcanwho($whatcanwho)
{
    $result = [];

    $whatcanwhokeys = array_keys($whatcanwho);
    sort($whatcanwhokeys);

    foreach ($whatcanwhokeys as $whatcanwhokey) {

        $who = $whatcanwhokey;
        $grant = $whatcanwho[$whatcanwhokey];

        // find other grantees with the same or more grants
        foreach ($whatcanwhokeys as $whoelse) {
            $grantelse = $whatcanwho[$whoelse];
            if (empty(array_diff($grant, $grantelse))) {
                if (!array_key_exists($who, $result)) {
                    $result[$who] = [];
                    $result[$who]['kann'] = $grant;
                    $result[$who]['wie auch'] = [];
                }
                if ($who !== $whoelse) {

                    $c1 = count($grantelse);
                    $c2 = count($grant);
                    $diff = $c1 - $c2;
                    $result[$who]['wie auch'][] = "$whoelse  (+ $diff)";
                }
            }
        }
    }

    // c
    return $result;
}

/**
 *
 * push auth for a given grantee to the authcollection
 *
 * eventurally authcollection is a flat list
 *
 * @param $resolved_auth  array authentifications for $grantee
 * @param array $authcollection array the result where we collect all the authentifications
 * @param $grantee string the name of the grante (status, grouptype+role ...)
 * @return array|null
 */
function push_authcollection($resolved_auth, array &$authcollection, $grantee)
{
    foreach ($resolved_auth as $authentry) {
        $grant = array_keys($authentry)[0];
        // if there are subelements for which we have a grant
        // push auth for every subelement
        if (is_array($authentry[$grant])) {
            foreach ($authentry[$grant] as $subgrant) {
                $subgrantkey = array_keys($subgrant)[0];
                // uncomment to swap grant, subgrant
                // push_authcollection([["$grant [$subgrantkey]" => ""]], $authcollection, $grantee);
                push_authcollection([["$subgrantkey ($grant)" => ""]], $authcollection, $grantee);
            }
        } else {
            // no subelements - push auth directly.
            // applies basiccally to person, status
            // ore recurcive invocation of authcollction with subgrantee
            if (!array_key_exists($grant, $authcollection)) {
                $authcollection[$grant] = [];
            }
            $authcollection[$grant][] = $grantee;
        }
    }
    return null;
}


////////////////////////////////////////////////////////////////////////////////////////////////

// some exra metadata for grouptypes which are not available in churchtools
// todo read type, prefix from CT ...
// be sure that we do not have more than 19 grouptypes
$grouptypedefs = [
    # Gruppentypen
    0 => ['color' => "#ff0000", 'type' => "Fehler", 'prefix' => "ER", 'shape' => "parallelogram"],
    1 => ['color' => "#ffe4c4", 'type' => "Kleingruppe", 'prefix' => "KG", 'shape' => "rectangle"],
    2 => ['color' => "#ff9933", 'type' => "Eventgruppe", 'prefix' => "EG", 'shape' => "rectangle"],
    3 => ['color' => "#00ffff", 'type' => "Organisation", 'prefix' => "OG", 'shape' => "rectangle"],
    4 => ['color' => "#b3ff99", 'type' => "Arbeitsgruppe", 'prefix' => "AG", 'shape' => "rectangle"],
    5 => ['color' => "#ffff00", 'type' => "Verteiler", 'prefix' => "VL", 'shape' => "rectangle"],
    6 => ['color' => "#9177fc", 'type' => "Merkmalsgruppe", 'prefix' => "MG", 'shape' => "rectangle"],
    7 => ['color' => "#9177fc", 'type' => "Hilfsgruppe", 'prefix' => "ZZ", 'shape' => "rectangle"],
    8 => ['color' => "#bfbfbf", 'type' => "Berechtigung", 'prefix' => "RG", 'shape' => "fatarrow"],

    # globale berechtiungsvergabe

    20 => ['color' => "#bfbfff", 'type' => "Status", 'prefix' => "ST", 'shape' => "octagon"],
    21 => ['color' => "#009900", 'type' => "Gruppentyp", 'prefix' => "GRTYP", 'shape' => "hexagon"],
    22 => ['color' => "#ffffff", 'type' => "Global Definition", 'prefix' => "GL", 'shape' => "ellipse"],

    # Gruppentypberechtigungen

    30 => ['color' => "#ffffff", 'type' => "Gruppenrolle", 'prefix' => "GRRL", 'shape' => "ellipse"],
    31 => ['color' => "#eeeeee", 'type' => "Gruppenrollen", 'prefix' => "GRRLN", 'shape' => "ellipse"],
    32 => ['color' => "#ffffff", 'type' => "Gruppentyprolle", 'prefix' => "GTRL", 'shape' => "ellipse"],
    33 => ['color' => "#eeeeee", 'type' => "Gruppentyprollen", 'prefix' => "GTRLN", 'shape' => "ellipse"],
    # braucht man nicht

    # Berechtiungen: Die BER korellieren mit den Actors

    50 => ['color' => "#f0f000", 'type' => "Wiki", 'prefix' => "Wiki", 'shape' => "roundrectangle"],
    60 => ['color' => "#7ebce6", 'type' => "Person", 'prefix' => "PRS", 'shape' => "roundrectangle"],

    90 => ['color' => "#fffff", 'type' => "Legende", 'prefix' => "LG", 'shape' => "rectangle"]
];


// reading masterdata
echo "reading masterdata\n";
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
$authdefinitions = [];  // here we collect auth definitions
$pseudogroups = [];  // here we collect pseudogroups such as GRL, GRRLN

// reading auth by person
echo "creading auth by person\n";
list($personmissing, $personauth) = read_auth_by_person($masterdata_jsonpath, $authdefinitions, $pseudogroups);

// reading auth by status
echo "creading auth by status\n";
list($statusauth) = read_auth_by_status($masterdata_jsonpath, $authdefinitions, $pseudogroups);

// handle grouptypes
echo "creading auth by grouptypes\n";
list($grouptypeauth) = read_auth_by_grouptypes($masterdata_jsonpath, $authdefinitions, $pseudogroups);

// handle groups
echo "creading auth by groups\n";
list($groupmissing, $groupauth) = read_auth_by_groups($masterdata_jsonpath, $authdefinitions, $pseudogroups);

//

$grouphierarchy = new GroupHierarchy();
$grouphierarchy->setgrouptypedefs($grouptypedefs);

// reading groups
echo "creading groups\n";
$report2 = [
    'url' => "$ctdomain/api/groups",
    'method' => "GET",
    'data' => ['page' => 1, 'limit' => 100],
    'body' => [],
];

$report2['response'] = CTV2_sendRequestWithPagination($report2);
$groups = $report2['response']['data'];


// reading hierarchies
echo "creading group hierarchy\n";
$report3 = [
    'url' => "$ctdomain/api/groups/hierarchies",
    'method' => "GET",
    'data' => [],
    'body' => [],
];

$report3['response'] = CTV2_sendRequest($report3);
$hierarchys = [];
foreach ($report3['response']['data'] as $hierarchy) {
    $hierarchys[$hierarchy['groupId']] = $hierarchy;
}

foreach ($groups as $group) {
    $name = $group['name'];
    $groupid = $group['id'];

    //  # work around https://forum.church.tools/topic/7267/api-v2-gruppeninfo-liefert-nicht-immer-einen-gruppentyp
    //  # with group.dig("roles", 0, "groupTypeId") instead of group.dig("information", "groupTypeId")
    $desc = array_key_exists('note', $group['information']) ? $group['information']['note'] : "";
    if (array_key_exists('groupTypeId', $group['information'])) {
        $type = $group['information']['groupTypeId'];
    } else {
        $type = $group['roles'][0]['groupTypeId'];
    }

    $parents = array_key_exists($groupid, $hierarchys) ? $hierarchys[$groupid]['parents'] : [];
    $grouphierarchy->add($name, $parents, ['id' => $groupid, 'type' => $type, 'desc' => $desc]);
}
$grouphierarchy->buildhierarchy(); // to build internal hierarchy

// reading grouptypes and grouptyperoles
echo "creating grouptypes and grouptyperoles\n";
$report4 = [
    'url' => "$ctdomain/api/person/masterdata",
    'method' => "GET",
    'data' => [],
    'body' => [],
];
$report4['response'] = CTV2_sendRequest($report4);

$grouptypes = [];
foreach ($report4['response']['data']['groupTypes'] as $grouptype) {
    $grouptypes[$grouptype['id']] = $grouptype;
    $grouptypename = $grouptype['name'];
    $grouphierarchy->add("GRTYP $grouptypename", ["GRTYP Gruppentyp"]);
}


foreach ($report4['response']['data']['roles'] as $role) {
    $grouptypename = $grouptypes[$role['groupTypeId']]['name'];
    $gtabbreviation = getgrouptypeabbreviation($grouptypename);
    $name = "GTRL {$gtabbreviation} {$role['name']}";
    $grouphierarchy->add($name, ["GRTYP $grouptypename"]);
}


// add pseudogroups
echo "adding pseudogroups\n";
$grouphierarchy->add("GL Globale Rechte");
$grouphierarchy->add("GRTYP Gruppentyp", ["GL Globale Rechte"]);
$grouphierarchy->add("PRS Persons", ["GL Globale Rechte"]);
$grouphierarchy->add("ST Status", ["GL Globale Rechte"]);

///// create result files


$filebase = __DIR__ . "/../responses/{$ctinstance}v1_churchauth--all";

echo "create rubysimulation\n";
create_rubysimulationfile($authdefinitions + $pseudogroups,
    "$filebase.rb",
    $grouphierarchy);

echo "create markdown report\n";
create_markdownreport($authdefinitions,
    "$filebase.md");

echo "create json report\n";
create_jsonreport($authdefinitions,
    "$filebase.json");

// write puml file
echo "create plantuml file\n";
$pumlfile = fopen("$filebase.puml", "w");
fwrite($pumlfile, $grouphierarchy->toplantuml());
fclose($pumlfile);

// write graphml file
echo "create graphml file\n";
$graphmlfile = fopen("$filebase.graphml", "w");
fwrite($graphmlfile, $grouphierarchy->tographml());
fclose($graphmlfile);

// report results

$report['response'] = [
    'auth_by_person' => $personauth,
    'auth_by_status' => $statusauth,
    'auth_by_grouptypes' => $grouptypeauth,
    'auth_by_groups' => $groupauth,
    'debug' => [
        'masterdata' => $masterdata,
        'groupMemberstatus--withUndefined--group_id' => $groupmissing,
        'authdefintions' => $authdefinitions,
        'pseudogroups' => $pseudogroups,
        'grouphierarchy' => $grouphierarchy,
//        'report1' => $report1,
//        'report2' => $report2,
//        'report3' => $report3,
//        'report4' => $report4,
    ]
];

// ths depends on $report
$whocanwhat = create_whocanwhatreport($report['response'],
    "$filebase--whocanwhat");

$whatcanwho = invert_whocanwhat($whocanwhat);
$whoelse = find_roles_in_whatcanwho($whatcanwho);

// write misc json file for further investigation
echo "write misc json\n";

// sensure sorting
ksort($whocanwhat);
ksort($whatcanwho);
ksort($whoelse);

//write data
$miscjsonfile = fopen("$filebase.misc.json", "w");
$miscreport = [];
$miscreport['whocanwhat'] = $whocanwhat;
$miscreport['whatcanhwo'] = $whatcanwho;
$miscreport['whoelse'] = $whoelse;
fwrite($miscjsonfile, json_encode($miscreport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
fclose($miscjsonfile);

