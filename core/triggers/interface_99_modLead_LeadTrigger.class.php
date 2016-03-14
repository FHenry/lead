<?php
/* Copyright (C) 2016 Florian HENRY <florian.henry@open-concept.pro>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file core/triggers/interface_99_modLead_Lead.class.php
 * \ingroup lead
 */
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

// Extend DolibarrTriggers from Dolibarr 3.7
$dolibarr_version = versiondolibarrarray();
if ($dolibarr_version[0] < 3 || ($dolibarr_version[0] == 3 && $dolibarr_version[1] < 7)) { // DOL_VERSION < 3.7
	/**
	 * Class MyTrigger
	 */
	abstract class LeadTrigger
	{
	}
} else {
	/**
	 * Class MyTrigger
	 */
	abstract class LeadTrigger extends DolibarrTriggers
	{
	}
}

/**
 * Class InterfaceMytrigger
 */
class InterfaceLeadtrigger extends LeadTrigger
{
	/**
	 *
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db) {
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "lead";
		$this->description = "Lead";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		$this->picto = 'lead@lead';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc() {
		return $this->description;
	}

	/**
	 * Trigger version
	 *
	 * @return string Version of trigger file
	 */
	public function getVersion() {
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') {
			return $langs->trans("Development");
		} elseif ($this->version == 'experimental')

			return $langs->trans("Experimental");
		elseif ($this->version == 'dolibarr')
			return DOL_VERSION;
		elseif ($this->version)
			return $this->version;
		else {
			return $langs->trans("Unknown");
		}
	}

	/**
	 * Compatibility trigger function for Dolibarr < 3.7
	 *
	 * @param int $action Trigger action
	 * @param CommonObject $object Object trigged from
	 * @param User $user User that trigged
	 * @param Translate $langs Translations handler
	 * @param Conf $conf Configuration
	 * @return int <0 if KO, 0 if no triggered ran, >0 if OK
	 * @deprecated Replaced by DolibarrTriggers::runTrigger()
	 */
	public function run_trigger($action, $object, $user, $langs, $conf) {
		return $this->runTrigger($action, $object, $user, $langs, $conf);
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string $action Event action code
	 * @param Object $object Object
	 * @param User $user Object user
	 * @param Translate $langs Object langs
	 * @param Conf $conf Object conf
	 * @return int <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf) {
		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action
		// Users
		if ($action == 'LEAD_CREATE' || $action == 'LEAD_MODIFY') {
			dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

			// Check if date is set
			if (array_key_exists('options_rdv', $object->array_options) && ! empty($object->array_options['options_rdv']) && ! empty($object->fk_soc)) {

				require_once (DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php');

				$userassigned = array ();
				// Find sales man for customer
				$sql = 'SELECT fk_user FROM ' . MAIN_DB_PREFIX . 'societe_commerciaux WHERE fk_soc=' . $object->fk_soc;
				$resql = $this->db->query($sql);
				if ($resql) {
					while ( $obj = $this->db->fetch_object($resql) ) {
						$userassigned[] = array (
								'id' => $obj->fk_user,
								'answer_status' => 1
						);
					}
				} else {
					$this->error = $this->db->lasterror;
					$this->errors[] = $this->db->lasterror;

					dol_syslog(get_class($this) . '::' . $this->error, LOG_ERR);
					return - 1;
				}

				$actioncomm = new ActionComm($this->db);

				// check if event already exists
				$sql = 'SELECT id FROM ' . MAIN_DB_PREFIX . 'actioncomm WHERE elementtype=\'' . $object->element . '\' AND fk_element=' . $object->id;
				dol_syslog(get_class($this), LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$obj = $this->db->fetch_object($resql);
					// Update actioncomm
					if (! empty($obj->id)) {
						$actioncomm->fetch($obj->id);
						$actioncomm->datep = $object->array_options['options_rdv'];
						$actioncomm->userownerid = $userassigned[0];
						$actioncomm->userassigned = $userassigned;
						$result = $actioncomm->update($user);

						if ($result < 0) {
							$this->error = $actioncomm->error;
							$this->errors[] = $actioncomm->error;

							dol_syslog(get_class($this) . '::' . $this->error, LOG_ERR);
							return - 1;
						}
					} else {
						// Create actioncomm

						$actioncomm->type_code = 'AC_TEL';
						$actioncomm->label = 'Appel Lead ' . $object->ref;
						$actioncomm->datep = $object->array_options['options_rdv'];
						$actioncomm->datef = '';
						$actioncomm->durationp = 0;
						$actioncomm->punctual = 1;
						$actioncomm->percentage = 0; // Not applicable
						$actioncomm->socid = $object->fk_soc;
						$actioncomm->author = $userassigned[0]; // User saving action
						$actioncomm->fk_element = $object->id;
						$actioncomm->elementtype = $object->element;
						$actioncomm->userownerid = $userassigned[0];
						$actioncomm->userassigned = $userassigned;
						$ret = $actioncomm->add($user); // User qui saisit l'action
						if ($ret < 0) {
							$this->error = $actioncomm->error;
							$this->errors[] = $actioncomm->error;

							dol_syslog(get_class($this) . $this->error, LOG_ERR);
							return - 1;
						}
					}
				} else {
					$this->error = $this->db->lasterror;
					$this->errors[] = $this->db->lasterror;

					dol_syslog(get_class($this) . '::' . $this->error, LOG_ERR);
					return - 1;
				}
			}
		}

		return 0;
	}
}
