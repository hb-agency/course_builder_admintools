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
 * @copyright  Winans Creative 2011, Helmut SchottmŸller 2009
 * @author     Blair Winans <blair@winanscreative.com>
 * @author     Fred Bliss <fred.bliss@intelligentspark.com>
 * @author     Adam Fisher <adam@winanscreative.com>
 * @author     Includes code from survey_ce module from Helmut SchottmŸller <typolight@aurealis.de>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */

class ModuleCBFrontendReporting extends BackendModule
{

	/**
	 * Data container object
	 * @var object
	 */
	protected $objDc;

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'fe_cb_reporting';

	protected $strError = false;

	/**
	 * Construct
	 */
	public function __construct()
	{
	    parent::__construct();

		$this->loadLanguageFile('tl_cb_reporting');
	    $this->loadDataContainer('tl_cb_reporting');
	    
	    $this->loadDataContainer('tl_cb_course');
	    $this->loadLanguageFile('tl_cb_course');
	    
	    $this->loadDataContainer('tl_cb_quiz');
	    $this->loadLanguageFile('tl_cb_quiz');
	}


	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### COURSE BUILDER CLIENT REPORT ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = $this->Environment->script.'?do=modules&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		return parent::generate();
	}


	protected function compile()
	{
		// Load scripts
		$GLOBALS['TL_CSS'][] = 'system/modules/course_builder_admintools/html/reporting.css';

		$this->import('FrontendUser', 'User');

		$arrResults = array();
		
		if (TL_MODE == 'FE' && FE_USER_LOGGED_IN)
		{
			if (strlen($this->Input->get('act')) && $this->Input->get('act') == 'print')
				$this->generatePDF();
			
			$this->Template->print_href = $this->Environment->request . ((stripos($this->Environment->request, '?') !== false) ? '&act=print' : '?act=print');
			$this->Template->print_label = $GLOBALS['TL_LANG']['tl_cb_reporting']['print_report'];
			$this->Template->results = $this->getTemplateContent();
		}
	}
	
	protected function getTemplateContent($blnPrintPDF=false)
	{	
		// Get collection object
		if (($objResults = $this->getResults()) != true)
		{
			return;
		}

		$i=0;

		if($objResults->numRows)
		{
			//the report format should have a hook here to change output from standard text output to graphical output via a callback & 3rd party graphical reporting option.
			if (isset($GLOBALS['CB_HOOKS']['outputReport']) && is_array($GLOBALS['CB_HOOKS']['outputReport']))
			{
				foreach ($GLOBALS['CB_HOOKS']['outputReport'] as $callback)
				{
					$this->import($callback[0]);
					$strBuffer = $this->$callback[0]->$callback[1]($objResults, 'results');	//TODO: include what report type this is
				}
			}
			else
			{
				if ($blnPrintPDF)
					$objTemplate = new FrontendTemplate('cb_frontend_reporting_pdf');
				else
					$objTemplate = new FrontendTemplate('cb_frontend_reporting_list');

				$arrHeadingData[] = 'Course';
				$arrHeadingData[] = 'Member';
				$arrHeadingData[] = 'Date';
				$arrHeadingData[] = 'Passed';
				$arrHeadingData[] = 'Score';

				while($objResults->next())
				{
					$arrColumnData = array();	//reset

					$i++;

					$arrColumnData[] = array
					(
						'html' 		=> '<strong>'.$objResults->course_name.'</strong>',
						'class'		=> ''
					);

					$arrColumnData[] = array
					(
						'html'		=> ucwords(strtolower($objResults->member_firstname . ' ' . $objResults->member_lastname)),
						'class'		=> ''
					);

					$arrColumnData[] = array
					(
						'html'		=> $this->parseDate($GLOBALS['TL_CONFIG']['dateFormat'], $objResults->quiz_tstamp),
						'class'		=> ''
					);

					$arrColumnData[] = array
					(
						'html'		=> (($objResults->quiz_passed == '1') ? 'Y' : 'N'),
						'class'		=> ''
					);

					$arrColumnData[] = array
					(
						'html'		=> '<strong>'.((is_numeric($objResults->quiz_score)) ? round($objResults->quiz_score, 2) : $objResults->quiz_score).'</strong>',
						'class'		=> ''
					);

					$arrRows[] = array
					(
						'columns'	=> $arrColumnData
					);

					$fltTotalRevenue += $objResults->total_price;
				}

				$objTemplate->headings = $arrHeadingData;
				$objTemplate->rows = $arrRows;

				$objTemplate->noResults = '';

				$strBuffer .= $objTemplate->parse();
			}
			
			return $strBuffer;
		}
		else
		{
			return '<div class="tl_gerror">'.$GLOBALS['TL_LANG']['MSC']['noResults'].'</div>';
		}
	}

	/**
	 * Select all collections for the current table from the DB and return the result objects
	 * @return object
	 */
	protected function getResults()
	{
	    //$this->loadLanguageFile('tl_cb_quiz');

		//Initial query		
		$query = "SELECT DISTINCT c.`name` AS `course_name`,
					m.`firstname` AS `member_firstname`,
					m.`lastname` AS `member_lastname`,
					ah.`id` AS `accountholder_id`,
					ah.`firstname` AS `accountholder_firstname`,
					ah.`lastname` AS `accountholder_lastname`,
					cdata.`pass` AS `quiz_passed`,
					qdata.`score` AS `quiz_score`,
					qdata.`tstamp` AS `quiz_tstamp`,
					q.`passing_score` AS `quiz_passing_score` 
				FROM `tl_cb_course` c
					LEFT OUTER JOIN `tl_cb_coursedata` cdata
						ON c.`id` = cdata.`courseid`
					LEFT OUTER JOIN `tl_member` m
						ON cdata.pid = m.`id`
					LEFT OUTER JOIN `tl_cb_quizdata` qdata
						ON cdata.`id` = qdata.`pid`
					LEFT OUTER JOIN `tl_cb_quiz` q
						ON qdata.`quizid` = q.`id`
					LEFT OUTER JOIN `tl_cb_member_clients` mcl
						ON m.`id` = mcl.`clientid`
					LEFT OUTER JOIN `tl_cb_member_courses` mco
						ON mcl.`clientid` = mco.`pid`
					LEFT OUTER JOIN `tl_member` ah
						ON mcl.`pid` = ah.`id`
				WHERE cdata.`tstamp` != 0
					AND ah.`id` = ?";

		// Order by & group by
		$query .= " ORDER BY c.`name`, m.`firstname`, m.`lastname`";

		// Execute query
		$objCollection = $this->Database->prepare($query)->execute($this->User->id);

		if ($objCollection->numRows < 1)
		{
			return null;
		}

		return $objCollection;
	}
	
	
	/**
	 * Generate the collection using a template. Useful for PDF output.
	 *
	 * @param  string
	 * @return string
	 */
	public function getPdfTemplate($strTemplate=null, $blnResetConfig=true)
	{
		$strArticle = $this->replaceInsertTags($this->getTemplateContent(true));
		$strArticle = html_entity_decode($strArticle, ENT_QUOTES, $GLOBALS['TL_CONFIG']['characterSet']);
		$strArticle = $this->convertRelativeUrls($strArticle, '', true);

		// Remove form elements and JavaScript links
		$arrSearch = array
		(
			'@<form.*</form>@Us',
			'@<a [^>]*href="[^"]*javascript:[^>]+>.*</a>@Us'
		);

		$strArticle = preg_replace($arrSearch, '', $strArticle);

		// Handle line breaks in preformatted text
		$strArticle = preg_replace_callback('@(<pre.*</pre>)@Us', 'nl2br_callback', $strArticle);

		// Default PDF export using TCPDF
		$arrSearch = array
		(
			'@<span style="text-decoration: ?underline;?">(.*)</span>@Us',
			'@(<img[^>]+>)@',
			'@(<div[^>]+block[^>]+>)@',
			'@[\n\r\t]+@',
			'@<br /><div class="mod_article@',
			'@href="([^"]+)(pdf=[0-9]*(&|&amp;)?)([^"]*)"@'
		);

		$arrReplace = array
		(
			'<u>$1</u>',
			'<br />$1',
			'<br />$1',
			' ',
			'<div class="mod_article',
			'href="$1$4"'
		);

		$strArticle = preg_replace($arrSearch, $arrReplace, $strArticle);

		return $strArticle;
	}


	public function generatePDF($strTemplate=null, $pdf=null, $blnOutput=true)
	{
		if (!is_object($pdf))
		{
			// TCPDF configuration
			$l['a_meta_dir'] = 'ltr';
			$l['a_meta_charset'] = $GLOBALS['TL_CONFIG']['characterSet'];
			$l['a_meta_language'] = $GLOBALS['TL_LANGUAGE'];
			$l['w_page'] = 'page';

			// Include library
			require_once(TL_ROOT . '/system/config/tcpdf.php');
			require_once(TL_ROOT . '/plugins/tcpdf/tcpdf.php');

			// Create new PDF document
			$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true);

			// Set document information
			$pdf->SetCreator(PDF_CREATOR);
			$pdf->SetAuthor(PDF_AUTHOR);

// @todo $objInvoice is not defined
//			$pdf->SetTitle($objInvoice->title);
//			$pdf->SetSubject($objInvoice->title);
//			$pdf->SetKeywords($objInvoice->keywords);

			// Remove default header/footer
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);

			// Set margins
			$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

			// Set auto page breaks
			$pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

			// Set image scale factor
			$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

			// Set some language-dependent strings
			$pdf->setLanguageArray($l);

			// Initialize document and add a page
			$pdf->AliasNbPages();

			// Set font
			$pdf->SetFont(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN);
		}

		// Start new page
		$pdf->AddPage();

		// Write the HTML content
		$pdf->writeHTML($this->getPdfTemplate(), true, 0, true, 0);

		if ($blnOutput)
		{
			// Close and output PDF document
			// @todo $strInvoiceTitle is not defined
			$pdf->lastPage();
			$pdf->Output(standardize(ampersand('cbreport', false), true) . '.pdf', 'D');
			//$pdf->Output(standardize(ampersand('cbreport', false), true) . '.pdf', 'I');  // For debugging

			// Stop script execution
			exit;
		}

		return $pdf;
	}
}