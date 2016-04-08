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
 * \file lead/lead/list.php
 * \ingroup lead
 * \brief list of lead
 */
$res = @include '../../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once '../class/lead.class.php';
require_once '../lib/lead.lib.php';
require_once '../class/html.formlead.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

// Security check
if (! $user->rights->lead->read)
	accessforbidden();

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');

//Socid is fill when come from thirdparty tabs
$socid=GETPOST('socid','int');

//view type is special predefined filter
$viewtype=GETPOST('viewtype','alpha');

// Search criteria
$search_commercial = GETPOST("search_commercial");
$search_soc = GETPOST("search_soc");
$search_ref = GETPOST("search_ref");
$search_ref_int = GETPOST("search_ref_int");
$search_type = GETPOST('search_type');
if ($search_type == - 1)
	$search_type = 0;
$search_status = GETPOST('search_status');
if ($search_status == - 1)
	$search_status = 0;
$search_month = GETPOST('search_month', 'aplha');
$search_year = GETPOST('search_year', 'int');
$search_invoiceid = GETPOST('search_invoiceid', 'int');
$search_invoiceref = GETPOST('search_invoiceref', 'alpha');
$search_propalref = GETPOST('search_propalref', 'alpha');
$search_propalid = GETPOST('search_propalid', 'alpha');

$link_element = GETPOST("link_element");
if (! empty($link_element)) {
	$action = 'link_element';
}

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x")) {
	$search_commercial = '';
	$search_soc = '';
	$search_ref = '';
	$search_ref_int = '';
	$search_type = '';
	$search_status = '';
	$search_month = '';
	$search_year = '';
	$search_invoiceid='';
	$search_invoiceref='';
	$search_propalref='';
	$search_propalid='';
}

$filter = array();
if (! empty($search_commercial)) {
	$filter['t.fk_user_resp'] = $search_commercial;
	$option .= '&search_commercial=' . $search_commercial;
}
if (! empty($search_soc)) {
	$filter['so.nom'] = $search_soc;
	$option .= '&search_soc=' . $search_soc;
}
if (!empty($socid)) {
	$filter['so.rowid'] = $socid;
	$option .= '&socid=' . $socid;
}
if (! empty($search_ref)) {
	$filter['t.ref'] = $search_ref;
	$option .= '&search_ref=' . $search_ref;
}
if (! empty($search_ref_int)) {
	$filter['t.ref_int'] = $search_ref_int;
	$option .= '&search_ref_int=' . $search_ref_int;
}
if (! empty($search_type)) {
	$filter['t.fk_c_type'] = $search_type;
	$option .= '&search_type=' . $search_type;
}
if (! empty($search_status)) {
	$filter['t.fk_c_status'] = $search_status;
	$option .= '&search_status=' . $search_status;
}
if (! empty($search_month)) {
	$filter['MONTH(t.date_closure)'] = $search_month;
	$option .= '&search_month=' . $search_month;
}
if (! empty($search_year)) {
	$filter['YEAR(t.date_closure)'] = $search_year;
	$option .= '&search_year=' . $search_year;
}

if (!empty($viewtype)) {
	if ($viewtype=='current') {
		$filter['t.fk_c_status !IN'] = '6,7';
	}
	if ($viewtype=='my') {
		$filter['t.fk_user_resp'] = $user->id;
	}
	if ($viewtype=='late') {
		$filter['t.fk_c_status !IN'] = '6,7';
		$filter['t.date_closure<'] = dol_now();
	}
	$option .= '&viewtype=' . $viewtype;
}

/*if (! empty($search_invoiceid)) {
	$invoice = new Facture($db);
	$invoice->fetch($search_invoiceid);
	$search_invoiceref = $invoice->ref;
	$object_socid = $invoice->socid;
}

if (! empty($search_invoiceref)) {
	$invoice = new Facture($db);
	$invoice->fetch('', $search_invoiceref);
	$search_invoiceid = $invoice->id;
	$object_socid = $invoice->socid;
}

if (! empty($search_propalref)) {
	$propal = new Propal($db);
	$propal->fetch('', $search_propalref);
	$search_propalid = $propal->id;
	$object_socid = $propal->socid;
}

if (! empty($search_propalid)) {
	$propal = new Propal($db);
	$propal->fetch($search_propalid, '');
	$search_propalref = $propal->ref;
	$object_socid = $propal->socid;
}*/

/*
if (!empty($user->rights->societe->client->voir)) {
	$filter['userlimitviewsoc'] = 1;
} else {
	$filter['userlimitviewsoc'] = 0;
}

$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
*/

if ($page == - 1) {
	$page = 0;
}

$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$form = new Form($db);
$formlead = new FormLead($db);
$object = new Lead($db);
$formother = new FormOther($db);

if (empty($sortorder))
	$sortorder = "DESC";
if (empty($sortfield))
	$sortfield = "t.date_closure";

$title = $langs->trans('LeadList');

llxHeader('', $title);

if (!empty($socid)) {
	require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	$soc = new Societe($db);
	$soc->fetch($socid);
	$head = societe_prepare_head($soc);

	dol_fiche_head($head, 'tabLead', $langs->trans("Module103111Name"),0,dol_buildpath('/lead/img/object_lead.png', 1),1);
}

// Count total nb of records
$nbtotalofrecords = 0;

if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$nbtotalofrecords = $object->fetch_all($sortorder, $sortfield, 0, 0, $filter);
}
$resql = $object->fetch_all($sortorder, $sortfield, $conf->liste_limit, $offset, $filter);

if ($resql != - 1) {
	$num = $resql;

	print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $option, $sortfield, $sortorder, '', $num, $nbtotalofrecords);

	print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="search_form">' . "\n";

	if (! empty($sortfield))
		print '<input type="hidden" name="sortfield" value="' . $sortfield . '"/>';
	if (! empty($sortorder))
		print '<input type="hidden" name="sortorder" value="' . $sortorder . '"/>';
	if (! empty($page))
		print '<input type="hidden" name="page" value="' . $page . '"/>';
	if (! empty($viewtype))
		print '<input type="hidden" name="viewtype" value="' . $viewtype . '"/>';
	if (! empty($socid))
		print '<input type="hidden" name="socid" value="' . $socid . '"/>';

	$moreforfilter = $langs->trans('Period') . '(' . $langs->trans("LeadDateDebut") . ')' . ': ';
	$moreforfilter .= $langs->trans('Month') . ':<input class="flat" type="text" size="4" name="search_month" value="' . $search_month . '">';
	$moreforfilter .= $langs->trans('Year') . ':' . $formother->selectyear($search_year ? $search_year : - 1, 'search_year', 1, 20, 5);

	if ($moreforfilter) {
		print '<div class="liste_titre">';
		print $moreforfilter;
		print '</div>';
	}

	$i = 0;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"), $_SERVEUR['PHP_SELF'], "t.ref", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("LeadRefInt"), $_SERVEUR['PHP_SELF'], "t.ref_int", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Customer"), $_SERVEUR['PHP_SELF'], "so.nom", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("LeadCommercial"), $_SERVEUR['PHP_SELF'], "usr.lastname", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("LeadStatus"), $_SERVEUR['PHP_SELF'], "leadsta.label", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("LeadType"), $_SERVEUR['PHP_SELF'], "leadtype.label", "", $option, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("LeadAmountGuess"), $_SERVEUR['PHP_SELF'], "t.amount_prosp", "", $option, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("LeadRealAmount"), $_SERVEUR['PHP_SELF'], "", "", $option, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("LeadDeadLine"), $_SERVEUR['PHP_SELF'], "t.date_closure", "", $option, 'align="right"', $sortfield, $sortorder);


	$extrafields = new ExtraFields($db);
	$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
	if (count($extralabels) > 0) {
		foreach($extralabels as $code_extra=>$label_extra) {
			if ($extrafields->attribute_list[$code_extra]) {
				print_liste_field_titre($label_extra, $_SERVEUR['PHP_SELF'], "leadextra.".$code_extra, "", $option, ' align="right" ', $sortfield, $sortorder);
			}
		}
	}


	print '<td align="center"></td>';

	print "</tr>\n";

	print '<tr class="liste_titre">';

	print '<td><input type="text" class="flat" name="search_ref" value="' . $search_ref . '" size="5"></td>';

	print '<td><input type="text" class="flat" name="search_ref_int" value="' . $search_ref_int . '" size="5"></td>';

	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_soc" value="' . $search_soc . '" size="20">';
	print '</td>';

	print '<td class="liste_titre">';
	print $formother->select_salesrepresentatives($search_commercial, 'search_commercial', $user);
	print '</td>';

	print '<td class="liste_titre">';
	print $formlead->select_lead_status($search_status, 'search_status', 1);
	print '</td>';

	print '<td class="liste_titre">';
	print $formlead->select_lead_type($search_type, 'search_type', 1);
	print '</td>';

	// amount guess
	print '<td id="totalamountguess" align="right"></td>';
	// amount real
	print '<td id="totalamountreal" align="right"></td>';
	// dt closure
	print '<td></td>';


	$extrafields = new ExtraFields($db);
	$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
	if (count($extralabels) > 0) {
		foreach($extralabels as $code_extra=>$label_extra) {
			if ($extrafields->attribute_list[$code_extra]) {
				print '<td></td>';
			}
		}
	}

	// edit button
	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
	print '</td>';

	print "</tr>\n";
	print '</form>';

	$var = true;
	$totalamountguess = 0;
	$totalamountreal = 0;

	foreach ($object->lines as $line) {
		/**
		 * @var Lead $line
		 */

		// Affichage tableau des lead
		$var = ! $var;
		print '<tr ' . $bc[$var] . '>';

		// Ref
		print '<td><a href="card.php?id=' . $line->id . '">' . $line->ref . '</a>';
		if ($line->fk_c_status!=6) {
			$result=$line->isObjectSignedExists();
			if ($result<0) {
				setEventMessages($line->error, null, 'errors');
			}elseif ($result>0) {
				print img_warning($langs->trans('LeadObjectWindExists'));
			}
		}
		print '</td>';

		// RefInt
		print '<td><a href="card.php?id=' . $line->id . '">' . $line->ref_int . '</a></td>';

		// Societe
		print '<td>';
		if (! empty($line->fk_soc) && $line->fk_soc != - 1) {
			$soc = new Societe($db);
			$soc->fetch($line->fk_soc);
			print $soc->getNomURL(1);
		} else {
			print '&nbsp;';
		}
		print '</td>';

		// Commercial
		print '<td>';
		if (! empty($line->fk_user_resp)) {
			$userstatic = new User($db);
			$userstatic->fetch($line->fk_user_resp);
			if (! empty($userstatic->id)) {
				print $userstatic->getFullName($langs);
			}
		}
		print '</td>';

		// Status
		print '<td>' . $line->status_label . '</td>';

		// Type
		print '<td>' . $line->type_label . '</td>';

		// Amount prosp
		print '<td align="right">' . price($line->amount_prosp) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td>';
		$totalamountguess += $line->amount_prosp;

		// Amount real
		$amount = $line->getRealAmount();
		print '<td  align="right">' . price($amount) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td>';
		$totalamountreal += $amount;

		// Closure date
		print '<td  align="right">' . dol_print_date($line->date_closure, 'daytextshort') . '</td>';

		$extrafields = new ExtraFields($db);
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
		if (count($extralabels) > 0) {
			foreach($extralabels as $code_extra=>$label_extra) {
				if ($extrafields->attribute_list[$code_extra]) {
					print '<td align="right">'.$line->array_options['options_'.$code_extra].'</td>';
				}
			}
		}

		print '<td align="center"><a href="card.php?id=' . $line->id . '&action=edit">' . img_picto($langs->trans('Edit'), 'edit') . '</td>';

		print "</tr>\n";

		$i ++;
	}

	print "</table>";

	print '<script type="text/javascript" language="javascript">' . "\n";
	print '$(document).ready(function() {
					$("#totalamountguess").append("' . price($totalamountguess) . $langs->getCurrencySymbol($conf->currency) . '");
					$("#totalamountreal").append("' . price($totalamountreal) . $langs->getCurrencySymbol($conf->currency) . '");
			});';
	print "\n" . '</script>' . "\n";
} else {
	setEventMessages(null, $object->errors, 'errors');
}

if (!empty($socid)) {
	//print '</div>';
	print '<div class="tabsAction">';
	if ($user->rights->lead->write)
	{
		print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/lead/lead/card.php',1).'?action=create&socid='.$socid.'">'.$langs->trans('LeadCreate').'</a></div>';
	}
	else
	{
		print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('LeadCreate').'</a></div>';
	}
	print '</div>';
}

llxFooter();
$db->close();
