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
 * Library functions
 *
 * @package     local_more_notifications
 * @category    message
 * @copyright   2022 Matthew<you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for local_more_notifications plugin
 *
 * @package    local_more_notifications
 * @copyright  2024 Matthew Faulkner <matthewfaulkner@apoaevents.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_more_notifications_observer {


    /**
     * process notifications for comment created event
     *
     * @param \mod_wiki\event\comment_created $event
     * @return void
     */
    public static function wiki_comment_created(\mod_wiki\event\comment_created $event) {
        
        $data = $event->get_data();
        $other = $data['other'];
        $userid = $data['userid'];

        $pageid = $other['itemid'];

        $users = self::get_users_to_notify($pageid, $userid, SUBSCRIPTION_TYPE_COMMENT);

        if(empty($users)){
            return;
        }

        

    }

    /**
     * process notifications for page created event
     *
     * @param \mod_wiki\event\page_created $event
     * @return void
     */
    public static function wiki_page_created(\mod_wiki\event\page_created $event) {

    }

    /**
     * process notifications for page updated event
     *
     * @param \mod_wiki\event\page_created $event
     * @return void
     */
    public static function wiki_page_updated(\mod_wiki\event\page_updated $event) {

    }

    /**
     * process notifications for page deleted event
     *
     * @param \mod_wiki\event\page_created $event
     * @return void
     */
    public static function wiki_page_deleted(\mod_wiki\event\page_deleted $event) {

    }

    protected static function get_users_to_notify($pageid, $type, $userid){

        $wiki = wiki_get_wiki_from_pageid($pageid);

        if(!local_more_notifications_is_subs_enabled($wiki->id)){
            return [];
        }
        $users = local_more_notifications_get_all_subbed_users($wiki->id, SUBSCRIPTION_TYPE_COMMENT);

        unset($users[$userid]);

        return $users;
    }
}