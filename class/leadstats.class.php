<?php
/* Lead
 * Copyright (C) 2014-2015 Florian HENRY <florian.henry@open-concept.pro>
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

class LeadStats {
	
	protected $db;
	private $lead;
	
	
	function __construct($db) {
		global $conf, $user;
	
		$this->db = $db;
		
		require_once 'lead.class.php';
		
		$this->lead = new Lead($this->db);
		
	}
	
	/**
	 * Return count, and sum of products
	 *
	 * @param	int		$dt_start	date start
	 * @param	int		$dt_end		date end
	 * @param	int		$cachedelay		Delay we accept for cache file (0=No read, no save of cache, -1=No read but save)
	 * @return 	array					Array of values
	 */
	function getAllLeadByType($limit=5)
	{
		global $conf,$user,$langs;
	
		$datay=array();
		
		$sql = "SELECT";
		$sql .= " count(DISTINCT t.rowid), fk_c_type";
		$sql .= " FROM " . MAIN_DB_PREFIX . "lead as t";
		$sql .= " GROUP BY fk_c_type";
	
		
		$result=array();
		$res=array();
		
		dol_syslog(get_class($this).'::'.__METHOD__."", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0; $other=0;
			while ($i < $num)
			{
				$row = $this->db->fetch_row($resql);
				if ($i < $limit || $num == $limit) $result[$i] = array($this->lead->type[$row[1]].'('.$row[0].')',$row[0]);	
				else $other += $row[1];
				$i++;
			}
			if ($num > $limit) $result[$i] = array($langs->transnoentitiesnoconv("Other"),$other);
			$this->db->free($resql);
		}
		else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . '::'.__METHOD__.' '. $this->error, LOG_ERR);
			return - 1;
		}
		
		
		return $result;
	}
	
	/**
	 * Return count, and sum of products
	 *
	 * @param	int		$dt_start	date start
	 * @param	int		$dt_end		date end
	 * @param	int		$cachedelay		Delay we accept for cache file (0=No read, no save of cache, -1=No read but save)
	 * @return 	array					Array of values
	 */
	function getAllLeadByStatus($limit=5)
	{
		global $conf,$user,$langs;
	
		$datay=array();
	
		$sql = "SELECT";
		$sql .= " count(DISTINCT t.rowid), fk_c_status";
		$sql .= " FROM " . MAIN_DB_PREFIX . "lead as t";
		$sql .= " GROUP BY fk_c_type";
	
	
		$result=array();
		$res=array();
	
		dol_syslog(get_class($this).'::'.__METHOD__."", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0; $other=0;
			while ($i < $num)
			{
				$row = $this->db->fetch_row($resql);
				if ($i < $limit || $num == $limit) $result[$i] = array($this->lead->status[$row[1]].'('.$row[0].')',$row[0]);
				else $other += $row[1];
				$i++;
			}
			if ($num > $limit) $result[$i] = array($langs->transnoentitiesnoconv("Other"),$other);
			$this->db->free($resql);
		}
		else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . '::'.__METHOD__.' '. $this->error, LOG_ERR);
			return - 1;
		}
	
	
		return $result;
	}
}