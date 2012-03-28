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
 * Class to handle client records of an admin user
 */

class CBCourseClient extends Controller
{

	/**
	 * Name of the child table
	 * @var string
	 */
	protected $strTable='tl_cb_member_clients';
	
	/**
	 * Name of the child table
	 * @var string
	 */
	protected $ctable='tl_cb_member_courses';
	
	/**
	 * Data array
	 * @var array
	 */
	protected $arrData = array();

	/**
	 * Define if data should be threaded as "locked", eg. we are viewing a report
	 */
	protected $blnLocked = false;

	/**
	 * CourseBuilder object
	 * @var object
	 */
	protected $CourseBuilder;

	/**
	 * Configuration
	 * @var array
	 */
	protected $arrSettings = array();

	/**
	 * Cache properties
	 */
	protected $arrCache = array();
	
	
	public function __construct($arrData, $blnLocked=false)
	{
		parent::__construct();
		$this->import('Database');
		$this->import('CourseBuilder');
		
		if (FE_USER_LOGGED_IN)
		{
			$this->import('FrontendUser', 'User');
		}
		
		$this->blnLocked = $blnLocked;
		
		$this->arrData = $arrData;
		
	}
	
	/**
	 * Return data.
	 *
	 * @access public
	 * @param string $strKey
	 * @return mixed
	 */
	public function __get($strKey)
	{
		if (!isset($this->arrCache[$strKey]))
		{
			switch( $strKey )
			{
				case 'table':
					return $this->strTable;
					break;

				case 'ctable':
					return  $this->ctable;
					break;

				case 'id':
				case 'pid':
					return (int)$this->arrData[$strKey];
					break;
					
				case 'courses':
					
					break;

				default:
					if (array_key_exists($strKey, $this->arrData))
					{
						return deserialize($this->arrData[$strKey]);
					}
					else
					{
						return deserialize($this->arrSettings[$strKey]);
					}
					break;
			}
		}
		return $this->arrCache[$strKey];
	}


	/**
	 * Set data.
	 *
	 * @access public
	 * @param string $strKey
	 * @param string $varValue
	 * @return void
	 */
	public function __set($strKey, $varValue)
	{
		switch( $strKey )
		{
			case 'courses':
				$this->arrData[$strKey] = $varValue;
				break;

			case 'quantity_requested':
				$this->arrCache[$strKey] = $varValue;
				
				if (!$this->blnLocked)
				{
					$this->findPrice();
				}
				break;

			default:
				$this->arrCache[$strKey] = $varValue;
		}
	}


/**
	 * Check whether a property is set
	 * @param string
	 * @return boolean
	 */
	public function __isset($strKey)
	{
		return isset($this->arrData[$strKey]);
	}

	/**
	 * Return the current data as associative array
	 * @return array
	 */
	public function getData()
	{
		return $this->arrData;
	}
	
	
}