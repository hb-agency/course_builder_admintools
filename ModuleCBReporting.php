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

class ModuleCBReporting extends BackendModule
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
	protected $strTemplate = 'be_cb_reporting';


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


	protected function compile()
	{
		// Load scripts
		$GLOBALS['TL_CSS'][] = 'system/modules/course_builder_admintools/html/reporting.css';
		$GLOBALS['TL_CSS'][] = 'plugins/tablesort/css/tablesort.css';
		$GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/course_builder_admintools/html/tablesort_src.js';

		$this->import('BackendUser', 'User');

		$arrResults = array();

		$this->Template->headline		= $GLOBALS['TL_LANG']['tl_cb_reporting']['reports'];
		$this->Template->print_report	= $GLOBALS['TL_LANG']['tl_cb_reporting']['print_report'];
		$this->Template->goBack			= $GLOBALS['TL_LANG']['MSC']['goBack'];
		$this->Template->submit			= $GLOBALS['TL_LANG']['tl_cb_reporting']['generate'];
		
		try
		{
			$this->Template->results = $this->getTemplateContent();
			
			if (TL_MODE == 'BE' && strlen($this->Input->get('key')) && $this->Input->get('key') == 'print_report')
			{
				$this->generatePDF();
			}
		}
		catch (Exception $e)
		{
			$this->errMessage = 'Error -- ' . $e->getMessage();
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
			//$this->import('Isotope');

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
					$objTemplate = new BackendTemplate('cb_reporting_pdf');
				else
					$objTemplate = new BackendTemplate('cb_reporting_list');

				$arrHeadingData[] = 'Course';
				$arrHeadingData[] = 'Member';
				//$arrHeadingData[] = 'Quiz';
				$arrHeadingData[] = 'Date';
				$arrHeadingData[] = 'Attempt';
				$arrHeadingData[] = 'Passed';
				$arrHeadingData[] = 'Score';
				$arrHeadingData[] = 'Account Holder';
				
				if ($blnPrintPDF)
				{
					$arrHeadingData[] = 'Order';
					$arrHeadingData[] = 'Pay Method';
					$arrHeadingData[] = 'Pay Date';
					$arrHeadingData[] = 'Credits Purchased';
					$arrHeadingData[] = 'Credits Used';
					$arrHeadingData[] = 'Last Credit Used On';
				}

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
						'html'		=> ucwords($objResults->member_firstname . ' ' . $objResults->member_lastname),
						'class'		=> ''
					);

					//$arrColumnData[] = array
					//(
					//	'html' 		=> '<strong>'.$objResults->quiz_name.'</strong>',
					//	'class'		=> ''
					//);

					$arrColumnData[] = array
					(
						'html'		=> $this->parseDate($GLOBALS['TL_CONFIG']['dateFormat'], $objResults->quiz_tstamp),
						'class'		=> ''
					);

					$arrColumnData[] = array
					(
						'html'		=> $objResults->quiz_attempt,
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

					$arrColumnData[] = array
					(
						'html'		=> ucwords($objResults->accountholder_firstname . ' ' . $objResults->accountholder_lastname),
						'class'		=> ''
					);

					if ($blnPrintPDF)
					{
						$arrColumnData[] = array
						(
							'html'		=> $objResults->order_id,
							'class'		=> ''
						);
	
						$arrColumnData[] = array
						(
							'html'		=> (($objResults->accountholder_paymethod == 'cc') ? 'Credit Card' : 'Client Code'),
							'class'		=> ''
						);
	
						$arrColumnData[] = array
						(
							'html'		=> strlen($objResults->order_datepaid) ? $this->parseDate($GLOBALS['TL_CONFIG']['dateFormat'], $objResults->order_datepaid) : '',
							'class'		=> ''
						);
							
						// Find tokens separately from the query because the token data is serialized in the order
						$objOrderData = $this->Database->prepare("SELECT * FROM tl_iso_orders WHERE order_id=?")->execute(strlen($objResults->order_id) ? $objResults->order_id : '-1');
							
						$arrTokens = deserialize($objOrderData->tokens, true);
												
						if ($objOrderData->numRows && count($arrTokens))
						{
							$objTokenData = $this->Database->prepare("SELECT * FROM tl_iso_tokens WHERE token=?")->execute(isset($arrTokens[0]) ? $arrTokens[0] : '-1');
							
							$arrColumnData[] = array
							(
								'html'		=> $objTokenData->numRows ? $objTokenData->credits : '',
								'class'		=> ''
							);						
						}
						else
						{
							$arrColumnData[] = array
							(
								'html'		=> '',
								'class'		=> ''
							);
						}
		
						$arrColumnData[] = array
						(
							'html'		=> $objResults->credits_used,
							'class'		=> ''
						);
	
						$arrColumnData[] = array
						(
							'html'		=> strlen($objResults->credits_lastused) ? $this->parseDate($GLOBALS['TL_CONFIG']['dateFormat'], $objResults->credits_lastused) : '',
							'class'		=> ''
						);
					}

					$arrRows[] = array
					(
						'columns'	=> $arrColumnData
					);
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
	    $this->loadLanguageFile('tl_cb_quiz');
		$where = array();
		$value = array();
		$session = $this->Session->getData();

		//Initial query		
		$query = "SELECT DISTINCT c.`name` AS `course_name`,
					m.`firstname` AS `member_firstname`,
					m.`lastname` AS `member_lastname`,
					m.`company` AS `member_company`,
					m.`street` AS `member_street`,
					m.`postal` AS `member_postal`,
					m.`city` AS `member_city`,
					m.`state` AS `member_state`,
					m.`country` AS `member_country`,
					m.`phone` AS `member_phone`,
					m.`mobile` AS `member_mobile`,
					m.`fax` AS `member_fax`,
					m.`email` AS `member_email`,
					m.`website` AS `member_website`,
					m.`username` AS `member_username`,
					m.`dateAdded` AS `member_dateAdded`,
					m.`lastLogin` AS `member_lastLogin`,
					ah.`id` AS `accountholder_id`,
					ah.`firstname` AS `accountholder_firstname`,
					ah.`lastname` AS `accountholder_lastname`,
					CASE WHEN mcl.`clientid` <> mcl.`pid` THEN 'token' ELSE 'cc' END AS `accountholder_paymethod`,
					o.`order_id` AS `order_id`,
					o.`tstamp` AS `order_datepaid`,
					(SELECT mcourses.`totalattempts` FROM `tl_cb_member_clients` mclients 
					INNER JOIN `tl_cb_member_courses` mcourses ON mclients.`id` = mcourses.`pid` WHERE mclients.`id` = mcl.`id` AND mcourses.`courseid` = c.`id`) AS `credits_used`,					
					(SELECT MAX(mcourses2.`tstamp`) FROM `tl_cb_member_clients` mclients2 
					INNER JOIN `tl_cb_member_courses` mcourses2 ON mclients2.`id` = mcourses2.`pid` WHERE mclients2.`id` = mcl.`id` AND mcourses2.`courseid` = c.`id`) AS `credits_lastused`,
					q.`name` AS `quiz_name`,
					cdata.`attempt` AS `quiz_attempt`,
					(SELECT max(`attempt`) FROM `tl_cb_coursedata` WHERE `pid` = m.`id` AND `courseid` = c.`id` AND `status` NOT IN ('archived')) AS `quiz_attempts`,
					cdata.`pass` AS `quiz_passed`,
					qdata.`score` AS `quiz_score`,
					qdata.`status` AS `quiz_status`,
					qdata.`tstamp` AS `quiz_tstamp`,
					q.`passing_score` AS `quiz_passing_score` 
				FROM `tl_cb_coursedata` cdata
					INNER JOIN `tl_cb_course` c
						ON cdata.`courseid` = c.`id`
						AND cdata.`clientid` <> 0
					INNER JOIN `tl_cb_member_clients` mcl
						ON cdata.`clientid` = mcl.`id`
					INNER JOIN `tl_member` m
						ON mcl.`clientid` = m.`id`
					INNER JOIN `tl_cb_quizdata` qdata
						ON cdata.`id` = qdata.`pid`
					INNER JOIN `tl_cb_quiz` q
						ON qdata.`quizid` = q.`id`
					LEFT OUTER JOIN `tl_member` ah
						ON mcl.`pid` = ah.`id`
					LEFT OUTER JOIN `tl_iso_orders` o
						ON cdata.`orderid` = o.`id`
				WHERE cdata.`tstamp` != 0 
					AND cdata.`status` IN ('in_progress', 'complete')
					AND qdata.`status` IN ('in_progress', 'complete')
					AND (CASE WHEN cdata.`status` = 'complete' THEN q.`final` ELSE 1 END) = 1";

		// Set filter
		if ($this->Input->post('FORM_SUBMIT') == 'tl_filters')
		{
			$session['tl_cb_reporting']['status'] = '';
			// Statuses
			if ($this->Input->post('tl_status') != '')
			{
				try
				{
					/*$this->Database->prepare("SELECT * FROM tl_iso_orders WHERE status=?")
								   ->limit(1)
								   ->execute($this->Input->post('tl_status'));*/

					$session['tl_cb_reporting']['status'] = $this->Input->post('tl_status');
				}
				catch (Exception $e) {}
			}


			// Search
			$session['tl_cb_reporting']['search']['value'] = '';
			$session['tl_cb_reporting']['search']['field'] = $this->Input->post('tl_field', true);

			// Make sure the regular expression is valid
			if ($this->Input->postRaw('tl_value') != '' && $this->Input->postRaw('tl_field') != '_search_')
			{
				$arrVal = explode('.', $this->Input->postRaw('tl_field'));
				
				$strTblAlias = strlen($arrVal[0]) ? $arrVal[0] : '';
				$strTable = 'tl_cb_quizdata';
				
				switch ($strTblAlias)
				{
					case 'c':
						$strTable = 'tl_cb_course';
					  	break;
					case 'cdata':
						$strTable = 'tl_cb_coursedata';
					  	break;
					case 'm':
						$strTable = 'tl_member';
					  	break;
					case 'q':
						$strTable = 'tl_cb_quiz';
					  	break;
					case 'qdata':
						$strTable = 'tl_cb_quizdata';
					  	break;
					case 'mcl':
						$strTable = 'tl_cb_member_clients';
					  	break;
					case 'mco':
						$strTable = 'tl_cb_member_courses';
					  	break;
					case 'ah':
						$strTable = 'tl_member';
						break;
					case 't':
						$strTable = 'tl_iso_tokens';
					  	break;
					case 'tu':
						$strTable = 'tl_iso_tokens_usage';
					  	break;
					case 'o':
						$strTable = 'tl_iso_orders';
					  	break;
					case 'oi':
						$strTable = 'tl_iso_order_items';
					  	break;
					default:
						$strTable = 'tl_cb_quizdata';
				}

				try
				{
					$this->Database->prepare("SELECT * FROM {$strTable} {$strTblAlias}  WHERE " . $this->Input->post('tl_field', true) . " REGEXP ?")
								   ->limit(1)
								   ->execute($this->Input->postRaw('tl_value'));

					$session['tl_cb_reporting']['search']['value'] = $this->Input->postRaw('tl_value');
				}
				catch (Exception $e) {}

			}

			// Date Filter
			$session['tl_cb_reporting']['filter']['tstamp_from'] = $this->Input->post('tstamp_from');
			$session['tl_cb_reporting']['filter']['tstamp_to'] = $this->Input->post('tstamp_to');

			$this->Session->setData($session);
			$this->reload();
		}

		// Add status value to query
		if (strlen($session['tl_cb_reporting']['status']))
		{
			$strField = $session['tl_cb_reporting']['status'];

			$where[] = "cdata.status=?";
			$value[] = $strField;

			$this->Template->statusClass = ' active';
		}

		// Add search value to query
		if (strlen($session['tl_cb_reporting']['search']['value']))
		{
			$strField = $session['tl_cb_reporting']['search']['field'];

			$where[] = "CAST(" . $strField . " AS CHAR) REGEXP ?";
			$value[] = $session['tl_cb_reporting']['search']['value'];

			$this->Template->searchClass = ' active';
		}


		// Search options
		$options = '';
		
		$arrSearch = array
		(
			'_search_'			=> '_search_',
			'q.name'			=> 'quizname',
			'm.firstname'		=> 'firstname',
			'm.lastname'		=> 'lastname',
			'm.company'			=> 'company',
			'm.street'			=> 'street',
			'm.postal'			=> 'postal',
			'm.city'			=> 'city',
			'm.state'			=> 'state',
			'm.country'			=> 'country',
			'm.phone'			=> 'phone',
			'm.mobile'			=> 'mobile',
			'm.fax'				=> 'fax',
			'm.email'			=> 'email',
			'm.website'			=> 'website',
			'm.username'		=> 'username',
			'm.dateAdded'		=> 'dateadded',
			'm.lastLogin'		=> 'lastlogin',			
			'ah.firstname'		=> 'ahfirstname',
			'ah.lastname'		=> 'ahlastname',
			'ah.company'		=> 'ahcompany',
			'ah.street'			=> 'ahstreet',
			'ah.postal'			=> 'ahpostal',
			'ah.city'			=> 'ahcity',
			'ah.state'			=> 'ahstate',
			'ah.country'		=> 'ahcountry',
			'ah.phone'			=> 'ahphone',
			'ah.mobile'			=> 'ahmobile',
			'ah.fax'			=> 'ahfax',
			'ah.email'			=> 'ahemail',
			'ah.website'		=> 'ahwebsite',
			'ah.username'		=> 'ahusername',
		);

		foreach ($arrSearch as $field=>$val)
		{
			$options .= sprintf('<option value="%s"%s>%s</option>', $field, (($field == $session['tl_cb_reporting']['search']['field']) ? ' selected="selected"' : ''), (strlen($GLOBALS['TL_LANG']['tl_cb_reporting'][$val]) ? $GLOBALS['TL_LANG']['tl_cb_reporting'][$val] : $val));
		}

		$this->Template->searchOptions = $options;
		$this->Template->keywords = specialchars($session['tl_cb_reporting']['search']['value']);
		$this->Template->search = specialchars($GLOBALS['TL_LANG']['MSC']['search']);

		// Add date value to query
		if (strlen($session['tl_cb_reporting']['filter']['tstamp_from']) || strlen($session['tl_cb_reporting']['filter']['tstamp_to']))
		{
			if (strlen($session['tl_cb_reporting']['filter']['tstamp_from']) && strlen($session['tl_cb_reporting']['filter']['tstamp_to']))
			{
				$objDateFrom = new Date($session['tl_cb_reporting']['filter']['tstamp_from']);
				$objDateTo = new Date($session['tl_cb_reporting']['filter']['tstamp_to']);
			
				$where[] = "qdata.tstamp BETWEEN ? AND ?";
				$value[] = $objDateFrom->monthBegin;
				$value[] = $objDateTo->monthEnd;
				
				$this->Template->datefromfilterClass = ' active';
				$this->Template->datetofilterClass = ' active';
			}
			elseif(strlen($session['tl_cb_reporting']['filter']['tstamp_from']))
			{
				$objDateFrom = new Date($session['tl_cb_reporting']['filter']['tstamp_from']);
			
				$where[] = "qdata.tstamp >= ?";
				$value[] = $objDateFrom->monthBegin;
				$this->Template->datefromfilterClass = ' active';
			}
			elseif(strlen($session['tl_cb_reporting']['filter']['tstamp_to']))
			{
				$objDateTo = new Date($session['tl_cb_reporting']['filter']['tstamp_to']);
			
				$where[] = "qdata.tstamp <= ?";
				$value[] = $objDateTo->monthEnd;
				$this->Template->datetofilterClass = ' active';
			}
		}

		// Filter options - Only filtering by date for the moment
		$objFilter = $this->Database->prepare("SELECT tstamp FROM tl_cb_quizdata WHERE tstamp!=0")
									->execute();

		while ($objFilter->next())
		{
			$objDate = new Date($objFilter->tstamp);
			$filters[$objDate->monthBegin] = sprintf('<option value="%s"%s>%s</option>', $objFilter->tstamp, (($objFilter->tstamp == $session['tl_cb_reporting']['filter']['tstamp']) ? ' selected="selected"' : ''), date("F Y", $objDate->monthBegin));
		}

		$this->Template->thDate = $GLOBALS['TL_LANG']['tl_cb_reporting']['datefilter'];
		$this->Template->datefilterOptions = (count($filters) ? implode($filters) : '');
		$this->Template->datefilter = specialchars($GLOBALS['TL_LANG']['tl_cb_reporting']['datefilter']);

		//Status Options
		$statusoptions = '';
		$arrStatus = array
		(
			array
			(
				'status' 	=> 'in_progress',
				'label'		=> 'In Progress',
			),
			array
			(
				'status' 	=> 'complete',
				'label'		=> 'Complete',
			),
		);

		foreach($arrStatus as $status)
		{
			$statusoptions .= sprintf('<option value="%s"%s>%s</option>', $status['status'], (($status['status'] == $session['tl_cb_reporting']['status']) ? ' selected="selected"' : ''), $status['label']);
		}
		$this->Template->thStatus = $GLOBALS['TL_LANG']['tl_cb_reporting']['statusfilter'];
		$this->Template->statusOptions = $statusoptions;
		$this->Template->status = specialchars($GLOBALS['TL_LANG']['tl_cb_reporting']['statuses']);

		//Sorting Options
		$sortingoptions = '';
		$arrSorting = array
		(
			'_sorting_'			=> '_sorting_',
			'q.name'			=> 'quizname',
			'qdata.tstamp'		=> 'quizdate',
			'qdata.score'		=> 'quizscore',
			'm.lastname'		=> 'lastname',
			'm.firstname'		=> 'firstname'
		);

		foreach($arrSorting as $sorting)
		{
			$sortingoptions .= sprintf('<option value="%s"%s>%s</option>', $sorting['status'], (($sorting['status'] == $session['tl_cb_reporting']['status']) ? ' selected="selected"' : ''), $sorting['status']);
		}
		$this->Template->thSorting = $GLOBALS['TL_LANG']['tl_cb_reporting']['statusfilter'];
		$this->Template->sortingOptions = $sortingoptions;
		$this->Template->sorting = specialchars($GLOBALS['TL_LANG']['tl_cb_reporting']['statuses']);

		// Where
		if (count($where))
		{
			$query .= " AND " . implode(' AND ', $where);
		}

		// Order by & group by
		$query .= " ORDER BY m.`firstname`, m.`lastname`, cdata.`attempt`";

		// Execute query
		$objCollection = $this->Database->prepare($query)->execute($value);

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