<?php
/* 
 * Copyright (C) 2014 Florian HENRY <florian.henry@open-concept.pro>
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
 * \file lead/class/lead.class.php
 * \ingroup lead
 * \brief CRUD for Lead
 */

// Put here all includes required by your class file
require_once (DOL_DOCUMENT_ROOT . "/core/class/commonobject.class.php");
require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');

/**
 * Put here description of your class
 */
class Lead extends CommonObject {
	var $db; // !< To store db handler
	var $error; // !< To return error code (or message)
	var $errors = array (); // !< To return several error codes (or messages)
	var $element = 'lead'; // !< Id that identify managed objects
	var $table_element = 'lead'; // !< Name of table without prefix where object is stored
	protected $ismultientitymanaged = 1; // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	var $id;
	var $ref;
	var $ref_ext;
	var $ref_int;
	var $fk_soc;
	var $socid;
	var $fk_c_status;
	var $status_label;
	var $fk_c_type;
	var $type_label;
	var $date_closure = '';
	var $amount_prosp;
	var $fk_user_resp;
	var $description;
	var $fk_user_author;
	var $datec = '';
	var $fk_user_mod;
	var $tms = '';
	var $lines = array ();
	var $doclines = array ();
	var $status = array ();
	var $type = array ();
	var $listofreferent = array ();
	
	/**
	 * Constructor
	 *
	 * @param DoliDb $db handler
	 * @param int $load_dict status and type dictionnary
	 */
	function __construct($db, $load_dict = 1) {
		global $conf, $user;
		
		$this->db = $db;
		
		if (! empty($load_dict)) {
			$result_status = $this->_load_status();
			$result_type = $this->_load_type();
		} else {
			$result_status = 1;
			$result_type = 1;
		}
		
		if (! empty($conf->propal->enabled)) {
			$this->listofreferent['propal'] = array (
					'title' => "Proposal",
					'class' => 'Propal',
					'table' => 'propal',
					'filter' => array (
							'fk_statut' => '0,1,2' 
					),
					'test' => $conf->propal->enabled && $user->rights->propale->lire 
			);
		}
		if (! empty($conf->facture->enabled)) {
			$this->listofreferent['invoice'] = array (
					'title' => "Bill",
					'class' => 'Facture',
					'table' => 'facture',
					'test' => $conf->facture->enabled && $user->rights->facture->lire 
			);
		}
		if (! empty($conf->contrat->enabled)) {
			$this->listofreferent['contract'] = array (
					'title' => "Contrat",
					'class' => 'Contrat',
					'table' => 'contrat',
					'test' => $conf->contrat->enabled && $user->rights->contrat->lire 
			);
		}
		if (! empty($conf->commande->enabled)) {
			$this->listofreferent['orders'] = array (
					'title' => "Commande",
					'class' => 'Commande',
					'table' => 'commande',
					'test' => $conf->commande->enabled && $user->rights->commande->lire
			);
		}
		
		return ($result_status && $result_type);
	}
	
	/**
	 * Load status array
	 */
	private function _load_status() {
		global $langs;
		
		$sql = "SELECT rowid, code, label, active FROM " . MAIN_DB_PREFIX . "c_lead_status WHERE active=1";
		dol_syslog(get_class($this) . "::_load_status sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				
				$label = $langs->trans('LeadStatus_' . $obj->code);
				if ($label == 'LeadStatus_' . $obj->code) {
					$label = $obj->label;
				}
				
				$this->status[$obj->rowid] = $label;
			}
			$this->db->free($resql);
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::_load_status " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Load type array
	 */
	private function _load_type() {
		global $langs;
		
		$sql = "SELECT rowid, code, label FROM " . MAIN_DB_PREFIX . "c_lead_type  WHERE active=1";
		dol_syslog(get_class($this) . "::_load_type sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				$label = $langs->trans('LeadType_' . $obj->code);
				if ($label == 'LeadType_' . $obj->code) {
					$label = $obj->label;
				}
				
				$this->type[$obj->rowid] = $label;
			}
			$this->db->free($resql);
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::_load_type " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Create object into database
	 *
	 * @param User $user that creates
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	function create($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		// Clean parameters
		
		if (isset($this->ref))
			$this->ref = trim($this->ref);
		if (isset($this->ref_ext))
			$this->ref_ext = trim($this->ref_ext);
		if (isset($this->ref_int))
			$this->ref_int = trim($this->ref_int);
		if (isset($this->fk_c_status))
			$this->fk_c_status = trim($this->fk_c_status);
		if (isset($this->fk_c_type))
			$this->fk_c_type = trim($this->fk_c_type);
		if (isset($this->amount_prosp))
			$this->amount_prosp = trim($this->amount_prosp);
		if (isset($this->fk_user_resp))
			$this->fk_user_resp = trim($this->fk_user_resp);
		if (isset($this->description))
			$this->description = trim($this->description);
		if (isset($this->fk_user_author))
			$this->fk_user_author = trim($this->fk_user_author);
		if (isset($this->fk_user_mod))
			$this->fk_user_mod = trim($this->fk_user_mod);
		if (isset($this->fk_soc))
			$this->fk_soc = trim($this->fk_soc);
			
			// Check parameters
			// Put here code to add control on parameters values
		
		if (empty($this->fk_soc)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Customer'));
		}
		if (empty($this->ref_int)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRefInt'));
		}
		if (empty($this->fk_user_resp)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadCommercial'));
		}
		if (empty($this->fk_c_status)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadStep'));
		}
		if (empty($this->fk_c_type)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadType'));
		}
		if (! isset($this->amount_prosp)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadAmountGuess'));
		}
		if (dol_strlen($this->date_closure) == 0) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadDeadLine'));
		}
		
		if (! $error) {
			// Insert request
			$sql = "INSERT INTO " . MAIN_DB_PREFIX . "lead(";
			
			$sql .= "entity,";
			$sql .= "ref,";
			$sql .= "ref_ext,";
			$sql .= "ref_int,";
			$sql .= "fk_c_status,";
			$sql .= "fk_c_type,";
			$sql .= "fk_soc,";
			$sql .= "date_closure,";
			$sql .= "amount_prosp,";
			$sql .= "fk_user_resp,";
			$sql .= "description,";
			$sql .= "fk_user_author,";
			$sql .= "datec,";
			$sql .= "fk_user_mod,";
			$sql .= "tms";
			
			$sql .= ") VALUES (";
			
			$sql .= " " . $conf->entity . ",";
			$sql .= " " . (! isset($this->ref) ? 'NULL' : "'" . $this->db->escape($this->ref) . "'") . ",";
			$sql .= " " . (! isset($this->ref_ext) ? 'NULL' : "'" . $this->db->escape($this->ref_ext) . "'") . ",";
			$sql .= " " . (! isset($this->ref_int) ? 'NULL' : "'" . $this->db->escape($this->ref_int) . "'") . ",";
			$sql .= " " . (! isset($this->fk_c_status) ? 'NULL' : "'" . $this->fk_c_status . "'") . ",";
			$sql .= " " . (! isset($this->fk_c_type) ? 'NULL' : "'" . $this->fk_c_type . "'") . ",";
			$sql .= " " . (! isset($this->fk_soc) ? 'NULL' : "'" . $this->fk_soc . "'") . ",";
			$sql .= " " . (! isset($this->date_closure) || dol_strlen($this->date_closure) == 0 ? 'NULL' : "'" . $this->db->idate($this->date_closure)) . "',";
			$sql .= " " . (! isset($this->amount_prosp) ? 'NULL' : "'" . $this->amount_prosp . "'") . ",";
			$sql .= " " . (! isset($this->fk_user_resp) ? 'NULL' : "'" . $this->fk_user_resp . "'") . ",";
			$sql .= " " . (empty($this->description) ? 'NULL' : "'" . $this->db->escape($this->description) . "'") . ",";
			$sql .= " " . $user->id . ",";
			$sql .= " '" . $this->db->idate(dol_now()) . "',";
			$sql .= " " . $user->id . ",";
			$sql .= " '" . $this->db->idate(dol_now()) . "'";
			
			$sql .= ")";
			
			$this->db->begin();
			
			dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}
		
		if (! $error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "lead");
			
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.
				
				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}
		
		if (! $error) {
			
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error ++;
				}
			}
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}
	
	/**
	 * Load object in memory from the database
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch($id) {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";
		
		$sql .= " t.ref,";
		$sql .= " t.ref_ext,";
		$sql .= " t.ref_int,";
		$sql .= " t.fk_c_status,";
		$sql .= " t.fk_c_type,";
		$sql .= " t.fk_soc,";
		$sql .= " t.date_closure,";
		$sql .= " t.amount_prosp,";
		$sql .= " t.fk_user_resp,";
		$sql .= " t.description,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";
		
		$sql .= " FROM " . MAIN_DB_PREFIX . "lead as t";
		$sql .= " WHERE t.rowid = " . $id;
		$sql .= " AND t.entity = " . getEntity('lead', 1);
		
		dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				
				$this->id = $obj->rowid;
				
				$this->ref = $obj->ref;
				$this->ref_ext = $obj->ref_ext;
				$this->ref_int = $obj->ref_int;
				$this->fk_c_status = $obj->fk_c_status;
				$this->fk_c_type = $obj->fk_c_type;
				$this->fk_soc = $obj->fk_soc;
				// To allow fetch_thirdparty working
				$this->socid = $obj->fk_soc;
				$this->date_closure = $this->db->jdate($obj->date_closure);
				$this->amount_prosp = $obj->amount_prosp;
				$this->fk_user_resp = $obj->fk_user_resp;
				$this->description = $obj->description;
				$this->fk_user_author = $obj->fk_user_author;
				$this->datec = $this->db->jdate($obj->datec);
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->tms = $this->db->jdate($obj->tms);
				$this->status_label = $this->status[$this->fk_c_status];
				$this->type_label = $this->type[$this->fk_c_type];
				
				$extrafields = new ExtraFields($this->db);
				$extralabels = $extrafields->fetch_name_optionals_label($this->table_element, true);
				if (count($extralabels) > 0) {
					$this->fetch_optionals($this->id, $extralabels);
				}
			}
			$this->db->free($resql);
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Load object in memory from the database
	 *
	 * @param string $sortorder order
	 * @param string $sortfield field
	 * @param int $limit page
	 * @param int $offset
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
	 */
	function fetch_all($sortorder, $sortfield, $limit, $offset, $filter = array()) {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";
		
		$sql .= " t.ref,";
		$sql .= " t.ref_ext,";
		$sql .= " t.ref_int,";
		$sql .= " t.fk_c_status,";
		$sql .= " t.fk_c_type,";
		$sql .= " t.fk_soc,";
		$sql .= " t.date_closure,";
		$sql .= " t.amount_prosp,";
		$sql .= " t.fk_user_resp,";
		$sql .= " t.description,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.datec,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.tms";
		
		$sql .= " FROM " . MAIN_DB_PREFIX . "lead as t";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as so ON so.rowid=t.fk_soc";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as usr ON usr.rowid=t.fk_user_resp";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_lead_status as leadsta ON leadsta.rowid=t.fk_c_status";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_lead_type as leadtype ON leadtype.rowid=t.fk_c_type";
		
		$sql .= " WHERE t.entity IN (" . getEntity('lead') . ")";
		
		if (is_array($filter)) {
			foreach ( $filter as $key => $value ) {
				if (($key == 't.fk_c_status') || ($key == 't.rowid') || ($key == 'so.rowid') || ($key == 't.fk_c_type') || ($key == 't.fk_user_resp')) {
					$sql .= ' AND ' . $key . ' = ' . $value;
				} elseif ($key == 't.date_closure<') {
					// To allow $filter['YEAR(s.dated)']=>$year
					$sql .= " AND t.date_closure<='" . $this->db->idate($value) . "'";
				} elseif (strpos($key, 'date')) {
					// To allow $filter['YEAR(s.dated)']=>$year
					$sql .= ' AND ' . $key . ' = \'' . $value . '\'';
				} elseif ($key == 't.fk_c_status !IN') {
					$sql .= ' AND t.fk_c_status NOT IN (' . $value . ')';
				} elseif ($key == 't.rowid !IN') {
					$sql .= ' AND t.rowid NOT IN (' . $value . ')';
				} else {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		
		if (! empty($sortfield)) {
			$sql .= " ORDER BY " . $sortfield . ' ' . $sortorder;
		}
		
		if (! empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit + 1, $offset);
		}
		
		dol_syslog(get_class($this) . "::fetch_all sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array ();
			
			$num = $this->db->num_rows($resql);
			
			while ( $obj = $this->db->fetch_object($resql) ) {
				
				$line = new Lead($this->db, 0);
				
				$line->id = $obj->rowid;
				$line->ref = $obj->ref;
				$line->ref_ext = $obj->ref_ext;
				$line->ref_int = $obj->ref_int;
				$line->fk_c_status = $obj->fk_c_status;
				$line->fk_c_type = $obj->fk_c_type;
				$line->fk_soc = $obj->fk_soc;
				// To allow fetch_thirdparty working
				$line->socid = $obj->fk_soc;
				$line->date_closure = $this->db->jdate($obj->date_closure);
				$line->amount_prosp = $obj->amount_prosp;
				$line->fk_user_resp = $obj->fk_user_resp;
				$line->description = $obj->description;
				$line->fk_user_author = $obj->fk_user_author;
				$line->datec = $this->db->jdate($obj->datec);
				$line->fk_user_mod = $obj->fk_user_mod;
				$line->tms = $this->db->jdate($obj->tms);
				$line->status_label = $this->status[$line->fk_c_status];
				$line->type_label = $this->type[$line->fk_c_type];
				
				$extrafields = new ExtraFields($this->db);
				$extralabels = $extrafields->fetch_name_optionals_label($this->table_element, true);
				if (count($extralabels) > 0) {
					$line->fetch_optionals($line->id, $extralabels);
				}
				
				$this->lines[] = $line;
			}
			$this->db->free($resql);
			
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_all " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	
	/**
	 * Update object into database
	 *
	 * @param User $user that modifies
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function update($user = 0, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		// Clean parameters
		
		if (isset($this->ref))
			$this->ref = trim($this->ref);
		if (isset($this->ref_ext))
			$this->ref_ext = trim($this->ref_ext);
		if (isset($this->ref_int))
			$this->ref_int = trim($this->ref_int);
		if (isset($this->fk_c_status))
			$this->fk_c_status = trim($this->fk_c_status);
		if (isset($this->fk_c_type))
			$this->fk_c_type = trim($this->fk_c_type);
		if (isset($this->amount_prosp))
			$this->amount_prosp = trim($this->amount_prosp);
		if (isset($this->fk_user_resp))
			$this->fk_user_resp = trim($this->fk_user_resp);
		if (isset($this->description))
			$this->description = trim($this->description);
		if (isset($this->fk_user_author))
			$this->fk_user_author = trim($this->fk_user_author);
		if (isset($this->fk_user_mod))
			$this->fk_user_mod = trim($this->fk_user_mod);
		if (isset($this->fk_soc))
			$this->fk_soc = trim($this->fk_soc);
			
			// Check parameters
			// Put here code to add a control on parameters values
		
		if (empty($this->fk_soc)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('Customer'));
		}
		if (empty($this->ref_int)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadRefInt'));
		}
		if (empty($this->fk_user_resp)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadCommercial'));
		}
		if (empty($this->fk_c_status)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadStep'));
		}
		if (empty($this->fk_c_type)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadType'));
		}
		if (! isset($this->amount_prosp)) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadAmountGuess'));
		}
		if (dol_strlen($this->date_closure) == 0) {
			$error ++;
			$this->errors[] = $langs->trans('ErrorFieldRequired', $langs->transnoentities('LeadDeadLine'));
		}
		
		if (! $error) {
			// Update request
			$sql = "UPDATE " . MAIN_DB_PREFIX . "lead SET";
			
			$sql .= " ref=" . (isset($this->ref) ? "'" . $this->db->escape($this->ref) . "'" : "null") . ",";
			$sql .= " ref_ext=" . (isset($this->ref_ext) ? "'" . $this->db->escape($this->ref_ext) . "'" : "null") . ",";
			$sql .= " ref_int=" . (isset($this->ref_int) ? "'" . $this->db->escape($this->ref_int) . "'" : "null") . ",";
			$sql .= " fk_c_status=" . (isset($this->fk_c_status) ? $this->fk_c_status : "null") . ",";
			$sql .= " fk_c_type=" . (isset($this->fk_c_type) ? $this->fk_c_type : "null") . ",";
			$sql .= " fk_soc=" . (isset($this->fk_soc) ? $this->fk_soc : "null") . ",";
			$sql .= " date_closure=" . (dol_strlen($this->date_closure) != 0 ? "'" . $this->db->idate($this->date_closure) . "'" : 'null') . ",";
			$sql .= " amount_prosp=" . (isset($this->amount_prosp) ? $this->amount_prosp : "null") . ",";
			$sql .= " fk_user_resp=" . (isset($this->fk_user_resp) ? $this->fk_user_resp : "null") . ",";
			$sql .= " description=" . (! empty($this->description) ? "'" . $this->db->escape($this->description) . "'" : "null") . ",";
			$sql .= " fk_user_mod=" . $user->id . ",";
			$sql .= " tms='" . $this->db->idate(dol_now()) . "'";
			
			$sql .= " WHERE rowid=" . $this->id;
			
			$this->db->begin();
			
			dol_syslog(get_class($this) . "::update sql=" . $sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}
		
		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.
				
				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}
		
		if (! $error) {
			
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
{
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error ++;
				}
			}
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}
	
	/**
	 * Delete object in database
	 *
	 * @param User $user that deletes
	 * @param int $notrigger triggers after, 1=disable triggers
	 * @return int <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger = 0) {
		global $conf, $langs;
		$error = 0;
		
		$this->db->begin();
		
		if (! $error) {
			if (! $notrigger) {
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.
				
				// // Call triggers
				// include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				// $interface=new Interfaces($this->db);
				// $result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				// if ($result < 0) { $error++; $this->errors=$interface->errors; }
				// // End call triggers
			}
		}
		
		if (! $error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "lead_extrafields";
			$sql .= " WHERE fk_object=" . $this->id;
			
			dol_syslog(get_class($this) . "::delete sql=" . $sql);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}
		
		if (! $error) {
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "lead";
			$sql .= " WHERE rowid=" . $this->id;
			
			dol_syslog(get_class($this) . "::delete sql=" . $sql);
			$resql = $this->db->query($sql);
			if (! $resql) {
				$error ++;
				$this->errors[] = "Error " . $this->db->lasterror();
			}
		}
		
		// Commit or rollback
		if ($error) {
			foreach ( $this->errors as $errmsg ) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return - 1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}
	
	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param int $fromid of object to clone
	 * @return int id of clone
	 */
	function createFromClone($fromid) {
		global $user, $langs;
		
		$error = 0;
		
		$object = new Lead($this->db);
		
		$this->db->begin();
		
		// Load source object
		$object->fetch($fromid);
		$object->ref = $object->getNextNumRef();
		$object->ref_int = $this->ref_int;
		
		// Create clone
		$result = $object->create($user);
		
		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$error ++;
		}
		
		if (! $error) {
		}
		
		// End
		if (! $error) {
			$this->db->commit();
			return $object->id;
		} else {
			$this->db->rollback();
			return - 1;
		}
	}
	
	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	function initAsSpecimen() {
		$this->id = 0;
		$this->entity = $conf->entity;
		$this->ref = '';
		$this->ref_ext = '';
		$this->ref_int = '';
		$this->fk_c_status = '';
		$this->fk_c_type = '';
		$this->fk_soc = '';
		$this->date_closure = '';
		$this->amount_prosp = '';
		$this->fk_user_resp = '';
		$this->description = '';
		$this->fk_user_author = '';
		$this->datec = '';
		$this->fk_user_mod = '';
		$this->tms = '';
	}
	
	/**
	 * Returns the reference to the following non used Lead used depending on the active numbering module
	 * defined into LEAD_ADDON
	 *
	 * @param int $fk_user Id
	 * @param societe $objsoc Object
	 * @return string Reference libre pour la lead
	 */
	function getNextNumRef($fk_user = '', $objsoc = '') {
		global $conf, $langs;
		$langs->load("lead@lead");
		
		$dirmodels = array_merge(array (
				'/' 
		), ( array ) $conf->modules_parts['models']);
		
		if (! empty($conf->global->LEAD_ADDON)) {
			foreach ( $dirmodels as $reldir ) {
				$dir = dol_buildpath($reldir . "core/modules/lead/");
				if (is_dir($dir)) {
					$handle = opendir($dir);
					if (is_resource($handle)) {
						$var = true;
						
						while ( ($file = readdir($handle)) !== false ) {
							if ($file == $conf->global->LEAD_ADDON . '.php') {
								$file = substr($file, 0, dol_strlen($file) - 4);
								require_once $dir . $file . '.php';
								
								$module = new $file();
								
								// Chargement de la classe de numerotation
								$classname = $conf->global->LEAD_ADDON;
								
								$obj = new $classname();
								
								$numref = "";
								$numref = $obj->getNextValue($fk_user, $objsoc, $this);
								
								if ($numref != "") {
									return $numref;
								} else {
									$this->error = $obj->error;
									return "";
								}
							}
						}
					}
				}
			}
		} else {
			$langs->load("errors");
			print $langs->trans("Error") . " " . $langs->trans("ErrorModuleSetupNotComplete");
			return "";
		}
	}
	
	/**
	 * Give information on the object
	 *
	 * @param int $id object
	 * @return int <0 if KO, >0 if OK
	 */
	function info($id) {
		global $langs;
		
		$sql = "SELECT";
		$sql .= " p.rowid, p.datec, p.tms, p.fk_user_mod, p.fk_user_author";
		$sql .= " FROM " . MAIN_DB_PREFIX . "lead as p";
		$sql .= " WHERE p.rowid = " . $id;
		
		dol_syslog(get_class($this) . "::info sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
				$this->user_modification = $obj->fk_user_mod;
				$this->user_creation = $obj->fk_user_author;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::info " . $this->error, LOG_ERR);
			return - 1;
		}
	}
	public function getRealAmount() {
		$totalinvoiceamount = 0;
		$totalproposalamount = 0;
		
		$sql = "SELECT SUM(fac.total) as totalamount ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture as fac";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "element_element elmt ON  elmt.fk_target=" . $this->id;
		$sql .= " AND elmt.targettype='lead' AND elmt.sourcetype='facture' AND elmt.fk_source=fac.rowid";
		$sql .= " AND fac.fk_statut = 1";
		
		dol_syslog(get_class($this) . "::getRealAmount sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				if (! empty($obj->totalamount))
					;
				$totalinvoiceamount = $obj->totalamount;
			}
			$this->db->free($resql);
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::getRealAmount " . $this->error, LOG_ERR);
			return - 1;
		}
		
		$sql = "SELECT SUM(propal.total_ht) as totalamount ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "propal as propal";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "element_element elmt ON  elmt.fk_target=" . $this->id;
		$sql .= " AND elmt.targettype='lead' AND elmt.sourcetype='propal' AND elmt.fk_source=propal.rowid";
		$sql .= " AND propal.fk_statut = 1";
		
		dol_syslog(get_class($this) . "::getRealAmount sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				if (! empty($obj->totalamount))
					;
				$totalproposalamount = $obj->totalamount;
			}
			$this->db->free($resql);
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::getRealAmount " . $this->error, LOG_ERR);
			return - 1;
		}
		
		return ($totalproposalamount - $totalinvoiceamount);
	}
	
	/**
	 * Load properties id_previous and id_next
	 *
	 * @param string $filter
	 * @param int $fieldid of field to use for the select MAX and MIN
	 * @return int <0 if KO, >0 if OK
	 */
	function load_previous_next_ref_custom($filter, $fieldid) {
		global $conf, $user;
		
		if (! $this->table_element) {
			dol_print_error('', get_class($this) . "::load_previous_next_ref was called on objet with property table_element not defined", LOG_ERR);
			return - 1;
		}
		
		// this->ismultientitymanaged contains
		// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
		$alias = 's';
		if ($this->element == 'societe')
			$alias = 'te';
		
		$sql = "SELECT MAX(te." . $fieldid . ")";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as te";
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 2 || ($this->element != 'societe' && empty($this->isnolinkedbythird) && empty($user->rights->societe->client->voir)))
			$sql .= ", " . MAIN_DB_PREFIX . "societe as s"; // entity
		if (empty($this->isnolinkedbythird) && ! $user->rights->societe->client->voir)
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON " . $alias . ".rowid = sc.fk_soc";
		$sql .= " WHERE te." . $fieldid . " < '" . $this->db->escape($this->id) . "'";
		if (empty($this->isnolinkedbythird) && ! $user->rights->societe->client->voir)
			$sql .= " AND sc.fk_user = " . $user->id;
		if (! empty($filter))
			$sql .= " AND " . $filter;
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 2 || ($this->element != 'societe' && empty($this->isnolinkedbythird) && ! $user->rights->societe->client->voir))
			$sql .= ' AND te.fk_soc = s.rowid';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1)
			$sql .= ' AND te.entity IN (' . getEntity($this->element, 1) . ')';
			
			// print $sql."<br>";
		$result = $this->db->query($sql);
		if (! $result) {
			$this->error = $this->db->error();
			return - 1;
		}
		$row = $this->db->fetch_row($result);
		$this->ref_previous = $row[0];
		
		$sql = "SELECT MIN(te." . $fieldid . ")";
		$sql .= " FROM " . MAIN_DB_PREFIX . $this->table_element . " as te";
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 2 || ($this->element != 'societe' && empty($this->isnolinkedbythird) && ! $user->rights->societe->client->voir))
			$sql .= ", " . MAIN_DB_PREFIX . "societe as s";
		if (empty($this->isnolinkedbythird) && ! $user->rights->societe->client->voir)
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON " . $alias . ".rowid = sc.fk_soc";
		$sql .= " WHERE te." . $fieldid . " > '" . $this->db->escape($this->id) . "'";
		if (empty($this->isnolinkedbythird) && ! $user->rights->societe->client->voir)
			$sql .= " AND sc.fk_user = " . $user->id;
		if (! empty($filter))
			$sql .= " AND " . $filter;
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 2 || ($this->element != 'societe' && empty($this->isnolinkedbythird) && ! $user->rights->societe->client->voir))
			$sql .= ' AND te.fk_soc = s.rowid'; // If
		
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1)
			$sql .= ' AND te.entity IN (' . getEntity($this->element, 1) . ')';
			// Rem: Bug in some mysql version: SELECT MIN(rowid) FROM llx_socpeople WHERE rowid > 1 when one row in database with rowid=1, returns 1
			// instead of null
			
		// print $sql."<br>";
		$result = $this->db->query($sql);
		if (! $result) {
			$this->error = $this->db->error();
			return - 2;
		}
		$row = $this->db->fetch_row($result);
		$this->ref_next = $row[0];
		
		return 1;
	}
	
	/**
	 * Load object in memory from database
	 *
	 * @param int $id
	 * @return int if KO, >0 if OK
	 */
	public function fetch_document_link($id, $tablename) {
		global $langs;
		
		$this->doclines = array ();
		
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.fk_source,";
		$sql .= " t.sourcetype,";
		$sql .= " t.fk_target,";
		$sql .= " t.targettype";
		$sql .= " FROM " . MAIN_DB_PREFIX . "element_element as t";
		$sql .= " WHERE t.fk_target = " . $id;
		$sql .= " AND t.targettype='lead'";
		if (! empty($tablename)) {
			$sql .= " AND t.sourcetype='" . $tablename . "'";
		}
		$sql .= " ORDER BY t.sourcetype";
		
		dol_syslog(get_class($this) . "::fetch_document_link sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				$line = new DocLink($this->db);
				
				$line->id = $obj->rowid;
				$line->fk_source = $obj->fk_source;
				$line->sourcetype = $obj->sourcetype;
				$line->fk_target = $obj->fk_target;
				$line->targettype = $obj->targettype;
				
				$this->doclines[] = $line;
			}
			$this->db->free($resql);
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_document_link " . $this->error, LOG_ERR);
			
			return - 1;
		}
	}
	
	/**
	 * Load object in memory from database
	 *
	 * @param int $id
	 * @return int if KO, >0 if OK
	 */
	public function fetch_lead_link($id, $tablename) {
		global $langs;
		
		$this->doclines = array ();
		
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.fk_source,";
		$sql .= " t.sourcetype,";
		$sql .= " t.fk_target,";
		$sql .= " t.targettype";
		$sql .= " FROM " . MAIN_DB_PREFIX . "element_element as t";
		$sql .= " WHERE t.fk_source = " . $id;
		$sql .= " AND t.targettype='lead'";
		if (! empty($tablename)) {
			$sql .= " AND t.sourcetype='" . $tablename . "'";
		}
		$sql .= " ORDER BY t.sourcetype";
		
		dol_syslog(get_class($this) . "::fetch_document_link sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ( $obj = $this->db->fetch_object($resql) ) {
				$line = new Lead($this->db);
				$line->fetch($obj->fk_target);
				$this->doclines[] = $line;
			}
			$this->db->free($resql);
			
			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch_document_link " . $this->error, LOG_ERR);
			
			return - 1;
		}
	}
	
	/**
	 * Load object in memory from database
	 *
	 * @param int $id
	 * @return int if KO, >0 if OK
	 */
	public function getNomUrl($withpicto = 0) {
		global $langs;
		
		$result = '';
		
		$lien = '<a href="' . dol_buildpath('lead/lead/card.php', 1) . '?id=' . $this->id . '">';
		$lienfin = '</a>';
		
		$picto = 'propal';
		$label = $langs->trans("LeadShowLead") . ': ' . $this->ref;
		
		if ($withpicto)
			$result .= ($lien . img_object($label, $picto) . $lienfin);
		if ($withpicto && $withpicto != 2)
			$result .= ' ';
		$result .= $lien . $this->ref . $lienfin;
		return $result;
	}
	
	/**
	 *
	 * @param number $mode
	 * @return multitype:|string
	 */
	public function getLibStatut($mode = 0) {
		if (! empty($this->fk_c_status)) {
			return $this->status[$this->fk_c_status];
		} else {
			return '';
		}
	}
}
class DocLink {
	public $id;
	public $fk_source;
	public $fk_target;
	public $sourcetype;
	public $targettype;
}