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


class ModuleCBOrderDetails extends ModuleIsotope
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_cb_orderdetails';

	/**
	 * Disable caching of the frontend page if this module is in use.
	 * @var bool
	 */
	protected $blnDisableCache = true;


	public function generate($blnBackend=false)
	{
		if (TL_MODE == 'BE' && !$blnBackend)
		{
			$objTemplate = new BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### COURSE BUILDER: ORDER DETAILS ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = $this->Environment->script.'?do=modules&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		if ($blnBackend)
		{
			$this->backend = true;
			$this->jumpTo = 0;
		}

		return parent::generate();
	}


	protected function compile()
	{
		global $objPage;

		$objOrder = new IsotopeOrder();

		if (!$objOrder->findBy('uniqid', $this->Input->get('uid')))
		{
			$this->Template = new FrontendTemplate('mod_message');
			$this->Template->type = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['ERR']['orderNotFound'];
			return;
		}

		$arrOrder = $objOrder->getData();
		$this->Template->setData($arrOrder);

		$this->import('Isotope');
		$this->Isotope->overrideConfig($objOrder->config_id);

		// Article reader
		$arrPage = $this->Database->prepare("SELECT * FROM tl_page WHERE id=?")->limit(1)->execute($this->jumpTo)->fetchAssoc();

		$arrAllDownloads = array();
		$arrItems = array();
		$arrProducts = $objOrder->getProducts();

		foreach( $arrProducts as $i => $objProduct )
		{
			$arrDownloads = array();
			$objDownloads = $this->Database->prepare("SELECT p.*, o.* FROM tl_iso_order_downloads o LEFT OUTER JOIN tl_iso_downloads p ON o.download_id=p.id WHERE o.pid=?")->execute($objProduct->cart_id);
			
			$strToken = '';
			if (TL_MODE == 'FE' && $this->backend != true)
			{
				$objTokens = $this->Database->prepare("SELECT `token` FROM `tl_iso_tokens` WHERE `product_id` = ? AND `pid` = ?")->limit(1)->execute($objProduct->id, $this->User->id);
				if ($objTokens->numRows > 0)
				{
					$strToken = $objTokens->token;
				}
			}

			while( $objDownloads->next() )
			{
				$blnDownloadable = (($objOrder->status == 'complete' || (intval($objOrder->date_payed) > 0 && intval($objOrder->date_payed) <= time())) && ($objDownloads->downloads_remaining === '' || $objDownloads->downloads_remaining > 0)) ? true : false;

				// Send file to the browser
				if (strlen($this->Input->get('file')) && $this->Input->get('file') == $objDownloads->id && $blnDownloadable)
				{
					if (!$this->backend && $objDownloads->downloads_remaining !== '')
					{
						$this->Database->prepare("UPDATE tl_iso_order_downloads SET downloads_remaining=? WHERE id=?")->execute(($objDownloads->downloads_remaining-1), $objDownloads->id);
					}

					$this->sendFileToBrowser($objDownloads->singleSRC);
				}

				$arrDownload = array
				(
					'raw'			=> $objDownloads->row(),
					'title'			=> $objDownloads->title,
					'href'			=> (TL_MODE == 'FE' ? ($this->generateFrontendUrl($objPage->row()) . '?uid=' . $this->Input->get('uid') . '&amp;file=' . $objDownloads->id) : ''),
					'remaining'		=> ($objDownloads->downloads_allowed > 0 ? sprintf($GLOBALS['TL_LANG']['MSC']['downloadsRemaining'], intval($objDownloads->downloads_remaining)) : ''),
					'downloadable'	=> $blnDownloadable,
				);

				$arrDownloads[] = $arrDownload;
				$arrAllDownloads[] = $arrDownload;
			}

			$arrItems[] = array
			(
				'raw'				=> $objProduct->getData(),
				'sku'				=> $objProduct->sku,
				'name'				=> $objProduct->name,
				'image'				=> $objProduct->images->main_image,
				'token'				=> $strToken,
				'product_options'	=> $objProduct->getOptions(),
				'quantity'			=> $objProduct->quantity_requested,
				'price'				=> $this->Isotope->formatPriceWithCurrency($objProduct->price),
				'total'				=> $this->Isotope->formatPriceWithCurrency($objProduct->total_price),
				'href'				=> ($this->jumpTo ? $this->generateFrontendUrl($arrPage, '/product/'.$objProduct->alias) : ''),
				'tax_id'			=> $objProduct->tax_id,
				'downloads'			=> $arrDownloads,
				'class'				=> 'row_' . $i . ($i%2 ? ' even' : ' odd') . ($i==0 ? ' row_first' : ''),
			);
		}

		if (count($arrItems))
		{
			$arrItems[count($arrItems)-1]['class'] .= ' row_last';
		}

		$this->Template->info = deserialize($objOrder->checkout_info, true);
		$this->Template->items = $arrItems;
		$this->Template->downloads = $arrAllDownloads;
		$this->Template->downloadsLabel = $GLOBALS['TL_LANG']['MSC']['downloadsLabel'];

		$this->Template->raw = $arrOrder;

		$this->Template->date = $this->parseDate($GLOBALS['TL_CONFIG']['dateFormat'], $objOrder->date);
		$this->Template->time = $this->parseDate($GLOBALS['TL_CONFIG']['timeFormat'], $objOrder->date);
		$this->Template->datim = $this->parseDate($GLOBALS['TL_CONFIG']['datimFormat'], $objOrder->date);
		$this->Template->orderDetailsHeadline = sprintf($GLOBALS['TL_LANG']['MSC']['orderDetailsHeadline'], $objOrder->order_id, $this->Template->datim);
		$this->Template->orderStatus = sprintf($GLOBALS['TL_LANG']['MSC']['orderStatusHeadline'], $GLOBALS['TL_LANG']['ORDER'][$objOrder->status]);
		$this->Template->orderStatusKey = $objOrder->status;
		$this->Template->subTotalPrice = $this->Isotope->formatPriceWithCurrency($objOrder->subTotal);
		$this->Template->grandTotal = $this->Isotope->formatPriceWithCurrency($objOrder->grandTotal);
		$this->Template->subTotalLabel = $GLOBALS['TL_LANG']['MSC']['subTotalLabel'];
		$this->Template->grandTotalLabel = $GLOBALS['TL_LANG']['MSC']['grandTotalLabel'];

		$arrSurcharges = $objOrder->surcharges;
		if (is_array($arrSurcharges) && count($arrSurcharges))
		{
			foreach( $arrSurcharges as $k => $arrSurcharge )
			{
				$arrSurcharges[$k]['price']			= $this->Isotope->formatPriceWithCurrency($arrSurcharge['price']);
				$arrSurcharges[$k]['total_price']	= $this->Isotope->formatPriceWithCurrency($arrSurcharge['total_price']);
			}
		}
		else
		{
			$arrSurcharges = array();
		}

		$this->Template->surcharges = $arrSurcharges;

		$this->Template->billing_label = $GLOBALS['TL_LANG']['ISO']['billing_address'];
		$this->Template->billing_address = $this->Isotope->generateAddressString($objOrder->billing_address, $this->Isotope->Config->billing_fields);

		if (strlen($objOrder->shipping_method))
		{
			$arrShippingAddress = $objOrder->shipping_address;
			if (!is_array($arrShippingAddress) || $arrShippingAddress['id'] == -1)
			{
				$this->Template->has_shipping = false;
				$this->Template->billing_label = $GLOBALS['TL_LANG']['ISO']['billing_shipping_address'];
			}
			else
			{
				$this->Template->has_shipping = true;
				$this->Template->shipping_label = $GLOBALS['TL_LANG']['ISO']['shipping_address'];
				$this->Template->shipping_address = $this->Isotope->generateAddressString($arrShippingAddress, $this->Isotope->Config->shipping_fields);
			}
		}
	}
}

