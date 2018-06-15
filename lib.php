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
 * Lib class
 *
 * @package   repository_videofront
 * @copyright 2018 Eduardo Kraus  {@link http://videofront.com.br}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/repository/lib.php');

/**
 * Repository videofront class
 *
 * @package   repository_videofront
 * @copyright 2018 Eduardo Kraus  {@link http://videofront.com.br}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_videofront extends repository {

    public function check_login() {
        return !empty($this->keyword);
    }

    /**
     * Return search results.
     *
     * @param string $searchtext
     * @param int $page
     * @return array|mixed
     * @throws moodle_exception
     */
    public function search($searchtext, $page = 0) {
        global $SESSION;
        $sessionkeyword = 'videofront_' . $this->id . '_keyword';

        if ($page && !$searchtext && isset($SESSION->{$sessionkeyword})) {
            $searchtext = $SESSION->{$sessionkeyword};
        }

        $SESSION->{$sessionkeyword} = $searchtext;

        $this->keyword = $searchtext;
        $ret = array();
        $ret['nologin'] = true;
        $ret['page'] = (int)$page;
        if ($ret['page'] < 1) {
            $ret['page'] = 1;
        }
        $start = ($ret['page'] - 1) * 20 + 1;
        $ret['list'] = $this->search_videos($searchtext, 0, $start);
        $ret['norefresh'] = true;
        $ret['nosearch'] = true;
        // If the number of results is smaller than $max, it means we reached the last page.
        $ret['pages'] = (count($ret['list']) < 20) ? $ret['page'] : -1;
        return $ret;
    }

    /**
     * Private method to get videofront search results
     * @param string $keyword
     * @param int $start
     * @param int $max max results
     * @throws moodle_exception If the google API returns an error.
     * @return array
     */
    private function search_videos($searchtext, $folder, $start) {
        global $CFG;

        $config = get_config('videofront');

        $list = array();
        $error = null;

        require($CFG->dirroot . '/mod/videofront/classes/video.php');
        $videos = video::listing(1, $folder, "{$searchtext}%");

        foreach ($videos->videos as $video) {

            if ($video->VIDEO_TIPO == "video") {

                $thumb = "{$config->url}thumb/get/{$video->VIDEO_IDENTIFIER}/";

                $title = $video->VIDEO_TITULO;
                if (!$title) {
                    $title = $video->VIDEO_FILENAME;
                }

                $list[] = array(
                    'shorttitle' => $title,
                    'title' => $title . ".mp4",
                    'thumbnail_title' => $video->VIDEO_TITULO,
                    'thumbnail' => "{$thumb}?w=128",
                    'thumbnail_height' => 120,
                    'thumbnail_width' => 120,
                    'icon' => "{$thumb}?w=29",
                    'size' => '',
                    'date' => '',
                    'source' => 'videofront://' . $video->VIDEO_IDENTIFIER,
                );
            }
        }

        return $list;
    }

    /**
     * videofront plugin doesn't support global search
     */
    public function global_search() {
        return false;
    }

    public function get_listing($path = '', $page = '') {
        return array();
    }

    /**
     * Generate search form
     */
    public function print_login($ajax = true) {
        $ret = array();
        $search = new stdClass();
        $search->type = 'text';
        $search->id = 'videofront_search';
        $search->name = 's';
        $search->label = get_string('search', 'repository_videofront') . ': ';
        $ret['login'] = array($search);
        $ret['login_btn_label'] = get_string('search');
        $ret['login_btn_action'] = 'search';
        $ret['allowcaching'] = true;
        return $ret;
    }

    /**
     * file types supported by videofront plugin
     * @return array
     */
    public function supported_filetypes() {
        return array('video');
    }

    /**
     * videofront plugin only return external links
     * @return int
     */
    public function supported_returntypes() {
        return FILE_EXTERNAL;
    }

    /**
     * Is this repository accessing private data?
     *
     * @return bool
     */
    public function contains_private_data() {
        return false;
    }
}
