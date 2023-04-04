<?php
/*
 * Copyright (C) 2014-2016 Florian HENRY <florian.henry@atm-consulting.fr>
 * Copyright (C) 2015 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
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
 * \file	core/boxes/box_lead_current.php
 * \ingroup	lead
 * \brief	Current leads box
 */
include_once DOL_DOCUMENT_ROOT . "/core/boxes/modules_boxes.php";

/**
 * Class to manage the box
 */
class box_lead_current extends ModeleBoxes
{

	public $boxcode = "lead_current";

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

		$this->boxlabel = $langs->transnoentitiesnoconv("LeadList");
	}

	/**
	 * Load data into info_box_contents array to show array later.
	 *
	 * @param int $max of records to load
	 * @return void
	 */
	public function loadBox($max = 5)
	{
		global $conf, $user, $langs, $db;

		$this->max = $max;

		dol_include_once('/lead/class/lead.class.php');

		$lead = new Lead($db);

		$lead->fetchAll('DESC', 't.ref', $max, 0);

		$text = $langs->trans("LeadList");
		$text .= " (" . $langs->trans("LastN", $max) . ")";
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
				'url' => DOL_URL_ROOT . "/comm/card.php?socid=" . $line->fk_soc
			);

			$this->info_box_contents[$i][3] = array(
				'td' => 'align="left"',
				'text' => dol_trunc($line->thirdparty->name, 40),
				'url' => DOL_URL_ROOT . "/comm/card.php?socid=" . $line->fk_soc
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
	 * @param integer $nooutput nooutput
	 * @return void
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0) {
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
