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
 * Class for additional callback/hook function for the Admin Tools
 */

class CBCourseAdminTools extends Controller
{

	/**
	 * postCheckout Callback function
	 * @param IsotopeOrder
	 * @param array
	 */
	public function postCheckout( IsotopeOrder $objOrder, $arrItemIds )
	{
		$this->import('Database');
		$this->import('FrontendUser', 'User');
		
		$arrProducts = $objOrder->getProducts('', true);
		
		foreach( $arrProducts as $objProduct )
		{
			if( FE_USER_LOGGED_IN && $objProduct instanceof CourseProduct && $objProduct->courseid > 0  )
			{
				//A CourseProduct was purchased, so we need to do one of two things
				//1. Find out if they are using some form of payment that would tie them to another member, and relate them
				//2. If not, add them as a client of themselves and set their attempts
				
				$intParentID = $this->User->id;
				$intClientID = '';
				
				// HOOK: allow other modules to determine which member should be the parent
				if (isset($GLOBALS['CB_HOOKS']['addMemberClient']) && is_array($GLOBALS['CB_HOOKS']['addMemberClient']))
				{
					foreach ($GLOBALS['CB_HOOKS']['addMemberClient'] as $callback)
					{
						$this->import($callback[0]);
						$intParentID = $this->$callback[0]->$callback[1]($intParentID, $objOrder, $objProduct);
					}
				}
				
				//Determine if user is already a child of the parent - Don't cache the result, dummy!
				$objChild = $this->Database->prepare("SELECT * FROM tl_cb_member_clients WHERE pid=? AND clientid=?")
										   ->limit(1)
										   ->executeUncached( $intParentID, $this->User->id );
				if( !$objChild->numRows )
				{
					//Add the user as a child of the parent
					$arrSet = array
					(
						'pid'		=>	$intParentID,
						'clientid'	=> 	$this->User->id,
						'tstamp'	=>	time()
					);
					
					$intClientID = $this->Database->prepare("INSERT INTO tl_cb_member_clients %s")->set($arrSet)->execute()->insertId;				
				}
				else
				{
					$intClientID = $objChild->id;
				}
				
				//Determine if user already has this course assigned to them
				$objCourse = $this->Database->prepare("SELECT * FROM tl_cb_member_courses WHERE pid=? AND courseid=?")
										   ->limit(1)
										   ->execute( $intClientID, $objProduct->courseid );
				if( !$objCourse->numRows )
				{
					//Add the course to the client
					//Note that we are ONLY setting one attempt to this client, as if they are using tokens that quantity is managed separately
					// @todo see if there is some way to simplify this
					$arrCourse = array
					(
						'pid'			=>	$intClientID,
						'courseid'		=> 	$objProduct->courseid,
						'tstamp'		=>	time(),
						'totalattempts'	=>  1
					);
					
					$insertID = $this->Database->prepare("INSERT INTO tl_cb_member_courses %s")->set($arrCourse)->execute()->insertId;
					
					$this->log('Added member course ID:'.$objProduct->courseid.' to client ID:'. $insertID , __METHOD__, TL_ACCESS);
									
				}
				else
				{
					$this->Database->prepare("UPDATE tl_cb_member_courses SET totalattempts=? WHERE id=?")->execute( ($objCourse->totalattempts + 1) , $objCourse->id );
										
					$this->log('Updated member course ID:'.$objCourse->id.' to client ID:'. $intClientID , __METHOD__, TL_ACCESS);
					
				}
				
				
				// HOOK: allow other modules to execute actions after client has been added (email, etc.)
				if (isset($GLOBALS['CB_HOOKS']['postAddMemberClient']) && is_array($GLOBALS['CB_HOOKS']['postAddMemberClient']))
				{
					foreach ($GLOBALS['CB_HOOKS']['postAddMemberClient'] as $callback)
					{
						$this->import($callback[0]);
						$this->$callback[0]->$callback[1]($intParentID, $intClientID, $objOrder, $objProduct);
					}
				}
				
				
			}
		}
	}
	
	
	/**
	 * getAvailableCourses Callback function
	 * @param array
	 * @param CBModule
	 * @return array
	 */
	public function getAvailableCourses( $arrData, $objModule )
	{
		$this->import('Database');
		$this->import('FrontendUser', 'User');
		
		foreach($arrData as $key=>$course)
		{
			$intCredits = 0;
		
			if( FE_USER_LOGGED_IN )
			{
				//Check for credits for this user
				$objClient = $this->Database->prepare("SELECT * FROM tl_cb_member_clients WHERE clientid=?")->execute( $this->User->id );
				
				if( !$objClient->numRows )
				{
					unset($arrData[$key]);
					continue;
				}
				else
				{
					while( $objClient->next() )
					{
						$objCredits = $this->Database->prepare("SELECT * FROM tl_cb_member_courses WHERE pid=? AND courseid=?")->limit(1)->execute( $objClient->id, $course['id'] );
						$intCredits += ($objCredits->totalattempts - $objCredits->usedattempts);
						
					}
					
					if($intCredits <= 0)
					{
						unset($arrData[$key]);
					}
				}
			}
		}
		
		return $arrData;
	}
	
}