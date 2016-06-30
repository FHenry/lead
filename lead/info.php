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
 * \file lead/lead/info.php
 * \ingroup lead
 * \brief info of lead
 */
$res = @include '../../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once '../class/lead.class.php';
require_once '../lib/lead.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';

// Security check
if (! $user->rights->lead->read)
	accessforbidden();

$id = GETPOST('id', 'int');

/*
 * View
 */

llxHeader('', $langs->trans("LeadInfo"));

$object = new Lead($db);
$object->info($id);

$head = lead_prepare_head($object);

dol_fiche_head($head, 'info', $langs->trans("LeadLead"), 0, 'bill');

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';
print '</div>';

dol_fiche_end();
llxFooter();
$db->close();
