<?php
/* Copyright (C) 2015		Florian HENRY	<florian.henry@open-concept.pro>
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
	 * @param parameters meta datas of the hook (context, etc...)
	 * @param object the object you want to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param action current action (if set). Generally create or edit or null
	 * @return void
	 */
	function showLinkedObjectBlock($parameters, $object, $action) {
		global $conf, $langs;
		if (is_object($object) && ($object->element == 'propal' || $object->element == 'facture')) {
			$langs->load("lead@lead");
			require_once 'html.formlead.class.php';
			require_once 'lead.class.php';
			
			$lead = new Lead($object->db);
			$formlead = new FormLead($object->db);
			
			$ret = $lead->fetch_lead_link(($object->rowid ? $id = $object->rowid : $object->id), $object->table_element);
			if ($ret < 0) {
				setEventMessage($lead->error, 'errors');
			}
			// Build exlcude already linked lead
			$array_exclude_lead = array ();
			foreach ( $lead->doclines as $line ) {
				$array_exclude_lead[] = $line->id;
			}
			
			print '<br>';
			print_titre($langs->trans('Lead'));
			print '<form action="' . dol_buildpath("/lead/lead/manage_link.php", 1) . '" method="POST">';
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			print '<input type="hidden" name="redirect" value="http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '">';
			print '<input type="hidden" name="tablename" value="' . $object->table_element . '">';
			print '<input type="hidden" name="elementselect" value="' . ($object->rowid ? $object->rowid : $object->id) . '">';
			print '<input type="hidden" name="action" value="link">';
			print "<table class='noborder allwidth'>";
			print "<tr class='liste_titre'>";
			print "<td>" . $langs->trans('LeadLink') . "</td>";
			print "</tr>";
			$filter = array (
					'so.rowid' => ($object->fk_soc ? $object->fk_soc : $object->socid),
			);
			if (count($array_exclude_lead)>0) {
				$filter['t.rowid !IN']=implode($array_exclude_lead, ',');
			}
			$selectList = $formlead->select_lead('', 'leadid', 1, $filter);
			if (! empty($selectList)) {
				print '<tr>';
				print '<td>';
				print $selectList;
				print "<input type=submit name=join value=" . $langs->trans("Link") . ">";
				print '</td>';
				print '</tr>';
			}
			
			foreach ( $lead->doclines as $line ) {
				print '<tr><td>';
				print $line->getNomUrl(1);
				print '<a href="' . dol_buildpath("/lead/lead/manage_link.php", 1) . '?action=unlink&sourceid=' . ($object->rowid ? $object->rowid : $object->id);
				print '&sourcetype=' . $object->table_element;
				print '&leadid=' . $line->id;
				print '&redirect=' . urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
				print '">' . img_picto($langs->trans('LeadUnlinkDoc'), 'unlink.png@lead') . '</a>';
				print '</td>';
				print '</tr>';
			}
			print "</table>";
			print "</form>";
		}
		
		return 0;
	}
	
	/**
	 * addMoreActionsButtons Method Hook Call
	 *
	 * @param array $parameters parameters
	 * @param Object	&$object			Object to use hooks on
	 * @param string	&$action			Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 * @param object $hookmanager class instance
	 * @return void
	 */
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager) {
		global $langs,$conf,$user;
		
		$current_context=explode(':',$parameters['context']);
		if (in_array('commcard',$current_context)) {
			$langs->load("lead@lead");
	
			if ($user->rights->lead->write)
			{
				$html= '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/lead/lead/card.php',1).'?action=create&socid='.$object->id.'">'.$langs->trans('LeadCreate').'</a></div>';
			}
			else
			{
				$html= '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('LeadCreate').'</a></div>';
			}
				
			$html=str_replace('"','\"',$html);
			print '<script type="text/javascript">jQuery(document).ready(function () {jQuery(function() {jQuery(".tabsAction").append("' . $html . '");});});</script>';
		}
		if (in_array('propalcard',$current_context)) {
			$langs->load("lead@lead");
		
			if ($user->rights->lead->write)
			{
				$html= '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/lead/lead/card.php',1).'?action=create&amp;socid='.$object->socid.'&amp;amount_guess='.$object->total_ttc.'">'.$langs->trans('LeadCreate').'</a></div>';
			}
			else
			{
				$html= '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('LeadCreate').'</a></div>';
			}
		
			$html=str_replace('"','\"',$html);
			print '<script type="text/javascript">jQuery(document).ready(function () {jQuery(function() {jQuery(".tabsAction").append("' . $html . '");});});</script>';
		}
		return 0;
	}
}