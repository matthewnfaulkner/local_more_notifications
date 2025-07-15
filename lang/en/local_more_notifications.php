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
 * Plugin strings are defined here.
 *
 * @package     local_more_notifications
 * @category    string
 * @copyright   2022 Matthew<you@example.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['messageprovider:wikicomment'] = 'Comment';
$string['messageprovider:wikicommentdigest'] = 'Digest';
$string['messageprovider:wikiedit'] = 'Wiki Edit';
$string['messageprovider:wikieditdigest'] = 'Wiki Edit Digest';
$string['pluginname'] = 'More Notifications';
$string['processdigest'] = 'Process wiki notifications digest';
$string['wikimessageoutputs'] = 'Update wiki subscriptions';
$string['managesubscriptions'] = "Subscriptions";
$string['wholewiki'] = 'Whole Wiki';
$string['messageheading'] = 'All Wiki Notifications for: {$a}';
$string['messagewikis'] = 'Wiki\'s with Notifications:';
$string['messagewikiheading'] = 'For wiki: {$a}';
$string['messagepages'] = 'Pages';
$string['messagepageheading'] = 'Page: {$a}';
$string['messageuserheading'] = 'User {$a} made following changes.';
$string['edits'] = 'Edits';
$string['messagecreated'] = 'Created Page';
$string['messageeditcount'] = 'Made {$a} edits.';
$string['comments'] = 'Comments';
$string['madecomments'] = 'Made {$a} comment(s):';
$string['digestsubject'] = 'Wiki Digest';
$string['wikinotificationpreferences_help'] = "Below you can select wether to receive notifications for the whole wiki, or on a page by page basis. 
<br>
Subscribing to the whole wiki nullifies any page specific subscriptions. 
<br>
By subscribing to the whole wiki, you will also
receive notifications for any pages created after you set your preferences. Otherwise you will have to manually subscribe to each new page.
<br>
You can use the toggles to choose whether to receive notifications for comments, and/or edits.";
