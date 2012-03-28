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
 
 
class FormCourseAttemptsWizard extends Widget
{
	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'form_widget';


	/**
	 * Add specific attributes
	 * @param string
	 * @param mixed
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'value':
				$this->varValue = deserialize($varValue);
				break;

			case 'mandatory':
				$this->arrConfiguration['mandatory'] = $varValue ? true : false;
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}


	/**
	 * Recursively validate an input variable
	 * @param mixed
	 * @return mixed
	 */
	protected function validator($varInput)
	{
		if (!strlen($varInput) && !$this->mandatory)
		{
			return '';
		}

		if ($this->mandatory && !strlen(trim($varInput)))
		{
			if ($this->strLabel == '')
			{
				$this->addError($GLOBALS['TL_LANG']['ERR']['mdtryNoLabel']);
			}
			else
			{
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $this->strLabel));
			}
		}

		if (strlen($this->rgxp))
		{
			switch ($this->rgxp)
			{
				// Unique select values
				case 'unique':
					$arrSelects = array();
					foreach($varInput as $input)
					{
						if(in_array($input['select'], $arrSelects))
						{
							$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['unique'], $this->strLabel));
						}
						$arrSelects[] = $input['select'];
					}
					return $varInput;
					break;

				
				// HOOK: pass unknown tags to callback functions
				default:
					if (isset($GLOBALS['TL_HOOKS']['addCustomRegexp']) && is_array($GLOBALS['TL_HOOKS']['addCustomRegexp']))
					{
						foreach ($GLOBALS['TL_HOOKS']['addCustomRegexp'] as $callback)
						{
							$this->import($callback[0]);
							$break = $this->$callback[0]->$callback[1]($this->rgxp, $varInput, $this);

							// Stop the loop if a callback returned true
							if ($break === true)
							{
								break;
							}
						}
					}
					break;
			}
		}

		return $varInput;
	}



	/**
	 * Generate the widget and return it as string
	 * @return string
	 */
	public function generate()
	{
		$GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/course_builder_admintools/html/courseattemptswizard.js';
		$GLOBALS['TL_CSS'][] = 'system/modules/course_builder_admintools/html/courseattemptswizard.css';

		$arrButtons = array('delete');

		
		// Make sure there is at least an empty array
		if (!is_array($this->varValue) || count($this->varValue) == 0)
		{
			$this->varValue[0] = array('select'=>'', 'text'=>'');
		}
				
		$wizard = ($this->wizard) ? '<div class="tl_wizard">' . $this->wizard . '</div>' : '';
		// Add label
		$return .= '<div class="tl_multitextwizard">' . $wizard . '
	  <table cellspacing="0" cellpadding="0" id="ctrl_'.$this->strId.'" summary="Text wizard">';
		$hasTitles = array_key_exists('buttonTitles', $this->arrConfiguration) && is_array($this->arrConfiguration['buttonTitles']);
		
		
		// Add input fields
		foreach ($this->varValue as $i=>$client)
		{
			$return .= '<tr><td>'.$this->varValue[$i]['firstname'].'</td><td>'.$this->varValue[$i]['lastname'].'</td>';
			
			$return .= '<td style="padding-right: 5px;">';
			
			foreach($this->varValue[$i]['attempts'] as $attempt)
			{
			$return .= '<span>' . $attempt['name'] .' <input type="text" name="'.$this->strId.'['.$i.']['.$attempt['id'].']" class="tl_attempts" value="'.specialchars($attempt['attempts']).'"' . $this->getAttributes() . ' /></span>';
			}
			
			$return .= '</td>';
			
			$return .= '<td style="white-space:nowrap;">';
			// Add buttons
			foreach ($arrButtons as $button)
			{
				$buttontitle = ($hasTitles && array_key_exists($button, $this->arrConfiguration['buttonTitles'])) ? $this->arrConfiguration['buttonTitles'][$button] : $GLOBALS['TL_LANG'][$this->strTable][$button][0];
				$return .= '<a href="'.$this->addToUrl('&amp;'.$strCommand.'='.$button.'&amp;cid='.$i.'&amp;id='.$this->currentRecord).'" title="'.specialchars($buttontitle).'" onclick="CourseAttemptsWizard.selectvalueWizard(this, \''.$button.'\', \'ctrl_'.$this->strId.'\'); return false;">'.$this->generateImage($button.'.gif', $buttontitle, 'class="tl_listwizard_img"').'</a> ';
			}
			$return .= '</td></tr>';
		}

		return $return.'
  </table></div>';
	}
	
	
	
	/**
	 * Generate the text widget and return it as string
	 * @return string
	 */
	protected function generateText($intNum, $strLabel, $varValue=null)
	{
		$strClass = 'tl_inputvalue';
		$strName = $this->strId.'['.$intNum.']['.$strLabel.']';

		return sprintf('<input name="%s" id="ctrl_%s" class="%s%s"%s onfocus="Backend.getScrollOffset();" value="%s" readonly="readonly" />',
						$strName,
						$this->strId,
						$strClass,
						(strlen($this->strClass) ? ' ' . $this->strClass : ''),
						$this->getAttributes(),
						$varValue
					);
	}

	
}

?>