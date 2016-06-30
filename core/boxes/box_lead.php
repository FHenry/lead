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
 * \file		core/boxes/mybox.php
 * \ingroup	mymodule
 * \brief		This file is a sample box definition file
 * Put some comments here
 */
include_once DOL_DOCUMENT_ROOT . "/core/boxes/modules_boxes.php";

/**
 * Class to manage the box
 */
class box_lead extends ModeleBoxes
{

	public $boxcode = "lead";

	public $boximg = "lead@lead";

	public $boxlabel;

	public $depends = array(
		"lead"
	);

	public $db;

	public $param;

	public $info_box_head = array();

	public $info_box_contents = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $langs;
		$langs->load("boxes");
		$langs->load("lead@lead");
		
		$this->boxlabel = $langs->transnoentitiesnoconv("LeadLate");
	}

	/**
	 * Load data into info_box_contents array to show array later.
	 *
	 * @param int $max of records to load
	 *
	 * @return void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs, $db;
		
		$this->max = $max;
		
		dol_include_once('/lead/class/lead.class.php');
		
		$lead = new Lead($db);
		
		$lead->fetch_all('DESC', 't.date_closure', $max, 0, array(
			't.date_closure<' => dol_now()
		));
		
		$text = $langs->trans("LeadLate", $max);
		$this->info_box_head = array(
			'text' => $text,
			'limit' => dol_strlen($text)
		);
		
		$i = 0;
		foreach ($lead->lines as $line) {
			// FIXME: line is an array, not an object
			$line->fetch_thirdparty();
			// Ref
			$this->info_box_contents[$i][0] = array(
				'td' => 'align="left" width="16"',
				'logo' => $this->boximg,
				'url' => dol_buildpath('/lead/lead/card.php', 1) . '?id=' . $line->id
			);
			
			$this->info_box_contents[$i][1] = array(
				'td' => 'align="left"',
				'text' => $line->ref,
				'url' => dol_buildpath('/lead/lead/card.php', 1) . '?id=' . $line->id
			);
			
			$this->info_box_contents[$i][2] = array(
				'td' => 'align="left" width="16"',
				'logo' => 'company',
				'url' => DOL_URL_ROOT . "/comm/fiche.php?socid=" . $line->fk_soc
			);
			
			$this->info_box_contents[$i][3] = array(
				'td' => 'align="left"',
				'text' => dol_trunc($line->thirdparty->name, 40),
				'url' => DOL_URL_ROOT . "/comm/fiche.php?socid=" . $line->fk_soc
			);
			
			// Amount Guess
			
			$this->info_box_contents[$i][4] = array(
				'td' => 'align="left"',
				'text' => price($line->amount_prosp, 'HTML') . $langs->getCurrencySymbol($conf->currency)
			);
			
			// Amount real
			$this->info_box_contents[$i][5] = array(
				'td' => 'align="left"',
				'text' => $line->getRealAmount() . $langs->getCurrencySymbol($conf->currency)
			);
			
			$i ++;
		}
	}

	/**
	 * Method to show box
	 *
	 * @param array $head with properties of box title
	 * @param array $contents with properties of box lines
	 *
	 * @return void
	 */
	public function showBox($head = null, $contents = null)
	{
		parent::showBox($this->info_box_head, $this->info_box_contents);
	}
}
