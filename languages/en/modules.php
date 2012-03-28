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
 * Backend modules
 */
$GLOBALS['TL_LANG']['MOD']['tl_cb_reporting']			= array('Reporting','Run reports for Course Builder.');



/**
 * Frontend modules
 */
$GLOBALS['TL_LANG']['FMD']['cb_resultslist']		= array('Course Results Lister', 'Displays a list of course results for a user and the user\'s children.');
$GLOBALS['TL_LANG']['FMD']['cb_resultsreader']		= array('Course Results Reader', 'Displays a single course result for a user and/or one of the user\'s children.');
$GLOBALS['TL_LANG']['FMD']['cb_clientmanager']		= array('Course Client Manager', 'Allows a superuser to upload a CSV of names/emails/passwords for client users and manage their courses.');
$GLOBALS['TL_LANG']['FMD']['cb_clientreporting']	= array('Course Client Reporting', 'Allows a superuser to view and print reports based on their client users.');
$GLOBALS['TL_LANG']['FMD']['cb_orderdetails']		= array('Course Order Details', 'Displays details about an order.');
$GLOBALS['TL_LANG']['FMD']['cb_orderhistory']		= array('Course Order History', 'Displays a member\s order history.');