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
 * Renderer for local more notifications plugin
 *
 * @package     local_more_notifications
 * @category    message
 * @copyright   2022 Matthew<matthewfaulkner@apoaevents.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * message Renderer
 *
 * Class for rendering various message objects
 *
 * @package    local_more_notifications
 * @copyright  2022 Matthew<matthewfaulkner@apoaevents.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_more_notifications_renderer extends plugin_renderer_base {

    /**
     * Display the interface to manage both message outputs and default message outputs
     *
     * @param  stdClass $subwiki subwiki object

     * @return string The text to renderv
     */
    public function manage_wiki_notifications_menu($subwiki, $options) {
        $output = html_writer::start_tag('form', array('id' => 'defaultwikimessagesoutput', 'method' => 'post'));
        $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));

        // Add message output processors enabled/disabled and settings.
        $output .= $this->heading(get_string('wikimessageoutputs', 'local_more_notifications'));

        $pages = wiki_get_page_list($subwiki->id);

        $activesubscriptions = local_more_notifications_get_subscriptions($subwiki->id, $pages, $options);
        // Add active message output processors settings.
        $output .= $this->manage_wikipage_messageoutputs($subwiki->id, $pages, $options, $activesubscriptions);

        $output .= html_writer::start_tag('div', array('class' => 'form-buttons'));
        $output .= html_writer::empty_tag('input',
            array('type' => 'submit', 'value' => get_string('savechanges', 'admin'), 'class' => 'form-submit btn btn-primary')
        );
        $output .= html_writer::end_tag('div');
        $output .= html_writer::end_tag('form');

        return $output;
    }


    /**
     * Display the interface to manage page by page message outputs
     *
     * @param  int $subwikiid  subwiki id
     * @param  array $pages array of pages
     * @param array $options types of notifications
     * @return string The text to render
     */
    public function manage_wikipage_messageoutputs($subwikiid, $pages, $options, $activesubscriptions) {
        $context = [];

        $wholewiki = new stdClass();
        $wholewiki->title = get_string('wholewiki', 'local_more_notifications');
        
        foreach($options as $option){
                $setting = new StdClass();

                $settingname = 'wiki'.$subwikiid .'_'. $option['name'];
                $setting->enabledsetting = "enabled[$settingname]";

                $setting->enabled = array_key_exists($settingname, $activesubscriptions);
                    $labelparams = [
                ];
                $setting->enabledlabel = get_string('sendingviaenabled', 'message', $labelparams);


                $wholewiki->settings[] = $setting;
        }


        foreach ($pages as $page){

            foreach($options as $option){
                $setting = new StdClass();

                $settingname = 'page'.$page->id .'_'. $option['name'];
                $setting->enabledsetting = "enabled[$settingname]";

                $setting->enabled = array_key_exists($settingname, $activesubscriptions);

                    $labelparams = [
                ];
                $setting->enabledlabel = get_string('sendingviaenabled', 'message', $labelparams);


                $page->settings[] = $setting;
            }
            

        }

        $context['wholewiki'] = $wholewiki;
        $context['pages'] = array_values($pages);
        $context['options'] = $options;
        return $this->render_from_template('local_more_notifications/default_notification_preferences', $context);
    }


        /**
     * Renderer the digest block.
     * @param object $course
     * @param array $newdata
     * @return string
     */
    public function render_digest($course, $newdata) {  

        return $this->render_from_template('local_more_notifications/digest_message', $newdata);
    }


    protected function resetNumericIndexes(array $array): array {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = $this->resetNumericIndexes($value);

                // Check if all keys are numeric
                if (array_keys($value) === range(0, count($value) - 1)) {
                    // Already numerically indexed, so reindex
                    $array[$key] = array_values($value);
                } else {
                    // Associative array — preserve keys
                    $array[$key] = $value;
                }
            }
        }
        return $array;
    }

}   
