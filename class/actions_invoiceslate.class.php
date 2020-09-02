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
	 * Execute action
	 *
	 * @param	array			$parameters		Array of parameters
	 * @param	CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param	string			$action      	'add', 'update', 'view'
	 * @return	int         					<0 if KO,
	 *                           				=0 if OK but we want to process standard actions too,
	 *                            				>0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		global $db, $langs, $conf, $user;
		$this->resprints = '';
		return 0;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs, $db;
//
//		$error = 0;
//		$fac = new Facture($db);
//		$fac->fetch($parameters['socid']);
//
//		if ($fac->date_lim_reglement < dol_now('gmt')){
//				$error++;
//				print '<div class="alert">
//						<span class="closebtn" onclick="this.parentElement.style.display=\'none\';">&times;</span>
//						  Présence de factures impayées pour ce client
//					   </div>';
//			}

	}


	public function formObjectOptions($parameters, &$object, &$action)
	{
		global $conf, $user, $langs, $db;

				$error = 0;
				$fac = new Facture($db);
				$fac->fetch($parameters['socid']);

				if ($fac->date_lim_reglement < dol_now('gmt')){
						$error++;
						print '<div class="alert">
								<span class="closebtn" onclick="this.parentElement.style.display="none">&times;</span>
								  Présence de factures impayées pour ce client
							   </div>';
					}

	}

}
