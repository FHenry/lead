<?php
/* 
 * Copyright (C) 2014-2016 Florian HENRY <florian.henry@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file lead/core/modules/lead/modules_lead.php
 * \ingroup businesscase
 * \brief businesscase for numbering businesscase
 */

/**
 * Classe mere des modeles de numerotation des references de lead
 */
abstract class ModeleNumRefLead
{

	public $error = '';
	public $version = '';

	/**
	 * Return if a module can be used or not
	 *
	 * @return boolean true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**
	 * Renvoi la description par defaut du modele de numerotation
	 *
	 * @return string Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("lead@lead");
		return $langs->trans("NoDescription");
	}

	/**
	 * Renvoi un exemple de numerotation
	 *
	 * @return string Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("lead");
		return $langs->trans("NoExample");
	}

	/**
	 * Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 * de conflits qui empechera cette numerotation de fonctionner.
	 *
	 * @return boolean false si conflit, true si ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/**
	 * Renvoi prochaine valeur attribuee
	 *
	 * @param int $fk_user User creating
	 * @param Societe $objsoc party
	 * @param Lead $lead Lead
	 * @return string Valeur
	 */
	function getNextValue($fk_user, $objsoc, $lead)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 * Module version
	 *
	 * @return string The module version
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		switch($this->version) {
			case 'development':
				return $langs->trans("VersionDevelopment");
				break;
			case 'experimental':
				return $langs->trans("VersionExperimental");
				break;
			case 'dolibarr':
				return DOL_VERSION;
				break;
			default:
				return $langs->trans("NotAvailable");
		}
	}
}
