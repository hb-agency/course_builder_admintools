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
 * Table tl_cb_member_courses
 */
$GLOBALS['TL_DCA']['tl_cb_member_courses'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_cb_member_clients',
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('sorting'),
			'panelLayout'             => 'search,limit',
			'headerFields'            => array('clientid'),
			'child_record_callback'   => array('tl_cb_member_courses','renderLabel')
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();" accesskey="e"'
			)
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_content']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_content']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_content']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_content']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"',
				'button_callback'     => array('tl_cb_member_courses', 'deleteElement')
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_content']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		),
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{course_legend},courseid,totalattempts,usedattempts',
	),

	// Subpalettes
	'subpalettes' => array
	(

	),

	// Fields
	'fields' => array
	(
		'courseid' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_cb_member_courses']['courseid'],
			'default'                 => 'text',
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_cb_member_courses', 'getCourses'),
			'eval'                    => array('mandatory' => true)
		),
		'totalattempts' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_cb_member_courses']['totalattempts'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>3, 'tl_class'=>'w50')
		),
		'usedattempts' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_cb_member_courses']['usedattempts'],
			'exclude'                 => true,
			'inputType'               => 'text',
			'eval'                    => array('maxlength'=>3, 'tl_class'=>'w50')
		),
	)
);


class tl_cb_member_courses extends Backend
{
	/**
	 * Render the child record label
	 * @return array
	 */
	public function renderLabel($arrClient)
	{
		$objCourse = $this->Database->prepare("SELECT * FROM tl_cb_course WHERE id=?")->limit(1)->execute($arrClient['courseid']);
		return $objCourse->name;
	}
	
	
	/**
	 * Return the delete course element button
	 * @param array
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @param string
	 * @return string
	 */
	public function deleteElement($row, $href, $label, $title, $icon, $attributes)
	{
		$objElement = $this->Database->prepare("SELECT id FROM tl_cb_member_courses WHERE id=? AND usedattempts > 0")
									 ->limit(1)
									 ->execute($row['id']);

		return $objElement->numRows ? $this->generateImage(preg_replace('/\.gif$/i', '_.gif', $icon)) . ' ' : '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
	}
	
	
	/**
	 * Get all UNUSED courses and return as an array
	 * @return array
	 */
	public function getCourses()
	{
		$arrCourses = array();
				
		$objCourses = $this->Database->execute("SELECT id, name FROM tl_cb_course ORDER BY name");
		
		$objSelf = $this->Database->prepare("SELECT * FROM tl_cb_member_courses WHERE id=?")->execute($this->Input->get('id'));
		
		$arrUsedAlready = $this->Database->prepare("SELECT courseid FROM tl_cb_member_courses WHERE pid=?")->execute($objSelf->pid)->fetchEach('courseid');
		
		while ($objCourses->next())
		{
			if(!in_array($objCourses->id,$arrUsedAlready) || $objCourses->id == $objSelf->courseid)
			{
				$arrCourses[$objCourses->id] = $objCourses->name;
			}
		}

		return $arrCourses;
	}


}


?>