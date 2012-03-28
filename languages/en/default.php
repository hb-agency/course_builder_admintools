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
 * Misc
 */
 
$GLOBALS['TL_LANG']['CB']['MISC']['courses'] 		= 'Courses';
$GLOBALS['TL_LANG']['CB']['MISC']['users'] 			= 'Clients';
$GLOBALS['TL_LANG']['CB']['MISC']['viewresults'] 	= 'View Results';
$GLOBALS['TL_LANG']['CB']['MISC']['viewcourse']   	= 'View Course';
$GLOBALS['TL_LANG']['CB']['MISC']['date']			= 'Date taken';
$GLOBALS['TL_LANG']['CB']['MISC']['status']			= 'Status';
$GLOBALS['TL_LANG']['CB']['MISC']['score']			= 'Score (%)';
$GLOBALS['TL_LANG']['CB']['MISC']['cert']			= 'Certificate';
$GLOBALS['TL_LANG']['CB']['MISC']['certdownload']   = 'Download certificate';
$GLOBALS['TL_LANG']['CB']['MISC']['success']   		= 'Click here to download your certificate.';
$GLOBALS['TL_LANG']['CB']['MISC']['failure']   		= 'Click here to re-take the course.';
$GLOBALS['TL_LANG']['CB']['MISC']['hsuccess']   	= 'Course completed successfully.';
$GLOBALS['TL_LANG']['CB']['MISC']['hfailure']   	= 'Course completed unsuccessfully.';

/**
 * Errors
 */

$GLOBALS['TL_LANG']['CB']['ERR']['no_certificate'] 	  	= 'There is no record for that course to generate a certificate.';
$GLOBALS['TL_LANG']['CB']['ERR']['no_results'] 		  	= 'You have no client users.';
$GLOBALS['TL_LANG']['CB']['ERR']['no_courseattempts'] 	= 'You have no attempts left to manage.';
$GLOBALS['TL_LANG']['CB']['ERR']['too_many_assigned'] 	= 'You have too many courses attempts assigned. Please set the total to be less than or equal to the amount you have available.';
$GLOBALS['TL_LANG']['CB']['ERR']['no_courseresults'] 	= 'There are no results to display for this course.';