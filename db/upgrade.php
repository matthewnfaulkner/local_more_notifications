<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file keeps track of upgrades to the local_subscriptions plugin
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 * @package   local_more_notifications
 * @copyright 2024 Matthew Faulkner <matthewfaulkner@apoaevents.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_more_notifications_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    
    // Automatically generated Moodle v3.6.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.7.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.8.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v3.9.0 release upgrade line.
    // Put any upgrade step following this.

    // Automatically generated Moodle v4.0.0 release upgrade line.
    // Put any upgrade step following this.
    if ($oldversion < 2025070902) {

        // Define table local_more_notifications to be created.
        $table = new xmldb_table('local_more_notifications');

        // Adding fields to table local_more_notifications.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('subtype', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('targetid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('targettype', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_more_notifications.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('uniquesubs', XMLDB_KEY_UNIQUE, ['userid', 'subtype', 'targetid', 'targettype']);

        // Conditionally launch create table for local_more_notifications.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // More_notifications savepoint reached.
        upgrade_plugin_savepoint(true, 2025070902, 'local', 'more_notifications');
    }

    if ($oldversion < 2025070903) {

        // Define table local_more_notifications_wik to be created.
        $table = new xmldb_table('local_more_notifications_wik');

        // Adding fields to table local_more_notifications_wik.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('wikiid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');

        // Adding keys to table local_more_notifications_wik.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('uniquewikiid', XMLDB_KEY_UNIQUE, ['wikiid']);

        // Conditionally launch create table for local_more_notifications_wik.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // More_notifications savepoint reached.
        upgrade_plugin_savepoint(true, 2025070903, 'local', 'more_notifications');
    }

        if ($oldversion < 2025070904) {

        // Define field timelastsent to be added to local_more_notifications.
        $table = new xmldb_table('local_more_notifications');
        $field = new xmldb_field('timelastsent', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'targettype');

        // Conditionally launch add field timelastsent.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // More_notifications savepoint reached.
        upgrade_plugin_savepoint(true, 2025070904, 'local', 'more_notifications');
    }


    return true;
}
