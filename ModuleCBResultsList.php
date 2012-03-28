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

class ModuleCBResultsList extends ModuleCB
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_cb_resultslist';
	
	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### COURSE BUILDER: COURSE RESULTS LISTER ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = $this->Environment->script.'?do=modules&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		return parent::generate();
	}

	/**
	 * Generate module
	 */
	protected function compile()
	{
		if($this->cb_resultslist_layout)
		{
			$this->Template = new FrontendTemplate($this->cb_resultslist_layout);
		}
	
		$arrCourses = $this->getAvailableCourses(false);
		$arrRows = array();
				
		if(count($arrCourses))
		{
			$arrRows = $this->generateResultRows($arrCourses);
		}
		else
		{
			$_SESSION['CB_ERROR'][] = $GLOBALS['TL_LANG']['CB']['ERR']['noresults'];
		}
		
		$this->Template->nocourseresults = $GLOBALS['TL_LANG']['CB']['ERR']['no_courseresults'];
		$this->Template->viewcourse = $GLOBALS['TL_LANG']['CB']['MISC']['viewcourse'];
		$this->Template->results = count($arrRows) ? $arrRows : array();
			
	}

	/**
	 * Build rows of data for each course and each client member, including the logged in user
	 * @param  array
	 * @return array
	 */
	protected function generateResultRows($arrCourses)
	{
		$return = array();
		$count=0;
		foreach( $arrCourses as $arrCourse )
		{
			$arrRow = array();
			$arrRow['course'] = $arrCourse;
			$arrRow['course']['label'] = $arrCourse['name'];
			$arrRow['course']['class'] = ($count==0 ? 'first ' : ($count==count($arrCourses)-1 ? 'last ' : ''));
			$arrRow['course']['class'] .= 'row_'.$count;
			$arrRow['course']['readerhref'] = $this->generateFrontendURL($this->Database->execute("SELECT * FROM tl_page WHERE id={$this->cb_reader_jumpTo}")->fetchAssoc(), '/course/' . $arrCourse['alias']);

			
			//Get all completed attempts from all related users
			//@todo - tie it to a cookie
			
			$arrUsers = array();
			
			if(FE_USER_LOGGED_IN)
			{
				$objUser = $this->Database->prepare("SELECT m.* FROM tl_cb_member_clients mc INNER JOIN tl_member m ON m.id=mc.clientid WHERE mc.pid=? OR mc.clientid=?")
										  ->execute($this->User->id, $this->User->id);
										  
				
				while( $objUser->next() )
				{
					$arrRow['course']['results'][$objUser->id] = $this->getUserResults($objUser, $arrCourse);
				}
				
			}
			$return[] = $arrRow;
			$count++;
		}

		return $return;
	}
	
	/**
	 * Check whether the user has results and return a row
	 * @param  Database_Result
	 * @param  array
	 * @return string
	 */
	protected function getUserResults($objUser, $arrCourse)
	{
		$arrData = array();
		
		$objTemplate = new FrontendTemplate('cb_resultlist_table');
		
		global $objPage;
		
		$intPage = $this->cb_resultsreader_jumpTo ? $this->cb_resultsreader_jumpTo : $objPage->id;
		
		$objResults = $this->Database->execute("SELECT * FROM tl_cb_coursedata WHERE pid={$objUser->id} AND courseid={$arrCourse['id']}");
				
		if( $objResults->numRows )
		{
			$objTemplate->hreflabel =  $GLOBALS['TL_LANG']['CB']['MISC']['viewresults'];
			$objTemplate->href = $this->generateFrontendURL($this->Database->execute("SELECT * FROM tl_page WHERE id={$intPage}")->fetchAssoc(), '/course/' . $arrCourse['alias'] . '/user/' . $objUser->id);
			// @todo accommodate for non-alias URLs
		}
			
		$objTemplate->name 		= $objUser->lastname . ', ' . $objUser->firstname;
		$objTemplate->results 	= $arrData;
		
		return $objTemplate->parse();
	}


}