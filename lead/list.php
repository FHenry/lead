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
 * \file lead/lead/list.php
 * \ingroup lead
 * \brief list of lead
 */
$res = @include '../../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");
global $conf;
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once '../class/lead.class.php';
require_once '../lib/lead.lib.php';
require_once '../class/html.formlead.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
if ($conf->margin->enabled) {
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmargin.class.php';
}

if (function_exists('newToken')) $urlToken = "&token=".newToken();

$form = new Form($db);
$formlead = new FormLead($db);
$object = new Lead($db);
$formother = new FormOther($db);
if (! empty($conf->margin->enabled)) {
	$formmargin = new FormMargin($db);
	$rounding = min($conf->global->MAIN_MAX_DECIMALS_UNIT, $conf->global->MAIN_MAX_DECIMALS_TOT);
}

$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element, true);
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// Security check
if (! $user->rights->lead->read)
	accessforbidden();

$sortorder = GETPOST('sortorder', 'alpha');
$sortfield = GETPOST('sortfield', 'alpha');
$page = GETPOST('page', 'int');
$page = intval($page);

//Socid is fill when come from thirdparty tabs
$socid=GETPOST('socid','int');

//view type is special predefined filter
$viewtype=GETPOST('viewtype','alpha');

// Search criteria
$search_commercial = GETPOST("search_commercial",'alpha');
$search_soc = GETPOST("search_soc",'alpha');
$search_ref = GETPOST("search_ref",'alpha');
$search_ref_int = GETPOST("search_ref_int",'alpha');
$search_type = GETPOST('search_type','alpha');
if ($search_type == - 1)
	$search_type = 0;
$search_status = GETPOST('search_status','alpha');
if ($search_status == - 1)
	$search_status = 0;
$search_month = GETPOST('search_month', 'aplha');
$search_year = GETPOST('search_year', 'int');
$search_invoiceid = GETPOST('search_invoiceid', 'int');
$search_invoiceref = GETPOST('search_invoiceref', 'alpha');
$search_propalref = GETPOST('search_propalref', 'alpha');
$search_propalid = GETPOST('search_propalid', 'alpha');

$link_element = GETPOST("link_element",'alpha');
if (! empty($link_element)) {
	$action = 'link_element';
}

$option='';
$filter = array();

if (! empty($search_commercial) && $search_commercial > 0) {
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

// Initialize context for list
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'leadlist';

// Add $option from extra fields
foreach ($search_array_options as $key => $val)
{
	$crit=$val;
	$tmpkey=preg_replace('/search_options_/','',$key);
	$typ=$extrafields->attribute['lead']['type'][$tmpkey];
	if ($val != '') {
		$option.='&search_options_'.$tmpkey.'='.urlencode($val);
	}
	$mode=0;
	if (in_array($typ, array('int','double'))) $mode=1;    // Search on a numeric
	if ($val && ( ($crit != '' && ! in_array($typ, array('select'))) || ! empty($crit)))
	{
		$filter['leadextra.'.$tmpkey]=natural_search('leadextra.'.$tmpkey, $crit, $mode, 1);
	}
}

if (function_exists('newToken')) $option.= "&token=".newToken();
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $option.='&contextpage='.$contextpage;

$arrayfields = array(
		't.ref' => array(
				'label' => $langs->trans("Ref"),
				'checked' => 1
		),
		't.ref_int' => array(
				'label' => $langs->trans("LeadRefInt"),
				'checked' => 1
		),
		'so.nom' => array(
				'label' => $langs->trans("Customer"),
				'checked' => 1
		),
		'usr.lastname' => array(
				'label' => $langs->trans("LeadCommercial"),
				'checked' => 1
		),
		'leadsta.label' => array(
				'label' => $langs->trans("LeadStatus"),
				'checked' => 1
		),
		'leadtype.label' => array(
				'label' => $langs->trans("LeadType"),
				'checked' => 1
		),
		't.amount_prosp' => array(
				'label' => $langs->trans("LeadAmountGuess"),
				'checked' => 1
		),
		't.date_closure' => array(
				'label' => $langs->trans("LeadDeadLine"),
				'checked' => 1
		),
);

if (! empty($conf->margin->enabled)){
	$arrayfields['margin'] = array('label'=>$langs->trans("Margin"), 'checked'=>0);
	$arrayfields['markRate'] = array('label'=>$langs->trans("MarkRate"), 'checked'=>0);
}

// Extra fields
if (is_array($extrafields->attribute['lead']['label']) && count($extrafields->attribute['lead']['label'])) {
	foreach ( $extrafields->attribute['lead']['label'] as $key => $val ) {
		$typeofextrafield=$extrafields->attribute['lead']['label'][$key];
		if ($typeofextrafield!='separate') {
			$arrayfields["leadextra." . $key] = array(
					'label' => $extrafields->attribute['lead']['label'][$key],
					'checked' => $extrafields->attribute['lead']['list'][$key],
					'position' => $extrafields->attribute['lead']['pos'][$key],
					'enabled' => $extrafields->attribute['lead']['perms'][$key]
			);
		}
	}
}


if ($page == - 1) {
	$page = 0;
}

$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (empty($sortorder))
	$sortorder = "DESC";
if (empty($sortfield))
	$sortfield = "t.date_closure";

$title = $langs->trans('LeadList');

llxHeader('', $title);


include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x",'alpha') || GETPOST("button_removefilter.x",'alpha') || GETPOST("button_removefilter",'alpha')) {
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
	$search_array_options=array();
	$filter=array();
}

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
	$nbtotalofrecords = $object->fetchAll($sortorder, $sortfield, 0, 0, $filter);
}
$resql = $object->fetchAll($sortorder, $sortfield, $conf->liste_limit, $offset, $filter);

if ($resql != - 1) {
	$num = $resql;

	print_barre_liste($title, $page, $_SERVER['PHP_SELF'], $option, $sortfield, $sortorder, '', $num, $nbtotalofrecords);

	$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
	$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);

	print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" name="search_form">' . "\n";

	$newToken = function_exists('newToken') ? newToken() : $_SESSION['newtoken'];
	print '<input type="hidden" name="token" id="token" value="'.$newToken.'">';

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

	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';

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
	if (! empty($arrayfields['t.ref']['checked'])) print_liste_field_titre($langs->trans("Ref"), $_SERVER['PHP_SELF'], "t.ref", "", $option, '', $sortfield, $sortorder);
	if (! empty($arrayfields['t.ref_int']['checked'])) print_liste_field_titre($langs->trans("LeadRefInt"), $_SERVER['PHP_SELF'], "t.ref_int", "", $option, '', $sortfield, $sortorder);
	if (! empty($arrayfields['so.nom']['checked'])) print_liste_field_titre($langs->trans("Customer"), $_SERVER['PHP_SELF'], "so.nom", "", $option, '', $sortfield, $sortorder);
	if (! empty($arrayfields['usr.lastname']['checked'])) print_liste_field_titre($langs->trans("LeadCommercial"), $_SERVER['PHP_SELF'], "usr.lastname", "", $option, '', $sortfield, $sortorder);
	if (! empty($arrayfields['leadsta.label']['checked'])) print_liste_field_titre($langs->trans("LeadStatus"), $_SERVER['PHP_SELF'], "leadsta.label", "", $option, '', $sortfield, $sortorder);
	if (! empty($arrayfields['leadtype.label']['checked'])) print_liste_field_titre($langs->trans("LeadType"), $_SERVER['PHP_SELF'], "leadtype.label", "", $option, '', $sortfield, $sortorder);
	if (! empty($arrayfields['t.amount_prosp']['checked'])) print_liste_field_titre($langs->trans("LeadAmountGuess"), $_SERVER['PHP_SELF'], "t.amount_prosp", "", $option, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("LeadRealAmount"), $_SERVER['PHP_SELF'], "", "", $option, 'align="right"', $sortfield, $sortorder);
	if (! empty($conf->margin->enabled)) {
		if (!empty($arrayfields['margin']['checked'])) print_liste_field_titre($arrayfields['margin']['label'], $_SERVER["PHP_SELF"], "", "", "$option", 'align="center"', $sortfield, $sortorder);
		if (!empty($arrayfields['markRate']['checked'])) print_liste_field_titre($arrayfields['markRate']['label'], $_SERVER["PHP_SELF"], "", "", "$option", 'align="center"', $sortfield, $sortorder);
	}
	if (! empty($arrayfields['t.date_closure']['checked'])) print_liste_field_titre($langs->trans("LeadDeadLine"), $_SERVER['PHP_SELF'], "t.date_closure", "", $option, 'align="right"', $sortfield, $sortorder);

	// Extra fields
	if (is_array($extrafields->attribute['lead']['label']) && count($extrafields->attribute['lead']['label']))
	{
		foreach($extrafields->attribute['lead']['label'] as $key => $val)
		{
			if (! empty($arrayfields["leadextra.".$key]['checked']))
			{
				$align=$extrafields->getAlignFlag($key);
				print_liste_field_titre($extralabels[$key],$_SERVER["PHP_SELF"],"leadextra.".$key,"",$option,($align?'align="'.$align.'"':''),$sortfield,$sortorder);
			}
		}
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');

	print "</tr>\n";

	print '<tr class="liste_titre">';
	if (! empty($arrayfields['t.ref']['checked']))
	{
		print '<td><input type="text" class="flat" name="search_ref" value="' . $search_ref . '" size="5"></td>';
	}
	if (! empty($arrayfields['t.ref_int']['checked']))
	{
		print '<td><input type="text" class="flat" name="search_ref_int" value="' . $search_ref_int . '" size="5"></td>';
	}
	if (! empty($arrayfields['so.nom']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_soc" value="' . $search_soc . '" size="20">';
		print '</td>';
	}

	if (! empty($arrayfields['usr.lastname']['checked']))
	{
		print '<td class="liste_titre">';
		print $formother->select_salesrepresentatives($search_commercial, 'search_commercial', $user);
		print '</td>';
	}

	if (! empty($arrayfields['leadsta.label']['checked']))
	{
		print '<td class="liste_titre">';
		print $formlead->select_lead_status($search_status, 'search_status', 1);
		print '</td>';
	}

	if (! empty($arrayfields['leadtype.label']['checked']))
	{
		print '<td class="liste_titre">';
		print $formlead->select_lead_type($search_type, 'search_type', 1);
		print '</td>';
	}

	if (! empty($arrayfields['t.amount_prosp']['checked']))
	{
		// amount guess
		print '<td id="totalamountguess" align="right"></td>';
	}
	// amount real
	print '<td id="totalamountreal" align="right"></td>';

	if (!empty($arrayfields['margin']['checked'])){
		print '<td id="totalmargin" align="right"></td>';
	}
	if (!empty($arrayfields['markRate']['checked'])){
		print '<td align="right"></td>';
	}

	if (! empty($arrayfields['t.date_closure']['checked']))
	{
		// dt closure
		print '<td></td>';
	}


	// Extra fields
	if (is_array($extrafields->attribute['lead']['label']) && count($extrafields->attribute['lead']['label']))
	{
		foreach($extrafields->attribute['lead']['label'] as $key => $val)
		{
			if (! empty($arrayfields["leadextra.".$key]['checked']))
			{
				$align=$extrafields->getAlignFlag($key);
				$typeofextrafield=$extrafields->attribute['lead']['type'][$key];
				print '<td class="liste_titre'.($align?' '.$align:'').'">';
				if (in_array($typeofextrafield, array('varchar', 'int', 'double', 'select')))
				{
					$crit=$val;
					$tmpkey=preg_replace('/search_options_/','',$key);
					$searchclass='';
					if (in_array($typeofextrafield, array('varchar', 'select'))) $searchclass='searchstring';
					if (in_array($typeofextrafield, array('int', 'double'))) $searchclass='searchnum';
					print '<input class="flat'.($searchclass?' '.$searchclass:'').'" size="4" type="text" name="search_options_'.$tmpkey.'" value="'.dol_escape_htmltag($search_array_options['search_options_'.$tmpkey]).'">';
				}
				print '</td>';
			}
		}
	}

	// edit button
	print '<td class="liste_titre" align="right">';
	$searchpitco=$form->showFilterAndCheckAddButtons(0);
	print $searchpitco;
	print '</td>';

	print "</tr>\n";


	$var = true;
	$totalamountguess = 0;
	$totalamountreal = 0;
    $totalmargin = 0;
    $totalarray = array('nbfield' => 0);

	foreach ($object->lines as $line) {
		/**
		 * @var Lead $line
		 */

		if (! empty($conf->margin->enabled)) {
			$propal = new Propal($db);
			$lead = new Lead($db);
			$lead->fetchDocumentLink($line->id, $object->listofreferent['propal']['table']);
			$marginInfos["total_margin"] = 0;
			$marginInfos["total_mark_rate"] = 0;
			$countProp = 0;
			foreach ($lead->doclines as $propalArray){
				$propal->fetch($propalArray->fk_source);
				$marginInfosPropal = $formmargin->getMarginInfosArray($propal);
				$marginInfos["total_margin"] += $marginInfosPropal["total_margin"];
				$marginInfos["total_mark_rate"] += floatval($marginInfosPropal["total_mark_rate"]);
				$countProp++;
			}
			if ($countProp > 0){
				$marginInfos["total_mark_rate"] = $marginInfos["total_mark_rate"] / $countProp;
			}
		}

		// Affichage tableau des lead
		$var = ! $var;
		print '<tr ' . $bc[$var] . '>';

		if (! empty($arrayfields['t.ref']['checked']))
		{
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
		}

		if (! empty($arrayfields['t.ref_int']['checked']))
		{
			// RefInt
			print '<td><a href="card.php?id=' . $line->id . '">' . $line->ref_int . '</a></td>';
		}

		if (! empty($arrayfields['so.nom']['checked']))
		{
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
		}

		if (! empty($arrayfields['usr.lastname']['checked']))
		{
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
		}


		if (! empty($arrayfields['leadsta.label']['checked']))
		{
			// Status
			print '<td>' . $line->status_label . '</td>';
		}

		if (! empty($arrayfields['leadtype.label']['checked']))
		{
			// Type
			print '<td>' . $line->type_label . '</td>';
		}


		if (! empty($arrayfields['t.amount_prosp']['checked']))
		{
			// Amount prosp
			print '<td align="right" nowrap>' . price($line->amount_prosp) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td>';
		}
		$totalamountguess += $line->amount_prosp;

		// Amount real
		$amount = $line->getRealAmount();
		print '<td  align="right" nowrap>' . price($amount) . ' ' . $langs->getCurrencySymbol($conf->currency) . '</td>';
		$totalamountreal += $amount;

		if (! empty($conf->margin->enabled)) {
			// Margin
			if (!empty($arrayfields['margin']['checked'])) {
				print '<td align="center" nowrap>' . price($marginInfos['total_margin']). $langs->getCurrencySymbol($conf->currency) . "</td>\n";
				$totalmargin += $marginInfos['total_margin'];
			}
			// MarkRate
			if (!empty($arrayfields['markRate']['checked'])) {
				print '<td align="right">' . (($marginInfos['total_mark_rate'] == '') ? '' : price($marginInfos['total_mark_rate'], null, null, null, null, $rounding) . '%') . '</td>';
			}
		}

		if (! empty($arrayfields['t.date_closure']['checked']))
		{
			// Closure date
			print '<td  align="right">' . dol_print_date($line->date_closure, 'daytextshort') . '</td>';
		}

		// Extra fields
		if (is_array($extrafields->attribute['lead']['label']) && count($extrafields->attribute['lead']['label']))
		{
			foreach($extrafields->attribute['lead']['label'] as $key => $val)
			{
				if (! empty($arrayfields["leadextra.".$key]['checked']))
				{
					print '<td';
					$align=$extrafields->getAlignFlag($key);
					if ($align) print ' align="'.$align.'"';
					print '>';
					$tmpkey='options_'.$key;
					print $extrafields->showOutputField($key, $line->array_options[$tmpkey], '', 'lead');
					print '</td>';
				}
			}
			if (! $i) $totalarray['nbfield']++;
		}

		print '<td align="center"><a href="card.php?id=' . $line->id . '&action=edit'.$urlToken.'">' . img_picto($langs->trans('Edit'), 'edit') . '</td>';

		print "</tr>\n";

		$i ++;
	}

	print "</table>";
	print '</form>';

	print '<script type="text/javascript" language="javascript">' . "\n";
	print '$(document).ready(function() {
					$("#totalamountguess").append("' . price($totalamountguess) . $langs->getCurrencySymbol($conf->currency) . '");
					$("#totalamountreal").append("' . price($totalamountreal) . $langs->getCurrencySymbol($conf->currency) . '");';
	if (! empty($conf->margin->enabled)) {
		print '$("#totalmargin").append("' . price($totalmargin) . $langs->getCurrencySymbol($conf->currency) . '");';
	}
	print '});';
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

dol_fiche_end();
llxFooter();
$db->close();
