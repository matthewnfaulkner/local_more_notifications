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
 * Class for processing digest notifications
 *
 * @package     local_more_notifications
 * @category    message
 * @copyright   2022 Matthew<you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_more_notifications;

use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/messagelib.php');
require_once($CFG->dirroot . '/local/more_notifications/lib.php');
/**
 * Class for digest
 */
class digest {
    /**
     * @var $grouprestriction
     */
    private $grouprestriction = array();

    /**
     * @var $timelastsent
     */
    private $timelastsent = array();

    /**
     * @var $digesttype
     */
    private $digesttype = 0;

    /**
     * Get an instance of an object of this class. Create as a singleton.
     * @param boolean $forcenewinstance true, when a new instance should be created.
     * @return report_helper
     */
    public static function get_instance($forcenewinstance = false) {
        static $digest;

        if (isset($digest) && !$forcenewinstance) {
            return $digest;
        }

        $digest = new digest();
        return $digest;
    }

    /**
     * Create an object of the helper for a context.
     *
     */
    private function __construct() {
        $digesttype = get_config('local_more_notifications', 'digesttype');
        $this->digesttype = $digesttype;
    }

    /**
     * Get all new comments and replies since the last subscription senttime.
     * If a new reply is found, its comments is retrieved.
     *
     * @param object $user user
     * @return array, the list of new comments/replies indexed by courseid, contextid, commentid.
     */
    public function get_subscribed_new_comments_and_replies($user, $enabledwikis) {
        global $DB, $CFG;

        $authorfields = local_more_notifications_get_all_user_name_fields();

        //define search structure
        $search = [
            SUBSCRIPTION_TYPE_EDIT => [],
            SUBSCRIPTION_TYPE_COMMENT => [],
            
        ];


        //get subwikis

        list($enabledsql, $enabledparams) = $DB->get_in_or_equal(array_keys($enabledwikis), SQL_PARAMS_NAMED);

        $sql = "SELECT 
                    ROW_NUMBER() OVER () AS rownum,
                    m.*,
                    p.id as pageid, 
                    p.title as pagetitle,
                    s.id as subwikiid, 
                    s.groupid as groupid, 
                    c.shortname as coursename, 
                    c.id as courseid,
                    w.name as wikiname, 
                    w.id as wikiid 
                FROM {local_more_notifications} m
                LEFT JOIN {wiki_pages} p ON (m.targetid = p.id AND m.targettype = :typepage) OR (p.subwikiid = m.targetid AND m.targettype = :typewiki)
                LEFT JOIN {wiki_subwikis} s ON p.subwikiid = s.id
                LEFT JOIN {wiki} w ON s.wikiid = w.id
                LEFT JOIN {course} c ON w.course = c.id
                WHERE m.userid = :userid
                AND (s.wikiid $enabledsql OR p.id IS NULL)
                ORDER BY s.wikiid, s.id, p.id, m.timelastsent";

        $params = [
            'userid' => $user->id,
            'typewiki' => TARGET_TYPE_WIKI,
            'typepage' => TARGET_TYPE_PAGE
        ];

        $pages = $DB->get_records_sql($sql, array_merge($params, $enabledparams));
        
        if(empty($pages)){
            return [[], []];
        }

        $lastlasttime = INF;

        $substoremove = [];

        foreach($pages as $page) {
            $lastlasttime = min($lastlasttime, $page->timelastsent);
            if($page->pageid !== null){
                $search[$page->subtype][$page->pageid] = $page;
            }else if ($page->targettype == TARGET_TYPE_PAGE){
                $substoremove[$page->targetid] = $page;
                $search[$page->subtype][$page->targetid] = $page;
            }   
        }


        $newcomments = [];

        if(!empty($search[SUBSCRIPTION_TYPE_COMMENT])) {
            list($ctxsql, $ctxparams) = $DB->get_in_or_equal(array_values($enabledwikis), SQL_PARAMS_NAMED);
            list($cmtsql, $cmtparams) = $DB->get_in_or_equal(array_keys($search[SUBSCRIPTION_TYPE_COMMENT]), SQL_PARAMS_NAMED);


            //get comments

            $commonparams = [
                'component' => 'mod_wiki',
                'commentarea' => 'wiki_page',
                'userid' => $user->id,
                'timelastsent' => (int)$lastlasttime
            ];
            
            $subtype = SUBSCRIPTION_TYPE_COMMENT;
            $getcommentssql = "SELECT c.id, c.itemid as pageid, c.content, $authorfields, $subtype as subtype, u.id as userid FROM {comments} c
                            JOIN {user} u ON c.userid = u.id
                            WHERE c.contextid $ctxsql
                            AND c.component = :component
                            AND c.commentarea = :commentarea
                            AND c.itemid $cmtsql
                            AND c.userid <> :userid
                            AND c.timecreated > :timelastsent
                            ORDER BY c.contextid, c.itemid";
            
            $newcomments = $DB->get_records_sql($getcommentssql, array_merge($commonparams, $ctxparams, $cmtparams));
        }

        //get edits

        $newedits = [];

        if(!empty($search[SUBSCRIPTION_TYPE_EDIT])) {
            list($editsql, $editparams) = $DB->get_in_or_equal(array_keys($search[SUBSCRIPTION_TYPE_EDIT]), SQL_PARAMS_NAMED);

            $subtype = SUBSCRIPTION_TYPE_EDIT;
            $geteditssql = "SELECT w.pageid,
                                   $authorfields, 
                                   $subtype as subtype,
                                   u.id as userid,
                                   CASE WHEN w.version = 0 THEN 1 END AS created,
                                   COUNT(CASE WHEN w.version > 0 THEN 1 END) AS edit_count
                            FROM {wiki_versions}  w
                            JOIN {user} u ON w.userid = u.id
                            WHERE w.pageid $editsql
                            AND w.userid <> :userid
                            AND w.timecreated > :timelastsent
                            GROUP BY w.pageid
                            ORDER BY w.pageid, w.timecreated ASC";

            $editparams['userid'] = $user->id;
            $editparams['timelastsent'] = $lastlasttime;


            $newedits = $DB->get_records_sql($geteditssql, $editparams);
        }
        $editsandcomments = array_merge($newcomments, $newedits);

        $pagesbywiki = [];


        foreach ($editsandcomments as $newedit) {
            if ($page = $search[$newedit->subtype][$newedit->pageid]) {

                // === Courses ===
                if (!isset($pagesbywiki[$page->courseid])) {
                    $pagesbywiki[$page->courseid] = [
                        'courseid' => $page->courseid,
                        'coursename' => $page->coursename,
                        'wikis' => [],
                        'wikis_list' => []
                    ];
                }
                $course = &$pagesbywiki[$page->courseid];

                // === Wikis ===
                if (!isset($course['wikis'][$page->wikiid])) {
                    $wikidata = [
                        'wikiid' => $page->wikiid,
                        'wikiname' => $page->wikiname,
                        'subwikis' => [],
                        'subwikis_list' => []
                    ];
                    $wikiidx = array_push($course['wikis_list'], $wikidata) - 1;
                    $course['wikis'][$page->wikiid] = ['_idx' => $wikiidx];
                }
                $wikiidx = $course['wikis'][$page->wikiid]['_idx'];
                $wiki = &$course['wikis_list'][$wikiidx];

                // === Subwikis ===
                if (!isset($wiki['subwikis'][$page->subwikiid])) {
                    $subwikidata = [
                        'subwiki' => $page->subwikiid,
                        'pages' => [],
                        'pages_list' => [],
                    ];
                    $subwikiidx = array_push($wiki['subwikis_list'], $subwikidata) - 1;
                    $wiki['subwikis'][$page->subwikiid] = ['_idx' => $subwikiidx];
                }
                $subwikiidx = $wiki['subwikis'][$page->subwikiid]['_idx'];
                $subwiki = &$wiki['subwikis_list'][$subwikiidx];

                // === Pages ===
                if (!isset($subwiki['pages'][$page->pageid])) {
                    $pagedata = [
                        'pageid' => $page->pageid,
                        'pagename' => $page->pagetitle,
                        'pagelink' => new moodle_url('/mod/wiki/view.php', ['pageid' => $page->pageid]),
                        'users' => [],
                        'users_list' => []
                    ];
                    $pageidx = array_push($subwiki['pages_list'], $pagedata) - 1;
                    $subwiki['pages'][$page->pageid] = ['_idx' => $pageidx];
                }
                $pageidx = $subwiki['pages'][$page->pageid]['_idx'];
                $pageitem = &$subwiki['pages_list'][$pageidx];

                // === Users ===
                if (!isset($pageitem['users'][$newedit->userid])) {
                    $userdata = [
                        'userid' => $newedit->userid,
                        'username' => fullname($newedit),
                        'edits' => [],
                        'comments' => [],
                        'commentscount' => 0,
                        'editscount' => 0 
                    ];
                    $useridx = array_push($pageitem['users_list'], $userdata) - 1;
                    $pageitem['users'][$newedit->userid] = ['_idx' => $useridx];
                }
                $useridx = $pageitem['users'][$newedit->userid]['_idx'];
                $user = &$pageitem['users_list'][$useridx];

                // === Add Edit or Comment ===
                $sub = $page->subtype == SUBSCRIPTION_TYPE_COMMENT ? 'comments' : 'edits';
                $user[$sub. "count"] += 1;
                $user[$sub][] = $newedit;
            }
            
        }
        return [$pagesbywiki, $pages, $substoremove];
    }


    /**
     * Get plugin styles.
     * @return string css.
     */
    protected function get_css_styles() {
        global $CFG;
        $css = file_get_contents($CFG->dirroot.'/blocks/socialcomments/styles.css');
        return \html_writer::tag('style', $css);
    }

    /**
     * Send message
     * @param object $userto
     * @param string $messagetext
     * @return int message id
     */
    protected function send_message($userto, $messagetext) {
        $message = new \core\message\message();
        $message->courseid  = SITEID;
        $message->component = 'local_more_notifications';
        $message->name = 'wikidigest';
        $message->userfrom = \core_user::get_user(\core_user::NOREPLY_USER);
        $message->userto = $userto;
        $message->subject = get_string('digestsubject', 'local_more_notifications');
        $message->fullmessage = html_to_text($messagetext, 80, false);
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = $this->get_css_styles().$messagetext;
        $message->notification = 1;

        $messageid = message_send($message);

        return $messageid;
    }

    /**
     * Send out a digest for a user.
     *
     * @param object $user
     * @return boolean true, when successfully sent.
     */
    public function send_digest_for_user($user, $enabledwikis) {
        global $DB, $PAGE;

        list($newdata, $subscriptions, $substoremove) = $this->get_subscribed_new_comments_and_replies($user, $enabledwikis);

        $renderer = $PAGE->get_renderer('local_more_notifications');

        // Render new data for each course.
        foreach ($newdata as $courseid => $bywiki) {

            if (!$course = $DB->get_record('course', array('id' => $courseid))) {
                continue;
            }

            $messagetext = $renderer->render_digest($course, $bywiki);
            $messageid = $this->send_message($user, $messagetext);

        }

        $this->remove_delete_pages_subs($substoremove, $user->id);

        $this->update_subs_timelastsent($subscriptions);
        
        return true;
    }


    protected function update_subs_timelastsent($subscriptions){
        global $DB;

        $now = time();
        foreach($subscriptions as $subscription) {
            $updatedsub = new stdClass();
            $updatedsub->id = $subscription->id;
            $updatedsub->timelastsent = $now;
            $DB->update_record('local_more_notifications', $updatedsub, true); 
        }

    }


    protected function remove_delete_pages_subs($substoremove, $userid) {
        global $DB;

        if(empty($substoremove)){
            return;
        }

        $subids = implode(' AND ', array_keys($substoremove));

        $DB->delete_records('local_more_notifications', ['targetid' => $subids, 'targettype' => TARGET_TYPE_PAGE, 'userid' => $userid]);

        return;
    }

    /**
     * Cron setup.
     * @return boolean
     */
    public static function cron() {
        global $DB;

        $result = true;

        $limit = 0;
        $userspercron = 0;

        $enabledwikis = local_more_notifications_get_enabled_wikis();
        
        if(empty($enabledwikis)){
            //no wikis with notifications enabled
            return;
        }

        $wikicontexts = [];
        foreach($enabledwikis as $wiki) {
            if($cm = get_coursemodule_from_instance('wiki', $wiki->wikiid)){
                $context = \context_module::instance($cm->id);
                $wikicontexts[$wiki->wikiid] = $context->id;
            }
        }

        if(empty($wikicontexts)){
            return;
        }

        if ($userspercron > 0) {
            $limit = $userspercron;
        }

        // Get the users, that have subscriptions
        // (ordered by timelastent ASC to process the long waiting users first.

        $sql = "SELECT userid, targettype, targetid, subtype, MIN(timelastsent) as mintime
                FROM {local_more_notifications} 
                GROUP BY userid
                ORDER BY mintime ASC ";
        
        $userids = $DB->get_records_sql($sql, array(), 0, $limit);

        if (!$userids) {
            return $result;
        }

        $userids = array_keys($userids);

        foreach ($userids as $userid) {

            $user = $DB->get_record('user', array('id' => $userid, 'deleted' => 0));

            if (!$user) {
                continue;
            }

            \core\cron::setup_user();

            $digest = self::get_instance(true);
            $result = ($result && $digest->send_digest_for_user($user, $wikicontexts));
        }
         \core\cron::setup_user();

        return $result;
    }

    protected function get_users_to_notify($pageid, $userid,){

        $wiki = wiki_get_wiki_from_pageid($pageid);

        if(!local_more_notifications_is_subs_enabled($wiki->id)){
            return [];
        }
        $users = local_more_notifications_get_all_subbed_users($wiki->id, SUBSCRIPTION_TYPE_COMMENT);

        unset($users[$userid]);

        return $users;
    }

}
