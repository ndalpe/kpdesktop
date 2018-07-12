<?php
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
		$output = '<html>'.$output.'</html>';

		$d = new DOMDocument();
		$d->loadHTML($output, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

		// Add arrow to buttons
		foreach ($d->getElementsByTagName('a') as $key => $value) {

			// Forward button. Go to "How to use"
			if (strstr($d->saveHTML($value), '/mod/lesson/view.php?id=')) {
				// create the right arrow node
				$arrow = $d->createElement('i');
				$arrow->setAttribute('class', 'fa fa-arrow-circle-o-right');
				$d->getElementsByTagName('a')[$key]->appendChild($arrow);
			}

			// Back button. Return to Day 1 - bla bla bla
			if (strstr($d->saveHTML($value), '/course/view.php?id=')) {
				// save the node value
				$nv = $d->getElementsByTagName('a')[$key]->nodeValue;
				// Blank the node value to re-insert it after the <i> icon
				$d->getElementsByTagName('a')[$key]->nodeValue = '';

				// create the left arrow node
				$arrow = $d->createElement('i');
				$arrow->setAttribute('class', 'fa fa-arrow-circle-o-left');
				$d->getElementsByTagName('a')[$key]->appendChild($arrow);

				// append the node value
				$d->getElementsByTagName('a')[$key]->appendChild(
					$d->createTextNode($nv)
				);
			}
		}

		// find the lesson link
		$xpathsearch = new DOMXPath($d);
		$xpath_results = $xpathsearch->query('//a[contains(@href,"/mod/lesson/view.php")]');

		// save the link's html
		$goToNextActivity = $d->saveHTML($xpath_results->item(0));

		// remove the link
		if($link = $xpath_results->item(0)){
		    $link->parentNode->removeChild($link);
		}

		// Save the resulting html without the link
		$html = $d->saveHTML();

		// Remove the <html> wrapper
		$output = str_replace(array('<html>', '</html>'), '', $html);

		// add the activity link at the end of the document fragment
		$output = $output.$goToNextActivity;

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
	public function view_page($course, $quiz, $cm, $context, $viewobj) {
		global $CFG;

		$output = parent::view_page($course, $quiz, $cm, $context, $viewobj);

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
    	$output = parent::review_page($attemptobj, $slots, $page, $showall, $lastpage, $displayoptions, $summarydata);

    	// Add the "Day X" number
    	$Course = $attemptobj->get_course();
    	$courseFullName = format_string($Course->fullname, true, 1);
    	$a_title = explode('-', $courseFullName);
    	$dayName = strtoupper(trim($a_title[0]));

    	return $output.'<style type="text/css">.pagelayout-incourse #page-header .card::before{content: "'.$dayName.'";}</style>';
    }
}
