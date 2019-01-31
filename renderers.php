<?php
include_once($CFG->dirroot . "/report/iomadanalytics/classes/FlatFile.php");
include_once($CFG->dirroot . "/mod/lesson/renderer.php");
include_once($CFG->dirroot . "/mod/quiz/renderer.php");

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

		$d = new DOMDocument();
		$d->loadHTML($new_output, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		$links = $d->getElementsByTagName('a');

		// add the arrow to return btn
		for ($i = 0; $i < $links->length; $i++) {

			$currentNode = $d->saveHTML($links->item($i));

			// Return to Day 1 - bla bla bla
			if (strpos($currentNode, '/course/view.php') !== false) {

				// save the node value
				$nv = $d->getElementsByTagName('a')[$i]->nodeValue;

				// Blank the node value to re-insert it after the <i> icon
				$d->getElementsByTagName('a')[$i]->nodeValue = '';

				// create the left arrow node
				$arrow = $d->createElement('i');
				$arrow->setAttribute('class', 'fa fa-arrow-circle-o-left');
				$d->getElementsByTagName('a')[$i]->appendChild($arrow);

				// append the node value
				$d->getElementsByTagName('a')[$i]->appendChild(
					$d->createTextNode($nv)
				);
			}
		}

		// Add arrow to forward btn and remove the link from the HTML ie:Go to "How to use"
		for ($i = 0; $i < $links->length; $i++) {

			$currentNode = $d->saveHTML($links->item($i));

			// if the link leads to a lesson or a quiz
			if (
				strpos($currentNode, '/mod/lesson/view.php?id=') !== false ||
				strpos($currentNode, '/mod/quiz/view.php?id=') !== false
			) {
				// create the right arrow node
				$arrow = $d->createElement('i');
				$arrow->setAttribute('class', 'fa fa-arrow-circle-o-right');
				$d->getElementsByTagName('a')[$i]->appendChild($arrow);

				// backup the link html with the added icon
				$goToNextActivity = $d->saveHtml($d->getElementsByTagName('a')[$i]);

				// Delete the forward link
				$lnk_fwd = $links->item($i);
				$lnk_fwd->parentNode->removeChild($lnk_fwd);
			}
		}

		// Save the resulting html without the link
		$html = $d->saveHTML();

		// Remove the <html> wrapper
		$output = str_replace(array('<html>', '</html>'), '', $html);

		// add the activity link at the end of the document fragment
		$output .= $goToNextActivity;

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
			$attempt = '7070';
		} else if ($cm->course == '9') {  // Day 2
			$attempt = '7041';
		} else if ($cm->course == '10') { // Day 3
			$attempt = '7042';
		} else if ($cm->course == '11') { // Day 4
			$attempt = '7043';
		} else if ($cm->course == '12') { // Day 5
			$attempt = '7044';
		} else if ($cm->course == '13') { // Day 6
			$attempt = '7074';
		} else if ($cm->course == '14') { // Day 7
			$attempt = '7045';
		} else if ($cm->course == '15') { // Day 8
			$attempt = '7075';
		} else if ($cm->course == '16') { // Day 9
			$attempt = '7047';
		} else if ($cm->course == '17') { // Day 10
			$attempt = '7048';
		}

		// add the 'See quiz answers' button after second attempt
		if (count($viewobj->attemptobjs) >= 2) {

			// Add an html wrapper because DOMDocument needs a root node
			$new_output = '<html>'.$output.'</html>';

			// Create the DOM Object
			$d = new DOMDocument();
			$d->loadHTML($new_output, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
			$feedback = $d->getElementById('feedback');

			// Create the containing div to create a box
			$div = $d->createElement('div');
			$div->setAttribute('class', 'box continuebutton p-y-1');

			// Create the <a> element
			$a = $d->createElement('a', get_string('see-quiz-answer', 'theme_kpdesktop'));
			$a->setAttribute('href', '/theme/kpdesktop/review_quiz.php?attempt='.$attempt.'&cmid='.$cm->id);
			$a->setAttribute('class', 'btn btn-primary centerpadded lessonbutton standardbutton p-r-1');

			// Add the <a> to the <div>
			$div->appendChild($a);

			// Add the new div>a to the DOM Object
			$feedback->parentNode->insertBefore($div, $feedback);

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
