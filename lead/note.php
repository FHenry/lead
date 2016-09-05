<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Florian Henry		<florian.henry@atm-consulting.fr>
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

$res = @include '../../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once '../class/lead.class.php';
require_once '../class/html.formlead.class.php';
require_once '../lib/lead.lib.php';

$langs->load('lead@lead');

$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');

// Security check
if (! $user->rights->lead->read)
	accessforbidden();

$object = new Lead($db);
if ($id > 0) {
	$ret = $object->fetch($id);
	if ($ret < 0)
		setEventMessage($object->error, 'errors');
	if ($ret > 0)
		$ret = $object->fetch_thirdparty();
	if ($ret < 0)
		setEventMessage($object->error, 'errors');
}

$permissionnote=$user->rights->lead->write;	// Used by the include of actions_setnotes.inc.php
$permission=$user->rights->lead->write;

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not includ_once



/*
 * View
 */

llxHeader();

$form = new Form($db);
$formlead = new FormLead($db);

if ($id > 0)
{

    $head = lead_prepare_head($object);
	dol_fiche_head($head, 'note', $langs->trans('Module103111Name'), 0, dol_buildpath('/lead/img/object_lead.png', 1), 1);


	print '<table class="border" width="100%">';

	$linkback = '<a href="'.dol_buildpath("/lead/lead/list.php", 1).'">'.$langs->trans("BackToList").'</a>';

	print '<tr>';
	print '<td width="20%">';
	print $langs->trans('Ref');
	print '</td>';
	print '<td>';
	print $formlead->showrefnav_custom($object, 'id', $linkback, 1, 'rowid', 'ref', '');
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td width="20%">';
	print $langs->trans('LeadRefInt');
	print '</td>';
	print '<td>';
	print $object->ref_int;
	print '</td>';
	print '</tr>';
	print "</table>\n";

    print '<br>';

	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	dol_fiche_end();
}


llxFooter();

$db->close();
