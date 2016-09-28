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
 * \file admin/lead.php
 * \ingroup lead
 * \brief This file is an example module setup page
 * Put some comments here
 */
// Dolibarr environment
$res = @include '../../main.inc.php'; // From htdocs directory
if (! $res) {
	$res = @include '../../../main.inc.php'; // From "custom" directory
}

// Libraries
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once '../lib/lead.lib.php';
require_once '../class/lead.class.php';

// Translations
$langs->load("lead@lead");
$langs->load("admin");

// Access control
if (! $user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scandir', 'alpha');

/*
 * Actions
 */

if ($action == 'updateMask') {
	$maskconstlead = GETPOST('maskconstlead', 'alpha');
	$masklead = GETPOST('masklead', 'alpha');
	if ($maskconstlead)
		$res = dolibarr_set_const($db, $maskconstlead, $masklead, 'chaine', 0, '', $conf->entity);

	if (! $res > 0)
		$error ++;

	if (! $error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} else if ($action == 'setmod') {
	dolibarr_set_const($db, "LEAD_ADDON", $value, 'chaine', 0, '', $conf->entity);
} else if ($action == 'setvar') {

	$nb_day = GETPOST('LEAD_NB_DAY_COSURE_AUTO', 'int');
	if (! empty($nb_day)) {
		$res = dolibarr_set_const($db, 'LEAD_NB_DAY_COSURE_AUTO', $nb_day, 'chaine', 0, '', $conf->entity);
	}
	if (! $res > 0) {
		$error ++;
	}

	$user_goup = GETPOST('LEAD_GRP_USER_AFFECT', 'int');
	if ($user_goup == - 1)
		$user_goup = '';

	$res = dolibarr_set_const($db, 'LEAD_GRP_USER_AFFECT', $user_goup, 'chaine', 0, '', $conf->entity);
	if (! $res > 0) {
		$error ++;
	}

	$template = GETPOST('LEAD_PERSONNAL_TEMPLATE', 'alpha');
	if (! file_exists(dol_buildpath($template))) {
		$template = '';
	}

	$res = dolibarr_set_const($db, 'LEAD_PERSONNAL_TEMPLATE', $template, 'chaine', 0, '', $conf->entity);
	if (! $res > 0) {
		$error ++;
	}

	$force_use_thirdparty = GETPOST('LEAD_FORCE_USE_THIRDPARTY', 'int');
	$res = dolibarr_set_const($db, 'LEAD_FORCE_USE_THIRDPARTY', $force_use_thirdparty, 'yesno', 0, '', $conf->entity);
	if (! $res > 0) {
		$error ++;
	}

	$allow_multiple = GETPOST('LEAD_ALLOW_MULIPLE_LEAD_ON_CONTRACT', 'int');
	$res = dolibarr_set_const($db, 'LEAD_ALLOW_MULIPLE_LEAD_ON_CONTRACT', $allow_multiple, 'yesno', 0, '', $conf->entity);
	if (! $res > 0) {
		$error ++;
	}

	$LEAD_EVENT_RELANCE_TYPE = GETPOST('LEAD_EVENT_RELANCE_TYPE');
	$res = dolibarr_set_const($db, 'LEAD_EVENT_RELANCE_TYPE', $LEAD_EVENT_RELANCE_TYPE, 'chaine', 0, '', $conf->entity);
	if (! $res > 0) {
		$error ++;
	}

	$errordb = 0;
	$errors = array();
	if ($force_use_thirdparty == 1) {
		$sql = 'ALTER TABLE llx_lead ADD INDEX idx_llx_lead_fk_soc (fk_soc)';
		$resql = $db->query($sql);
		if (! $resql) {
			$errordb ++;
			$errors[] = $db->lasterror;
		}

		$sql = 'ALTER TABLE llx_lead ADD CONSTRAINT llx_lead_ibfk_3 FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid)';
		$resql = $db->query($sql);
		if (! $resql) {
			$errordb ++;
			$errors[] = $db->lasterror;
		}
	} else {
		$sql = 'ALTER TABLE llx_lead DROP FOREIGN KEY llx_lead_ibfk_3';
		$resql = $db->query($sql);
		if (! $resql && ($db->errno() != 'DB_ERROR_NOSUCHFIELD' && $db->errno() != 'DB_ERROR_NO_INDEX_TO_DROP')) {
			$errordb ++;
			$errors[] = $db->lasterror;
		}
		$sql = 'ALTER TABLE llx_lead DROP INDEX idx_llx_lead_fk_soc';
		$resql = $db->query($sql);
		if (! $resql && ($db->errno() != 'DB_ERROR_NOSUCHFIELD' && $db->errno() != 'DB_ERROR_NO_INDEX_TO_DROP')) {
			$errordb ++;
			$errors[] = $db->lasterror;
		}
	}
	if (! empty($errordb)) {
		setEventMessages(null, $errors, 'warnings');
	}

	if (! $error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

/*
 * View
 */
$page_name = "LeadSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = leadAdminPrepareHead();
dol_fiche_head($head, 'settings', $langs->trans("Module103111Name"), 0, "lead@lead");

/*
 * Module numerotation
 */
print_fiche_titre($langs->trans("LeadSetupPage"));

$dirmodels = array_merge(array(
		'/'
), ( array ) $conf->modules_parts['models']);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . "</td>\n";
print '<td>' . $langs->trans("Description") . "</td>\n";
print '<td class="nowrap">' . $langs->trans("Example") . "</td>\n";
print '<td align="center" width="60">' . $langs->trans("Status") . '</td>';
print '<td align="center" width="16">' . $langs->trans("Infos") . '</td>';
print '</tr>' . "\n";

clearstatcache();

$form = new Form($db);

foreach ( $dirmodels as $reldir ) {
	$dir = dol_buildpath($reldir . "core/modules/lead/");

	if (is_dir($dir)) {
		$handle = opendir($dir);
		if (is_resource($handle)) {
			$var = true;

			while ( ($file = readdir($handle)) !== false ) {
				if ((substr($file, 0, 9) == 'mod_lead_') && substr($file, dol_strlen($file) - 3, 3) == 'php') {
					$file = substr($file, 0, dol_strlen($file) - 4);
					require_once $dir . $file . '.php';

					/**
					 *
					 * @var ModeleNumRefLead $module
					 */
					$module = new $file();

					// Show modules according to features level
					if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2)
						continue;
					if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1)
						continue;

					if ($module->isEnabled()) {
						$var = ! $var;
						print '<tr ' . $bc[$var] . '><td>' . $module->nom . "</td><td>\n";
						print $module->info();
						print '</td>';

						// Show example of numbering module
						print '<td class="nowrap">';
						$tmp = $module->getExample();
						if (preg_match('/^Error/', $tmp))
							print '<div class="error">' . $langs->trans($tmp) . '</div>';
						elseif ($tmp == 'NotConfigured')
							print $langs->trans($tmp);
						else
							print $tmp;
						print '</td>' . "\n";

						print '<td align="center">';
						if ($conf->global->LEAD_ADDON == "$file") {
							print img_picto($langs->trans("Activated"), 'switch_on');
						} else {
							print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setmod&amp;value=' . $file . '">';
							print img_picto($langs->trans("Disabled"), 'switch_off');
							print '</a>';
						}
						print '</td>';

						$businesscase = new Lead($db);
						$businesscase->initAsSpecimen();

						// Info
						$htmltooltip = '';
						$htmltooltip .= '' . $langs->trans("Version") . ': <b>' . $module->getVersion() . '</b><br>';
						$nextval = $module->getNextValue($user->id, $mysoc, $businesscase);
						if ("$nextval" != $langs->trans("NotAvailable")) // Keep " on nextval
{
							$htmltooltip .= '' . $langs->trans("NextValue") . ': ';
							if ($nextval) {
								$htmltooltip .= $nextval . '<br>';
							} else {
								$htmltooltip .= $langs->trans($module->error) . '<br>';
							}
						}

						print '<td align="center">';
						print $form->textwithpicto('', $htmltooltip, 1, 0);
						print '</td>';

						print "</tr>\n";
					}
				}
			}
			closedir($handle);
		}
	}
}
print "</table><br>\n";

// Admin var of module
print_fiche_titre($langs->trans("LeadAdmVar"));

print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" >';
print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
print '<input type="hidden" name="action" value="setvar">';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td width="400px">' . $langs->trans("Valeur") . '</td>';
print "</tr>\n";

// Nb Days
print '<tr class="pair"><td>' . $langs->trans("LeadNbDayDefaultClosure") . '</td>';
print '<td align="left">';
print '<input type="text" name="LEAD_NB_DAY_COSURE_AUTO" value="' . $conf->global->LEAD_NB_DAY_COSURE_AUTO . '" size="4" ></td>';
print '</tr>';

// template
print '<tr class="impair"><td>Chemin du template personnel</td>';
print '<td align="left">';
print '<input type="text" name="LEAD_PERSONNAL_TEMPLATE" value="' . $conf->global->LEAD_PERSONNAL_TEMPLATE . '" size="30" ></td>';
print '</tr>';

// User Group
print '<tr class="pair"><td>' . $langs->trans("LeadUserGroupAffect") . '</td>';
print '<td align="left">';
print $form->select_dolgroups($conf->global->LEAD_GRP_USER_AFFECT, 'LEAD_GRP_USER_AFFECT', 1, array(), 0, '', '', $conf->entity);
print '</tr>';

// Force use thirdparty
print '<tr class="impair"><td>' . $langs->trans("LeadForceUseThirdparty") . info_admin($langs->trans("LeadForceUseThirdpartyHelp"),1) . '</td>';
print '<td align="left">';
$arrval = array(
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
);
print $form->selectarray("LEAD_FORCE_USE_THIRDPARTY", $arrval, $conf->global->LEAD_FORCE_USE_THIRDPARTY);
print '</tr>';

// Allow multiple lead on contract
print '<tr class="pair"><td>' . $langs->trans("LeadAllowMultipleOnContract") . '</td>';
print '<td align="left">';
$arrval = array(
		'0' => $langs->trans("No"),
		'1' => $langs->trans("Yes")
);
print $form->selectarray("LEAD_ALLOW_MULIPLE_LEAD_ON_CONTRACT", $arrval, $conf->global->LEAD_ALLOW_MULIPLE_LEAD_ON_CONTRACT);
print '</tr>';

if (! empty($conf->global->AGENDA_USE_EVENT_TYPE)) {

	print '<tr class="impair"><td>' . $langs->trans("LeadTypeRelance") . '</td>';
	print '<td align="left">';
	dol_include_once('/core/class/html.formactions.class.php');
	$formactions = new FormActions($db);
	$formactions->select_type_actions($conf->global->LEAD_EVENT_RELANCE_TYPE, "LEAD_EVENT_RELANCE_TYPE", "systemauto", 0, - 1);
	print '</tr>';
}

print '</table>';

print '<tr class="impair"><td colspan="2" align="right"><input type="submit" class="button" value="' . $langs->trans("Save") . '"></td>';
print '</tr>';

print '</table><br>';
print '</form>';

dol_fiche_end();

llxFooter();

$db->close();
