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

class ModuleCBResultsReader extends ModuleCB
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_cb_resultsreader';
	
	
	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### COURSE BUILDER: COURSE RESULTS READER ###';
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
		if($this->cb_resultsreader_layout)
		{
			$this->Template = new FrontendTemplate($this->cb_resultsreader_layout);
		}
		
		$arrCourses = $this->getAvailableCourses(false); //need to check to be sure that querystring params are valid for the user
		
		$arrRows = array();
				
		if(count($arrCourses))
		{
			$arrRows = $this->generateResults($arrCourses);
		}
		else
		{
			$_SESSION['CB_ERROR'][] = $GLOBALS['TL_LANG']['CB']['ERR']['noresults'];
		}
				
		$this->Template->results = count($arrRows) ? $arrRows : array();
		
		$this->Template->hdate = $GLOBALS['TL_LANG']['CB']['MISC']['date'];
		$this->Template->hstatus = $GLOBALS['TL_LANG']['CB']['MISC']['status'];
		$this->Template->hscore = $GLOBALS['TL_LANG']['CB']['MISC']['score'];
		$this->Template->hcert = $GLOBALS['TL_LANG']['CB']['MISC']['cert'];	
	}

	/**
	 * Build rows of data for each logged result for the passed user, including verifying permissions to view the passed parameters for security
	 * @param  array
	 * @param  array
	 * @return array
	 */
	protected function generateResults($arrCourses)
	{
		$return = array();
		
		$arrCourseIDs = array_keys($arrCourses);
		
		if( !$this->Input->get('course') || !$this->Input->get('user') )	
				return $return; //@todo create error msg
				
				
		//Get passed course
		//Generate Course Object and CourseElement Data
		$arrCourse = CBFrontend::getCoursebyAlias($this->Input->get('course'), $arrCourseIDs);
				
		//Check that course is allowed for this user
		if( count($arrCourse) && in_array( $arrCourse['id'] , $arrCourseIDs) )
		{
			//Check available client IDs
			$arrClients = $this->Database->prepare("SELECT clientid FROM tl_cb_member_clients WHERE pid=? OR clientid=?")
										 ->execute($this->User->id, $this->User->id)
										 ->fetchEach('clientid');
										 
			//Check that user is a valid client							 
			if( count($arrClients) && in_array( $this->Input->get('user'), $arrClients ) )
			{
				
				$objUser = $this->Database->prepare("SELECT * FROM tl_member WHERE id=?")
										  ->execute( $this->Input->get('user') );
	
				$this->Template->course = $arrCourse['name'];
				$this->Template->user = $objUser->firstname . ' ' . $objUser->lastname;
				
				$return = $this->getUserResults($objUser, $arrCourse);
				
			}
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
		$arrQuizzes = array();
		
		//Build array of quiz ids so we can find which one is the final quiz 
		$arrElements = deserialize( $arrCourse['courseelements'] );
		foreach( $arrElements as $element )
		{
			$arrEl = explode('|', $element);
			if($arrEl[0]=='quiz')
			{
				$arrQuizzes[] = $arrEl[1];
			}
		}
		
		global $objPage;
				
		$arrResults = $this->Database->execute("SELECT * FROM tl_cb_coursedata WHERE pid={$objUser->id} AND courseid={$arrCourse['id']} AND status != 'review'")->fetchAllAssoc();
				
		// HOOK for altering client/member results
		if (isset($GLOBALS['TL_HOOKS']['cb_getClientResults']) && is_array($GLOBALS['TL_HOOKS']['cb_getClientResults']))
		{
			foreach ($GLOBALS['TL_HOOKS']['cb_getClientResults'] as $callback)
			{
				$this->import($callback[0]);
				$arrResults = $this->$callback[0]->$callback[1]($arrResults, $this);
			}
		}
			
		if (count($arrResults))
		{
			foreach ($arrResults as $result)
			{
				$strHref = ($result['status']=='complete' && $result['pass']) ? $this->addToUrl('certificate=' . $result['uniqid'] ) : '';
				
				//Find master quiz
				$objQuiz = $this->Database->execute("SELECT * FROM tl_cb_quiz WHERE final=1 AND id IN(". implode(',', $arrQuizzes) .") LIMIT 0,1");
				
				//Get master quiz score from quizdata
				$objQuizData = $this->Database->execute("SELECT * FROM tl_cb_quizdata WHERE pid={$result['id']} AND quizid={$objQuiz->id}");
				
				$arrResult = array
				(
					'date'		=> 	$this->parseDate($GLOBALS['TL_CONFIG']['dateFormat'], $result['tstamp']),
					'status'	=>	$GLOBALS['TL_LANG']['CB'][$result['status']],	
					'score'		=>	ceil($objQuizData->score),
					'certhref'	=>  $strHref,
					'cert'		=>  $GLOBALS['TL_LANG']['CB']['MISC']['certdownload'],
					'class'		=>  ($result['pass'] && $result['status']=='complete')  ? 'pass' : ($result['pass'] < 1 && $result['status']=='complete' ? 'fail' : 'in_progress')
				);
				
				$arrData[] = $arrResult;			
				
			}
		}	
		
		return $arrData;
	}
	
}