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
 * Table tl_cb_member_clients
 */
$GLOBALS['TL_DCA']['tl_cb_member_clients'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'               => 'Table',
		'ptable'                      => 'tl_member',
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                    => 4,
			'fields'                  => array('sorting'),
			'panelLayout'             => 'filter;search,limit',
			'headerFields'            => array('firstname', 'lastname'),
			'child_record_callback'   => array('tl_cb_member_clients','renderLabel')
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
				'label'               => &$GLOBALS['TL_LANG']['tl_cb_member_clients']['edit'],
				'href'                => 'table=tl_cb_member_courses',
				'icon'                => 'edit.gif'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_cb_member_clients']['copy'],
				'href'                => 'act=paste&amp;mode=copy',
				'icon'                => 'copy.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			),
			'cut' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_cb_member_clients']['cut'],
				'href'                => 'act=paste&amp;mode=cut',
				'icon'                => 'cut.gif',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_cb_member_clients']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"',
				'button_callback'     => array('tl_cb_member_clients', 'deleteElement')
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['tl_cb_member_clients']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			),
		),
	),

	// Palettes
	'palettes' => array
	(
		'default'                     => '{client_legend},clientid',
	),

	// Subpalettes
	'subpalettes' => array
	(

	),

	// Fields
	'fields' => array
	(
		'clientid' => array
		(
			'label'                   => &$GLOBALS['TL_LANG']['tl_cb_member_clients']['clientid'],
			'default'                 => 'text',
			'exclude'                 => true,
			'filter'                  => true,
			'inputType'               => 'select',
			'options_callback'        => array('tl_cb_member_clients', 'getClients'),
		)	
	)
);


class tl_cb_member_clients extends Backend
{
	/**
	 * Render the child record label
	 * @return array
	 */
	public function renderLabel($arrClient)
	{
		$objUser = $this->Database->prepare("SELECT * FROM tl_member WHERE id=?")->limit(1)->execute($arrClient['clientid']);
		return $objUser->lastname . ', '. $objUser->firstname;
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
		$objElement = $this->Database->prepare("SELECT id FROM tl_cb_member_courses WHERE pid=? AND usedattempts > 0")
									 ->limit(1)
									 ->execute($row['id']);

		return $objElement->numRows ? $this->generateImage(preg_replace('/\.gif$/i', '_.gif', $icon)) . ' ' : '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.specialchars($title).'"'.$attributes.'>'.$this->generateImage($icon, $label).'</a> ';
	}
	
	
	/**
	 * Get all members and return them as array
	 * @return array
	 */
	public function getClients()
	{

		$arrMembers = array();
		$objMembers = $this->Database->execute("SELECT id, firstname, lastname FROM tl_member ORDER BY firstname");

		while ($objMembers->next())
		{
			$arrMembers[$objMembers->id] = $objMembers->firstname . ' ' . $objMembers->lastname;
		}

		return $arrMembers;
	}


}


?>