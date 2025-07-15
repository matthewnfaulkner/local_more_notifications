<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

use core_reportbuilder\external\columns\sort\get;

/**
 * Library functions
 *
 * @package     local_more_notifications
 * @category    message
 * @copyright   2022 Matthew<you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 
defined('MOODLE_INTERNAL') || die();


 define('TARGET_TYPE_PAGE', 0);
 define('TARGET_TYPE_WIKI', 1);

 define('SUBSCRIPTION_TYPE_COMMENT', 0);
 define('SUBSCRIPTION_TYPE_EDIT', 1);

 function local_more_notifications_extend_settings_navigation(
    settings_navigation $nav,
    context $context){
        global $COURSE;
        if($context->contextlevel != CONTEXT_MODULE){
            return;
        }

        
        require_capability('local/more_notifications:subscribe', $context);

        if(!$cm = get_coursemodule_from_id('wiki', $context->instanceid, $COURSE->id, false)){
            return;
        }

        if(!local_more_notifications_is_subs_enabled($cm->instance)){
            return;
        }

        if(!$modsettings = $nav->find('modulesettings', navigation_node::TYPE_SETTING)){
                    $modsettings = $nav->add(get_string('pluginadministration', 'mod_wiki'), null, navigation_node::TYPE_SETTING, null, 'modulesettings');
        }

        global $subwiki; 

        if(!$subwiki){
            return;
        }
        
        $modsettings->add(
            get_string('managesubscriptions', 'local_more_notifications'),
            new moodle_url('/local/more_notifications/manage_wiki_notifications.php', array('subwikiid' => $subwiki->id)),
            navigation_node::TYPE_ACTIVITY,
            null,
            'wikisubscribe',
            new pix_icon('i/notifications', 'subscriptions')
        );

        return;
    }

function local_more_notifications_get_subscription_options() {
    return [SUBSCRIPTION_TYPE_COMMENT => [
                'displayname' => get_string('comments', 'local_more_notifications'),
                'name' => 'comments',
                'type' => SUBSCRIPTION_TYPE_COMMENT
            ],
            SUBSCRIPTION_TYPE_EDIT => [
                'displayname' => get_string('edits', 'local_more_notifications'),
                'name' => 'edits',
                'type' => SUBSCRIPTION_TYPE_EDIT
            ]
        ];
}

/**
 * Saves form data as new subscriptions
 * @param array $newsubs array of new subscriptions
 * @param array $tounsubscribe array of unsubscriptions
 * @return void
 */
function local_more_notifications_save_subscriptions(array $tosubscribe = [], array $tounsubscribe = []) {
    global $USER, $DB;

    foreach($tosubscribe as $newsub) {
        if($newsub['userid'] != $USER->id) {
            throw new exception("Inserted record userid does not match current user");
        }
        try {
            $DB->insert_record('local_more_notifications', $newsub);
        }
        catch(dml_exception $e){
            continue;
        }    
    }


    foreach($tounsubscribe as $unsub) {
        if($unsub['userid'] != $USER->id) {
            throw new exception("Deleted record userid does not match current user");
        }

        $DB->delete_records('local_more_notifications', $unsub);
    }

}

function local_more_notifications_get_subscriptions($subwikiid, $pages, $subscriptionoptions) {
    global $DB, $USER;
    
    list($wheres, $params) = $DB->get_in_or_equal(array_keys($pages), SQL_PARAMS_NAMED);

    $sql = "SELECT * FROM {local_more_notifications}    
            WHERE userid = :userid
            AND
            (
                (targetid = :subwikiid AND targettype = :typewiki)
                OR 
                (targetid $wheres AND targettype = :typepage)
            )";

    $params['userid'] = $USER->id;
    $params['subwikiid'] = $subwikiid;
    $params['typewiki'] = TARGET_TYPE_WIKI;
    $params['typepage'] = TARGET_TYPE_PAGE;
    
    if(!$subscriptions = $DB->get_records_sql($sql, $params)){
        return [];
    }

    $subsbysetting = [];

    foreach($subscriptions as $subscription) {
        if($subscription->targettype == TARGET_TYPE_WIKI){
            $setting = 'wiki' . $subscription->targetid . '_' . $subscriptionoptions[$subscription->subtype]['name'];
            $subsbysetting[$setting] = $subscription;
        }
        if($subscription->targettype == TARGET_TYPE_PAGE){
            $setting = 'page' . $subscription->targetid . '_' . $subscriptionoptions[$subscription->subtype]['name'];
            $subsbysetting[$setting] = $subscription;
        }
    }

    return $subsbysetting;
}

function local_more_notifications_coursemodule_standard_elements($modform, $form){

    global $DB;

    if(!$modform instanceof mod_wiki_mod_form){
        return;
    }

    $enabled = 0;

    if($instance = $modform->get_instance()){
        $enabled = $DB->get_field('local_more_notifications_wik', 'enabled', array('wikiid' => $instance));
    }
    

    $form->addElement('header', 'subscriptionsenabledheader', get_string('subscriptionsenabled'));
    $form->addElement('checkbox', 'subscriptionsenabled', get_string('subscriptionsenabled'));

    $form->setDefault('subscriptionsenabled', $enabled);

    }

function local_more_notifications_coursemodule_edit_post_actions($moduleinfo, $course){
    global $DB;

    if($moduleinfo->modulename != 'wiki'){
        return $moduleinfo;
    }
    
    $newrecord = new stdClass();
    $newrecord->wikiid = $moduleinfo->instance;
    $newrecord->enabled = isset($moduleinfo->subscriptionsenabled) ? 1 : 0;

    if($oldrecord = $DB->get_record('local_more_notifications_wik', array('wikiid' => $moduleinfo->instance))){
        $newrecord->id = $oldrecord->id;
        $DB->update_record('local_more_notifications_wik', $newrecord);
    }
    else{
        $DB->insert_record('local_more_notifications_wik', $newrecord);
    }

    return $moduleinfo;

}

function local_more_notifications_pre_course_module_delete($cm) {
    global $DB;

    if($cm->module != $DB->get_field('modules', 'id', array('name' => 'wiki'))){
        return;
    }

    $DB->delete_records('local_more_notifications_wik', array('wikiid' => $cm->instance));
}

function local_more_notifications_get_enabled_wikis() {
    global $DB;

    return $DB->get_records('local_more_notifications_wik', array('enabled' => 1), '', 'wikiid');
}

function local_more_notifications_is_subs_enabled(int $wikiid) {
    global $DB;

    return $DB->record_exists('local_more_notifications_wik', array('wikiid' => $wikiid, 'enabled' => 1));
}

function local_more_notifications_get_all_subbed_users(int $wikiid, int $type) {
    global $DB;

    $subwikis = wiki_get_subwikis($wikiid);

    list($wikisql, $wikiparams) = $DB->get_in_or_equal(array_keys($subwikis), SQL_PARAMS_NAMED);

    $subwikisql = "SELECT id 
            FROM {wiki_pages} p
            WHERE subwikiid $wikisql";

    $pageids = $DB->get_fieldset_sql($subwikisql, $wikiparams);

    list($pagesql, $pageparams) = $DB->get_in_or_equal($pageids, SQL_PARAMS_NAMED);

    $subssql = "SELECT userid FROM
                {local_more_notifications}
                WHERE
                (targetid $wikisql AND targettype = :typesubwiki)
                OR
                (targetid $pagesql AND targettype = :typepage)
                AND subtype = :subtype
                GROUP BY userid";

    $subsparams = [
        'typesubwiki' => TARGET_TYPE_WIKI,
        'typepage' => TARGET_TYPE_PAGE,
        'subtype' => $type
    ];

    return $DB->get_records_sql($subssql, array_merge($pageparams, $subsparams));
}

/**
 * Get user name fields
 */
function local_more_notifications_get_all_user_name_fields() {
    $userfieldsapi = \core_user\fields::for_name();
    $userfields = array_values($userfieldsapi->get_sql('u')->mappings);
    $userfields = implode(",", $userfields);
    return $userfields;
}