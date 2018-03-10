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
 * KP Desktop
 *
 * @package    theme_kpdesktop
 * @copyright  2018 PT Bridgeus Kizuna Asia
 * @author     Nicolas Dalpe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

$THEME->name = 'kpdesktop';

// Get the parents theme SCSS
$THEME->scss = function() {
	$parentconfig = theme_config::load('boost');
	return theme_boost_get_main_scss_content($parentconfig);
};

// Get the kpdesktop theme styling
$THEME->extrascsscallback = 'theme_kpdesktop_get_extra_scss';

$THEME->editor_sheets = [];
$THEME->parents = ['iomadboost', 'boost'];
$THEME->enable_dock = false;
$THEME->yuicssmodules = array();
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->requiredblocks = '';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;
$THEME->hidefromselector = false;
