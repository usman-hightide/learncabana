<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/*

VS SCORM 1.2 RTE - subs.php
Rev 2009-11-30-01
Copyright (C) 2009, Addison Robson LLC

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor,
Boston, MA 02110-1301, USA.

*/

function gb_get_scorm_data($content_id, $registration_id, $var_key = null, $default = "", $user_id = null)
{
    global $scorm_data;
    if(empty($user_id))
    $user_id = get_current_user_id();
    if (empty($user_id))
        return array();

    global $wpdb;
    $scorm_key = $content_id."_".$registration_id;

    if(empty($scorm_data))
    $scorm_data = array();

    if(empty($scorm_data[$scorm_key]))
        $scorm_data[$scorm_key] = array();

    if (empty($scorm_data[$scorm_key])) {

        $result = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."grassblade_scorm_data WHERE user_id = '%d' AND content_id = '%d' AND registration_id = '%s' ORDER BY id", $user_id, $content_id, $registration_id), ARRAY_A);
        if ($result) {
            foreach ($result as $key => $value) {
                $scorm_data[$scorm_key][$value['var_key']] = trim($value['var_value']);
            }
        }
    }

    if(empty($var_key))
        return $scorm_data[$scorm_key];
    else
        return isset($scorm_data[$scorm_key][$var_key])? $scorm_data[$scorm_key][$var_key]:$default;
    /*
    $result = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."grassblade_scorm_data WHERE user_id = '%d' AND content_id = '%d' AND registration_id = '%s' AND var_key = '%s' ORDER BY id DESC LIMIT 1", $user_id, $content_id, $registration_id, $var_key), ARRAY_A);

    if ($result) {
       $scorm_data[ $result[0]['var_key'] ] = $result[0]['var_value'];
    }
    return $scorm_data;
    */
}

/* To Store Additional cmi.interactions.* and cmi.objectives.* fields */
/*
add_filter("gb_scorm_key_check", function($r, $var_key) {
    return ($r ||  strpos($var_key, "cmi.objectives.") === 0 || strpos($var_key, "cmi.interactions.") === 0);
}, 10, 2);
*/

function gb_scorm_key_check($var_key) {
    $unique_fld = ['cmi.core.score.raw','adlcp:masteryscore',
        'cmi.launch_data','cmi.suspend_data','cmi.core.lesson_location','cmi.core.credit',
        'cmi.core.lesson_status','cmi.core.entry','cmi.core.exit','cmi.core.total_time',
        'cmi.core.session_time','cmi.score.raw','adlcp:masteryscore',
        'cmi.launch_data','cmi.suspend_data','cmi.location','cmi.credit',
        'cmi.completion_status','cmi.success_status','cmi.entry','cmi.exit','cmi.total_time',
        'cmi.session_time'];//,'cmi.interactions._count','cmi.objectives._count'];

    $return = (in_array($var_key,$unique_fld));// || strpos($var_key, "cmi.objectives.") === 0 || strpos($var_key, "cmi.interactions.") === 0);

    return apply_filters("gb_scorm_key_check", $return, $var_key);
}
function gb_set_scorm_data($content_id, $registration_id, $var_key, $var_value, $user_id = null)
{
    if( empty($user_id) )
    $user_id = get_current_user_id();
    if (empty($user_id))
        return false;



    if ( gb_scorm_key_check($var_key) ) {
        $result = gb_get_scorm_data($content_id, $registration_id, $var_key, null, $user_id);

        global $wpdb;

        if ( !is_null($result) ) {
            $wpdb->update( $wpdb->prefix."grassblade_scorm_data", array('var_value' => $var_value ), array('user_id' => $user_id,'content_id' => $content_id,'registration_id' => $registration_id,'var_key' => $var_key ));
        } else {
            $data = array(
                        "content_id" => $content_id,
                        "user_id" => $user_id,
                        "registration_id" => $registration_id,
                        "var_key" => $var_key,
                        "var_value" => $var_value,
                    );
            $wpdb->insert($wpdb->prefix."grassblade_scorm_data", $data);
        }
    }
    return true;
}

// ------------------------------------------------------------------------------------
// LMS-specific code
// ------------------------------------------------------------------------------------

function get_gb_scormversion( $dom )
{
    // // adlcp namespace
    $manifest = $dom->getElementsByTagName('manifest');
    $adlcp = $manifest->item(0)->getAttribute('xmlns:adlcp');

    foreach ($manifest as $manifestEl) {
        $metadata = $manifestEl->getElementsByTagName("metadata");
        if ($metadata->item(0)->nodeValue !='') {
            $schema = $metadata->item(0)->getElementsByTagName("schema");
            $schemaversion = $metadata->item(0)->getElementsByTagName("schemaversion");
            $adlcplocation = $metadata->item(0)->getElementsByTagNameNS($adlcp, "location");
            $scorm_version['schema'] = $schema->item(0)->nodeValue;
            $scorm_version['schemaversion'] = $schemaversion->item(0)->nodeValue;
            $scorm_version['adlcplocation'] = @$adlcplocation->item(0)->textContent;
        } else {
            // adlcp namespace
            $adlcp = $manifest->item(0)->getAttribute('xmlns:adlcp');
            // get the organizations element
            $organizationsList = $manifestEl->getElementsByTagName('organizations');
            foreach ($organizationsList as $organizationsListRow) {
                $organizationList = $organizationsListRow->getElementsByTagName('organization');
                foreach ($organizationList as $organizationListRow) {
                    $metadata = $organizationListRow->getElementsByTagName('metadata');
                    foreach ($metadata as $metadataEl) {
                        $schema = $metadataEl->getElementsByTagName("schema");
                        $schemaversion = $metadataEl->getElementsByTagName("schemaversion");
                        $adlcplocation = $metadataEl->getElementsByTagNameNS($adlcp, "location");
                        $scorm_version['schema'] = $schema->item(0)->textContent;
                        $scorm_version['schemaversion'] = $schemaversion->item(0)->textContent;
                        $scorm_version['adlcplocation'] = $adlcplocation->item(0)->textContent;
                    }
                }
            }
        }
    }
    return $scorm_version;
}

function get_gb_masteryscore( $dom )
{

    // adlcp namespace
    $manifest = $dom->getElementsByTagName('manifest');
    $adlcp = $manifest->item(0)->getAttribute('xmlns:adlcp');
	$master_score= 50;

    foreach ($manifest as $manifestEl) {
        // get the organizations element
        $organizationsList = $manifestEl->getElementsByTagName('organizations');
        foreach ($organizationsList as $organizationsListRow) {
            $organizationList = $organizationsListRow->getElementsByTagName('organization');
            foreach ($organizationList as $organizationListRow) {
                $itemNode=$organizationListRow->getElementsByTagName('item');
                foreach ($itemNode as $itemNodeEl) {
                    $master_scoreNode=$itemNodeEl->getElementsByTagNameNS($adlcp, 'masteryscore');
                    if (@$master_scoreNode->item(0)->textContent !='') {
                        $master_score = $master_scoreNode->item(0)->textContent;
                    }
                }
            }
        }
    }
    return $master_score;
}

function gb_read_imsmanifestfile( $dom )
{
    // central array for resource data
    global $resourceData;


    // adlcp namespace
    $manifest = $dom->getElementsByTagName('manifest');
    $adlcp = $manifest->item(0)->getAttribute('xmlns:adlcp');

    // READ THE RESOURCES LIST
    // array to store the results
    $resourceData = array();

    // get the list of resource element
    $resourceList = $dom->getElementsByTagName('resource');
    $r = 0;

    foreach ($resourceList as $rtemp) {

    // decode the resource attributes

        $identifier = $resourceList->item($r)->getAttribute('identifier');
        $resourceData[$identifier]['type'] = $resourceList->item($r)->getAttribute('type');
        if ($resourceList->item($r)->hasAttribute('adlcp:scormtype')) {
            $resourceData[$identifier]['scormtype'] = $resourceList->item($r)->getAttribute('adlcp:scormtype');
        }
        if ($resourceList->item($r)->hasAttribute('adlcp:scormType')) {
            $resourceData[$identifier]['scormtype'] = $resourceList->item($r)->getAttribute('adlcp:scormType');
        }
        $resourceData[$identifier]['href'] = $resourceList->item($r)->getAttribute('href');

        // list of files
        $fileList = $resourceList->item($r)->getElementsByTagName('file');

        $f = 0;

        foreach ($fileList as $ftemp) {
            $resourceData[$identifier]['files'][$f] =  $fileList->item($f)->getAttribute('href');
            $f++;
        }

        // list of dependencies
        $dependencyList = $resourceList->item($r)->getElementsByTagName('dependency');
        $d = 0;
        foreach ($dependencyList as $dtemp) {
            $resourceData[$identifier]['dependencies'][$d] =  $dependencyList->item($d)->getAttribute('identifierref');
            $d++;
        }
        $r++;
    }

    // resolve resource dependencies to create the file lists for each resource
    foreach ($resourceData as $identifier => $resource) {
        $resourceData[$identifier]['files'] = gb_resolveIMSManifestDependencies($identifier);
    }

    // READ THE ITEMS LIST
    // array to store the results

    $itemData = array();

    // get the list of item elements
    $itemList = $dom->getElementsByTagName('item');

    $i = 0;
    foreach ($itemList as $itemp) {

    // decode the item attributes and sub-elements

        $identifier = $itemList->item($i)->getAttribute('identifier');
        $itemData[$identifier]['identifierref'] = $itemList->item($i)->getAttribute('identifierref');
        $itemData[$identifier]['parameters'] = $itemList->item($i)->getAttribute('parameters');

        $itemData[$identifier]['title'] = $itemList->item($i)->getElementsByTagName('title')->item(0)->nodeValue;
        //  $itemData[$identifier]['masteryscore'] = $itemList->item($i)->getElementsByTagNameNS($adlcp,'masteryscore')->item(0)->nodeValue;
        // $itemData[$identifier]['datafromlms'] = $itemList->item($i)->getElementsByTagNameNS($adlcp,'datafromlms')->item(0)->nodeValue;
        $i++;
    }

    // PROCESS THE ITEMS LIST TO FIND SCOS

    // array for the results
    $SCOdata = array();

    // loop through the list of items

    foreach ($itemData as $identifier => $item) {

        // find the linked resource
        $identifierref = $item['identifierref'];

        // is the linked resource a SCO? if not, skip this item
        if(isset($resourceData[$identifierref]['scormtype']) ){
            if (strtolower($resourceData[$identifierref]['scormtype']) != 'sco' ) { continue; }

            // save data that we want to the output array
            $SCOdata[$identifier]['title'] = $item['title'];
            //  $SCOdata[$identifier]['masteryscore'] = $item['masteryscore'];
            //  $SCOdata[$identifier]['datafromlms'] = $item['datafromlms'];
            $SCOdata[$identifier]['href'] = $resourceData[$identifierref]['href'];
            if (isset($item['parameters'])) {
                $SCOdata[$identifier]['href'] = $SCOdata[$identifier]['href'].$item['parameters'];
            }
            $SCOdata[$identifier]['files'] = $resourceData[$identifierref]['files'];
        }
    }
    return $SCOdata;
}

function gb_resolveIMSManifestDependencies($identifier)
{
    global $resourceData;

    if(isset($resourceData[$identifier]) && isset($resourceData[$identifier]['files']))
    $files = $resourceData[$identifier]['files'];
    else
    $files = array();

    if (isset($resourceData[$identifier]['dependencies'])) {
        $dependencies = $resourceData[$identifier]['dependencies'];
        if (is_array($dependencies)) {
            foreach ($dependencies as $d => $dependencyidentifier) {
                $files = array_merge($files, gb_resolveIMSManifestDependencies($dependencyidentifier));
                unset($resourceData[$identifier]['dependencies'][$d]);
            }
            $files = array_unique($files);
        }
    }
    return $files;
}

function gb_getORGdata( $dom )
{
    // ------------------------------------------------------------------------------------
    // Preparations
    // ------------------------------------------------------------------------------------


    // adlcp namespace
    $manifest = $dom->getElementsByTagName('manifest');
    $adlcp = $manifest->item(0)->getAttribute('xmlns:adlcp');

    // READ THE RESOURCES LIST

    // get the organizations element
    $organizationsList = $dom->getElementsByTagName('organizations');

    // iterate over each of the organizations
    foreach ($organizationsList as $organizationsListRow) {
        $organizationList = $organizationsListRow->getElementsByTagName('organization');
        foreach ($organizationList as $organizationListRow) {
            $itemsList = $organizationListRow->getElementsByTagName('item');
            foreach ($itemsList as $itemsListRow) {
                // decode the attributes
                // e.g. <item identifier="I_A001" identifierref="A001">

                $identifier = $itemsListRow->getAttribute('identifier');
                $identifierref = $itemsListRow->getAttribute('identifierref');
                $titleTag = $itemsListRow->getElementsByTagName('title');
                $title = $titleTag->item(0)->nodeValue;

                $description = !empty($itemsListRow->getElementsByTagName('description')->item(0)->nodeValue)? $itemsListRow->getElementsByTagName('description')->item(0)->nodeValue:"";
                $masteryscoreTag = $itemsListRow->getElementsByTagNameNS($adlcp, 'masteryscore');
                $launchdataTag = $itemsListRow->getElementsByTagNameNS($adlcp, 'datafromlms');

                // table row
                $ORGdata[$identifier]['identifier'] = $identifier;
                $ORGdata[$identifier]['identifierref'] = $identifierref;
                $ORGdata[$identifier]['name'] =$title;
                $ORGdata[$identifier]['description'] =$description;
                ;
            }
        }
    }

    $ORGdata["description"] = !empty($manifest->item(0)->getElementsByTagName("description")->item(0)->textContent)? $manifest->item(0)->getElementsByTagName("description")->item(0)->textContent:"";
    return($ORGdata);
}



function gb_cleanVar($value)
{
    $value = (trim($value) == "") ? " " : htmlentities(trim($value));
    return $value;
}