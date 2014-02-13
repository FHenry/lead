<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 *  \file       dev/skeletons/lead.class.php
 *  \ingroup    mymodule othermodule1 othermodule2
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *				Initialy built by build_class_from_table on 2014-02-13 15:52
 */

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");


/**
 *	Put here description of your class
 */
class Lead extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='lead';			//!< Id that identify managed objects
	var $table_element='lead';		//!< Name of table without prefix where object is stored

	protected $ismultientitymanaged = 1; // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	
	
    var $id;
    
	var $ref;
	var $ref_ext;
	var $ref_int;
	var $fk_c_status;
	var $status_label;
	var $fk_c_type;
	var $type_label;
	var $date_closure='';
	var $amount_prosp;
	var $fk_user_resp;
	var $description;
	var $fk_user_author;
	var $datec='';
	var $fk_user_mod;
	var $tms='';
	
	var $lines = array();
	
	var $status = array();
	var $type = array();

    


    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      		Database handler
     *  @param	int			$load_dict      load status and type dictionnary
     */
    function __construct($db,$load_dict=1)
    {
    	global $langs;
    	
        $this->db = $db;
        
        if (!empty($load_dict)) {
        	$result_status = _load_status();
        	$result_type = _load_type();
        } else {
        	$result_status=1;
        	$result_type=1;
        }
        
        return ($result_status && $result_type);
    }
    
    /**
     *  Load status array
     *
     */
    private function _load_status() {
    	$sql = "SELECT rowid, code, label FROM ".MAIN_DB_PREFIX."c_lead_status";
    	dol_syslog(get_class($this)."::_load_status sql=".$sql, LOG_DEBUG);
    	$resql=$this->db->query($sql);
    	if ($resql)
    	{
    		while ($obj = $this->db->fetch_object($resql))
    		{
    			$label = $langs->trans('LeadStatus_'.$obj->code);
    			if ($label == 'LeadStatus_'.$obj->code){
    				$label=$obj->label;
    			}
    	
    			$this->status[$obj->rowid]=$label;
    	
    		}
    		$this->db->free($resql);
    		
    		return 1;
    	}
    	else
    	{
    		$this->error="Error ".$this->db->lasterror();
    		dol_syslog(get_class($this)."::_load_status ".$this->error, LOG_ERR);
    		return -1;
    	}
    }
    
    
    /**
     *  Load type array
     *
     */
    private  function _load_type() {
    	$sql = "SELECT rowid, code, label FROM ".MAIN_DB_PREFIX."c_lead_type";
    	dol_syslog(get_class($this)."::_load_type sql=".$sql, LOG_DEBUG);
    	$resql=$this->db->query($sql);
    	if ($resql)
    	{
    		while ($obj = $this->db->fetch_object($resql))
    		{
    			$label = $langs->trans('LeadType_'.$obj->code);
    			if ($label == 'LeadType_'.$obj->code){
    				$label=$obj->label;
    			}
    			 
    			$this->type[$obj->rowid]=$label;
    			 
    		}
    		$this->db->free($resql);
    		
    		return 1;
    	}
    	else
    	{
    		$this->error="Error ".$this->db->lasterror();
    		dol_syslog(get_class($this)."::_load_type ".$this->error, LOG_ERR);
    		return -1;
    	}
    }


    /**
     *  Create object into database
     *
     *  @param	User	$user        User that creates
     *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
     *  @return int      		   	 <0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
        
		if (isset($this->ref)) $this->ref=trim($this->ref);
		if (isset($this->ref_ext)) $this->ref_ext=trim($this->ref_ext);
		if (isset($this->ref_int)) $this->ref_int=trim($this->ref_int);
		if (isset($this->fk_c_status)) $this->fk_c_status=trim($this->fk_c_status);
		if (isset($this->fk_c_type)) $this->fk_c_type=trim($this->fk_c_type);
		if (isset($this->amount_prosp)) $this->amount_prosp=trim($this->amount_prosp);
		if (isset($this->fk_user_resp)) $this->fk_user_resp=trim($this->fk_user_resp);
		if (isset($this->description)) $this->description=trim($this->description);
		if (isset($this->fk_user_author)) $this->fk_user_author=trim($this->fk_user_author);
		if (isset($this->fk_user_mod)) $this->fk_user_mod=trim($this->fk_user_mod);

        

		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."lead(";
		
		$sql.= "ref,";
		$sql.= "ref_ext,";
		$sql.= "ref_int,";
		$sql.= "fk_c_status,";
		$sql.= "fk_c_type,";
		$sql.= "date_closure,";
		$sql.= "amount_prosp,";
		$sql.= "fk_user_resp,";
		$sql.= "description,";
		$sql.= "fk_user_author,";
		$sql.= "datec,";
		$sql.= "fk_user_mod,";
		$sql.= "tms";

		
        $sql.= ") VALUES (";
        
		$sql.= " ".(! isset($this->ref)?'NULL':"'".$this->db->escape($this->ref)."'").",";
		$sql.= " ".(! isset($this->ref_ext)?'NULL':"'".$this->db->escape($this->ref_ext)."'").",";
		$sql.= " ".(! isset($this->ref_int)?'NULL':"'".$this->db->escape($this->ref_int)."'").",";
		$sql.= " ".(! isset($this->fk_c_status)?'NULL':"'".$this->fk_c_status."'").",";
		$sql.= " ".(! isset($this->fk_c_type)?'NULL':"'".$this->fk_c_type."'").",";
		$sql.= " ".(! isset($this->date_closure) || dol_strlen($this->date_closure)==0?'NULL':"'".$this->db->idate($this->date_closure))."',";
		$sql.= " ".(! isset($this->amount_prosp)?'NULL':"'".$this->amount_prosp."'").",";
		$sql.= " ".(! isset($this->fk_user_resp)?'NULL':"'".$this->fk_user_resp."'").",";
		$sql.= " ".(! isset($this->description)?'NULL':"'".$this->db->escape($this->description)."'").",";
		$sql.= " ".$user->id.",";
		$sql.= " '".$this->db->idate(dol_now())."',";
		$sql.= " ".$user->id.",";
		$sql.= " '".$this->db->idate(dol_now())."'";

        
		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."lead");

			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
			}
        }

        // Commit or rollback
        if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
    }


    /**
     *  Load object in memory from the database
     *
     *  @param	int		$id    Id object
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		
		$sql.= " t.ref,";
		$sql.= " t.ref_ext,";
		$sql.= " t.ref_int,";
		$sql.= " t.fk_c_status,";
		$sql.= " t.fk_c_type,";
		$sql.= " t.date_closure,";
		$sql.= " t.amount_prosp,";
		$sql.= " t.fk_user_resp,";
		$sql.= " t.description,";
		$sql.= " t.fk_user_author,";
		$sql.= " t.datec,";
		$sql.= " t.fk_user_mod,";
		$sql.= " t.tms";

		
        $sql.= " FROM ".MAIN_DB_PREFIX."lead as t";
        $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;
                
				$this->ref = $obj->ref;
				$this->ref_ext = $obj->ref_ext;
				$this->ref_int = $obj->ref_int;
				$this->fk_c_status = $obj->fk_c_status;
				$this->fk_c_type = $obj->fk_c_type;
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
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }
    
    
    
    /**
     *  Load object in memory from the database
     *
	 * @param string $sortorder order
	 * @param string $sortfield field
	 * @param int $limit page
	 * @param int $offset
	 * @param array $filter output
	 * @return int <0 if KO, >0 if OK
     */
    function fetch_all($sortorder, $sortfield, $limit, $offset, $filter = array())
    {
    	global $langs;
    	$sql = "SELECT";
    	$sql.= " t.rowid,";
    
    	$sql.= " t.ref,";
    	$sql.= " t.ref_ext,";
    	$sql.= " t.ref_int,";
    	$sql.= " t.fk_c_status,";
    	$sql.= " t.fk_c_type,";
    	$sql.= " t.date_closure,";
    	$sql.= " t.amount_prosp,";
    	$sql.= " t.fk_user_resp,";
    	$sql.= " t.description,";
    	$sql.= " t.fk_user_author,";
    	$sql.= " t.datec,";
    	$sql.= " t.fk_user_mod,";
    	$sql.= " t.tms";
    
    
    	$sql.= " FROM ".MAIN_DB_PREFIX."lead as t";
    	
    	$sql .= " WHERE s.entity IN (" . getEntity ( 'lead' ) . ")";
    	
    	if (is_array ( $filter )) {
			if (($key == 't.fk_c_status') || ($key == 't.rowid') || ($key == 't.fk_c_type')) {
					$sql .= ' AND ' . $key . ' = ' . $value;
			}elseif (strpos ( $key, 'date' )) {
				// To allow $filter['YEAR(s.dated)']=>$year
				$sql .= ' AND ' . $key . ' = \'' . $value . '\'';
			}else {
					$sql .= ' AND ' . $key . ' LIKE \'%' . $this->db->escape ( $value ) . '%\'';
				}
		}
    
    	dol_syslog(get_class($this)."::fetch_all sql=".$sql, LOG_DEBUG);
    	$resql=$this->db->query($sql);
    	if ($resql)
    	{
    		$this->lines[]=array();
    		
    		while ($obj = $this->db->fetch_object($resql))
    		{
    			$line = new self($this->db,0);
    
    			$line->id    = $obj->rowid;
    
    			$line->ref = $obj->ref;
    			$line->ref_ext = $obj->ref_ext;
    			$line->ref_int = $obj->ref_int;
    			$line->fk_c_status = $obj->fk_c_status;
    			$line->fk_c_type = $obj->fk_c_type;
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
    
    			$this->lines[]=$line;
    		}
    		$this->db->free($resql);
    
    		return 1;
    	}
    	else
    	{
    		$this->error="Error ".$this->db->lasterror();
    		dol_syslog(get_class($this)."::fetch_all ".$this->error, LOG_ERR);
    		return -1;
    	}
    }


    /**
     *  Update object into database
     *
     *  @param	User	$user        User that modifies
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
        
		if (isset($this->ref)) $this->ref=trim($this->ref);
		if (isset($this->ref_ext)) $this->ref_ext=trim($this->ref_ext);
		if (isset($this->ref_int)) $this->ref_int=trim($this->ref_int);
		if (isset($this->fk_c_status)) $this->fk_c_status=trim($this->fk_c_status);
		if (isset($this->fk_c_type)) $this->fk_c_type=trim($this->fk_c_type);
		if (isset($this->amount_prosp)) $this->amount_prosp=trim($this->amount_prosp);
		if (isset($this->fk_user_resp)) $this->fk_user_resp=trim($this->fk_user_resp);
		if (isset($this->description)) $this->description=trim($this->description);
		if (isset($this->fk_user_author)) $this->fk_user_author=trim($this->fk_user_author);
		if (isset($this->fk_user_mod)) $this->fk_user_mod=trim($this->fk_user_mod);

        

		// Check parameters
		// Put here code to add a control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."lead SET";
        
		$sql.= " ref=".(isset($this->ref)?"'".$this->db->escape($this->ref)."'":"null").",";
		$sql.= " ref_ext=".(isset($this->ref_ext)?"'".$this->db->escape($this->ref_ext)."'":"null").",";
		$sql.= " ref_int=".(isset($this->ref_int)?"'".$this->db->escape($this->ref_int)."'":"null").",";
		$sql.= " fk_c_status=".(isset($this->fk_c_status)?$this->fk_c_status:"null").",";
		$sql.= " fk_c_type=".(isset($this->fk_c_type)?$this->fk_c_type:"null").",";
		$sql.= " date_closure=".(dol_strlen($this->date_closure)!=0 ? "'".$this->db->idate($this->date_closure)."'" : 'null').",";
		$sql.= " amount_prosp=".(isset($this->amount_prosp)?$this->amount_prosp:"null").",";
		$sql.= " fk_user_resp=".(isset($this->fk_user_resp)?$this->fk_user_resp:"null").",";
		$sql.= " description=".(isset($this->description)?"'".$this->db->escape($this->description)."'":"null").",";
		$sql.= " fk_user_mod=".$user->id.",";
		$sql.= " tms='".$this->db->idate(dol_now())."'";

        
        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
	    	}
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
    }


 	/**
	 *  Delete object in database
	 *
     *	@param  User	$user        User that deletes
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$this->db->begin();

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
		        // want this action calls a trigger.

		        //// Call triggers
		        //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
		        //$interface=new Interfaces($this->db);
		        //$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
		        //if ($result < 0) { $error++; $this->errors=$interface->errors; }
		        //// End call triggers
			}
		}

		if (! $error)
		{
    		$sql = "DELETE FROM ".MAIN_DB_PREFIX."lead";
    		$sql.= " WHERE rowid=".$this->id;

    		dol_syslog(get_class($this)."::delete sql=".$sql);
    		$resql = $this->db->query($sql);
        	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}



	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *	@param	int		$fromid     Id of object to clone
	 * 	@return	int					New id of clone
	 */
	function createFromClone($fromid)
	{
		global $user,$langs;

		$error=0;

		$object=new Lead($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		// ...

		// Create clone
		$result=$object->create($user);

		// Other options
		if ($result < 0)
		{
			$this->error=$object->error;
			$error++;
		}

		if (! $error)
		{


		}

		// End
		if (! $error)
		{
			$this->db->commit();
			return $object->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;
		
		$this->ref='';
		$this->ref_ext='';
		$this->ref_int='';
		$this->fk_c_status='';
		$this->fk_c_type='';
		$this->date_closure='';
		$this->amount_prosp='';
		$this->fk_user_resp='';
		$this->description='';
		$this->fk_user_author='';
		$this->datec='';
		$this->fk_user_mod='';
		$this->tms='';
	}

}
?>
