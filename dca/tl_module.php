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
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['cb_resultslist']			= '{title_legend},name,headline,type;{redirect_legend},cb_resultsreader_jumpTo, cb_reader_jumpTo;{template_legend:hide},cb_includeMessages,cb_resultslist_layout;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['cb_resultsreader']			= '{title_legend},name,headline,type;{template_legend:hide},cb_includeMessages,cb_resultsreader_layout;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['cb_clientmanager']			= '{title_legend},name,headline,type;{template_legend:hide},cb_includeMessages;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['cb_clientreporting']		= '{title_legend},name,headline,type;{template_legend:hide},cb_includeMessages;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['cb_orderdetails']			= '{title_legend},name,headline,type;{template_legend:hide},cb_includeMessages;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['cb_orderhistory']			= '{title_legend},name,headline,type;{config_legend},iso_config_ids;{redirect_legend},jumpTo;{template_legend},iso_includeMessages;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['fields']['cb_resultsreader_jumpTo'] = array
(
	'label'						=> &$GLOBALS['TL_LANG']['tl_module']['cb_resultsreader_jumpTo'],
	'exclude'                 => true,
	'inputType'               => 'pageTree',
	'explanation'             => 'jumpTo',
	'eval'                    => array('fieldType'=>'radio'),
);

$GLOBALS['TL_DCA']['tl_module']['fields']['cb_reader_jumpTo'] = array
(
	'label'						=> &$GLOBALS['TL_LANG']['tl_module']['cb_reader_jumpTo'],
	'exclude'                 => true,
	'inputType'               => 'pageTree',
	'explanation'             => 'jumpTo',
	'eval'                    => array('fieldType'=>'radio'),
);

$GLOBALS['TL_DCA']['tl_module']['fields']['cb_resultslist_layout'] = array
(
	'label'						=> &$GLOBALS['TL_LANG']['tl_module']['cb_resultslist_layout'],
	'exclude'					=> true,
	'inputType'					=> 'select',
	'options_callback'			=> array('tl_module_cb_admin', 'getResultsListTemplates'),
	'eval'						=> array('includeBlankOption'=>true, 'tl_class'=>'w50'),
);

$GLOBALS['TL_DCA']['tl_module']['fields']['cb_resultsreader_layout'] = array
(
	'label'						=> &$GLOBALS['TL_LANG']['tl_module']['cb_resultsreader_layout'],
	'exclude'					=> true,
	'inputType'					=> 'select',
	'options_callback'			=> array('tl_module_cb_admin', 'getResultsReaderTemplates'),
	'eval'						=> array('includeBlankOption'=>true, 'tl_class'=>'w50'),
);

class tl_module_cb_admin extends Backend
{

	/**
	 * Return results templates as array
	 * @param object
	 * @return array
	 */
	public function getResultsListTemplates(DataContainer $dc)
	{
		$intPid = $dc->activeRecord->pid;

		if ($this->Input->get('act') == 'overrideAll')
		{
			$intPid = $this->Input->get('id');
		}

		return $this->getTemplateGroup('mod_cb_resultslist', $intPid);
	}
	
	/**
	 * Return results templates as array
	 * @param object
	 * @return array
	 */
	public function getResultsReaderTemplates(DataContainer $dc)
	{
		$intPid = $dc->activeRecord->pid;

		if ($this->Input->get('act') == 'overrideAll')
		{
			$intPid = $this->Input->get('id');
		}

		return $this->getTemplateGroup('mod_cb_resultsreader', $intPid);
	}

}


?>