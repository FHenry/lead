<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2011 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Florian HENRY         <florian.henry@atm-consulting.fr>
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load('lead@lead');
$langs->load('other');

$id=GETPOST('id','int');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm', 'alpha');

// Security check
if (! $user->rights->lead->read)
	accessforbidden();

// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";

$object = new Lead($db);
if ($id > 0) {
	$ret = $object->fetch($id);
	if ($ret < 0)
		setEventMessage($object->error, 'errors');
	if ($ret > 0)
		$ret = $object->fetch_thirdparty();
	if ($ret < 0)
		setEventMessage($object->error, 'errors');

	$upload_dir = $conf->lead->dir_output . "/" . dol_sanitizeFileName($object->ref);
}

/*
 * Actions
 */
include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';


/*
 * View
 */

llxHeader();

$form = new Form($db);
$formlead = new FormLead($db);

if ($id > 0 || ! empty($ref))
{
	if ($object->fetch($id) > 0)
	{
		$object->fetch_thirdparty();

		$head = lead_prepare_head($object);
		dol_fiche_head($head, 'documents', $langs->trans('Module103111Name'), 0, dol_buildpath('/lead/img/object_lead.png', 1), 1);


		// Construit liste des fichiers
		$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
		$totalsize=0;
		foreach($filearray as $key => $file)
		{
			$totalsize+=$file['size'];
		}



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
		print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
		print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
		print "</table>\n";
		print "</div>\n";


		$modulepart = 'lead';
		$permission = $user->rights->lead->write;
		$param = '&id=' . $object->id;
		include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';

	}
	else
	{
		dol_print_error($db);
	}
}
else
{
	print $langs->trans("ErrorUnknown");
}

$db->close();

dol_fiche_end();

llxFooter();
