<?php
/* Lead
* Copyright (C) 2014       Florian Henry   <florian.henry@open-concept.pro>
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
* along with this program; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
*/

/**
 * \file lead/lead/list.php
 * \ingroup lead
 * \brief list of lead
 */
$res = @include ("../../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../../main.inc.php"); // For "custom" directory
if (! $res)
	die ( "Include of main fails" );

require_once (DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php');
require_once ('../class/lead.class.php');
require_once ('../lib/lead.lib.php');
require_once ('../class/html.formlead.class.php');
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once (DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php');

// Security check
if (! $user->rights->lead->read)
	accessforbidden ();

$sortorder = GETPOST ( 'sortorder', 'alpha' );
$sortfield = GETPOST ( 'sortfield', 'alpha' );
$page = GETPOST ( 'page', 'int' );

// Search criteria
$search_commercial 	= GETPOST ( "search_commercial" );
$search_soc 		= GETPOST ( "search_soc" );
$search_ref 		= GETPOST ( "search_ref" );
$search_ref_int 	= GETPOST ( "search_ref_int" );
$search_type 		= GETPOST ('search_type');
if ($search_type==-1) $search_type=0;
$search_status 		= GETPOST ('search_status');
if ($search_status==-1) $search_status=0;
$search_month 		= GETPOST ( 'search_month', 'aplha' );
$search_year 		= GETPOST ( 'search_year', 'int' );


// Do we click on purge search criteria ?
if (GETPOST ( "button_removefilter_x" )) {
	$search_commercial 	='';
	$search_soc 		='';
	$search_ref 		='';
	$search_ref_int 	='';
	$search_type 		='';
	$search_status 		='';
	$search_month 		='';
	$search_year 		='';	
}

$filter = array ();
if (! empty ( $search_commercial )) {
	$filter ['t.fk_user_resp'] = $search_commercial;
}
if (! empty ( $search_soc )) {
	$filter ['so.nom'] = $search_soc;
}
if (! empty ( $search_ref )) {
	$filter ['t.ref'] = $search_ref;
}
if (! empty ( $search_ref_int )) {
	$filter ['t.ref_int'] = $search_ref_int;
}
if (! empty ( $search_type )) {
	$filter ['t.fk_c_type'] = $search_type;
}
if (! empty ( $search_status )) {
	$filter ['t.fk_c_status'] = $search_status;
}
if (! empty ( $search_month )) {
	$filter ['MONTH(t.date_closure)'] = $search_month;
}
if (! empty ( $search_year )) {
	$filter ['YEAR(t.date_closure)'] = $search_year;
}

if ($page == - 1) {
	$page = 0;
}

$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$form = new Form ( $db );
$formlead= new FormLead ( $db );
$object = new Lead ( $db );
$formother = new FormOther ( $db );


if (empty ( $sortorder ))
	$sortorder = "ASC";
if (empty ( $sortfield ))
	$sortfield = "t.date_closure";

$title = $langs->trans('LeadList');


llxHeader ( '', $title );


// Count total nb of records
$nbtotalofrecords = 0;

if (empty ( $conf->global->MAIN_DISABLE_FULL_SCANLIST )) {
	$nbtotalofrecords = $object->fetch_all ( $sortorder, $sortfield, 0, 0, $filter );
}
$resql = $object->fetch_all ( $sortorder, $sortfield, $conf->liste_limit, $offset, $filter );

if ($resql != - 1) {
	$num = $resql;
	
	if (! empty ( $search_commercial ))
		$option .= '&search_commercial=' . $search_commercial;
	if (! empty ( $search_soc ))
		$option .= '&search_soc=' . $search_soc;
	if (! empty ( $search_ref ))
		$option .= '&search_ref=' . $search_ref;
	if (! empty ( $search_ref_int ))
		$option .= '&search_ref_int=' . $search_ref_int;
	if (! empty ( $search_type ))
		$option .= '&search_type=' . $search_type;
	if (! empty ( $search_status ))
		$option .= '&search_status=' . $search_status;
	if (! empty ( $search_month ))
		$option .= '&search_month=' . $search_month;
	if (! empty ( $search_year ))
		$option .= '&search_year=' . $search_year;
	
	
	print_barre_liste ( $title, $page, $_SERVEUR ['PHP_SELF'], $option, $sortfield, $sortorder, '', $num, $nbtotalofrecords );
	
	print '<form method="post" action="' . $_SERVER ['PHP_SELF'] . '" name="search_form">' . "\n";

	if (! empty ( $sortfield ))
		print '<input type="hidden" name="sortfield" value="' . $sortfield . '"/>';
	if (! empty ( $sortorder ))
		print '<input type="hidden" name="sortorder" value="' . $sortorder . '"/>';
	if (! empty ( $page ))
		print '<input type="hidden" name="page" value="' . $page . '"/>';
		
	$moreforfilter = $langs->trans ( 'Period' ) . '(' . $langs->trans ( "AgfDateDebut" ) . ')' . ': ';
	$moreforfilter .= $langs->trans ( 'Month' ) . ':<input class="flat" type="text" size="4" name="search_month" value="' . $search_month . '">';
	$moreforfilter .= $langs->trans ( 'Year' ) . ':' . $formother->selectyear ( $search_year ? $search_year : - 1, 'search_year', 1, 20, 5 );
	
	if ($moreforfilter) {
		print '<div class="liste_titre">';
		print $moreforfilter;
		print '</div>';
	}
	
	$i = 0;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre ( $langs->trans ( "Ref" ), $_SERVEUR ['PHP_SELF'], "t.ref", "", $option, '', $sortfield, $sortorder );
	print_liste_field_titre ( $langs->trans ( "LeadRefInt" ), $_SERVEUR ['PHP_SELF'], "t.ref_int", "", $option, '', $sortfield, $sortorder );
	print_liste_field_titre ( $langs->trans ( "Customer" ), $_SERVEUR ['PHP_SELF'], "so.nom", "", $option, '', $sortfield, $sortorder );
	print_liste_field_titre ( $langs->trans ( "LeadCommercial" ), $_SERVEUR ['PHP_SELF'], "usr.lastname", "", $option, '', $sortfield, $sortorder );
	print_liste_field_titre ( $langs->trans ( "LeadStep" ), $_SERVEUR ['PHP_SELF'], "leadsta.label", "", $option, '', $sortfield, $sortorder );
	print_liste_field_titre ( $langs->trans ( "LeadType" ), $_SERVEUR ['PHP_SELF'], "leadtype.label", "", $option, '', $sortfield, $sortorder );
	print_liste_field_titre ( $langs->trans ( "LeadAmountGuess" ), $_SERVEUR ['PHP_SELF'], "t.amount_prosp", "", $option, '', $sortfield, $sortorder );
	print_liste_field_titre ( $langs->trans ( "LeadRealAmount" ), $_SERVEUR ['PHP_SELF'], "", "", $option, '', $sortfield, $sortorder );
	print_liste_field_titre ( $langs->trans ( "LeadDeadLine" ), $_SERVEUR ['PHP_SELF'], "t.date_closure", "", $option, '', $sortfield, $sortorder );
	print '<td></td>';
	
	print "</tr>\n";
	
	print '<tr class="liste_titre">';


	print '<td><input type="text" class="flat" name="search_ref" value="' . $search_ref . '" size="5"></td>';
	
	print '<td><input type="text" class="flat" name="search_ref_int" value="' . $search_ref_int . '" size="5"></td>';
	
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_soc" value="' . $search_soc . '" size="20">';
	print '</td>';
	
	print '<td class="liste_titre">';
	print $formother->select_salesrepresentatives ( $search_commercial, 'search_commercial', $user );
	print '</td>';
	
	
	print '<td class="liste_titre">';
	print $formlead->select_lead_status($search_status,'search_status',1);
	print '</td>';
	
	print '<td class="liste_titre">';
	print $formlead->select_lead_type($search_type,'search_type',1);
	print '</td>';
	
	//amount guess
	print '<td id="totalamountguess"></td>';
	//amount real
	print '<td id="totalamountreal"></td>';
	//dt closure
	print '<td></td>';
	//edit button
	print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/search.png" value="' . dol_escape_htmltag ( $langs->trans ( "Search" ) ) . '" title="' . dol_escape_htmltag ( $langs->trans ( "Search" ) ) . '">';
	print '&nbsp; ';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="' . DOL_URL_ROOT . '/theme/' . $conf->theme . '/img/searchclear.png" value="' . dol_escape_htmltag ( $langs->trans ( "RemoveFilter" ) ) . '" title="' . dol_escape_htmltag ( $langs->trans ( "RemoveFilter" ) ) . '">';
	print '</td>';
	
	print "</tr>\n";
	print '</form>';
	
	$var = true;
	$totalamountguess=0;
	$totalamountreal=0;

	foreach ( $object->lines as $line ) {

			// Affichage tableau des lead
			$var = ! $var;
			print "<tr $bc[$var]>";
			
			//Ref
			print '<td><a href="card.php?id=' . $line->id . '">' . $line->ref . '</a></td>';
			
			//RefInt
			print '<td><a href="card.php?id=' . $line->id . '">' . $line->ref_int . '</a></td>';
			
			// Societe
			print '<td>';
			if (! empty ( $line->fk_soc ) && $line->fk_soc != - 1) {
				$soc = new Societe ( $db );
				$soc->fetch ( $line->fk_soc );
				print $soc->getNomURL ( 1 );
			} else {
				print '&nbsp;';
			}
			print '</td>';
			
			//Commercial
			print '<td>';
			if (!empty($line->fk_user_resp)) {
				$userstatic = new User ( $db );
				$userstatic->fetch ( $line->fk_user_resp );
				if (! empty ( $userstatic->id )) {
					print $userstatic->getFullName($langs);
				}
			}
			print '</td>';
			
			//Status
			print '<td>' .$line->status_label . '</td>';
			
			//Type
			print '<td>' .$line->type_label . '</td>';
			
			//Amount prosp
			print '<td>' . price ( $line->amount_prosp ) . ' ' . $langs->getCurrencySymbol ( $conf->currency ) . '</td>';
			$totalamountguess+=$line->amount_prosp;
			
			//Amount real
			$amount=$line->getRealAmount();
			print '<td>'. price ( $amount ) . ' ' . $langs->getCurrencySymbol ( $conf->currency ) . '</td>';
			$totalamountreal+=$amount;
				
			//Closure date
			print '<td>' . dol_print_date ( $line->date_closure, 'daytextshort' ) . '</td>';
			
			print '<td><a href="card.php?id='.$line->id.'&action=edit">'.img_picto($langs->trans('Edit'),'edit').'</td>';
			
			print "</tr>\n";
		
		
		$i ++;
	}
	
	print "</table>";
	
		
	print '<script type="text/javascript" language="javascript">' . "\n";
	print '$(document).ready(function() {
					$("#totalamountguess").append("' . price ( $totalamountguess ) . $langs->getCurrencySymbol ( $conf->currency ) . '");
					$("#totalamountreal").append("' . price ( $totalamountreal ) . $langs->getCurrencySymbol ( $conf->currency ) . '");
			});';
	print "\n" . '</script>' . "\n";

} else {
	setEventMessage ( $object->error, 'errors' );
}

llxFooter ();
$db->close ();
