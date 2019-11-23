<?php
include_once($CFG->dirroot . "/report/iomadanalytics/classes/FlatFile.php");
include_once($CFG->dirroot . "/mod/lesson/renderer.php");
include_once($CFG->dirroot . "/mod/quiz/renderer.php");
include_once($CFG->dirroot . "/course/renderer.php");

class theme_kpdesktop_mod_lesson_renderer extends mod_lesson_renderer
{
	/**
	 * Returns the header for the lesson module
	 *
	 * @param lesson $lesson a lesson object.
	 * @param string $currenttab current tab that is shown.
	 * @param bool   $extraeditbuttons if extra edit buttons should be displayed.
	 * @param int    $lessonpageid id of the lesson page that needs to be displayed.
	 * @param string $extrapagetitle String to appent to the page title.
	 * @return string
	 */
	public function header($lesson, $cm, $currenttab = '', $extraeditbuttons = false, $lessonpageid = null, $extrapagetitle = null) {
		global $CFG,$DB;

		$activityname = format_string($lesson->name, true, $lesson->course);
		if (empty($extrapagetitle)) {
			$title = $this->page->course->shortname.": ".$activityname;
		} else {
			$title = $this->page->course->shortname.": ".$activityname.": ".$extrapagetitle;
		}

		// Get the "Day X" number
		$courseFullName = format_string($this->page->course->fullname, true, 1);
		$a_title = explode('-', $courseFullName);
		$dayName = strtoupper(trim($a_title[0]));
		$CFG->additionalhtmlhead = '<style type="text/css">.pagelayout-incourse #page-header .card::before{content: "'.$dayName.'";}</style>';

		// Build the buttons
		$context = context_module::instance($cm->id);

		// Get the section namename
		$Section = $DB->get_record('course_sections', array('id'=>$cm->section), $fields='*', $strictness=IGNORE_MISSING);
		$SectionName = format_string($Section->name, true, $lesson->course);

		/// Header setup
		$this->page->set_title($title);

		// original line
		// $this->page->set_heading($this->page->course->fullname);
		// Override
		$this->page->set_heading($SectionName);

		lesson_add_header_buttons($cm, $context, $extraeditbuttons, $lessonpageid);
		$output = $this->output->header();

		if (has_capability('mod/lesson:manage', $context)) {
			$output .= $this->output->heading_with_help($activityname, 'overview', 'lesson');

			if (!empty($currenttab)) {
				ob_start();
				include($CFG->dirroot.'/mod/lesson/tabs.php');
				$output .= ob_get_contents();
				ob_end_clean();
			}
		} else {
			$output .= $this->output->heading($activityname);
		}

		foreach ($lesson->messages as $message) {
			$output .= $this->output->notification($message[0], $message[1], $message[2]);
		}

		return $output;
	}

	// Reverse the order of the Go-To-Next-Activity link and the return-to-course-content
	public function display_eol_page(lesson $lesson, $data) {
		$output = parent::display_eol_page($lesson, $data);

		// Add an html wrapper because DOMDocument needs a root node
		$new_output = '<html>'.$output.'</html>';

		// Added to prevent the DOM parser to throw a warning when & occurs
		// as the parser believe this is an unclosed html entity
		// DOMDocument::loadHTML(): htmlParseEntityRef: no name in Entity
		$new_output = str_replace('&', '$amp;', $new_output);

		$d = new DOMDocument();
		$d->loadHTML(mb_convert_encoding($new_output, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

		// remove the progress bar
		$divs = $d->getElementsByTagName('div');
		for ($i=0; $i < $divs->length; $i++) {
			if (strstr($divs->item($i)->getAttribute('class'), 'progress_bar') !== false) {
				$progress_bar = $divs->item($i);
				$progress_bar->parentNode->removeChild($progress_bar);
			}
		}

		$links = $d->getElementsByTagName('a');

		// Multi language proof the "Return" and "Go to ..." buttons
		for ($j=0; $j < $links->length; $j++) {
			$links->item($j)->nodeValue = format_string($links->item($j)->nodeValue, true, 1);
		}

		// Add arrows on each button
		foreach ($links as $key => $link) {

			// Get the link href attr
			$href = $link->getAttribute('href');

			if (strpos($href, '/course/view.php') !== false) {
				// save the node value
				$nv = $d->getElementsByTagName('a')[$key]->nodeValue;

				// Blank the node value to re-insert it after the <i> icon
				$d->getElementsByTagName('a')[$key]->nodeValue = '';

				// create the left arrow node
				$arrow = $d->createElement('i');
				$arrow->setAttribute('class', 'fa fa-arrow-circle-o-left');
				$d->getElementsByTagName('a')[$key]->appendChild($arrow);

				// Add an ID attr so it is easier to style
				$d->getElementsByTagName('a')[$key]->setAttribute('id', 'eol_return');

				// append the node value
				$d->getElementsByTagName('a')[$key]->appendChild(
					$d->createTextNode($nv)
				);
			} else if (
				strpos($href, '/mod/lesson/view.php?id=') !== false ||
				strpos($href, '/mod/quiz/view.php?id=') !== false
			) {
				// create the right arrow node
				$arrow = $d->createElement('i');
				$arrow->setAttribute('class', 'fa fa-arrow-circle-o-right');

				// Add arrow to btn
				$d->getElementsByTagName('a')[$key]->appendChild($arrow);

				// Add an ID attr so it is easier to style
				$d->getElementsByTagName('a')[$key]->setAttribute('id', 'eol_goto');
			}
		}

		// Save the button's html and remove them from DOM
		$savedBtn = array();

		// Cycle the DOM backward to remove all element
		for ($i = $links->length; --$i >= 0; ) {

			// Get the DOM node
			$href = $links->item($i);

			// save the button's HTML
			$savedBtn[] = $d->saveHTML($href);

			// remove the node from the DOM
			$href->parentNode->removeChild($href);
		}

		// Add the buttons back into the DOM $d
		foreach ($savedBtn as $key => $button) {

			// Create a DOM fragment
			$frag = $d->createDocumentFragment();

			// Append the button's HTML to the DOM Fragment
			$frag->appendXML($button);

			// append the node value
			$d->getElementsByTagName('div')[0]->appendChild($frag);
		}

		// Save the resulting html without the link
		$html = $d->saveHTML();

		// Remove the <html> wrapper
		$output = str_replace(array('<html>', '</html>'), '', $html);

		// Add bootstrap button css class
		$output = str_replace('class="centerpadded', 'class="btn btn-primary centerpadded', $output);

		// add the activity link at the bottom of the document fragment
		return $output;
	}
}

class theme_kpdesktop_mod_quiz_renderer extends mod_quiz_renderer
{
	// attempt start page
	// /mod/quiz/view.php?id=57
	public function view_page($course, $quiz, $cm, $context, $viewobj)
	{
		global $CFG;

		$output = parent::view_page($course, $quiz, $cm, $context, $viewobj);

		// Set the right attempt id for each course module
		// the attempt id was created by doing the quiz and answering
		// all the right question using the admin account
		if ($cm->course == '7') { // Day 1
			$attempt = '7203';
		} else if ($cm->course == '9') {  // Day 2
			$attempt = '7204';
		} else if ($cm->course == '10') { // Day 3
			$attempt = '7205';
		} else if ($cm->course == '11') { // Day 4
			$attempt = '7206';
		} else if ($cm->course == '12') { // Day 5
			$attempt = '7207';
		} else if ($cm->course == '13') { // Day 6
			$attempt = '7208';
		} else if ($cm->course == '14') { // Day 7
			$attempt = '7209';
		} else if ($cm->course == '15') { // Day 8
			$attempt = '7210';
		} else if ($cm->course == '16') { // Day 9
			$attempt = '7211';
		} else if ($cm->course == '17') { // Day 10
			$attempt = '7212';
		}

		// Verify that if we have an attempt with an inprogress state
		$attempInProgress = false;
		foreach ($viewobj->attempts as $key => $value) {
			if ($value->state == 'inprogress') {
				$attempInProgress = true;
			}
		}

		// add the 'See quiz answers' button if
		// we are at the second attempt and the attempt is completed
		// or we are at the third attempt
		if (
			(count($viewobj->attemptobjs) == 2 && $attempInProgress === false) || (count($viewobj->attemptobjs) == 3)
		) {

			// Add an html wrapper because DOMDocument needs a root node
			$new_output = '<html>'.$output.'</html>';

			// Create the DOM Object
			$d = new DOMDocument();
			//$d->loadHTML($new_output, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
			$d->loadHTML(mb_convert_encoding($new_output, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

			// Create the containing div to create a box
			$div = $d->createElement('div');
			$div->setAttribute('class', 'box continuebutton p-y-1');

			// Create the <a> element
			$a = $d->createElement('a', get_string('see-quiz-answer', 'theme_kpdesktop'));
			$a->setAttribute('href', '/theme/kpdesktop/review_quiz.php?attempt='.$attempt.'&cmid='.$cm->id);
			$a->setAttribute('class', 'btn btn-secondary centerpadded lessonbutton standardbutton p-r-1');

			// Add the <a> to the <div>
			$div->appendChild($a);

			// Add the new div>a to the page
			$d->appendChild($div);

			// Save the resulting html with the div>a
			$html = $d->saveHTML();

			// Remove the <html> wrapper
			$output = str_replace(array('<html>', '</html>'), '', $html);
		}

		// Add the "Day X" number
		$courseFullName = format_string($course->fullname, true, 1);
		$a_title = explode('-', $courseFullName);
		$dayName = strtoupper(trim($a_title[0]));
		$dayName = format_string($dayName, true, 1);

		return $output.'<style type="text/css">.pagelayout-incourse #page-header .card::before{content: "'.$dayName.'";}</style>';
	}

	// question page
	// /mod/quiz/attempt.php?attempt=6695&cmid=57
	public function attempt_page($attemptobj, $page, $accessmanager, $messages, $slots, $id, $nextpage) {
		global $CFG;

		// Add the "Day X" number
		$Course = $attemptobj->get_course();
		$courseFullName = format_string($Course->fullname, true, 1);
		$a_title = explode('-', $courseFullName);
		$dayName = strtoupper(trim($a_title[0]));
		$CFG->additionalhtmlhead = '<style type="text/css">.pagelayout-incourse #page-header .card::before{content: "'.$dayName.'";}</style>';

		// call the parent method to get the html page content
		$output = parent::attempt_page($attemptobj, $page, $accessmanager, $messages, $slots, $id, $nextpage);

		// get the quiz name
		$quiz = $attemptobj->get_quiz();

		// Format quiz name to be multilanguage compatible
		$quizName = format_string($quiz->name, true, 1);

		// replce page title with quiz name
		$output2 = preg_replace('/<h1[^>]*>.*?<\/h1>/i', '<h1>'.$quizName.'</>', $output);

		return $output2;
	}

	// Summary of attempt (submit and finish all)
	// /mod/quiz/summary.php?attempt=6685
	public function summary_page($attemptobj, $displayoptions) {
		global $CFG;

		$output = parent::summary_page($attemptobj, $displayoptions);

		// Add the "Day X" number
		$Course = $attemptobj->get_course();
		$courseFullName = format_string($Course->fullname, true, 1);
		$a_title = explode('-', $courseFullName);
		$dayName = strtoupper(trim($a_title[0]));

		return $output.'<style type="text/css">.pagelayout-incourse #page-header .card::before{content: "'.$dayName.'";}</style>';
	}

	public function review_page(quiz_attempt $attemptobj, $slots, $page, $showall, $lastpage, mod_quiz_display_options $displayoptions, $summarydata)
	{
		global $CFG;

		// Number of allowed attempt for the quiz
		$allowedAttempt = $attemptobj->get_num_attempts_allowed();

		// current attempt #
		$currentAttempt = $attemptobj->get_attempt_number();

		// Display the correct answer if we reach the last attempt
		if ($allowedAttempt == $currentAttempt) {
			$displayoptions->rightanswer = 1;
		} else {
			$displayoptions->rightanswer = 0;
		}

		// Render Moodle's original method
		$output = parent::review_page($attemptobj, $slots, $page, $showall, $lastpage, $displayoptions, $summarydata);

		// Add the "Day X" number
		$Course = $attemptobj->get_course();
		$courseFullName = format_string($Course->fullname, true, 1);
		$a_title = explode('-', $courseFullName);
		$dayName = strtoupper(trim($a_title[0]));

		return $output.'<style type="text/css">.pagelayout-incourse #page-header .card::before{content: "'.$dayName.'";}</style>';
	}
}

class theme_kpdesktop_core_course_renderer extends core_course_renderer {

	/**
	 * Renders HTML to display one course module for display within a section.
	 *
	 * This function calls:
	 * {@link core_course_renderer::course_section_cm()}
	 *
	 * @param stdClass $course
	 * @param completion_info $completioninfo
	 * @param cm_info $mod
	 * @param int|null $sectionreturn
	 * @param array $displayoptions
	 * @return String
	 */
	public function course_section_cm_list_item($course, &$completioninfo, cm_info $mod, $sectionreturn, $displayoptions = array()) {
		global $USER;

	    $output = '';
	    if ($modulehtml = $this->course_section_cm($course, $completioninfo, $mod, $sectionreturn, $displayoptions)) {
	        $modclasses = 'activity ' . $mod->modname . ' modtype_' . $mod->modname . ' ' . $mod->extraclasses;

	    	// add completion status to the activity's <li> css class list
			$completion_state = $completioninfo->get_data($mod, false, $USER->id);
			if ($completion_state->completionstate === 0) {
				$modclasses .= ' not_completed';
			} else {
				$modclasses .= ' completed';
			}

	        $output .= html_writer::tag('li', $modulehtml, array('class' => $modclasses, 'id' => 'module-' . $mod->id));
	    }
	    return $output;
	}
}