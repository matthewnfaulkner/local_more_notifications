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

/**
 * Manage notifcation settings for given
 *
 * @package     local_more_notifications
 * @category    message
 * @copyright   2022 Matthew<you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('../../config.php');
require_once('lib.php');
require_once($CFG->dirroot . '/mod/wiki/lib.php');
require_once($CFG->dirroot . '/mod/wiki/locallib.php');
require_once($CFG->dirroot . '/mod/wiki/pagelib.php');

$subwikiid = required_param('subwikiid', PARAM_INT); // Page ID
$option = optional_param('option', 0, PARAM_INT); // Option ID




if (!$subwiki = wiki_get_subwiki($subwikiid)) {
    throw new \moodle_exception('incorrectsubwikiid', 'wiki');
}
if (!$cm = get_coursemodule_from_instance("wiki", $subwiki->wikiid)) {
    throw new \moodle_exception('invalidcoursemodule');
}
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
if (!$wiki = wiki_get_wiki($subwiki->wikiid)) {
    throw new \moodle_exception('incorrectwikiid', 'wiki');
}


$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);


require_course_login($course, true, $cm);


if (!wiki_user_can_view($subwiki, $wiki)) {
    throw new \moodle_exception('cannotviewpage', 'wiki');
}

$context = context_module::instance($cm->id);

require_capability('local/more_notifications:subscribe', $context);


$subscriptionoptions = local_more_notifications_get_subscription_options();

if (($form = data_submitted()) && confirm_sesskey()) {
    $tosubscribe = array();
    $tounsubscribe = array();
    // Prepare default message outputs settings.

    $pages = wiki_get_page_list($subwiki->id);

    foreach($subscriptionoptions as $option) {
            $settingname = 'wiki' . $subwiki->id . '_' . $option['name'];

            $subscription = [
                    'targettype' => TARGET_TYPE_WIKI,
                    'targetid' => $subwiki->id,
                    'userid' => $USER->id,
                    'subtype' => $option['type']
                ];
            if(isset($form->enabled[$settingname])){
                $tosubscribe[] = $subscription;
            }
            else{
                $tounsubscribe[] = $subscription;
            }
    }

    foreach($pages as $page) {
        foreach($subscriptionoptions as $option) {
            $settingname = 'page' . $page->id . '_' . $option['name'];

            $subscription = [
                    'targettype' => TARGET_TYPE_PAGE,
                    'targetid' => $page->id,
                    'userid' => $USER->id,
                    'subtype' => $option['type']
                ];
            if(isset($form->enabled[$settingname])){
                $tosubscribe[] = $subscription;
            }
            else{
                $tounsubscribe[] = $subscription;
            }
        }
    }

    local_more_notifications_save_subscriptions($tosubscribe, $tounsubscribe);

    $url = new moodle_url('manage_wiki_notifications.php', array('subwikiid' => $subwiki->id));
    redirect($url, get_string('subscriptiosnsaved', 'more_notifications'));
}

$renderer = $PAGE->get_renderer('local_more_notifications');



// Print page header


echo $OUTPUT->header();

echo $renderer->manage_wiki_notifications_menu($subwiki, $subscriptionoptions);

echo $OUTPUT->footer();