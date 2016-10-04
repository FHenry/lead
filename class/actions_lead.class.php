<?php
/* Copyright (C) 2015		Florian HENRY	<florian.henry@atm-consulting.fr>
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
 * \file htdocs/lead/class/actions_lead.class.php
 * \ingroup lead
 * \brief Fichier de la classe des actions/hooks des lead
 */
class ActionsLead // extends CommonObject
{

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param string[] $parameters meta datas of the hook (context, etc...)
	 * @param Lead $object the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string $action current action (if set). Generally create or edit or null
	 * @return int Hook status
	 */
	function showLinkedObjectBlock($parameters, $object, $action) {
		global $conf, $langs, $db;

		require_once 'lead.class.php';

		$lead = new Lead($db);

		$authorized_object = array ();
		foreach ( $lead->listofreferent as $referent ) {
			$authorized_object[] = $referent['table'];
		}

		if (is_object($object) && in_array($object->table_element, $authorized_object)) {
			$langs->load("lead@lead");
			require_once 'html.formlead.class.php';

			$formlead = new FormLead($db);

			$ret = $lead->fetchLeadLink(($object->rowid ? $id = $object->rowid : $object->id), $object->table_element);
			if ($ret < 0) {
				setEventMessages(null, $lead->errors, 'errors');
			}
			// Build exlcude already linked lead
			$array_exclude_lead = array ();
			foreach ( $lead->doclines as $line ) {
				$array_exclude_lead[] = $line->id;
			}

			print '<br>';
			print_fiche_titre($langs->trans('Lead'));
			if (count($lead->doclines) == 0 || ($object->table_element=='contrat' && !empty($conf->global->LEAD_ALLOW_MULIPLE_LEAD_ON_CONTRACT))) {
				print '<form action="' . dol_buildpath("/lead/lead/manage_link.php", 1) . '" method="POST">';
				print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
				print '<input type="hidden" name="redirect" value="http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '">';
				print '<input type="hidden" name="tablename" value="' . $object->table_element . '">';
				print '<input type="hidden" name="elementselect" value="' . ($object->rowid ? $object->rowid : $object->id) . '">';
				print '<input type="hidden" name="action" value="link">';
			}
			print "<table class='noborder allwidth'>";
			print "<tr class='liste_titre'>";
			print "<td>" . $langs->trans('LeadLink') . "</td>";
			print "</tr>";
			$filter = array (
					'so.rowid' => ($object->fk_soc ? $object->fk_soc : $object->socid)
			);
			if (count($array_exclude_lead) > 0) {
				$filter['t.rowid !IN'] = implode($array_exclude_lead, ',');
			}
			$selectList = $formlead->select_lead('', 'leadid', 1, $filter);
			if (! empty($selectList) && (count($lead->doclines) == 0  || ($object->table_element=='contrat' && !empty($conf->global->LEAD_ALLOW_MULIPLE_LEAD_ON_CONTRACT)))) {
				print '<tr>';
				print '<td>';
				print $selectList;
				print "<input type=submit name=join value=" . $langs->trans("Link") . ">";
				print '</td>';
				print '</tr>';
			}

			foreach ( $lead->doclines as $line ) {
				print '<tr><td>';
				print $line->getNomUrl(1).'-'.dol_trunc($line->description).' ('.$line->status_label.' - '.$line->type_label.')';
				print '<a href="' . dol_buildpath("/lead/lead/manage_link.php", 1) . '?action=unlink&sourceid=' . ($object->rowid ? $object->rowid : $object->id);
				print '&sourcetype=' . $object->table_element;
				print '&leadid=' . $line->id;
				print '&redirect=' . urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
				print '">' . img_picto($langs->trans('LeadUnlinkDoc'), 'unlink.png@lead') . '</a>';
				print '</td>';
				print '</tr>';
			}
			print "</table>";
			if (count($lead->doclines) == 0  || ($object->table_element=='contrat' && !empty($conf->global->LEAD_ALLOW_MULIPLE_LEAD_ON_CONTRACT))) {
				print "</form>";
			}
		}

		// Always OK
		return 0;
	}

	/**
	 * addMoreActionsButtons Method Hook Call
	 *
	 * @param string[] $parameters parameters
	 * @param CommonObject $object Object to use hooks on
	 * @param string $action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param HookManager $hookmanager class instance
	 * @return int Hook status
	 */
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager) {
		global $langs, $conf, $user, $db ,$bc;

		$current_context = explode(':', $parameters['context']);
		if (in_array('commcard', $current_context)) {

			$langs->load("lead@lead");

			if ($user->rights->lead->write) {
				$html = '<div class="inline-block divButAction"><a class="butAction" href="' . dol_buildpath('/lead/lead/card.php', 1) . '?action=create&socid=' . $object->id . '">' . $langs->trans('LeadCreate') . '</a></div>';
			} else {
				$html = '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('LeadCreate') . '</a></div>';
			}

			$html = str_replace('"', '\"', $html);

			$js= '<script type="text/javascript">'."\n";
			$js.= '	$(document).ready('."\n";
			$js.= '		function () {'."\n";
			$js.= '			$(".tabsAction").append("' . $html . '");'."\n";
			$js.= '		});'."\n";
			$js.= '</script>';
			print $js;

			if ($user->rights->lead->read) {

				require_once 'lead.class.php';
				$lead = new Lead($db);

				$filter['so.rowid'] = $object->id;
				$resql = $lead->fetch_all('DESC', 't.date_closure', 0, 0, $filter);
				if ($resql == - 1) {
					setEventMessages(null, $object->errors, 'errors');
				}

				$total_lead = count($lead->lines);

				// $filter['so.rowid'] = $object->id;
				$resql = $lead->fetch_all('DESC', 't.date_closure', 4, 0, $filter);
				if ($resql == - 1) {
					setEventMessages(null, $object->errors, 'errors');
				}

				$num = count($lead->lines);

				$html = '<table class="noborder" width="100%">';

				$html .= '<tr class="liste_titre">';
				$html .= '<td colspan="6">';
				$html .= '<table width="100%" class="nobordernopadding"><tr><td>' . $langs->trans("LeadLastLeadUpdated", ($num <= 4 ? $num : 4)) . '</td><td align="right"><a href="' . dol_buildpath('/lead/lead/list.php', 1) . '?socid=' . $object->id . '">' . $langs->trans("LeadList") . ' (' . $total_lead . ')</a></td>';
				$html .= '<td width="20px" align="right"><a href="' . dol_buildpath('/lead/index.php', 1) . '">' . img_picto($langs->trans("Statistics"), 'stats') . '</a></td>';
				$html .= '</tr></table></td>';
				$html .= '</tr>';

				foreach ( $lead->lines as $lead_line ) {
					$var = ! $var;
					$html .='<tr '. $bc[$var].'>';
					$html .= '<td>'.$lead_line->getNomUrl(1).'</td>';
					$html .= '<td>'.$lead_line->ref_int.'</td>';
					$html .= '<td>'.$lead_line->type_label.'</td>';
					$html .= '<td>'.price($lead_line->amount_prosp) . ' ' . $langs->getCurrencySymbol($conf->currency).'</td>';
					$html .= '<td>'.dol_print_date($lead_line->date_closure, 'daytextshort').'</td>';
					$html .= '<td>'.$lead_line->getLibStatut(2).'</td>';
					$html .= '</tr>';
				}

				$html .= '</table>';
				$html = str_replace('"', '\"', $html);
				$js= '<script type="text/javascript">'."\n";
				$js.= '	$(document).ready('."\n";
				$js.= '		function () {'."\n";
				$js.= '			$(".ficheaddleft").append("' . $html . '");'."\n";
				$js.= '		});'."\n";
				$js.= '</script>';
				print $js;
			}
		}
		if (in_array('propalcard', $current_context)) {
			require_once 'lead.class.php';
			$lead = new Lead($db);

			$ret = $lead->fetchLeadLink(($object->rowid ? $id = $object->rowid : $object->id), $object->table_element);
			if ($ret < 0) {
				setEventMessages(null, $lead->errors, 'errors');
			}

			if (count($lead->doclines) == 0) {
				$langs->load("lead@lead");

				if ($user->rights->lead->write) {
					$html = '<div class="inline-block divButAction"><a class="butAction" href="' . dol_buildpath('/lead/lead/card.php', 1) . '?action=create&amp;socid=' . $object->socid . '&amp;amount_guess=' . $object->total_ht . '&amp;propalid=' . $object->id . '">' . $langs->trans('LeadCreate') . '</a></div>';
				} else {
					$html = '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('LeadCreate') . '</a></div>';
				}

				$html = str_replace('"', '\"', $html);
				$js= '<script type="text/javascript">'."\n";
				$js.= '	$(document).ready('."\n";
				$js.= '		function () {'."\n";
				$js.= '			$(".tabsAction").append("' . $html . '");'."\n";
				$js.= '		});'."\n";
				$js.= '</script>';
				print $js;
			}
		}

		// Always OK
		return 0;
	}

	/**
	 * addSearchEntry Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object &$object Object to use hooks on
	 * @param string &$action Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
	 */
	public function addSearchEntry($parameters, &$object, &$action, $hookmanager) {
		global $conf, $langs;
		$langs->load('lead@lead');

		$arrayresult['searchintolead'] = array (
				'text' => img_object('', 'lead@lead') . ' ' . $langs->trans("Module103111Name"),
				'url' => dol_buildpath('/lead/lead/list.php', 1) . '?search_ref=' . urlencode($parameters['search_boxvalue'])
		);

		$this->results = $arrayresult;

		return 0;
	}
}
