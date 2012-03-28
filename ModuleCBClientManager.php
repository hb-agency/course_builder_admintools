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
 * @copyright  Winans Creative 2011, Helmut SchottmÙller 2009
 * @author     Blair Winans <blair@winanscreative.com>
 * @author     Fred Bliss <fred.bliss@intelligentspark.com>
 * @author     Adam Fisher <adam@winanscreative.com>
 * @author     Includes code from survey_ce module from Helmut SchottmÙller <typolight@aurealis.de>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */

class ModuleCBClientManager extends ModuleCB
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_cb_clientmanager';
	
	/**
	 * Form ID
	 * @var string
	 */
	protected $strFormId = 'cb_client_manager';
	

	/**
	 * Total attempts and used attempts array
	 * @var int
	 */
	protected $arrAttempts;
	
	/**
	 * array of al user IDs
	 * @var array
	 */
	protected $arrUsers;
	
	/**
	 * Errors array
	 * @var int
	 */
	protected $arrErrors;
	
	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### COURSE BUILDER: CLIENT MANAGER ###';
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
		$arrClients = array();
		$intCourseCount = 0;
		
		if(!FE_USER_LOGGED_IN)
		{
			$this->arrErrors['no_results'] = $GLOBALS['TL_LANG']['CB']['ERR']['no_results'];
			$this->Template->hideForm = true;
		}
		
		//Compile any uploaded members
		$this->Template->uploader = $this->getClientUploader();
		
		
		//Change submitted data
		if($this->Input->post('FORM_SUBMIT')== $this->strFormId)
		{
			//Update values
			$arrSubmit = $this->Input->post('coursewizard');
			foreach($arrSubmit as $client=>$attempts)
			{
				$this->updateAttempts($client,$attempts);
			}					
		}
		
		$intUser = FE_USER_LOGGED_IN ? $this->User->id : null;
		
		//Compile total attempts per course per superadmin
		$this->getTotalAttempts();
		
		if(!count($this->arrAttempts))
		{
			$this->arrErrors['no_courseattempts'] = $GLOBALS['TL_LANG']['CB']['ERR']['no_courseattempts'];
			$this->Template->hideForm = true;
		}
		
		//Find existing clients and list them
		$objClient = $this->Database->prepare("SELECT m.*, c.id as parentid FROM tl_cb_member_clients c, tl_member m WHERE c.pid=? AND m.id=c.clientid")->execute($intUser);
		
		if(!$objClient->numRows)
		{
			$this->arrErrors['no_results'] = $GLOBALS['TL_LANG']['CB']['ERR']['no_results'];
			$this->Template->hideForm = true;
		}
		
		$row=0;
		
		//Add client users
		while($objClient->next())
		{
			$arrResults = $this->getUserCourseAttempts($objClient, $row);
			if(count($arrResults))
			{
				$arrClients[$objClient->id] = $arrResults;
				$row++;
			}
			
		}

		//Generate the user widget	
		$objWidget = new FormCourseAttemptsWizard();	
		$objWidget->value = $arrClients;
		$objWidget->id = 'coursewizard';
		$objWidget->tableless = true;
		
		ksort($this->arrAttempts);
		
		$this->Template->courseinfo = $this->arrAttempts;
		$this->Template->action = 	$this->Environment->request;
		$this->Template->formId = 	$this->strFormId;
		$this->Template->clients = 	$objWidget->parse();
		$this->Template->errors = 	$this->arrErrors;
		$this->Template->courseCount = $intCourseCount;
		$this->Template->userLabel = $GLOBALS['TL_LANG']['CB']['MISC']['users'];
		$this->Template->courseLabel = $GLOBALS['TL_LANG']['CB']['MISC']['courses'];	
	}

	/**
	 * Get an array of course data
	 * @access protected
	 * @param Database result
	 * @param int
	 * @return array
	 */
	protected function getUserCourseAttempts($objUser, $row=0)
	{
		$arrReturn = array();
		$arrAttempts = array();
		
		$objCourses = $this->Database->prepare("SELECT c.* FROM tl_cb_member_courses c LEFT JOIN tl_cb_member_clients m ON c.pid=m.id WHERE m.clientid=? ORDER BY courseid ASC")->execute($objUser->id);
		
		if($objCourses->numRows)
		{
		
			while( $objCourses->next() )
			{
			
				$objCourse = $this->Database->prepare("SELECT * FROM tl_cb_course WHERE id=?")->limit(1)->execute( $objCourses->courseid );
				
				if($objCourse->numRows)
				{							
					//Compare assigned attempts to attempts available
					if( ( (int)$this->arrAttempts[$objCourse->id]['assigned'] + $objCourses->totalattempts ) > $this->arrAttempts[$objCourse->id]['purchased'])
					{
						$this->arrErrors['too_many_assigned'] = $GLOBALS['TL_LANG']['CB']['ERR']['too_many_assigned'];
						//Now you've done it. We have to update all future users' attempts to zero
						$this->Database->execute("UPDATE tl_cb_member_courses SET totalattempts=0 WHERE pid={$objCourses->pid} AND courseid={$objCourses->courseid}");
						$arrAttempts[] = array('name'=>$objCourse->name, 'id'=>$objCourse->id, 'attempts'=>0);
						
					}
					else
					{
						$arrAttempts[] = array('name'=>$objCourse->name, 'id'=>$objCourse->id, 'attempts'=>$objCourses->totalattempts);
						$this->arrAttempts[$objCourse->id]['assigned'] = $this->arrAttempts[$objCourse->id]['assigned'] + (int) $objCourses->totalattempts;
					}
				}	
			}
		}
		else //User is a client but no assigned attempts. Create a blank record for each course assigned to the admin and set to 0
		{
			$arrPurchased = deserialize($this->User->courses);
			
			foreach($arrPurchased as $course)
			{
				$objCourse = $this->Database->prepare("SELECT * FROM tl_cb_course WHERE id=?")->limit(1)->execute($course['select']);
			
				$arrSet = array(
					'pid'			=> $objUser->parentid,
					'tstamp' 		=> time(),
					'courseid'		=> $objCourse->id,
					'totalattempts' => 0
				);
				
				$this->Database->prepare("INSERT INTO tl_cb_member_courses %s")->set($arrSet)->execute();
				
				$arrAttempts[] = array('name'=>$objCourse->name, 'id'=>$objCourse->id, 'attempts'=>0);
			}
		}
				
		$strClass = $row%2 ? ' row_even' : ' row_odd';
		
		$arrReturn = array(
			'firstname'	=> $objUser->firstname,
			'lastname'	=> $objUser->lastname,
			'attempts'	=> $arrAttempts,
			'class'		=> $strClass
		);
		
		return $arrReturn;
	
	}
	
	/**
	 * Return total attempts vs. used attempts for the logged in user
	 * @access protected
	 */
	protected function getTotalAttempts()
	{				
		
		$arrPurchased = deserialize($this->User->courses);
			
		foreach($arrPurchased as $course)
		{
			$objCourse = $this->Database->prepare("SELECT * FROM tl_cb_course WHERE id=?")->limit(1)->execute($course['select']);
			$this->arrAttempts[$objCourse->id]['purchased'] = (int) $course['text'];
			$this->arrAttempts[$objCourse->id]['name'] = $objCourse->name;
			
			if($this->arrAttempts[$objCourse->id]['available'] > $this->arrAttempts[$objCourse->id]['purchased'] )
				$this->arrAttempts[$objCourse->id]['available'] = $this->arrAttempts[$objCourse->id]['purchased'];
		}
		
		$objCourse = $this->Database->prepare("SELECT mco.*, mco.courseid as id FROM tl_cb_member_courses mco INNER JOIN tl_cb_member_clients mcl ON mco.pid=mcl.id WHERE mcl.pid=? ORDER BY mco.courseid ASC")->execute($this->User->id);
		
		while($objCourse->next())
		{
			$this->arrAttempts[$objCourse->id]['used'] = (int) $objCourse->usedattempts + $this->arrAttempts[$objCourse->id]['used'];
			$this->arrAttempts[$objCourse->id]['available'] = $this->arrAttempts[$objCourse->id]['purchased'] - $this->arrAttempts[$objCourse->id]['used'];
		}
		
		$this->arrAttempts[$objCourse->id]['assigned'] = 0;
		
	}
	
	
	/**
	 * update a user's course attempts
	 * @access protected
	 */
	protected function updateAttempts($intUser, $arrData)
	{		
		$objClient = $this->Database->prepare("SELECT id FROM tl_cb_member_clients WHERE clientid=? AND pid=?")->execute($intUser, $this->User->id);
		
		foreach($arrData as $course=>$value)
		{
			$this->Database->prepare("UPDATE tl_cb_member_courses SET totalattempts=? WHERE pid=? AND courseid=?")->execute($value, $objClient->id, $course);	
		}
			
	}
	
	/**
	 * build a form that allowed upload of a CSV file of users
	 * @access protected
	 */
	protected function getClientUploader()
	{				
		$objUploader = new FormFileUpload();
		$objUploader->mandatory = true;
		$objUploader->name = $this->strFormId .  '_upload';
		$objUploader->id = $this->strFormId .  '_upload';
		$objUploader->extensions = 'csv';
		$objUploader->storeFile = true;
		$objUploader->uploadFolder = 'system/tmp';
		$objUploader->tableless = true;
		$objUploader->value = $this->Input->post($this->strFormId .  '_upload') ? $this->Input->post($this->strFormId .  '_upload') : '';
		
		$objCheckbox = new FormCheckBox();
		$objCheckbox->name = $this->strFormId .  '_checkbox';
		$objCheckbox->id = $this->strFormId .  '_checkbox';
		$objCheckbox->tableless = true;
		$objCheckbox->options = array( array('value'=>'1', 'label'=>'Append to existing' ));
		
		if($this->Input->post('FORM_SUBMIT')== $this->strFormId . '_uploader')
		{
			$objUploader->validate();
			
			if ($objUploader->hasErrors())
			{
				$this->doNotSubmit = true;
			}
			else
			{				
				$this->import('String');
				
				$intUser = FE_USER_LOGGED_IN ? $this->User->id : null;
				$arrExisting = $this->Database->prepare("SELECT m.* FROM tl_cb_member_clients c, tl_member m WHERE c.pid=? AND m.id=c.clientid")->execute($intUser)->fetchEach('id');
				
				//Update values
				$arrRows = array();
				$objFile = new File('system/tmp/' . $_SESSION['FILES'][$this->strFormId .  '_upload']['name']);
				$resFile = $objFile->handle;
				while(($arrRow = @fgetcsv($resFile, null, ',')) !== false)
				{
					$arrRows[] = $arrRow;
				}
								
				foreach($arrRows as $user)
				{
					//see if username and email combo exists
					$objUser = $this->Database->prepare("SELECT * FROM tl_member WHERE username=? AND email=?")->limit(1)->execute($user[2],$user[3]);
					
					//Existing user - update info
					if($objUser->numRows || in_array($objUser->id, $arrExisting))
					{
						$this->updateUserData($user, $objUser);
					}
					else
					{
						$this->createUserData($user);
					}
					
				}
				
				//Delete file
				$objFile->delete();
			}
			
			//Reload page
			$this->reload();
			
		}
		
		return $objUploader->parse() . '<br/>' . $objCheckbox->parse();
			
	}
	
	/**
	 * create a new frontend user from an array of data
	 * @access protected
	 */
	protected function createUserData($arrData)
	{
		$strSalt = substr(md5(uniqid(mt_rand(), true)), 0, 23);
		
		$arrUser = array(
			'firstname' => $arrData[0],
			'lastname'	=> $arrData[1],
			'username'	=> $arrData[2],
			'email'		=> $arrData[3],
			'password'	=> sha1($strSalt . $arrData[4]) . ':' . $strSalt,
			'tstamp' 	=> time(),
			'login' 	=> 1,
			'activation' => md5(uniqid(mt_rand(), true)),
			'dateAdded' => time()
		);
		
		//Find CourseClient Group... if doesn't exist, create one.
		$arrGroup = array();
		$objGroup = $this->Database->execute("SELECT * FROM tl_member_group WHERE name='CourseClient'");
		if(!$objGroup->numRows)
		{
			try
			{
				$arrGroup[] = $this->Database->execute("INSERT INTO tl_member_group (name) VALUES ('CourseClient')")->insertID;
			}
			catch (Exception $e)
			{
				return;
			}
			
		}
		else
		{
			$arrGroup[] = $objGroup->id;
		}
		
		// Set default group
		$arrUser['groups'] = serialize($arrGroup);
		// Auto-activate account
		$arrUser['disable'] = 0;
				
		try
		{
			// Create user
			$objNewUser = $this->Database->prepare("INSERT INTO tl_member %s")->set($arrUser)->execute();
			$insertId = $objNewUser->insertId;			
			
			$arrUpdate = array(
				'pid'	=> $this->User-id,
				'tstamp' 	=> time(),
				'clientid' => $insertId
			);
			
			//Add user as a client of the logged in user
			$objNewClient = $this->Database->prepare("INSERT INTO tl_cb_member_clients %s")->set($arrUpdate)->execute();
			$clientId = $objNewClient->insertId;	
			
			//Update course data for client
			$arrCourses = array_slice($arrData,5);
			
			for($i=0; $i<count($arrCourses); $i+=2)
			{
				if( $arrCourses[$i] && $arrCourses[$i+1] )
				{
					$arrCourseKeys[$arrCourses[$i]] = $arrCourses[$i+1];
				}
			}
			
			if( count($arrCourseKeys) )
			{
				foreach($arrCourseKeys as $course=>$attempts)
				{
					$objCourse = $this->Database->prepare("SELECT * FROM tl_cb_course WHERE name=?")->limit(1)->execute($course);
					
					if($objCourse->numRows)
					{
					
						$arrCourseUpdate = array(
							'pid'			=> $clientId,
							'tstamp' 		=> time(),
							'courseid'		=> $objCourse->id,
							'totalattempts' => $attempts
						);
					
						$this->Database->prepare("INSERT INTO tl_cb_member_courses %s")->set($arrCourseUpdate)->execute();
					}
				}
			}
			
		}
		catch (Exception $e)
		{
			$this->log('Create User Failed On Course Manager', 'ModuleCBClientManager createUserData()', TL_ERROR);
			return;
		}
		
		// HOOK: send insert ID and user data
		if (isset($GLOBALS['TL_HOOKS']['createNewUser']) && is_array($GLOBALS['TL_HOOKS']['createNewUser']))
		{
			foreach ($GLOBALS['TL_HOOKS']['createNewUser'] as $callback)
			{
				$this->import($callback[0]);
				$this->$callback[0]->$callback[1]($insertId, $arrUser);
			}
		}
		
		/*
		$strConfirmation = 'Thank you for your registration on '.$this->Environment->host.'.'. "\n\n";
		$strConfirmation .= 'Your username is: '.$arrData['username']." \n";
		$strConfirmation .= 'and your password is: '.$clearPass."\n\n";
		$strConfirmation .= 'Please keep this info in a safe place and use it to re-login to your account. '. "\n";
				
		$objEmail = new Email();

		$objEmail->from = 'admin@mysite.com';
		$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
		$objEmail->subject = sprintf($GLOBALS['TL_LANG']['MSC']['emailSubject'], $this->Environment->host);
		$objEmail->text = $strConfirmation;
		$objEmail->sendTo($arrData['email']);
		*/
	
	}

	/**
	 * update a user's course data
	 * @access protected
	 */
	protected function updateUserData($arrData, $objUser)
	{
		$strSalt = substr(md5(uniqid(mt_rand(), true)), 0, 23);
		
		$arrUser = array(
			'firstname' => $arrData[0],
			'lastname'	=> $arrData[1],
			'username'	=> $arrData[2],
			'email'		=> $arrData[3],
			'password'	=> sha1($strSalt . $arrData[4]) . ':' . $strSalt,
			'tstamp' 	=> time(),
		);
		
		//Find CourseClient Group... if doesn't exist, create one.
		$arrGroup = array();
		$objGroup = $this->Database->execute("SELECT * FROM tl_member_group WHERE name='CourseClient'");
		if(!$objGroup->numRows)
		{
			try
			{
				$arrGroup[] = $this->Database->execute("INSERT INTO tl_member_group (name) VALUES ('CourseClient')")->insertID;
			}
			catch (Exception $e)
			{
				return;
			}
			
		}
		else
		{
			$arrGroup[] = $objGroup->id;
		}
		
		// Set default group
		$arrUser['groups'] = serialize($arrGroup);
		// Auto-activate account
		$arrUser['disable'] = 0;
				
		try
		{
			// Update user
			$objNewUser = $this->Database->prepare("UPDATE tl_member %s WHERE id=?")->set($arrUser)->execute($objUser->id);		
			
			$arrUpdate = array(
				'pid'	=> $this->User-id,
				'tstamp' 	=> time(),
				'clientid' => $objUser->id
			);
			
			//Add user as a client of the logged in user if they are not already
			$objCurrClient = $this->Database->prepare("SELECT id as insertId FROM tl_cb_member_clients WHERE clientid=?")->execute($objUser->id);
			if(!$objCurrClient->numRows)
			{
				$objCurrClient = $this->Database->prepare("INSERT INTO tl_cb_member_clients %s")->set($arrUpdate)->execute();
			}
				
			
			//Update course data for client
			$arrCourses = array_slice($arrData,5);
			
			for($i=0; $i<count($arrCourses); $i+=2)
			{
				if( strlen($arrCourses[$i]) && $arrCourses[$i+1] )
				{
					$arrCourseKeys[$arrCourses[$i]] = $arrCourses[$i+1];
				}
			}
			
			
			if( count($arrCourseKeys) )
			{
				foreach($arrCourseKeys as $course=>$attempts)
				{
					$objCourse = $this->Database->prepare("SELECT * FROM tl_cb_course WHERE name=?")->limit(1)->execute($course);
					
					if($objCourse->numRows)
					{
					
						$arrCourseUpdate = array(
							'pid'			=> $objCurrClient->insertId,
							'tstamp' 		=> time(),
							'courseid'		=> $objCourse->id,
							'totalattempts' => $attempts
						);
												
						//See if record exists to update.
						$objCurrCourse = $this->Database->prepare("SELECT id FROM tl_cb_member_courses WHERE pid=? AND courseid=?")->execute($objCurrClient->insertId, $objCourse->id);
						if($objCurrCourse->numRows)
						{	
							$this->Database->prepare("UPDATE tl_cb_member_courses %s WHERE id=? AND courseid=?")->set($arrCourseUpdate)->execute($objCurrCourse->id, $objCourse->id);
						}
						else
						{
							$this->Database->prepare("INSERT INTO tl_cb_member_courses %s")->set($arrCourseUpdate)->execute();
						}
					}
				}
			}
			
		}
		catch (Exception $e)
		{
			$this->log('Update User Failed On Course Manager:' . $e, 'ModuleCBClientManager updateUserData()', TL_ERROR);
			return;
		}

	
	}

	/**
	 * delete a user and reset their course data
	 * @access protected
	 */
	protected function deleteUserData($arrData)
	{
		
	
	}


}