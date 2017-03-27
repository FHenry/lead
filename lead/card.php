<?php
/* Lead
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
$res = @include '../../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once '../class/lead.class.php';
require_once '../class/html.formlead.class.php';
require_once '../lib/lead.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
if (! empty($conf->propal->enabled))
	require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
if (! empty($conf->facture->enabled))
	require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
if (! empty($conf->contrat->enabled))
	require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
if (! empty($conf->commande->enabled))
	require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

if (! empty($conf->agenda->enabled))
	dol_include_once('/comm/action/class/actioncomm.class.php');

if (! empty($conf->global->LEAD_GRP_USER_AFFECT))
	require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

	// Security check
if (! $user->rights->lead->read)
	accessforbidden();

$langs->load('lead@lead');
if (! empty($conf->propal->enabled))
	$langs->load('propal');
if (! empty($conf->facture->enabled))
	$langs->load('bills');
if (! empty($conf->contrat->enabled))
	$langs->load('contracts');
if (! empty($conf->commande->enabled))
	$langs->load('order');
$action = GETPOST('action', 'alpha');

$id = GETPOST('id', 'int');

$confirm = GETPOST('confirm', 'alpha');

$ref_int = GETPOST('ref_int', 'alpha');
$socid = GETPOST('socid', 'int');
if ($socid == - 1)
	$socid = 0;
$userid = GETPOST('userid', 'int');
$leadstatus = GETPOST('leadstatus', 'int');
$leadtype = GETPOST('leadtype', 'int');
$amount_guess = GETPOST('amount_guess');
$description = GETPOST('description');
$deadline = dol_mktime(0, 0, 0, GETPOST('deadlinemonth'), GETPOST('deadlineday'), GETPOST('deadlineyear'));

$date_relance = dol_mktime(0, 0, 0, GETPOST('date_relancemonth'), GETPOST('date_relanceday'), GETPOST('date_relanceyear'));

$object = new Lead($db);
$extrafields = new ExtraFields($db);

$error = 0;

// Limuit uer list to groups
$includeuserlist = array();
if (! empty($conf->global->LEAD_GRP_USER_AFFECT)) {
	$usergroup = new UserGroup($db);
	$result = $usergroup->fetch($conf->global->LEAD_GRP_USER_AFFECT);
	if ($result < 0)
		setEventMessages(null, $usergroup->errors, 'errors');

	$includeuserlisttmp = $usergroup->listUsersForGroup();
	if (is_array($includeuserlisttmp) && count($includeuserlisttmp) > 0) {
		foreach ( $includeuserlisttmp as $usertmp ) {
			$includeuserlist[] = $usertmp->id;
		}
	}
}

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
if ($id > 0) {
	$ret = $object->fetch($id);
	if ($ret < 0)
		setEventMessages(null, $object->errors, 'errors');
	if ($ret > 0)
		$ret = $object->fetch_thirdparty();
	if ($ret < 0)
		setEventMessages(null, $object->errors, 'errors');
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array(
		'leadcard'
));

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

if ($action == "add") {

	$error = 0;

	if (!empty($socid)) {
		$thirdparty=new Societe($db);
		$thirdparty->fetch($socid);
		$object->ref = $object->getNextNumRef($userid,$thirdparty);
	} else {
		$object->ref = $object->getNextNumRef($userid);
	}

	$object->ref_int = $ref_int;
	$object->fk_c_status = $leadstatus;
	$object->fk_c_type = $leadtype;
	$object->amount_prosp = price2num($amount_guess);
	$object->date_closure = $deadline;
	$object->fk_soc = $socid;
	$object->fk_user_resp = $userid;
	$object->fk_user_author = $userid;
	$object->description = $description;

	$extrafields->setOptionalsFromPost($extralabels, $object);

	$result = $object->create($user);
	if ($result < 0) {
		$action = 'create';
		setEventMessages(null, $object->errors, 'errors');
		$error ++;
	}

	$propalid = GETPOST('propalid', 'int');
	if (! empty($propalid)) {
		$tablename = 'propal';
		$elementselectid = $propalid;
		$result = $object->add_object_linked($tablename, $elementselectid);
		if ($result < 0) {
			setEventMessages(null, $object->errors, 'errors');
			$error ++;
		}
	}

	if ($date_relance) {

		$object->addRelance($date_relance);
	}

	if (empty($error)) {
		header('Location:' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
	}
} elseif ($action == "update") {
	$object->ref_int = $ref_int;
	$object->fk_c_status = $leadstatus;
	$object->fk_c_type = $leadtype;
	$object->amount_prosp = $amount_guess;
	$object->date_closure = $deadline;
	$object->fk_soc = $socid;
	$object->fk_user_resp = $userid;
	$object->description = $description;

	$extrafields->setOptionalsFromPost($extralabels, $object);

	$result = $object->update($user);
	if ($result < 0) {
		$action = 'edit';
		setEventMessages(null, $object->errors, 'errors');
	} else {
		header('Location:' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
	}
} elseif ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->lead->delete) {
	$result = $object->delete($user);
	if ($result < 0) {
		setEventMessages(null, $object->errors, 'errors');
	} else {
		header('Location:' . dol_buildpath('/lead/lead/list.php', 1));
	}
} elseif ($action == "addelement") {
	$tablename = GETPOST("tablename");
	$elementselectid = GETPOST("elementselect");
	$result = $object->add_object_linked($tablename, $elementselectid);
	if ($result < 0) {
		setEventMessages(null, $object->errors, 'errors');
	}
} elseif ($action == "unlink") {

	$sourceid = GETPOST('sourceid');
	$sourcetype = GETPOST('sourcetype');

	$result = $object->deleteObjectLinked($sourceid, $sourcetype);
	if ($result < 0) {
		setEventMessages(null, $object->errors, 'errors');
	}
} elseif ($action == "confirm_clone" && $confirm == 'yes') {

	$object_clone = new Lead($db);
	$object_clone->ref_int = GETPOST('ref_interne');
	$result = $object_clone->createFromClone($object->id);
	if ($result < 0) {
		setEventMessages(null, $object_clone->errors, 'errors');
	} else {
		header('Location:' . $_SERVER["PHP_SELF"] . '?id=' . $result);
	}
} else if ($action === 'confirm_create_propale' && $confirm == 'yes') {
	$propal = new Propal($db);
	$propal->socid = $object->fk_soc;
	$propal->fetch_thirdparty();
	$propal->duree_validite = 15;
	$propal->cond_reglement_id = 1;
	$propal->mode_reglement_id = 0;
	$propal->origin_id = 0;
	$propal->availability_id = 0;
	$propal->datep = time();
	$propal->statut = Propal::STATUS_DRAFT;
	$propal->modelpdf = 'azur';
	$propal->create($user);
	$result = $object->add_object_linked('propal', $propal->id);
	if ($result < 0) {
		setEventMessages(null, $object->errors, 'errors');
	} else {
		header('Location:' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
	}
} else if ($action === 'confirm_create_contract' && $confirm == 'yes') {
	
	$contract = new Contrat($db);
	$contract->socid = $object->fk_soc;
	$contract->fetch_thirdparty();
	$contract->commercial_signature_id= $user->id;
	$contract->commercial_suivi_id= $user->id;
	$contract->date_contrat= dol_now();
	$result=$contract->create($user);
	if ($result < 0) {
		setEventMessages($contract->error, $contract->errors, 'errors');
	} else {
		$result = $object->add_object_linked('contrat', $contract->id);
		if ($result < 0) {
			setEventMessages(null, $object->errors, 'errors');
		} else {
			header('Location:' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
		}
	}
	
} else if ($action === 'confirm_clone_propale' && $confirm == 'yes') {
	$propale_id = GETPOST('propale_id');
	$propale = new Propal($db);
	$propale->fetch($propale_id);
	$new_propale_id = $propale->createFromClone($object->fk_soc);
	$result = $object->add_object_linked('propal', $new_propale_id);
	if ($result < 0) {
		setEventMessages(null, $object->errors, 'errors');
	} else {
		header('Location:' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
	}
} else if ($action == "confirm_lost" && $confirm == 'yes') {
	// Status 7=LOST hard coded, loaded by default in data.sql dictionnary (but check is done in this card that call this method)
	$object->fk_c_status = 7;
	$result = $object->update($user);
	if ($result < 0) {
		setEventMessages(null, $object->errors, 'errors');
	} else {
		header('Location:' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
	}
} else if ($action === 'confirm_relance' && GETPOST('confirm') === 'yes') {

	if ($date_relance) {
		$object->addRelance($date_relance);
		setEventMessage($langs->trans('relanceAdded'));
	}
} else if (strpos($action, 'ext_head') !== false && !empty($conf->global->LEAD_PERSONNAL_TEMPLATE) && file_exists(dol_buildpath($conf->global->LEAD_PERSONNAL_TEMPLATE))) {
	$res = include dol_buildpath($conf->global->LEAD_PERSONNAL_TEMPLATE);
}

/*
 * View
 */

llxHeader('', $langs->trans('Module103111Name'));

$form = new Form($db);
$formlead = new FormLead($db);

$now = dol_now();

if ($action === 'create_relance') {
	print $form->formconfirm("card.php?id=" . $object->id, $langs->trans("CreateRelance"), $langs->trans("ConfirmCreateRelance"), "confirm_relance", array(
			array(
					'type' => 'date',
					'name' => 'date_relance'
			)
	), '', 1);
}

if ($action === 'clone_propale') {
	$TRes = array();
	$form = new Form($db);
	$sql = 'SELECT rowid, ref, ref_client from ' . MAIN_DB_PREFIX . 'propal ORDER BY ref, ref_client';
	$result = $db->query($sql);
	if($result){
		while($row = $db->fetch_object($result)) {
			$TRes[$row->rowid] = $row->ref.' '.$row->ref_client;
		}
	}
	$test = $form->selectarray('propale_id',$TRes, 'propale_id', 0, 0, 0, 'style="min-width:200px;"', 0, 0, 0, '', '', 1);
	print $form->formconfirm("card.php?id=" . $object->id, $langs->trans("LeadClonePropale"), $langs->trans("LeadChoosePropale"), "confirm_clone_propale", array(
			array(
					'type' => 'other',
					'name' => 'propale_id',
					'value' => $test
			)
	), '', 1);
}
if ($action === 'create_propale') {
	print $form->formconfirm("card.php?id=" . $object->id, $langs->trans("LeadCreatePropale"), $langs->trans("LeadConfirmCreatePropale"), "confirm_create_propale", array(), '', 1);
} 
if ($action === 'create_contract') {
	print $form->formconfirm("card.php?id=" . $object->id, $langs->trans("LeadCreateContract"), $langs->trans("LeadConfirmCreateContract"), "confirm_create_contract", array(), '', 1);
} 
// Add new proposal
if ($action == 'create' && $user->rights->lead->write) {

	if (!empty($conf->global->LEAD_PERSONNAL_TEMPLATE) && file_exists(dol_buildpath($conf->global->LEAD_PERSONNAL_TEMPLATE))) {
		$res = include dol_buildpath($conf->global->LEAD_PERSONNAL_TEMPLATE);
	} else {

		dol_fiche_head();

		print_fiche_titre($langs->trans("LeadCreate"), '', dol_buildpath('/lead/img/object_lead.png', 1), 1);

		print '<form name="addlead" action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';

		print '<input type="hidden" name="propalid" value="' . GETPOST('propalid', 'int') . '">';

		print '<input type="hidden" name="action" value="add">';

		print '<table class="border" width="100%">';
		print '<tr>';
		print '<td class="fieldrequired"  width="20%">';
		print $langs->trans('LeadCommercial');
		print '</td>';
		print '<td>';
		print $form->select_dolusers(empty($userid) ? $user->id : $userid, 'userid', 0, array(), 0, $includeuserlist, '', 0, 0, 0, '', 0, '', '', 1);
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td class="fieldrequired"  width="20%">';
		print $langs->trans('LeadRefInt');
		print '</td>';
		print '<td>';
		print '<input type="text" name="ref_int" size="10" value="' . $ref_int . '"/>';
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td class="fieldrequired"  width="20%">';
		print $langs->trans('LeadStatus');
		print '</td>';
		print '<td>';
		print $formlead->select_lead_status($leadstatus, 'leadstatus', 0);
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td class="fieldrequired"  width="20%">';
		print $langs->trans('LeadType');
		print '</td>';
		print '<td>';
		print $formlead->select_lead_type($leadtype, 'leadtype', 0);
		print '</td>';
		print '</tr>';

		print '<tr>';
		if (! empty($conf->global->LEAD_FORCE_USE_THIRDPARTY)) {
			print '<td class="fieldrequired">';
		} else {
			print '<td>';
		}
		print $langs->trans('Customer');
		print '</td>';
		print '<td>';
		$events = array();
		print $form->select_thirdparty_list($socid, 'socid', 'client<>0', 1, 1, 0, $events);
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td class="fieldrequired"  width="20%">';
		print $langs->trans('LeadAmountGuess');
		print '</td>';
		print '<td>';
		print '<input type="text" name="amount_guess" size="5" value="' . price2num($amount_guess) . '"/>';
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td class="fieldrequired"  width="20%">';
		print $langs->trans('LeadDeadLine');
		print '</td>';
		print '<td>';

		if (strlen($deadline) == 0) {
			$deadline = dol_time_plus_duree(dol_now(), $conf->global->LEAD_NB_DAY_COSURE_AUTO, 'd');
		}

		print $form->select_date($deadline, 'deadline', 0, 0, 0, "addlead", 1, 1, 0, 0);
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td width="20%">';
		print $langs->trans('LeadDateRelance');
		print '</td>';
		print '<td>';

		print $form->select_date(null, 'date_relance', 0, 0, 0, "addlead", 1, 1, 0, 0);
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td>';
		print $langs->trans('LeadDescription');
		print '</td>';
		print '<td>';
		$doleditor = new DolEditor('description', $object->description, '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->FCKEDITOR_ENABLE || $conf->global->FCKEDITOR_ENABLE_SOCIETE, 4, 90);
		$doleditor->Create();
		print '</td>';
		print '</tr>';

		// Other attributes
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

		if (empty($reshook) && ! empty($extrafields->attribute_label)) {
			print $object->showOptionals($extrafields, 'edit');
		}

		print '</table>';

		print '<div class="center">';
	}
	print '<input type="submit" class="button" value="' . $langs->trans("Create") . '">';
	print '&nbsp;<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';

	dol_fiche_end();
}

elseif ($action == 'edit') {

	$head = lead_prepare_head($object);
	dol_fiche_head($head, 'card', $langs->trans('Module103111Name'), 0, dol_buildpath('/lead/img/object_lead.png', 1), 1);

	if (!empty($conf->global->LEAD_PERSONNAL_TEMPLATE) && file_exists(dol_buildpath($conf->global->LEAD_PERSONNAL_TEMPLATE))) {
		$res = include dol_buildpath($conf->global->LEAD_PERSONNAL_TEMPLATE);
	} else {

		print '<form name="editlead" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" method="POST">';
		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
		print '<input type="hidden" name="action" value="update">';

		print '<table class="border" width="100%">';
		print '<tr>';
		print '<td class="fieldrequired"  width="20%">';
		print $langs->trans('LeadCommercial');
		print '</td>';
		print '<td>';
		print $form->select_dolusers($object->fk_user_resp, 'userid', 0, array(), 0, $includeuserlist, '', 0, 0, 0, '', 0, '', '', 1);
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td class="fieldrequired"  width="20%">';
		print $langs->trans('LeadRefInt');
		print '</td>';
		print '<td>';
		print '<input type="text" name="ref_int" size="10" value="' . $object->ref_int . '"/>';
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td class="fieldrequired"  width="20%">';
		print $langs->trans('LeadStatus');
		print '</td>';
		print '<td>';
		print $formlead->select_lead_status($object->fk_c_status, 'leadstatus', 0);
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td class="fieldrequired"  width="20%">';
		print $langs->trans('LeadType');
		print '</td>';
		print '<td>';
		print $formlead->select_lead_type($object->fk_c_type, 'leadtype', 0);
		print '</td>';
		print '</tr>';

		print '<tr>';
		if (! empty($conf->global->LEAD_FORCE_USE_THIRDPARTY)) {
			print '<td class="fieldrequired">';
		} else {
			print '<td>';
		}
		print $langs->trans('Customer');
		print '</td>';
		print '<td>';
		$events = array();
		print $form->select_thirdparty_list($object->thirdparty->id, 'socid', 'client<>0', 1, 1, 0, $events);
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td class="fieldrequired"  width="20%">';
		print $langs->trans('LeadAmountGuess');
		print '</td>';
		print '<td>';
		print '<input type="text" name="amount_guess" size="5" value="' . price2num($object->amount_prosp) . '"/>';
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td class="fieldrequired"  width="20%">';
		print $langs->trans('LeadDeadLine');
		print '</td>';
		print '<td>';
		print $form->select_date($object->date_closure, 'deadline', 0, 0, 0, "addlead", 1, 1, 0, 0);
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td>';
		print $langs->trans('LeadDescription');
		print '</td>';
		print '<td>';
		$doleditor = new DolEditor('description', $object->description, '', 160, 'dolibarr_notes', 'In', true, false, $conf->global->FCKEDITOR_ENABLE || $conf->global->FCKEDITOR_ENABLE_SOCIETE, 4, 90);
		$doleditor->Create();
		print '</td>';
		print '</tr>';

		// Other attributes
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

		if (empty($reshook) && ! empty($extrafields->attribute_label)) {
			print $object->showOptionals($extrafields, 'edit');
		}

		print '</table>';

		print '<div class="center">';
	}
	print '<input type="submit" class="button" value="' . $langs->trans("Save") . '">';
	print '&nbsp;<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
	print '</div>';

	print '</form>';
} else {
	/*
	 * Show object in view mode
	 */
	$head = lead_prepare_head($object);
	dol_fiche_head($head, 'card', $langs->trans('Module103111Name'), 0, dol_buildpath('/lead/img/object_lead.png', 1), 1);

	// Confirm form
	$formconfirm = '';

	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('LeadDelete'), $langs->trans('LeadConfirmDelete'), 'confirm_delete', '', 0, 1);
	}

	if ($action == 'close') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('LeadLost'), $langs->trans('LeadConfirmLost'), 'confirm_lost', '', 0, 1);
	}

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array(
				// 'text' => $langs->trans("ConfirmClone"),
				// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' =>
				// 1),
				// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value'
				// => 1),
				array(
						'type' => 'text',
						'name' => 'ref_interne',
						'label' => $langs->trans("LeadRefInt"),
						'value' => $langs->trans('CopyOf') . ' ' . $object->ref_int
				)
		);
		// Paiement incomplet. On demande si motif = escompte ou autre
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('Clone'), $langs->trans('ConfirmCloneLead', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	$printformconfirm = false;
	if (empty($formconfirm)) {
		$parameters = array();
		$formconfirm = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (! empty($formconfirm))
			$printformconfirm = true;
	} else {
		$printformconfirm=true;
	}
	$linkback = '<a href="' . dol_buildpath('/lead/lead/list.php', 1) . '">' . $langs->trans("BackToList") . '</a>';

	if (!empty($conf->global->LEAD_PERSONNAL_TEMPLATE) && file_exists(dol_buildpath($conf->global->LEAD_PERSONNAL_TEMPLATE))) {
		$res = include dol_buildpath($conf->global->LEAD_PERSONNAL_TEMPLATE);
	} else {

		if ($printformconfirm) {
			print $formconfirm;
		}

		print '<table class="border" width="100%">';
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

		print '<tr>';
		print '<td width="20%">';
		print $langs->trans('LeadCommercial');
		print '</td>';
		print '<td>';
		$userstatic = new User($db);
		$result = $userstatic->fetch($object->fk_user_resp);
		if ($result < 0) {
			setEventMessages($userstatic->errors, 'errors');
		}
		print $userstatic->getFullName($langs);
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td>';
		print $langs->trans('Company');
		print '</td>';
		print '<td>';
		print $object->getNomUrlCompany(1);
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td>';
		print $langs->trans('LeadStatus');
		print '</td>';
		print '<td>';
		print $object->status_label;
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td>';
		print $langs->trans('LeadType');
		print '</td>';
		print '<td>';
		print $object->type_label;
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td>';
		print $langs->trans('LeadAmountGuess');
		print '</td>';
		print '<td>';
		print price($object->amount_prosp, 'HTML') . $langs->getCurrencySymbol($conf->currency);
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td>';
		print $langs->trans('LeadDeadLine');
		print '</td>';
		print '<td>';
		print dol_print_date($object->date_closure, 'daytext');
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td>';
		print $langs->trans('LeadRealAmount');
		print '</td>';
		print '<td>';
		print $object->getRealAmount() . $langs->getCurrencySymbol($conf->currency);
		print '</td>';
		print '</tr>';

		print '<tr>';
		print '<td>';
		print $langs->trans('LeadDescription');
		print '</td>';
		print '<td>';
		print $object->description;
		print '</td>';
		print '</tr>';

		// Other attributes
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

		if (empty($reshook) && ! empty($extrafields->attribute_label)) {
			print $object->showOptionals($extrafields);
		}

		print '</table>';
		print "</div>\n";

		/*
		 * Barre d'actions
		 */
		print '<div class="tabsAction">';
	}
	// Delete
	if ($user->rights->lead->write) {
		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=edit">' . $langs->trans("Edit") . "</a></div>\n";
		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=clone">' . $langs->trans("Clone") . "</a></div>\n";
		if($user->rights->propale->creer) {
			print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=create_propale">' . $langs->trans("LeadCreatePropale") . "</a></div>\n";
			print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=clone_propale">' . $langs->trans("LeadClonePropale") . "</a></div>\n";
		}
		if($user->rights->contrat->creer) {
			print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=create_contract">' . $langs->trans("LeadCreateContract") . "</a></div>\n";
		}
		print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=create_relance">' . $langs->trans("CreateRelance") . "</a></div>\n";
		if ($object->status[7] == $langs->trans('LeadStatus_LOST') && $object->fk_c_status != 7) {
			print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=close">' . $langs->trans("LeadLost") . "</a></div>\n";
		}
	} else {
		print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("anoughPermissions")) . '">' . $langs->trans("Edit") . "</a></div>";
		print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("anoughPermissions")) . '">' . $langs->trans("Clone") . "</a></div>";
		// print '<div class="inline-block divButAction"><font class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans("LeadLost") . "</font></div>";
	}

	// Delete
	if ($user->rights->lead->delete) {
		print '<div class="inline-block divButAction"><a class="butActionDelete" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=delete">' . $langs->trans("Delete") . "</a></div>\n";
	} else {
		print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("anoughPermissions")) . '">' . $langs->trans("Delete") . "</a></div>";
	}
	print '</div>';

	print_fiche_titre($langs->trans('LeadDocuments'), '', 'lead@lead');

	foreach ( $object->listofreferent as $key => $value ) {
		$title = $value['title'];
		$classname = $value['class'];
		$tablename = $value['table'];
		$qualified = $value['test'];

		if ($qualified) {
			print '<br>';
			print_fiche_titre($langs->trans($title));

			$selectList = $formlead->select_element($tablename, $object);
			if ($selectList) {
				print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '" method="post">';
				print '<input type="hidden" name="tablename" value="' . $tablename . '">';
				print '<input type="hidden" name="action" value="addelement">';
				print '<table><tr><td>' . $langs->trans("SelectElement") . '</td>';
				print '<td>' . $selectList . '</td>';
				print '<td><input type="submit" class="button" value="' . $langs->trans("LeadAddElement") . '"></td>';
				print '</tr></table>';
				print '</form>';
			}
			print '<table class="noborder" width="100%">';

			print '<tr class="liste_titre">';
			print '<td></td>';
			print '<td width="100">' . $langs->trans("Ref") . '</td>';
			print '<td width="100" align="center">' . $langs->trans("Date") . '</td>';
			print '<td>' . $langs->trans("ThirdParty") . '</td>';
			if (empty($value['disableamount']))
				print '<td align="right" width="120">' . $langs->trans("AmountHT") . '</td>';
			if (empty($value['disableamount']))
				print '<td align="right" width="120">' . $langs->trans("AmountTTC") . '</td>';
			print '<td align="right" width="200">' . $langs->trans("Status") . '</td>';
			print '</tr>';

			$ret = $object->fetchDocumentLink($object->id, $tablename);
			if ($ret < 0) {
				setEventMessages(null, $object->errors, 'errors');
			}

			$elementarray = array();
			$elementarray = $object->doclines;
			if (count($elementarray) > 0 && is_array($elementarray)) {
				$var = true;
				$total_ht = 0;
				$total_ttc = 0;
				$num = count($elementarray);
				foreach ( $elementarray as $line ) {
					/**
					 *
					 * @var CommonObject $element
					 */
					$element = new $classname($db);
					$element->fetch($line->fk_source);
					$element->fetch_thirdparty();

					$var = ! $var;
					print "<tr " . $bc[$var] . ">";

					print '<td width="1%">';
					print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&action=unlink&sourceid=' . $element->id . '&sourcetype=' . $tablename . '">' . img_picto($langs->trans('LeadUnlinkDoc'), 'unlink.png@lead') . '</a>';
					print "</td>\n";

					// Ref
					print '<td align="left">';
					print $element->getNomUrl(1);
					print "</td>\n";

					// Date
					$date = $element->date;
					if (empty($date)) {
						$date = $element->datep;
					}
					if (empty($date)) {
						$date = $element->date_contrat;
					}
					if (empty($date)) {
						$date = $element->datev; // Fiche inter
					}
					print '<td align="center">' . dol_print_date($date, 'day') . '</td>';

					// Third party
					print '<td align="left">';
					if (is_object($element->thirdparty))
						print $element->thirdparty->getNomUrl(1, '', 48);
					print '</td>';

					// Amount
					if (empty($value['disableamount'])) {
						print '<td align="right">' . (isset($element->total_ht) ? price($element->total_ht) : '&nbsp;') . '</td>';
					}

					// Amount
					if (empty($value['disableamount'])) {
						print '<td align="right">' . (isset($element->total_ttc) ? price($element->total_ttc) : '&nbsp;') . '</td>';
					}

					// Status
					print '<td align="right">' . $element->getLibStatut(5) . '</td>';

					print '</tr>';

					$total_ht = $total_ht + $element->total_ht;
					$total_ttc = $total_ttc + $element->total_ttc;
				}

				print '<tr class="liste_total">';
				print '<td>&nbsp;</td>';
				print '<td colspan="3">' . $langs->trans("Number") . ': ' . $num . '</td>';
				if (empty($value['disableamount']))
					print '<td align="right" width="100">' . $langs->trans("TotalHT") . ' : ' . price($total_ht) . '</td>';
				if (empty($value['disableamount']))
					print '<td align="right" width="100">' . $langs->trans("TotalTTC") . ' : ' . price($total_ttc) . '</td>';
				print '<td>&nbsp;</td>';
				print '</tr>';
			}
			print "</table>";
		}
	}
}

dol_fiche_end();
llxFooter();
$db->close();
