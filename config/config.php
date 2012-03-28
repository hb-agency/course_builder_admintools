<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Winans Creative 2011, Helmut Schottmüller 2009
 * @author     Blair Winans <blair@winanscreative.com>
 * @author     Fred Bliss <fred.bliss@intelligentspark.com>
 * @author     Adam Fisher <adam@winanscreative.com>
 * @author     Includes code from survey_ce module from Helmut Schottmüller <typolight@aurealis.de>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */


/**
 * Frontend modules
 */
$GLOBALS['FE_MOD']['coursebuilder']['cb_resultslist'] = 'ModuleCBResultsList';
$GLOBALS['FE_MOD']['coursebuilder']['cb_resultsreader'] = 'ModuleCBResultsReader';
$GLOBALS['FE_MOD']['coursebuilder']['cb_clientmanager'] = 'ModuleCBClientManager';
$GLOBALS['FE_MOD']['coursebuilder']['cb_clientreporting'] = 'ModuleCBFrontendReporting';
$GLOBALS['FE_MOD']['coursebuilder']['cb_orderdetails'] = 'ModuleCBOrderDetails';
$GLOBALS['FE_MOD']['coursebuilder']['cb_orderhistory'] = 'ModuleCBOrderHistory';



/**
 * Back end modules
 */
$GLOBALS['BE_MOD']['accounts']['member']['tables'][] = 'tl_cb_member_clients';
$GLOBALS['BE_MOD']['accounts']['member']['tables'][] = 'tl_cb_member_courses';

array_insert($GLOBALS['BE_MOD']['coursebuilder'], 4, array
(
	'tl_cb_reporting' => array
	(
		'label'				=> &$GLOBALS['TL_LANG']['MOD']['tl_cb_reporting'],
		'callback'			=> 'ModuleCBReporting',
		'icon'				=> 'system/modules/course_builder_admintools/html/barchartmulticolor.png',
		'print_report'		=> array('tl_cb_reporting', 'printReportToPDF')
	)
));

 /**
 * Hooks
 */
$GLOBALS['ISO_HOOKS']['postCheckout'][] = array('CBCourseAdminTools','postCheckout');
$GLOBALS['CB_HOOKS']['getAvailableCourses'][] = array('CBCourseAdminTools','getAvailableCourses');