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
 * \file		lib/lead.lib.php
 * \ingroup	lead
 * \brief		This file is an example module library
 * Put some comments here
 */
function leadAdminPrepareHead()
{
	global $langs, $conf;
	
	$langs->load("lead@lead");
	$langs->load("admin");
	
	$h = 0;
	$head = array();
	
	$head[$h][0] = dol_buildpath("/lead/admin/admin_lead.php", 1);
	$head[$h][1] = $langs->trans("SettingsLead");
	$head[$h][2] = 'settings';
	$h ++;
	
	$head[$h][0] = dol_buildpath("/lead/admin/lead_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'attributes';
	$h ++;
	
	$head[$h][0] = dol_buildpath("/lead/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h ++;
	
	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array(
	// 'entity:+tabname:Title:@lead:/lead/mypage.php?id=__ID__'
	// ); // to add new tab
	// $this->tabs = array(
	// 'entity:-tabname:Title:@lead:/lead/mypage.php?id=__ID__'
	// ); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'lead_admin');
	
	return $head;
}

/**
 * Prepare page head
 *
 * @param Lead $object The lead
 *
 * @return array Header contents (tabs)
 */
function lead_prepare_head($object)
{
	global $langs, $conf;
	
	$langs->load("lead@lead");
	
	$h = 0;
	$head = array();
	
	$head[$h][0] = dol_buildpath("/lead/lead/card.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("LeadLead");
	$head[$h][2] = 'card';
	$h ++;
	
	$head[$h][0] = dol_buildpath("/lead/lead/contact.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Contacts");
	$head[$h][2] = 'contact';
	$h ++;
	
	$head[$h][0] = dol_buildpath("/lead/lead/document.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Documents");
	$head[$h][2] = 'documents';
	$h ++;
	
	if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
	{
		$nbNote = 0;
		if(!empty($object->note_private)) $nbNote++;
		if(!empty($object->note_public)) $nbNote++;
		$head[$h][0] = dol_buildpath("/lead/lead/note.php", 1) . '?id=' . $object->id;
		$head[$h][1] = $langs->trans('Notes');
		if($nbNote > 0) $head[$h][1].= ' ('.$nbNote.')';
		$head[$h][2] = 'note';
		$h++;
	}
	
	$head[$h][0] = dol_buildpath("/lead/lead/info.php", 1) . '?id=' . $object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h ++;
	
	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array(
	// 'entity:+tabname:Title:@lead:/lead/mypage.php?id=__ID__'
	// ); // to add new tab
	// $this->tabs = array(
	// 'entity:-tabname:Title:@lead:/lead/mypage.php?id=__ID__'
	// ); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'lead');
	
	return $head;
}

/**
 * Prepare head for statistics page
 *
 * @return array Header contents (tabs)
 */
function lead_stats_prepare_head()
{
	global $langs, $conf;

	$langs->load("lead@lead");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/lead/index.php", 1);
	$head[$h][1] = $langs->trans("LeadStats");
	$head[$h][2] = 'stat';
	$h ++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array(
	// 'entity:+tabname:Title:@lead:/lead/mypage.php?id=__ID__'
	// ); // to add new tab
	// $this->tabs = array(
	// 'entity:-tabname:Title:@lead:/lead/mypage.php?id=__ID__'
	// ); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'lead_stats');

	return $head;
}
