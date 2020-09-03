<?php

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__) . '/';

global $db, $user;

// Include and load Dolibarr environment variables
$res = 0;

// LES USERS sont chargés avec main.inc. pas avec master.inc !!!
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (!$res) die("Include of master fails");

require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';

// Load traductions files requiredby by page
$langs->loadLangs(array("invoiceslate@invoiceslate", "other", 'main'));

$option_value 	= GETPOST('optionValue');
$action 		= GETPOST('action');

$errormysql = -1;
$jsonResponse = new stdClass();

if (isset($option_value) && $action == 'chooseThirdParty' ) {

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

print json_encode($jsonResponse, JSON_PRETTY_PRINT);
