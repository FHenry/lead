<?php 
/* Manage Lead
 * Copyright (C) 2014  Florian HENRY <florian.henry@open-concept.pro>
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

$res = @include ("../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../main.inc.php"); // For "custom" directory
if (! $res)
	die ( "Include of main fails" );

require_once '../../class/lead.class.php'; 
require_once '../../lib/lead.lib.php';
require_once(DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php');


// Security check
if (! $user->rights->lead->read)
	accessforbidden ();


$langs->load ( 'lead@lead' );

$id=GETPOST('id','int');
/*
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');
$userid=GETPOST('userid','int');
$propalid=GETPOST('propalid','int');
$invoiceid=GETPOST('invoiceid','int');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$typecreate=GETPOST('type','alpha');
*/

$object= new Lead($db);
$extrafields = new ExtraFields($db);

$error=0;

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
if ($id > 0)
{
	$ret=$object->fetch($id);
	if ($ret < 0) setEventMessage($object->error,'errors');
	if ($ret > 0) $ret=$object->fetch_thirdparty();
	if ($ret < 0) setEventMessage($object->error,'errors');
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('leadcard'));


/*
 * Actions
*/

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

if ($action=="add") {
	
}

else if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->businesscase->delete) {
	$result=$object->delete($user);
	if ($result<0) {
		setEventMessage($object->errors,'errors');
	}else {
		header('Location:'.dol_buildpath('/businesscase/index.php',1));
	}
}



/*
 * View
*/

llxHeader('',$langs->trans('Module103111Name'));

$form = new Form($db);
$formlead = new FormLead($db);
$companystatic=new Societe($db);



$now=dol_now();
// Add new proposal
if ($action == 'create' && $user->rights->lead->write)
{
	print_fiche_titre($langs->trans("LeadCreate"),'',dol_buildpath('/lead/img/object_lead.png',1),1);

	print '<form name="addprop" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	print '<table class="border" width="100%">';
	print '<tr>';
	print '<td class="fieldrequired"  width="20%">';
	print $langs->trans('LeadCommercial');
	print '</td>';
	print '<td>';
	print $form->select_users($userid,'userid',0);
	print '</td>';
	print '</tr>';
	print '<tr>';
	print '<td class="fieldrequired">';
	print $langs->trans('Customer');
	print '</td>';
	print '<td>';
	$events=array();
	print $form->select_company($socid,'socid','client<>0',1,1,0,$events);
	print '</td>';
	print '</tr>';
	
	print '<table>';

	print '<center>';
	print '<input type="submit" class="button" value="'.$langs->trans("BuCaNewBusinessCase").'">';
	print '&nbsp;<input type="button" class="button" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
	print '</center>';


	print '</form>';

}else
{
	/*
	 * Show object in view mode
	*/
	$head = businesscase_prepare_head($object);
	dol_fiche_head($head, 'card', $langs->trans('Module103010Name'), 0, dol_buildpath('/businesscase/img/object_businesscase.png',1),1);

	//Confirm form
	$formconfirm='';
	if ($action == 'delete')
	{
		$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('BuCaDelete'), $langs->trans('BuCaConfirmDelete'), 'confirm_delete', '', 0, 1);
	}

	if (empty($formconfirm))
	{
		$parameters=array();
		$formconfirm=$hookmanager->executeHooks('formConfirm',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	}
	print $formconfirm;

	$linkback = '<a href="'.dol_buildpath('/businesscase/business/case/list.php',1).'">'.$langs->trans("BackToList").'</a>';

	print '<table class="border" width="100%">';
	print '<tr>';
	print '<td width="20%">';
	print $langs->trans('Ref');
	print '</td>';
	print '<td>';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '');
	print '</td>';
	print '</tr>';
	
	print '<tr>';
	print '<td width="20%">';
	print $langs->trans('BuCaCommercial');
	print '</td>';
	print '<td>';
	$userstatic=new User($db);
	$result = $userstatic->fetch($object->fk_user);
	if ($result<0) {
		setEventMessage($userstatic->error,'errors');
	}
	print $userstatic->getFullName($langs);
	print '</td>';
	print '</tr>';

	// Ref Client
	print '<tr><td>'.$form->editfieldkey("RefCustomer",'ref_client',$object->ref_client,$object,$user->rights->businesscase->write,'string').'</td><td>';
	print $form->editfieldval("RefCustomer",'ref_client',$object->ref_client,$object,$user->rights->businesscase->write ,'string');
	print '</td></tr>';

	print '<tr>';
	print '<td>';
	print $langs->trans('Company');
	print '</td>';
	print '<td>';
	print $object->thirdparty->getNomUrl();
	print '</td>';
	print '</tr>';


	// Other attributes
	$res=$object->fetch_optionals($object->id,$extralabels);
	$parameters=array('colspan' => ' colspan="2"');
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action); // Note that $action and $object may have been modified by hook
	if (empty($reshook) && ! empty($extrafields->attribute_label))
	{

		if ($action == 'edit_extras')
		{
			print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" method="post" name="formsoc">';
			print '<input type="hidden" name="action" value="update_extras">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
		}

		foreach($extrafields->attribute_label as $key=>$label)
		{
			if ($action == 'edit_extras') {
				$value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);
			} else {
				$value=$object->array_options["options_".$key];
			}
			if ($extrafields->attribute_type[$key] == 'separate')
			{
				print $extrafields->showSeparator($key);
			}
			else
			{
				print '<tr><td';
				if (! empty($extrafields->attribute_required[$key])) print ' class="fieldrequired"';
				print '>'.$label.'</td><td>';
				// Convert date into timestamp format
				if (in_array($extrafields->attribute_type[$key],array('date','datetime')))
				{
					$value = isset($_POST["options_".$key])?dol_mktime($_POST["options_".$key."hour"], $_POST["options_".$key."min"], 0, $_POST["options_".$key."month"], $_POST["options_".$key."day"], $_POST["options_".$key."year"]):$db->jdate($object->array_options['options_'.$key]);
				}

				if ($action == 'edit_extras' && $user->rights->businesscase->write)
				{
					print $extrafields->showInputField($key,$value);
				}
				else
				{
					print $extrafields->showOutputField($key,$value);
				}
				print '</td></tr>'."\n";
			}
		}

		if(count($extrafields->attribute_label) > 0) {

			if ($action == 'edit_extras' && $user->rights->businesscase->write)
			{
				print '<tr><td></td><td>';
				print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
				print '</form>';
				print '</td></tr>';

			}
			else {
				if ($object->statut == 0 && $user->rights->businesscase->write)
				{
					print '<tr><td></td><td><a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit_extras">'.img_picto('','edit').' '.$langs->trans('Modify').'</a></td></tr>';
				}
			}
		}
	}

	print '</table>';
	print "</div>\n";

	/*
	 * Barre d'actions
	*
	*/
	print '<div class="tabsAction">';

	// Delete
	if ($user->rights->ficheinter->creer)
	{
		print '<div class="inline-block divButAction"><a class="butAction" href="ficheinter.php?id='.$object->id.'&action=create">'.$langs->trans("BuCaCreateInter")."</a></div>\n";
	}
	else
	{
		print '<div class="inline-block divButAction"><font class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("BuCaCreateInter")."</font></div>";
	}

	// Delete
	if ($user->rights->businesscase->delete)
	{
		print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete">'.$langs->trans("Delete")."</a></div>\n";
	}
	else
	{
		print '<div class="inline-block divButAction"><font class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("Delete")."</font></div>";
	}
	print '</div>';
	
	//Business case line
	print '<div class="fiche">';
	$linesarray=$object->fetch_lines();

	if ($linesarray<0) {
		setEventMessage($object->error,'errors');
	}
	
	print '<table class="noborder" width="100%">';
	print '<tr>';
	print '<th width="10px"></th>';
	print '<th align="left">'.$langs->trans('Label').'</th>';
	print '<th align="left">'.$langs->trans('Element').'</th>';
	print '</tr>';
	if (is_array($linesarray) && count($linesarray)>0) {
		foreach($linesarray as $line) {
			print '<tr>';
			print '<td>';
			for ($i=0;$i<$line->level_line;$i++) {
				print '&nbsp;&nbsp;&nbsp;&nbsp;';
			}
			print '</td>';
			print '<td>'.$line->label.'</td>';
			print '<td>';
			if (!empty($line->fk_element)) {
				print $object->getElementUrl($line->fk_element,$line->elementtype,1);
			}
			print '</td>';
			print '</tr>';
		}
	}
	print '</table>';	
	print '</div>';

}



llxFooter ();
$db->close ();
?>