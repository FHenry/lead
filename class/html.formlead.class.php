<?php
/* Lead
 * Copyright (C) 2014 Florian HENRY   <florian.henry@open-concept.pro>
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
 *	\file       lead/class/html.fromlead.class.php
 *  \ingroup    lead
 *	\brief      File of class with all html predefined components
 */


class FormLead
{
	var $db;
	var $error;
	var $num;


	/**
	 * Constructor
	 *
	 * @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

    /**
     *    Return combo list of differents propal
     *
     *    @param	string	$selected    Preselected value
     *    @param	string	$htmlname    html name of the component
     *    @param	array	$filter		 SQL filter
     *    @param	int		$option_only output only options
     *    @param	int		$showempty	Show empty row
     *    @return	void
     */
    function select_propal($selected='',$htmlname='propalid',$filter='',$option_only=0,$showempty=1)
    {
        global $langs,$conf;

        $sql = "SELECT ";
        $sql.= " t.rowid, ";
        $sql.= " t.ref ";
        $sql.= " FROM ".MAIN_DB_PREFIX."propal as t";
        $sql.= " WHERE t.entity IN (".$conf->entity.")";
    	//Manage filter
		if (!empty($filter) && is_array($filter)){
			foreach($filter as $key => $value) {
				if ($key != 'specials') {
					$sql.= ' AND '.$key.' = \''.$value.'\'';
				} else {
					$sql.= ' AND '.$value;
				}
			}
		}
        dol_syslog(get_class($this)."::select_propal sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
        	$out='';
        	$this->num=$this->db->num_rows($resql);
        	if (empty($option_only)) {
            	$out.= '<select class="flat" name="'.$htmlname.'" id="'.$htmlname.'">';
        	}
        	if (!empty($showempty)) {
            	if (empty($selected)) {
            		$out.= '<option value="" selected="selected">&nbsp;</option>';
            	}else {
            		$out.= '<option value="">&nbsp;</option>';
            	}
        	}
            $num = $this->db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                while ($i < $num)
                {
                    $obj = $this->db->fetch_object($resql);
                    if ($selected == $obj->rowid)
                    {
                        $out.= '<option value="'.$obj->rowid.'" selected="selected">';
                    }
                    else
                    {
                        $out.= '<option value="'.$obj->rowid.'">';
                    }
                    $out.=$obj->ref;
                    $out.= '</option>';
                    $i++;
                }
            }
            if (empty($option_only)) {
            	$out.= '</select>';
            }
            return $out;
        }
        else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
    }
    
    /**
     *    Return combo list of differents invoice
     *
     *    @param	string	$selected    Preselected value
     *    @param	string	$htmlname    html name of the component
     *    @param	array	$filter		 SQL filter
     *    @param	int		$option_only output only options
     *    @param	int		$showempty	Show empty row
     *    @return	void
     */
    function select_invoice($selected='',$htmlname='invoiceid',$filter='',$option_only=0,$showempty=1)
    {
    	global $langs,$conf;
    
    	$sql = "SELECT ";
    	$sql.= " t.rowid, ";
    	$sql.= " t.facnumber as ref ";
    	$sql.= " FROM ".MAIN_DB_PREFIX."facture as t";
    	$sql.= " WHERE t.entity IN (".$conf->entity.")";
    	//Manage filter
    	if (!empty($filter) && is_array($filter)){
    		foreach($filter as $key => $value) {
    			if ($key != 'specials') {
    				$sql.= ' AND '.$key.' = \''.$value.'\'';
    			} else {
    				$sql.= ' AND '.$value;
    			}
    		}
    	}
    	dol_syslog(get_class($this)."::select_invoice sql=".$sql);
    	$resql=$this->db->query($sql);
    	if ($resql)
    	{
    		$out='';
    		$this->num=$this->db->num_rows($resql);
    		if (empty($option_only)) {
    			$out.= '<select class="flat" name="'.$htmlname.'" id="'.$htmlname.'">';
    		}
    		if (!empty($showempty)) {
    			if (empty($selected)) {
    				$out.= '<option value="" selected="selected">&nbsp;</option>';
    			}else {
    				$out.= '<option value="">&nbsp;</option>';
    			}
    		}
    		$num = $this->db->num_rows($resql);
    		$i = 0;
    		if ($num)
    		{
    			while ($i < $num)
    			{
    				$obj = $this->db->fetch_object($resql);
    				if ($selected == $obj->rowid)
    				{
    					$out.= '<option value="'.$obj->rowid.'" selected="selected">';
    				}
    				else
    				{
    					$out.= '<option value="'.$obj->rowid.'">';
    				}
    				$out.=$obj->ref;
    				$out.= '</option>';
    				$i++;
    			}
    		}
    		if (empty($option_only)) {
    			$out.= '</select>';
    		}
    		return $out;
    	}
    	else
    	{
    		$this->error=$this->db->lasterror();
    		return -1;
    	}
    }
    
    /**
     *    Build Select List of element associable to a businesscase
     *
     *    @param	object  $object			Object to parse
     *    @param	object	$businesscase	Object source
     *    @param	string	$htmlname    	html name of the component
     *    @return	string			The HTML select list of element
     */
    function select_element($object,$businesscase, $htmlname='elementselect')
    {    
    	switch ($object->table_element)
    	{
    		case "facture":
    			$sql = "SELECT rowid, facnumber as ref";
    			break;
    		case "facture_fourn":
    			$sql = "SELECT rowid, ref";
    			break;
    		case "facture_rec":
    			$sql = "SELECT rowid, titre as ref";
    			break;
    		case "actioncomm":
    			$sql = "SELECT id as rowid, label as ref";
    			$projectkey="fk_project";
    			break;
    		default:
    			$sql = "SELECT rowid, ref";
    			break;
    	}
    
    	$sql.= " FROM ".MAIN_DB_PREFIX.$object->table_element;
    	$sql.= " WHERE rowid NOT IN (SELECT fk_source FROM ".MAIN_DB_PREFIX."element_element WHERE targettype='".$businesscase->element."')";
    	$sql.= " AND fk_soc=".$businesscase->fk_soc;
    	$sql.= " AND entity IN (".getEntity($object->element,1).")";
    	$sql.= " ORDER BY ref DESC";
    
    	dol_syslog(get_class($this)."::select_element sql=".$sql, LOG_DEBUG);
    
    	$resql=$this->db->query($sql);
    	if ($resql)
    	{
    		$num = $this->db->num_rows($resql);
    		$i = 0;
    		if ($num > 0)
    		{
    			$sellist = '<select class="flat" name="'.$htmlname.'">';
    			while ($i < $num)
    			{
    				$obj = $this->db->fetch_object($resql);
    				$sellist .='<option value="'.$obj->rowid.'">'.$obj->ref.'</option>';
    				$i++;
    			}
    			$sellist .='</select>';
    		}
    		return $sellist ;
    	}
    	$this->db->free($resql);
    }
    
    
    /**
     *    Return combo list of differents status
     *
     *    @param	string	$selected    Preselected value
     *    @param	string	$htmlname    html name of the component
     *    @param	int		$showempty	Show empty row
     *    @return	void
     */
    function select_lead_status($selected='',$htmlname='leadstatus',$showempty=1) {

    	require_once 'lead.class.php';
    	$lead = new Lead($this->db);
    	
    	return $this->selectarray ( $htmlname, $lead->status, $selected, $showempty );
    	
    	
    }
    
    /**
     *    Return combo list of differents status
     *
     *    @param	string	$selected    Preselected value
     *    @param	string	$htmlname    html name of the component
     *    @param	int		$showempty	Show empty row
     *    @return	void
     */
    function select_lead_type($selected='',$htmlname='leadstatus',$showempty=1) {
    
    	require_once 'lead.class.php';
    	$lead = new Lead($this->db);
    	 
    	return $this->selectarray ( $htmlname, $lead->type, $selected, $showempty );
    	 
    	 
    }
}