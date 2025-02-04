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

defined('MOODLE_INTERNAL') || die('Invalid access');

class filter_warpwire extends moodle_text_filter
{
    public function filter($text, array $options = array())
    {
        global $COURSE, $PAGE, $CFG, $USER;

        // iframe template element
        $iframe_template = '<iframe 
          width="WIDTH"
          height="HEIGHT"
          src="URL"
          frameborder="0"
          scrolling="0"
          allow="autoplay *; encrypted-media *; fullscreen *;"
          title="Warpwire Media"
          allowfullscreen></iframe>';

        // collect information about context to send to iframe
        $modInfo = get_fast_modinfo($COURSE);
        $sections = $modInfo->get_section_info_all();

        // Check if this is serving data to the mobile app.
        $wstoken = null;
        if(WS_SERVER){
            require_once($CFG->dirroot . '/admin/tool/mobile/lib.php');
            $wstoken = tool_mobile_get_token($USER->id);
        }

        // match all warpwire shortcode instances returned from plugins
        if (preg_match_all('/<img.*?>/is', $text, $matches_code)) {
            foreach ($matches_code[0] as $ci => $code) {
                $textToReplace = $code;

                if (preg_match('/\[warpwire:(.*)?\]/is', urldecode($code), $matches_string)) {
                    $url = htmlspecialchars_decode($matches_string[1]);

                    $currentSectionId = null;
                    foreach ($sections as $section) {
                        $sectionContent = $section->summary;
                        if (strstr($sectionContent, urlencode($url)) !== false) {
                            $currentSectionId = $section->id;
                            break;
                        }
                    }

                    // default width and height values for iframe
                    $iframe_width = 480;
                    $iframe_height = 360;

                    $url_parts = parse_url($url);

                    $parameters = array();
                    if (!empty($url_parts['query'])) {
                        parse_str($url_parts['query'], $parameters);
                    }

                    $url_parts['query'] = http_build_query($parameters, '', '&');

                    $url = $url_parts['scheme'].'://'.$url_parts['host'].$url_parts['path'].'?'.$url_parts['query'];

                    $parts = array(
                        'url' => $url,
                        'course_id' => $COURSE->id,
                        'module_id' => isset($PAGE->cm->id) ? $PAGE->cm->id : '',
                        'section_id' => $currentSectionId
                    );

                    // Append wstoken if defined.
                    if(!empty($wstoken)){
                        $parts['wstoken'] = $wstoken->token;
                    }

                    $partsString = http_build_query($parts, '', '&');

                    // TODO: edit here

                    $url = $CFG->wwwroot . '/local/warpwire/?' .$partsString;

                    if (!empty($parameters['width'])) {
                        $iframe_width = $parameters['width'];
                    }
                    if (!empty($parameters['height'])) {
                        $iframe_height = $parameters['height'];
                    }

                    if (class_exists('DOMDocument')) {
                        $doc = new DOMDocument();
                        $doc->loadHTML($code);
                        $imageTags = $doc->getElementsByTagName('img');

                        foreach ($imageTags as $tag) {
                            $iframe_width = $tag->getAttribute('width');
                            $iframe_height = $tag->getAttribute('height');
                        }
                    }
            
                    $patterns = array('/URL/', '/WIDTH/', '/HEIGHT/');
                    $replace = array($url, $iframe_width, $iframe_height);
                    $iframe_html = preg_replace($patterns, $replace, $iframe_template);

                    // replace the shortcode with the iframe html
                    $text = str_replace($textToReplace, $iframe_html, $text);
                }
            }
        }

        // match all warpwire shortcode instances manually inserted
        if (preg_match_all('/\[warpwire(\:(.+))?( .+)?\](.+)?\/a>/isU', $text, $matches_code)) {
            foreach ($matches_code[0] as $index => $code) {
                $textToReplace = $matches_code[0][$index];

                $url = '';
                if (!empty($matches_code[3][$index])) {
                    $url = preg_replace('/^ href=("|\')/', '', $matches_code[3][$index]);
                }

                $url = htmlspecialchars_decode($url);

                $currentSectionId = null;
                foreach ($sections as $section) {
                    $sectionContent = $section->summary;
                    if (strstr($sectionContent, urlencode($url)) !== false) {
                        $currentSectionId = $section->id;
                        break;
                    }
                }

                // default width and height values for iframe
                $iframe_width = 480;
                $iframe_height = 360;

                $url_parts['query'] = http_build_query($parameters, '', '&');

                $url = $url_parts['scheme'].'://'.$url_parts['host'].$url_parts['path'].'?'.$url_parts['query'];

                $parts = array(
                    'url' => $url,
                    'course_id' => $COURSE->id,
                    'module_id' => isset($PAGE->cm->id) ? $PAGE->cm->id : '',
                    'section_id' => $currentSectionId
                );

                // Append wstoken if defined.
                if(!empty($wstoken)){
                    $parts['wstoken'] = $wstoken->token;
                }

                $partsString = http_build_query($parts, '', '&');

                // TODO: edit here

                $url = $CFG->wwwroot . '/local/warpwire/?' .$partsString;

                if (!empty($parameters['width'])) {
                    $iframe_width = $parameters['width'];
                }
                if (!empty($parameters['height'])) {
                    $iframe_height = $parameters['height'];
                }

                $patterns = array('/URL/', '/WIDTH/', '/HEIGHT/');
                $replace = array($url, $iframe_width, $iframe_height);
                $iframe_html = preg_replace($patterns, $replace, $iframe_template);

                // replace the shortcode with the iframe html
                $text = str_replace($textToReplace, $iframe_html, $text);
            }
        }

        return $text;
    }
}
