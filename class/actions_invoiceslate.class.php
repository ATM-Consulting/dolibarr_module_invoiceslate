<?php
/* Copyright (C) 2020 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    invoiceslate/class/actions_invoiceslate.class.php
 * \ingroup invoiceslate
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsInvoiceslate
 */
class ActionsInvoiceslate
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Affichage en rouge et gras du nom du tiers si impayé en retard
	 *
	 * @param	array			$parameters		Array of parameters
	 * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			$action      	'add', 'update', 'view'
	 *
	 * @return	int         					<0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *                            				>0 if OK and we want to replace standard actions.
	 */
	function getNomUrl($parameters, &$object, &$action, $hookmanager)
	{
		if($object->element == 'societe' && empty($parameters['option'])){
			global $langs;
			$langs->load('bills');
			$langs->load('invoiceslate@invoiceslate');
			$dataClient = $this->_getDataClient($object->id);
			if($dataClient) {

				$dom = new DOMDocument();
				@$dom->loadHTML (mb_convert_encoding($parameters['getnomurl'], 'HTML-ENTITIES', "UTF-8"));
				$links = $dom->getElementsByTagName ( 'a');
				$title = '';
				foreach ($links as $link){

					$title = $link->getAttribute('title');
					$class = $link->getAttribute('class');

					// Unpaid
					if($dataClient->total_unpaid>0){
						$style = $link->getAttribute('style');
						$style.= 'color: red; font-weight: bold;';
						$link->setAttribute('style', $style);

						$title.= '<div style="color:red; font-weight: bold;">'. $langs->trans('Unpaid').' : '.price($dataClient->total_unpaid).'</div>';
					}

					// last Order
					if($dataClient->last_order){
						$title.= '<div>'. $langs->trans('LastOrder').' : '.dol_print_date($dataClient->last_order).'</div>';
					}

					// Have a tooltip ?
					$haveToolTip = strpos($class, 'classfortooltip');
					if($haveToolTip !== false){
						$link->setAttribute('title', $title);
					}
				}

				$this->resprints = $dom->saveHTML();

				// last Order badge
				if($dataClient->last_order){

					$now = new DateTime();
					$last_order = new DateTime();
					$last_order->setTimestamp($dataClient->last_order);
					$interval = $now->diff($last_order);
					$nbMonth = intval($interval->format('%r%m'));
					$badgeClass = "badge-primary";
					if($nbMonth<6){
						$badgeClass = "badge-warning";
					}
					elseif($nbMonth<3){
						$badgeClass = "badge-info";
					}

					$this->resprints.= '<span class="classfortooltip badge '.$badgeClass.'" title="'.dol_htmlentities($title, ENT_QUOTES).'" >'.$nbMonth.'</span>';
				}
			}
		}

		return 0;
	}


	public function addMoreBoxStatsCustomer($parameters, &$object, &$action)
	{
		global $conf, $user, $langs, $db;

		$contextArray = explode(':',$parameters['context']);
		$langs->load('invoiceslate@invoiceslate');

		if (in_array('thirdpartycomm', $contextArray ) && !empty($object))
		{
			if($object->element == 'societe'){
				$dataClient = $this->_getDataClient($object->id);
				if($dataClient) {
					if($dataClient->total_unpaid>0)
					{
						$icon = 'bill';
						$text = $langs->trans("Unpaid");
						$boxstat = '<div id="customer-unpaid-boxstats" class="boxstats" data-unpaid="'.$dataClient->total_unpaid.'"  title="'.dol_escape_htmltag($text).'" >';
						$boxstat .= '<span class="boxstatstext">'.img_object("", $icon).' '.$text.'</span><br>';
						$boxstat .= '<span class="boxstatsindicator'.($dataClient->total_unpaid > 0 ? ' amountremaintopay' : '').'">'.price($dataClient->total_unpaid, 1, $langs, 1, -1, -1, $conf->currency).'</span>';
						$boxstat .= '</div>';

						$this->resprints = $boxstat;
					}
				}
			}
		}
	}




	/**
	 * Récupération des données qui permettent de définir si le tiers est à afficher en rouge
	 * - Facture impayée dont la date d'échénace et dépasée
	 * - Top 10 client TODO ?
	 *
	 *
	 * @return boolean | object      false or objet of data
	 */
	function _getDataClient($fk_soc = 0, $useCache = true) {
		global $db, $INVOICESLATE_CACHE_getDataClient;

		$fk_soc = intval($fk_soc);

		if($useCache && isset($INVOICESLATE_CACHE_getDataClient[$fk_soc])) return $INVOICESLATE_CACHE_getDataClient[$fk_soc];

		// init cache
		if(!isset($INVOICESLATE_CACHE_getDataClient)){
			$INVOICESLATE_CACHE_getDataClient = array();
		}

		$customerData = new stdClass();
		$customerData->errorMsg = array();
		$customerData->errors = 0;

		// Get unpaid
		$customerData->total_unpaid = 0;
		$sql = 'SELECT SUM(total_ttc) as total_unpaid FROM '.MAIN_DB_PREFIX.'facture WHERE fk_statut =1 AND paye = 0 AND date_lim_reglement < NOW() AND fk_soc = '.intval($fk_soc);
		$resql = $db->query($sql);
		if($resql){
			$obj = $db->fetch_object($resql);
			$customerData->total_unpaid = $obj->total_unpaid;
		}
		else{
			$customerData->errors ++;
			$customerData->errorMsg[] = $db->error();
		}

		// Last order
		$customerData->last_order = false;
		$sql = 'SELECT max(date_valid) as last_order FROM '.MAIN_DB_PREFIX.'commande WHERE fk_statut >= 1 AND fk_soc = '.intval($fk_soc);
		$resql = $db->query($sql);
		if($resql){
			$obj = $db->fetch_object($resql);
			$customerData->last_order = $db->jdate($obj->last_order);
		}
		else{
			$customerData->errors ++;
			$customerData->errorMsg[] = $db->error();
		}


		$INVOICESLATE_CACHE_getDataClient[$fk_soc] = $customerData;
		return $customerData;
	}
}
