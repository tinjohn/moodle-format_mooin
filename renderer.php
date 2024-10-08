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
 * Renderer for outputting the mooin4 course format.
 *
 * @package format_mooin4
 * @copyright 2022 ISy TH Lübeck <dev.ild@th-luebeck.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/course/format/renderer.php');
require_once('locallib.php');

global $PAGE;
// Call jquery amd
//$PAGE->requires->js_call_amd('format_mooin4/complete_section');
//$PAGE->requires->js_call_amd('format_mooin4/section_completion_handler', 'init', [[
//    'section' => $section->id
//]]);

/**
 * Basic renderer for mooin4 format.
 *
 * @copyright 2022 ISy TH Lübeck <dev.ild@th-luebeck.de>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class format_mooin4_renderer extends format_section_renderer_base {

    /**
     * Constructor method, calls the parent constructor.
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);

        // Since format_mooin4_renderer::section_edit_control_items() only displays the 'Highlight' control
        // when editing mode is on we need to be sure that the link 'Turn editing mode on' is available for a user
        // who does not have any other managing capability.
        $page->set_other_editing_capability('moodle/course:setcurrentsection');
    }

    /**
     * Generate the starting container html for a list of sections.
     *
     * @return string HTML to output.
     */
    protected function start_section_list() {
        return html_writer::start_tag('ul', ['class' => 'topics']);
    }

    /**
     * Generate the closing container html for a list of sections.
     *
     * @return string HTML to output.
     */
    protected function end_section_list() {
        return html_writer::end_tag('ul');
    }

    /**
     * Generate the title for this section page.
     *
     * @return string the page title
     */
    protected function page_title() {
        return get_string('topicoutline');
    }

    /**
     * Generate the section title, wraps it in a link to the section page if page is to be displayed on a separate page.
     *
     * @param section_info|stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title($section, $course) {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section));
    }

    /**
     * Generate the section title to be displayed on the section page, without a link.
     *
     * @param section_info|stdClass $section The course_section entry from DB
     * @param int|stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title_without_link($section, $course) {
        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section, false));
    }


/**
     * Generate the edit control items of a section
     *
     * @param stdClass $course The course entry from DB
     * @param stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of edit control items
     */
    protected function section_edit_control_items($course, $section, $onsectionpage = false) {
        if (!$this->page->user_is_editing()) {
            return array();
        }

        global $DB;
        if ($onsectionpage) {
            $url = course_get_url($course, $section->section);
        } else {
            $url = course_get_url($course);
        }
        $url->param('sesskey', sesskey());

        $sectionreturn = $onsectionpage ? $section->section : null;

        $coursecontext = context_course::instance($course->id);
        $numsections = course_get_format($course)->get_last_section_number();
        $isstealth = $section->section > $numsections;

        $baseurl = course_get_url($course, $sectionreturn);
        $baseurl->param('sesskey', sesskey());

        $controls = array();

        if (!$isstealth && has_capability('moodle/course:update', $coursecontext)) {
            if ($section->section > 0
                && get_string_manager()->string_exists('editsection', 'format_'.$course->format)) {
                $streditsection = get_string('editsection', 'format_'.$course->format);
            } else {
                $streditsection = get_string('editsection');
            }

            $controls['edit'] = array(
                'url'   => new moodle_url('/course/editsection.php', array('id' => $section->id, 'sr' => $sectionreturn)),
                'icon' => 'i/settings',
                'name' => $streditsection,
                'pixattr' => array('class' => ''),
                'attr' => array('class' => 'icon edit'));
        }

        if ($section->section && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
                    /*
                    if ($course->marker == $section->section) {  // Show the "light globe" on/off.
                        $url->param('marker', 0);
                        $highlightoff = get_string('highlightoff');
                        $controls['highlight'] = [
                            'url' => $url,
                            'icon' => 'i/marked',
                            'name' => $highlightoff,
                            'pixattr' => ['class' => ''],
                            'attr' => [
                                'class' => 'editing_highlight',
                                'data-action' => 'removemarker'
                            ],
                        ];
                    } else {
                        $url->param('marker', $section->section);
                        $highlight = get_string('highlight');
                        $controls['highlight'] = [
                            'url' => $url,
                            'icon' => 'i/marker',
                            'name' => $highlight,
                            'pixattr' => ['class' => ''],
                            'attr' => [
                                'class' => 'editing_highlight',
                                'data-action' => 'setmarker'
                            ],
                        ];
                    }
                    */
                    if ($chapter = $DB->get_record('format_mooin4_chapter', array('sectionid' => $section->id))) {
                        //$url = new moodle_url('/course/view.php');
                        $url->param('unsetchapter', $section->id);
                        $controls['unsetchapter'] = array(
                            'url' => $url,
                            'icon' => 'i/settings',
                            'name' => get_string('unsetchapter', 'format_mooin4'),
                            'pixattr' => array('class' => ''),
                            //'attr' => array('class' => 'icon editing_delete')
                        );
                    }
                    else {
                        //$url = new moodle_url('/course/view.php');
                        $url->param('setchapter', $section->id);
                        $controls['setchapter'] = array(
                            'url' => $url,
                            'icon' => 'i/settings',
                            'name' => get_string('setchapter', 'format_mooin4'),
                            'pixattr' => array('class' => ''),
                            //'attr' => array('class' => 'icon editing_delete')
                        );
                    }
                }

        if ($section->section) {
            $url = clone($baseurl);
            if (!$isstealth) {
                if (has_capability('moodle/course:sectionvisibility', $coursecontext)) {
                    if ($section->visible) { // Show the hide/show eye.
                        $strhidefromothers = get_string('hidefromothers', 'format_'.$course->format);
                        $url->param('hide', $section->section);
                        $controls['visiblity'] = array(
                            'url' => $url,
                            'icon' => 'i/hide',
                            'name' => $strhidefromothers,
                            'pixattr' => array('class' => ''),
                            'attr' => array('class' => 'icon editing_showhide',
                                'data-sectionreturn' => $sectionreturn, 'data-action' => 'hide'));
                    } else {
                        $strshowfromothers = get_string('showfromothers', 'format_'.$course->format);
                        $url->param('show',  $section->section);
                        $controls['visiblity'] = array(
                            'url' => $url,
                            'icon' => 'i/show',
                            'name' => $strshowfromothers,
                            'pixattr' => array('class' => ''),
                            'attr' => array('class' => 'icon editing_showhide',
                                'data-sectionreturn' => $sectionreturn, 'data-action' => 'show'));
                    }
                }

                if (!$onsectionpage && $section->section != 1) { // Never allow moving section number 1
                    if (has_capability('moodle/course:movesections', $coursecontext)) {
                        $url = clone($baseurl);
                        if ($section->section > 1) { // Add a arrow to move section up.
                            $url->param('section', $section->section);
                            $url->param('move', -1);
                            $strmoveup = get_string('moveup');
                            $controls['moveup'] = array(
                                'url' => $url,
                                'icon' => 'i/up',
                                'name' => $strmoveup,
                                'pixattr' => array('class' => ''),
                                'attr' => array('class' => 'icon moveup'));
                        }

                        $url = clone($baseurl);
                        if ($section->section < $numsections) { // Add a arrow to move section down.
                            $url->param('section', $section->section);
                            $url->param('move', 1);
                            $strmovedown = get_string('movedown');
                            $controls['movedown'] = array(
                                'url' => $url,
                                'icon' => 'i/down',
                                'name' => $strmovedown,
                                'pixattr' => array('class' => ''),
                                'attr' => array('class' => 'icon movedown'));
                        }
                    }
                }
            }


            $chapter = $DB->get_record('format_mooin4_chapter', array('sectionid' => $section->id));
            if (course_can_delete_section($course, $section) && !$chapter) {
                if (get_string_manager()->string_exists('deletesection', 'format_'.$course->format)) {
                    $strdelete = get_string('deletesection', 'format_'.$course->format);
                } else {
                    $strdelete = get_string('deletesection');
                }
                $url = new moodle_url('/course/format/mooin4/editsection.php', array(
                    'id' => $section->id,
                    'sr' => $sectionreturn,
                    'delete' => 1,
                    'sesskey' => sesskey()));
                $controls['delete'] = array(
                    'url' => $url,
                    'icon' => 'i/delete',
                    'name' => $strdelete,
                    'pixattr' => array('class' => ''),
                    'attr' => array('class' => 'icon editing_delete'));
            }
        }

        return $controls;
    }

    /**
     * Generate the edit control items of a section.
     *
     * @param int|stdClass $course The course entry from DB
     * @param section_info|stdClass $section The course_section entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return array of edit control items
     */
    // protected function section_edit_control_items($course, $section, $onsectionpage = false) {
    //     global $DB;

    //     if (!$this->page->user_is_editing()) {
    //         return [];
    //     }

    //     $coursecontext = context_course::instance($course->id);

    //     if ($onsectionpage) {
    //         $url = course_get_url($course, $section->section);
    //     } else {
    //         $url = course_get_url($course);
    //     }
    //     $url->param('sesskey', sesskey());

    //     $controls = [];
    //     if ($section->section && has_capability('moodle/course:setcurrentsection', $coursecontext)) {
    //         /*
    //         if ($course->marker == $section->section) {  // Show the "light globe" on/off.
    //             $url->param('marker', 0);
    //             $highlightoff = get_string('highlightoff');
    //             $controls['highlight'] = [
    //                 'url' => $url,
    //                 'icon' => 'i/marked',
    //                 'name' => $highlightoff,
    //                 'pixattr' => ['class' => ''],
    //                 'attr' => [
    //                     'class' => 'editing_highlight',
    //                     'data-action' => 'removemarker'
    //                 ],
    //             ];
    //         } else {
    //             $url->param('marker', $section->section);
    //             $highlight = get_string('highlight');
    //             $controls['highlight'] = [
    //                 'url' => $url,
    //                 'icon' => 'i/marker',
    //                 'name' => $highlight,
    //                 'pixattr' => ['class' => ''],
    //                 'attr' => [
    //                     'class' => 'editing_highlight',
    //                     'data-action' => 'setmarker'
    //                 ],
    //             ];
    //         }
    //         */
    //         if ($chapter = $DB->get_record('format_mooin4_chapter', array('sectionid' => $section->id))) {
    //             //$url = new moodle_url('/course/view.php');
    //             $url->param('unsetchapter', $section->id);
    //             $controls['unsetchapter'] = array(
    //                 'url' => $url,
    //                 'icon' => 'i/settings',
    //                 'name' => get_string('unsetchapter', 'format_mooin4'),
    //                 'pixattr' => array('class' => ''),
    //                 //'attr' => array('class' => 'icon editing_delete')
    //             );
    //         }
    //         else {
    //             //$url = new moodle_url('/course/view.php');
    //             $url->param('setchapter', $section->id);
    //             $controls['setchapter'] = array(
    //                 'url' => $url,
    //                 'icon' => 'i/settings',
    //                 'name' => get_string('setchapter', 'format_mooin4'),
    //                 'pixattr' => array('class' => ''),
    //                 //'attr' => array('class' => 'icon editing_delete')
    //             );
    //         }
    //     }



    //     $parentcontrols = parent::section_edit_control_items($course, $section, $onsectionpage);

    //     // If the edit key exists, we are going to insert our controls after it.
    //     if (array_key_exists("edit", $parentcontrols)) {
    //         $merged = [];
    //         // We can't use splice because we are using associative arrays.
    //         // Step through the array and merge the arrays.
    //         foreach ($parentcontrols as $key => $action) {
    //             $merged[$key] = $action;
    //             if ($key == "edit") {
    //                 // If we have come to the edit key, merge these controls here.
    //                 $merged = array_merge($merged, $controls);
    //             }
    //         }

    //         return $merged;
    //     } else {
    //         return array_merge($controls, $parentcontrols);
    //     }
    // }

     /**
     * Return the navbar content in specific section so that it can be echoed out by the layout
     *
     * @return string XHTML navbar
     */
    public function navbar($displaysection = 0) {
        global $COURSE;
        $items = $this->page->navbar->get_items();
        $itemcount = count($items);
        if ($itemcount === 0) {
            return '';
        }

        $htmlblocks = array();
        // Iterate the navarray and display each node
        $separator = get_separator();
        for ($i=0;$i < $itemcount;$i++) {
            if( $displaysection == 0) {
                $val = $COURSE->shortname;
                $item = $items[$i];
                $item->hideicon = true;
                if ($i===0) {
                    $content = html_writer::tag('li', $this->render($item));
                } else
                if($i === $itemcount - 2) {
                    $content = html_writer::tag('li', '  ');
                }else
                if ($i === $itemcount - 1) {
                    $content = html_writer::tag('li', '  '. ' / '.$val); // $separator.$this->render($item)
                } else {
                    $content = '';
                }
            } else {

                $val  = ' Kap. '. ' '.$displaysection .' / Lek.  '. ' ' .$displaysection .'.'. $displaysection . ':';
                $item = $items[$i];
                $item->hideicon = true;
                if ($i===0) {
                    $content = html_writer::tag('li', '  '); // $this->render($item)
                } else
                if($i === $itemcount - 2) {
                    $content = html_writer::tag('li', '  '. $this->render($item));
                }else
                if ($i === $itemcount - 1) {
                    $content = html_writer::tag('li', '  '. ' / '.$val); // $separator.$this->render($item)
                } else {
                    $content = '';
                }
            }
            /*  {
                $content = html_writer::tag('li', $separator.$this->render($item));
            } */
            $htmlblocks[] = $content;
        }

        //accessibility: heading for navbar list  (MDL-20446)
        $navbarcontent = html_writer::tag('span', get_string('pagepath'),
                array('class' => 'accesshide', 'id' => 'navbar-label'));
        // $navbarcontent .= html_writer::start_tag('nav', array('aria-labelledby' => 'navbar-label'));

        $navbarcontent .= html_writer::tag('nav',
                html_writer::tag('ul', join('', $htmlblocks),array('class' => "navmenu", 'id'=> 'menu'),array('aria-labelledby' => 'navbar-label')),
                );
        // $navbarcontent .= html_writer::start_tag('ul', array('id' => "menu"));
        // XHTML
        return $navbarcontent;
    }

    /**
     * Generate next/previous section links for naviation
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections The course_sections entries from the DB
     * @param int $sectionno The section number in the course which is being displayed
     * @return array associative array with previous and next section link
     */
    protected function get_nav_links($course, $sections, $sectionno) {
        global $DB;
        // FIXME: This is really evil and should by using the navigation API.
        $course = course_get_format($course)->get_course();
        $canviewhidden = has_capability('moodle/course:viewhiddensections', context_course::instance($course->id))
            or !$course->hiddensections;

        $links = array('previous' => '', 'previous_top' => '', 'next' => '', 'next_top' => '');
        $back = $sectionno - 1;

        foreach ($sections as $section) {
            if ($chapter = $DB->get_record('format_mooin4_chapter', array('sectionid' => $sections[$section->section]->id))) {
                $sections[$section->section]->name = $chapter->title;
                $sections[$section->section]->ischapter = true;
            }
            else {
                $sections[$section->section]->ischapter = false;
            }
        }

        while ($back > 0 and empty($links['previous'])) {

            if (($canviewhidden || $sections[$back]->uservisible) && empty($sections[$back]->ischapter)) { // $sections[$back]->ischapter == false
                $params = array();
                if (!$sections[$back]->visible) {
                    $params = array('class' => 'dimmed_text');
                }
                $previouslink = html_writer::tag('span', $this->output->larrow(), array('class' => 'larrow'));
                if(isset($sections[$back]->ischapter)) {
                    $previouslink .= get_string('previous_lesson','format_mooin4');
                } else {
                    $previouslink .= get_string('previous_chapter','format_mooin4');
                }

                $links['previous'] = html_writer::link(course_get_url($course, $back), $previouslink, $params);

                $previouslink_top = html_writer::tag('span', $this->output->larrow(), array('class' => 'larrow'));
                $links['previous_top'] = html_writer::link(course_get_url($course, $back), $previouslink_top, $params);
            }
            $back--;
        }

        $forward = $sectionno + 1;
        /*
        if (isset($sections[$forward]->id)) {
            if ($chapter = $DB->get_record('format_mooin4_chapter', array('sectionid' => $sections[$forward]->id))) {
                $sections[$forward]->name = $chapter->title;
                $sections[$forward]->ischapter = true;
            }else if ($sections[$forward]) {
                $sections[$forward]->ischapter = false;
            }
        }
        */

        $numsections = course_get_format($course)->get_last_section_number();
        while ($forward <= $numsections and empty($links['next'])) {
            if (($canviewhidden || $sections[$forward]->uservisible) && empty($sections[$forward]->ischapter)) { // $sections[$forward]->ischapter == false
                $params = array();
                if (!$sections[$forward]->visible) {
                    $params = array('class' => 'dimmed_text');
                }
                if(isset($sections[$forward]->ischapter)) {
                    $nextlink = get_string('next_lesson','format_mooin4');

                } else {
                    $nextlink = get_string('next_chapter','format_mooin4');
                    $params = array('class' => 'next_chapter');
                }

                $nextlink .= html_writer::tag('span', $this->output->rarrow(), array('class' => 'rarrow'));
                $links['next'] = html_writer::link(course_get_url($course, $forward), $nextlink, $params);

                $nextlink_top = html_writer::tag('span', $this->output->rarrow(), array('class' => 'rarrow'));
                $links['next_top'] = html_writer::link(course_get_url($course, $forward), $nextlink_top, $params);
            }
            $forward++;
        }

        return $links;
    }

    /**
     * Output the html for a multiple section page
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     */
    public function print_multiple_section_page($course, $sections, $mods, $modnames, $modnamesused) {

        $out = null;

        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        $context = context_course::instance($course->id);

        $out .= $this->output->heading(get_string('topicoutline','format_mooin4'), 2, ''); //accesshide
        //$out .= $this->output->heading($this->page_title(), 2, ''); //accesshide

        // Copy activity clipboard..
        $out .= $this->course_activity_clipboard($course, 0);

        // Now the list of sections..
        $out .= $this->start_section_list();
        $numsections = course_get_format($course)->get_last_section_number();

        foreach ($modinfo->get_section_info_all() as $section => $thissection) {
            if ($section == 0 && !$this->page->user_is_editing()) {
                continue;
            }

            if ($section > $numsections) {
                // activities inside this section are 'orphaned', this section will be printed as 'stealth' below
                continue;
            }
            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display,
            // OR it is hidden but the course has a setting to display hidden sections as unavilable.
            $showsection = $thissection->uservisible ||
                    ($thissection->visible && !$thissection->available && !empty($thissection->availableinfo)) ||
                    (!$thissection->visible && !$course->hiddensections);
            if (!$showsection) {
                continue;
            }
            $course->coursedisplay = 1;
            if (!$this->page->user_is_editing()) {
                // Display section summary only.
                $out .= $this->section_summary($thissection, $course, null);
            } else {
                $out .= $this->section_header($thissection, $course, false, 0);
                $out .= $this->section_footer();
            }
        }

        $changenumsection = '';
        if ($this->page->user_is_editing() and has_capability('moodle/course:update', $context)) {
            // Print stealth sections if present.
            foreach ($modinfo->get_section_info_all() as $section => $thissection) {
                if ($section <= $numsections or empty($modinfo->sections[$section])) {
                    // this is not stealth section or it is empty
                    continue;
                }
                $out .= $this->stealth_section_header($section);
                $out .= $this->courserenderer->course_section_cm_list($course, $thissection, 0);
                $out .= $this->stealth_section_footer();
            }

            $out .= $this->end_section_list();

            $changenumsection = $this->change_number_sections($course, 0);
            //$out .= $this->change_number_sections($course, 0);
        } else {
            $out .= $this->end_section_list();
        }
        $templatecontext = ['topics' => $out,
                            'changenumsection' => $changenumsection];
       return $templatecontext;
    }

    /**
     * Output the html for a single section page .
     *
     * @param stdClass $course The course entry from DB
     * @param array $sections (argument not used)
     * @param array $mods (argument not used)
     * @param array $modnames (argument not used)
     * @param array $modnamesused (argument not used)
     * @param int $displaysection The section number in the course which is being displayed
     */
    public function print_single_section_page($course, $sections, $mods, $modnames, $modnamesused, $displaysection) {
        global $PAGE, $DB, $USER, $OUTPUT;
        $PAGE->requires->js_call_amd('theme_mooin4/navigation-header', 'scrollHeader');


        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();
        $modules_course = $DB->get_records('course_modules', array('course' => $course->id));
        $section_course = $DB->get_records('course_sections', array('course' =>$course->id));
        $sections = ($DB->count_records('course_sections', ['course' =>$course->id])) - 1;


        $modinfo = get_fast_modinfo($course);
        $course = course_get_format($course)->get_course();

        // Can we view the section in question?
        if (!($sectioninfo = $modinfo->get_section_info($displaysection)) || !$sectioninfo->uservisible) {
            // This section doesn't exist or is not available for the user.
            // We actually already check this in course/view.php but just in case exit from this function as well.
            print_error('unknowncoursesection', 'error', course_get_url($course),
                format_string($course->fullname));
        }
        // $PAGE->navbar->ignore_active();
        // $PAGE->navbar->add('/ Kap.'.$displaysection);
        // Copy activity clipboard..

        // ADDED tina john 20240830. Optional hidden h5p access links
        $css = '';
        if (!$this->page->user_is_editing() && !$course->displayh5picons) {
            $css .=
            '.h5pactivity .activity-instance {
              display: none !important;
            }';
            $css .= 
                '.description .course-description-item {
                background-color: #fff;
                 padding-left: 0rem;
                 padding-right: 0rem;
            }';
        }
        if ($css) {
            echo html_writer::tag('style', $css);
        }
        // End.


        $thissection = $modinfo->get_section_info(0);
        if ($this->page->user_is_editing()) {
            echo $this->start_section_list();
            echo $this->section_header($thissection, $course, true, $displaysection);
            echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
            echo $this->courserenderer->course_section_add_cm_control($course, 0, $displaysection);
            echo $this->section_footer();
            echo $this->end_section_list();
        }

        


        // Start single-section div
        echo html_writer::start_tag('div', array('class' => 'single-section'));

        // The requested section page.
        $thissection = $modinfo->get_section_info($displaysection);
        // $PAGE->navbar->add('Perial'.$displaysection);
        // nav_bar_in_single_section($course, $displaysection);
       // var_dump($PAGE->context );

       /* for ($i=1; $i <= $sections; $i++) {
            if (array_key_exists('btnComplete-'.$i, $_POST) && $i == $displaysection) {
                complete_section($USER->id, $course->id, $i);
            }
       } */

        // Title with section navigation links.
        $sectionnavlinks = $this->get_nav_links($course, $modinfo->get_section_info_all(), $displaysection);
        //$PAGE->requires->js_call_amd('format_mooin4/complete_section', [['link' => $sectionnavlinks['next']]]);


        $sectiontitle = '';


        $sectiontitle .= html_writer::start_tag('div', array('id' => 'custom-top-nav', 'class' => 'section-navigation navigationtitle'));

        // breadcrumb come here
        // Custom Navbar in single-section display by different view ( Desktop & Mobile )

        //$sectiontitle .= html_writer::start_tag('div', array('class' => 'custom-navbar'));
        $sectiontitle .= course_navbar();
        //$sectiontitle .= html_writer::end_tag('div');

        // $sectiontitle .= html_writer::start_tag('div', array('class' => 'custom-navbar-mobile'));
        // $sectiontitle .= navbar_mobile($displaysection);
        // $sectiontitle .= html_writer::end_tag('div');


        $sectiontitle .= html_writer::start_tag('div', array('id' => 'sectionname-container', 'class' => 'inner-title-navigation'));




        $sectiontitle .= html_writer::tag('span', $sectionnavlinks['previous_top'], array('class' => 'mdl-left', 'aria-label' => get_string('previous_lesson', 'format_mooin4'))); //Screenreader?
        // Title attributes
        $classes = 'sectionname';
        if (!$thissection->visible) {
            $classes .= ' dimmed_text';

        }
        $sectionname = html_writer::tag('span', $this->section_title_without_link($thissection, $course));
        $sectiontitle .= $this->output->heading($sectionname, 3, $classes);
        $sectiontitle .= html_writer::tag('span', $sectionnavlinks['next_top'], array('class' => 'mdl-right', 'aria-label' => get_string('next_lesson', 'format_mooin4')));

        $sectiontitle .= html_writer::end_tag('div');

        // Progress bar anzeige
          //$check_sequence = $DB->get_records('course_sections', ['course' => $course->id, 'section' => $displaysection], '', '*');
          //$val = array_values($check_sequence);
          //var_dump($val[0]);
        if (!$this->page->user_is_editing() ) { // &&  !empty($val[0]->sequence)
                // Get the right section from DB to the use in the get_progress
                // Check if the sequence in course_sections is a list or a single element
/*
                $element = $DB->get_record('course_modules', ['id'=> $val[0]->sequence], 'section', IGNORE_MISSING);
                //echo "Element";
                // var_dump($element);
                if(!empty($val[0]->sequence) ) {
                    $sec_in_course_modules = $element->section;
                    $v =  get_progress($course->id, $sec_in_course_modules); // get_section_grades($displaysection);
                    // var_dump($v); // $sec_in_course_modules

                    if (gettype($v) == 'array') {
                        $ocp = round($v['percentage']);
                    } else {
                        $ocp = round($v);
                    }

                    if ($ocp != -1) {

                    // Save the section for user in user_prefernces
                        $value = $USER->id . '-' . $course->id . '-' . strval($v['sectionid']);
                        $label_complete = $DB->record_exists('user_preferences',
                            array('name' => 'section_progress_label-'.$value,
                                'value' => $value));

                        if (!$label_complete && $ocp == 100) {
                            // Save the section into the DB user_prefernces so later, we can use it
                            $preferences = [
                                'section_progress_label-'.$value => $value,
                            ];
                            set_user_preferences($preferences, $USER->id);
                        }
                        $sectiontitle .=  get_progress_bar($ocp, 100, $sec_in_course_modules); // $displaysection
                    } else {

                        $completionthistile = section_progress($modinfo->sections[$displaysection], $modinfo->cms); // $sec_in_course_modules
                        // var_dump($modinfo->cms);
                        // use the completion_indicator to show the right percentage in secton
                        $section_percent = completion_indicator($completionthistile['completed'], $completionthistile['outof'], true, false);
                        $sectiontitle .=  get_progress_bar($section_percent['percent'], 100, $sec_in_course_modules); // $displaysection
                    }
                } else {

                    $completionthistile = section_empty($val[0]);
                    // use the completion_indicator to show the right percentage in secton
                    $section_percent = completion_indicator($completionthistile['completed'], $completionthistile['outof'], true, false);
                    $sectiontitle .=  get_progress_bar($section_percent['percent'], 100, $val[0]->id); // $displaysection

                }
                */

            $section_progress = get_section_progress($course->id, $thissection->id, $USER->id);
            $sectiontitle .=  get_progress_bar($section_progress, 100, $thissection->id); // $displaysection
        }


        $sectiontitle .= html_writer::end_tag('div');

        echo $sectiontitle;
        // Now the list of sections..

        echo $this->start_section_list();

        echo $this->section_header($thissection, $course, true, $displaysection);

        echo $this->courserenderer->course_section_cm_list($course, $thissection, $displaysection);
        echo $this->courserenderer->course_section_add_cm_control($course, $displaysection, $displaysection);

        // TODO remove all the following lines until new complete section button
        // new complete section button
        // no activities in this section?
        if (!$coursemodules = $DB->get_records('course_modules', array('course' => $course->id,
                                                                    'deletioninprogress' => 0,
                                                                    'section' => $thissection->id,
                                                                    'completion' => 2))) {

            $complete_button = '';
            if (get_user_preferences('format_mooin4_section_completed_'.$thissection->id, 0, $USER->id) == 0) {
                $complete_button .= html_writer::start_tag('button', array('data-action' => 'format_mooin4/section_completion_handler-button', 'type' => 'button', 'class'=>'comp_btn btn-outline-secondary btn_comp bottom_complete-' .$course->id, 'id' => 'id_bottom_complete-' .$thissection->id, 'name'=> 'btnComplete-' . $displaysection,'value' => get_string('mark_page_as_read', 'format_mooin4')));

                $complete_button .= get_string('mark_page_as_read', 'format_mooin4');
                $complete_button .= html_writer::end_tag('button');
            }
            else {
                $complete_button .= html_writer::start_tag('button', array('class'=>'comp_btn completed btn-secondary complete_section-' .$thissection->id, 'id' => 'id_bottom_complete-' .$thissection->id, "disabled" => "true"));

                $complete_button .= html_writer::start_span('bottom_button-' .$thissection->id) . get_string('page_read', 'format_mooin4') . html_writer::end_span();
                $complete_button .= html_writer::end_tag('button');
            }
            echo $complete_button;
        }
//*/


        echo $this->section_footer();
        echo $this->end_section_list();

                                // find a way to show the popup in a section after completion
                                $course_progress = get_course_progress($course->id, $USER->id);
                                $value = 'format_mooin4_course_completed_'.$USER->id. '_'.$course->id;

                                $sql = 'SELECT up.*
                                        FROM {user_preferences} up
                                        WHERE up.name = :name AND up.userid = :user_id';
                                $param = ['name'=> $value, 'user_id'=>$USER->id];

                                $value_exist = $DB->record_exists_sql($sql, $param);

                                $modal_out = null;
                                    $modal_out .= html_writer::start_tag('div', ['id'=>'myModal', 'class'=>'modal_style']);
                                    $modal_out .= html_writer::start_tag('div', ['class'=>'modal_content_style']);
                                    $modal_out .= html_writer::span(get_string('modal_course_complete_title', 'format_mooin4'), 'modal-title');

                                    $modal_out .= html_writer::start_span('close_style') . html_writer::end_span();
                                    $modal_out .= html_writer::nonempty_tag('h3', get_string('modal_course_complete', 'format_mooin4'), null);
                                    $modal_out .= html_writer::span('', 'bi bi-mortarboard-fill');
                                    //$modal_out .= html_writer::nonempty_tag('p', get_progress_bar_course($course_progress, 100), null);
                                    $modal_out .= html_writer::span('Schliessen', 'modal_button_close mooin4-btn btn-primary text_close', array('role' => 'button'));

                                    $modal_out .= html_writer::end_tag('div');
                                    $modal_out .= html_writer::end_tag('div');

                                // end
        // Second Modal display
        $modal_last_section = null;
        $modal_last_section .= html_writer::start_tag('div', ['id'=>'myModal', 'class'=>'modal_style']);
        $modal_last_section .= html_writer::start_tag('div', ['class'=>'modal_content_style']);
        $modal_last_section .= html_writer::span(get_string('modal_last_section_of_chapter_title','format_mooin4'), 'modal-title');

        $modal_last_section .= html_writer::start_span('close_style') . html_writer::end_span();
        $modal_last_section .= html_writer::nonempty_tag('h3', get_string('modal_last_section_of_chapter','format_mooin4'), null);
        $modal_last_section .= html_writer::span('', 'bi bi-star');

        $modal_last_section .= html_writer::span('Schliessen', 'modal_button_close mooin4-btn btn-primary text_close', array('role' => 'button'));
        $modal_last_section .= html_writer::end_tag('div');
        $modal_last_section .= html_writer::end_tag('div');

        // Get chapter

        $chapter_position_in_course = get_chapter_for_section($thissection->id);
        $chapter_info = $DB->get_record('format_mooin4_chapter', ['courseid'=>$course->id, 'chapter'=>$chapter_position_in_course]);
        // End Modal

        $check_completed_chapter = get_chapter_info($chapter_info);

        // Last Modal complete Kapitel in course
        $modal_kapitel_completed = null;
        $modal_kapitel_completed .= html_writer::start_tag('div', ['id'=>'myModal', 'class'=>'modal_style']);
        $modal_kapitel_completed .= html_writer::start_tag('div', ['class'=>'modal_content_style']);
        $modal_kapitel_completed .= html_writer::span(get_string('modal_chapter_complete_title','format_mooin4'), 'modal-title');
        $modal_kapitel_completed .= html_writer::start_span('close_style') . html_writer::end_span();
        $modal_kapitel_completed .= html_writer::nonempty_tag('h3', get_string('modal_chapter_complete','format_mooin4'), null);
        $modal_kapitel_completed .= html_writer::span('', 'bi bi-check-circle');

        //$modal_kapitel_completed .= html_writer::start_span('done_style')  . html_writer::end_span();
        $modal_kapitel_completed .= html_writer::start_tag('div', ['class'=>'modal_bottom_style']);
        // $modal_kapitel_completed .= html_writer::start_tag('button', ['class'=>'modal_button_close btn-primary']);
        // $modal_kapitel_completed .= html_writer::start_span('text_close') . 'SCHLIESSEN' . html_writer::end_span();
        // $modal_kapitel_completed .= html_writer::end_tag('button');

        if ($sectionnavlinks['next'] !== "") {
            $modal_kapitel_completed .= html_writer::span('Schliessen', 'modal_button_close mooin4-btn btn-white text_close', array('role' => 'button'));

            // $modal_kapitel_completed .= html_writer::start_tag('button', ['class'=>'modal_button_close btn-primary']);
            // $modal_kapitel_completed .= html_writer::tag('span', $sectionnavlinks['next'], array('class' => 'modal_btn_next_chapter mdl-right'));
            // $modal_kapitel_completed .= html_writer::end_tag('button');
            $modal_kapitel_completed .= html_writer::tag('span', $sectionnavlinks['next'], array('class' => 'modal_btn_next_chapter mooin4-btn mooin4-btn-primary'));
        } else {
            $modal_kapitel_completed .= html_writer::span('Schliessen', 'modal_button_close mooin4-btn btn-primary text_close', array('role' => 'button'));

        }
       $modal_kapitel_completed .= html_writer::end_tag('div');
        $modal_kapitel_completed .= html_writer::end_tag('div');
        $modal_kapitel_completed .= html_writer::end_tag('div');

        //$PAGE->requires->js_call_amd('format_mooin4/modalTest', 'completeModal');

       

        $isLastSectionOfChapter = false;
        if (!$this->page->user_is_editing() && is_last_section_of_chapter($thissection->id) && $check_completed_chapter['completed'] == false) {
            //$chapter = get_parent_chapter($thissection);
            if (!get_user_preferences('hide_modal_for_section_'.$thissection->id)) {
                $isLastSectionOfChapter = true;
                set_user_preference('hide_modal_for_section_'.$thissection->id, 'true');
            }
            
        }
        $PAGE->requires->js_call_amd('format_mooin4/section_completion_handler', 'init',
          [[
             'section_id' => $thissection->id,
             'isLastSectionOfChapter' => $isLastSectionOfChapter,
             'courseCompletedAlready' => is_course_completed($course->id)

         ]]);
         $PAGE->requires->js_call_amd('format_mooin4/modalGenerator', 'init');


        /*
        if (!$this->page->user_is_editing() && intval($course_progress) == intval(100) && !$value_exist) { // && !$value_exist
            //  && $v->value == 1

            // $modal_out .= html_writer::start_tag('div', ['class'=>]);
            $PAGE->requires->js_call_amd('format_mooin4/show_popup');
            echo $modal_out;
            set_user_preference('format_mooin4_course_completed_'.$USER->id . '_'. $course->id, 1, $USER->id);
            //$PAGE->requires->js('format_mooin4/show_popup');

        } else if ((!$this->page->user_is_editing()  && $check_completed_chapter['completed'] == true && is_last_section_of_chapter($thissection->id)) || (
         !$this->page->user_is_editing()  && is_section_completed($chapter_info->courseid, $thissection) && is_last_section_of_chapter($thissection->id) && $check_completed_chapter['completed'] == true)) {

            $PAGE->requires->js_call_amd('format_mooin4/show_popup');
            echo $modal_kapitel_completed;
        }
        //*/
        // End
        // Display section bottom navigation.
        $sectionbottomnav = '';
        $sectionbottomnav .= html_writer::start_tag('div', array('class' => 'section-navigation mdl-bottom'));
        $sectionbottomnav .= html_writer::tag('span', $sectionnavlinks['previous'], array('class' => 'mdl-left', 'aria-label' => get_string('previous_lesson', 'format_mooin4')));

        $sectionbottomnav .= html_writer::tag('span', $sectionnavlinks['next'], array('class' => 'mdl-right', 'aria-label' => get_string('next_lesson', 'format_mooin4')));
        /* $sectionbottomnav .= html_writer::tag('div', $this->section_nav_selection($course, $sections, $displaysection),
            array('class' => 'mdl-align')); */
       /*  $sectionbottomnav .= html_writer::tag('div', $this->section_nav_selection($course, $sections, $displaysection),
            array('class' => 'mdl-align')); */
        $sectionbottomnav .= html_writer::end_tag('div');
        echo $sectionbottomnav;

        // Close single-section div.
        echo html_writer::end_tag('div');
        //$PAGE->requires->js_call_amd('format_mooin4/test', 'init');
    }

    /**
     * Generate the content to displayed on the right part of a section
     * before course modules are included
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a section page
     * @return string HTML to output.
     */
    protected function section_right_content($section, $course, $onsectionpage) {
        $o = $this->output->spacer();

        $controls = $this->section_edit_control_items($course, $section, $onsectionpage);
        $o .= $this->section_edit_control_menu($controls, $course, $section);

        return $o;
    }

    /**
     * Generate the display of the header part of a section before
     * course modules are included
     * This function is used if user is editing
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $onsectionpage true if being printed on a single-section page
     * @param int $sectionreturn The section to return to after an action
     * @return string HTML to output.
     */
    protected function section_header($section, $course, $onsectionpage, $sectionreturn=null) {
        global $DB;
        $o = '';
        $currenttext = '';
        $sectionstyle = '';

        if ($section->section == 0) {
            //$sectionstyle = ' hidden';
        }

        if ($section->section != 0) {
            // Only in the non-general sections.
            if (!$section->visible) {
                $sectionstyle = ' hidden';
            }
            if (course_get_format($course)->is_section_current($section)) {
                $sectionstyle = ' current';
            }
        }

        $o .= html_writer::start_tag('li', [
            'id' => 'section-'.$section->section,
            'class' => 'section main clearfix'.$sectionstyle,
            'role' => 'region',
            'aria-labelledby' => "sectionid-{$section->id}-title",
            'data-sectionid' => $section->section,
            'data-sectionreturnid' => $sectionreturn
        ]);

        // if section is number 1, prevent drag & drop, to avoid lessons without parent chapter
        if ($section->section != 1) {
            $leftcontent = $this->section_left_content($section, $course, $onsectionpage);
            $o.= html_writer::tag('div', $leftcontent, array('class' => 'left side'));
        }

        $rightcontent = $this->section_right_content($section, $course, $onsectionpage);
        $o.= html_writer::tag('div', $rightcontent, array('class' => 'right side'));
        $o.= html_writer::start_tag('div', array('class' => 'content'));

        // When not on a section page, we display the section titles except the general section if null
        $hasnamenotsecpg = (!$onsectionpage && ($section->section != 0 || !is_null($section->name)));

        // When on a section page, we only display the general section title, if title is not the default one
        $hasnamesecpg = ($onsectionpage && ($section->section == 0 && !is_null($section->name)));

        $classes = ' accesshide';
        if ($hasnamenotsecpg || $hasnamesecpg) {
            $classes = '';
        }
        if ($chapter = $DB->get_record('format_mooin4_chapter', array('sectionid' => $section->id))) {
            //$section->name = get_string('chapter', 'format_mooin4').' '.$chapter->chapter.' - '.$chapter->title;
            $section->name = $chapter->title;
            $sectionname = get_string('chapter', 'format_mooin4').' '.$chapter->chapter.' '.html_writer::tag('span', $this->section_title_without_link($section, $course));
            $classes .= ' chapter-editing';
        }
        else {
            $sectionname = get_string('lesson', 'format_mooin4').' '.get_section_prefix($section).' '.html_writer::tag('span', $this->section_title($section, $course));
        }

        $o .= $this->output->heading($sectionname, 3, 'sectionname' . $classes, "sectionid-{$section->id}-title");


        $o .= $this->section_availability($section);

        return $o;
    }

    /**
     * Generate a summary of a section for display on the 'course index page'
     *
     * @param stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param array    $mods (argument not used)
     * @return string HTML to output.
     */
    protected function section_summary($section, $course, $mods) {
        global $DB, $USER, $COURSE;
        $classattr = 'section main section-summary clearfix';
        $linkclasses = '';

        // If section is hidden then display grey section link
        if (!$section->visible) {
            $classattr .= ' hidden';
            $linkclasses .= ' dimmed_text';
        } else if (course_get_format($course)->is_section_current($section)) {
            $classattr .= ' current';
        }

        $chapter = $DB->get_record('format_mooin4_chapter', array('sectionid' => $section->id));

        $title = get_section_name($course, $section);
        $o = '';


        //if ($section->uservisible) {
            if ($chapter) {
                $chapterinfo = get_chapter_info($chapter);

                $chaptercompleted = '';
                if ($chapterinfo['completed'] == true) {
                    $chaptercompleted = ' completed';
                }

                // mark as locked/invisible
                $chapterlocked = '';
                if (!$section->uservisible) {
                    $chapterlocked = ' locked';
                }

                $lastvisited = 'false';
                if ($chapterinfo['lastvisited'] == true) {
                    $lastvisited = 'true';
                }

                $o .= html_writer::start_tag('li', [
                    'id' => 'section-' . $section->section,
                    'class' => $classattr . ' chapter'.$chaptercompleted.$chapterlocked,
                    'role' => 'region',
                    'aria-label' => $title,
                    'data-sectionid' => $section->section
                ]);

                $o .= html_writer::tag('div', '', array('class' => 'left side'));
                $o .= html_writer::tag('div', '', array('class' => 'right side'));
                $o .= html_writer::start_tag('div', array('class' => 'content'));
                $title = get_string('chapter', 'format_mooin4').' '.$chapter->chapter . ': ' . $title;
                $sectionids = get_sections_for_chapter($chapter->id);
                $h = html_writer::span('','list-marker');
                $h .= $this->output->heading($title, 3, 'section-title');

                $o .= html_writer::tag(
                        'a',
                        $h,
                        array(
                            'href' => '.chapter-' . $chapter->chapter,
                            'data-toggle' => 'collapse',
                            'role' => 'button',
                            'aria-expanded' => $lastvisited, //'false', //Set true if get_expand_string($section); -> show
                            'aria-controls' => $sectionids
                        )
                    );
                // TODO mark as completed when all section of chapter are completed
                $o .= html_writer::end_tag('div');
                $o .= html_writer::end_tag('li');
            } else {
                $chapter = get_chapter_for_section($section->id);
                if (is_first_section_of_chapter($section->id)) {
                    $expand = get_expand_string($section);
                    $o .= html_writer::start_tag('div', array('class' => 'collapse chapter-content chapter-' . $chapter.$expand));
                }

                // mark as completed
                $completed = '';
                /*
                $user_complete_label = $USER->id . '-' . $COURSE->id . '-' . $section->id;  // $section->section
                $label_complete = $DB->record_exists('user_preferences', array('value' => $user_complete_label));
                if (is_array(get_progress($course->id, $section->id))) {
                    $progress_result = intval(get_progress($course->id, $section->id)['percentage']);
                    if ($progress_result == 100) {
                        $completed .= ' completed';
                    }
                }
                else if($label_complete) {
                    if (is_section_completed($course->id, $section)) {
                        $completed .= ' completed';
                    }
                }
                */
                $progress_result = get_section_progress($course->id, $section->id, $USER->id);
                if ($progress_result == 100) {
                    $completed .= ' completed';
                }

                // mark as locked/invisible
                $locked = '';
                if (!$section->uservisible) {
                    $locked = ' locked';
                }

                // highlight as last visited section
                $lastvisitedsection = '';
                if (get_user_preferences('format_mooin4_last_section_in_course_'.$course->id, 0, $USER->id) == $section->section) {
                    $lastvisitedsection = ' active';
                }

                $o .= html_writer::start_tag('li', [
                        'id' => 'section-' . $section->section,
                        'class' => $classattr . ' lesson'.$completed.$locked.$lastvisitedsection,
                        'role' => 'region',
                        'aria-label' => $title,
                        'data-sectionid' => $section->section
                    ]);

                $o .= html_writer::tag('div', '', array('class' => 'left side'));
                $o .= html_writer::tag('div', '', array('class' => 'right side'));
                $o .= html_writer::start_tag('div', array('class' => 'content'));

                $sectionprefix = get_section_prefix($section);
                $title = get_string('lesson', 'format_mooin4').' '.$sectionprefix . ': ' . $title;

                if ($section->uservisible) {
                    $title = html_writer::tag(
                        'a',
                        $title,
                        array('href' => course_get_url($course, $section->section), 'class' => $linkclasses)
                    );
                }
                else {
                    // TODO $title = html_writer::tag('span', $title, array('class' => $locked));
                }

                $o .= $this->output->heading($title, 3, 'section-title');

                $o .= html_writer::end_tag('div');
                $o .= html_writer::end_tag('li');
                if (is_last_section_of_chapter($section->id)) {
                    $o .= html_writer::end_tag('div');
                }
            }
        //} else {
        //    $o .= $this->output->heading($title, 3, 'section-title');
        //    $o .= html_writer::end_tag('div');
        //    $o .= html_writer::end_tag('li');
        //}



        return $o;
    }

     /**
     * Returns controls in the bottom of the page to increase/decrease number of sections
     *
     * @param stdClass $course
     * @param int|null $sectionreturn
     * @return string
     */
    protected function change_number_sections($course, $sectionreturn = null) {
        $coursecontext = context_course::instance($course->id);
        if (!has_capability('moodle/course:update', $coursecontext)) {
            return '';
        }

        $format = course_get_format($course);
        $options = $format->get_format_options();
        $maxsections = $format->get_max_sections();
        $lastsection = $format->get_last_section_number();
        $supportsnumsections = array_key_exists('numsections', $options);
        $out = '';

        if ($supportsnumsections) {
            // Current course format has 'numsections' option, which is very confusing and we suggest course format
            // developers to get rid of it (see MDL-57769 on how to do it).
            // Display "Increase section" / "Decrease section" links.

            $out .= html_writer::start_tag('div', array('id' => 'changenumsections', 'class' => 'mdl-right'));

            // Increase number of sections.
            if ($lastsection < $maxsections) {
                $straddsection = get_string('increasesections', 'moodle');
                $url = new moodle_url('/course/changenumsections.php',
                    array('courseid' => $course->id,
                          'increase' => true,
                          'returnurl' => course_get_url($course),
                          'sesskey' => sesskey()));
                $icon = $this->output->pix_icon('t/switch_plus', $straddsection);
                $out .= html_writer::link($url, $icon.get_accesshide($straddsection), array('class' => 'increase-sections'));
            }

            if ($course->numsections > 0) {
                // Reduce number of sections sections.
                $strremovesection = get_string('reducesections', 'moodle');
                $url = new moodle_url('/course/changenumsections.php',
                    array('courseid' => $course->id,
                          'increase' => false,
                          'returnurl' => course_get_url($course),
                          'sesskey' => sesskey()));
                $icon = $this->output->pix_icon('t/switch_minus', $strremovesection);
                $out .= html_writer::link($url, $icon.get_accesshide($strremovesection), array('class' => 'reduce-sections'));
            }

            $out .= html_writer::end_tag('div');

        } else if (course_get_format($course)->uses_sections()) {
            if ($lastsection >= $maxsections) {
                // Don't allow more sections if we already hit the limit.
                return;
            }
            // Current course format does not have 'numsections' option but it has multiple sections suppport.
            // Display the "Add section" link that will insert a section in the end.
            // Note to course format developers: inserting sections in the other positions should check both
            // capabilities 'moodle/course:update' and 'moodle/course:movesections'.
            $out .= html_writer::start_tag('div', array('id' => 'changenumsections', 'class' => 'mdl-right'));
            if (get_string_manager()->string_exists('addsections', 'format_'.$course->format)) {
                $straddsections = get_string('addsections', 'format_'.$course->format);
            } else {
                $straddsections = get_string('addsections');
            }
            $url = new moodle_url('/course/changenumsections.php',
                ['courseid' => $course->id, 'insertsection' => 0,'returnurl' => course_get_url($course), 'sesskey' => sesskey()]);
            if ($sectionreturn !== null) {
                $url->param('sectionreturn', $sectionreturn);
            }
            $icon = $this->output->pix_icon('t/add', '');
            $newsections = $maxsections - $lastsection;
            $out .= html_writer::link($url, $icon . $straddsections,
                array('class' => 'add-sections', 'data-add-sections' => $straddsections, 'data-new-sections' => $newsections));
                $out .= html_writer::end_tag('div');
        }
        return $out;
    }

}
