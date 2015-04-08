<?php
/* Lead
 * Copyright (C) 2014-2015 Florian HENRY <florian.henry@open-concept.pro>
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
$res = @include ("../main.inc.php"); // For root directory
if (! $res)
	$res = @include ("../../main.inc.php"); // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

require_once './class/leadstats.class.php';
	
// Security check
if (! $user->rights->lead->read)
	accessforbidden();

$langs->load('lead@lead');

llxHeader('', $langs->trans('Module103111Name'));

$stats_lead= new LeadStats($db);

$data1 = $stats_lead->getAllLeadByType();
if (!is_array($data1) && $data1<0) {
	setEventMessage($stats_lead->error,'errors');
}
if (empty($data1))
{
	$showpointvalue=0;
	$nocolor=1;
	$data1=array(array(0=>$langs->trans("None"),1=>1));
}

$filenamenb = $conf->lead->dir_output . "/stats/leadbytype.png";
$fileurlnb = DOL_URL_ROOT . '/viewimage.php?modulepart=leadstats&amp;file=leadbytype.png';
$px = new DolGraph();
$mesg = $px->isGraphKo();
if (empty($mesg)) {
	$i=0;$tot=count($data1);$legend=array();
	while ($i <= $tot)
	{
		$data1[$i][0]=$data1[$i][0];	// Required to avoid error "Could not draw pie with labels contained inside canvas"
		$legend[]=$data1[$i][0];
		$i++;
	}
	$px->SetData($data1);
	unset($data1);

	if ($nocolor)
		$px->SetDataColor(array (
				array (
						220,
						220,
						220
				)
		));
		$px->SetPrecisionY(0);
		$px->SetLegend($legend);
		$px->setShowLegend(0);
		$px->setShowPointValue($showpointvalue);
		$px->setShowPercent(1);
		$px->SetMaxValue($px->GetCeilMaxValue());
		$px->SetWidth(400);
		$px->SetHeight(400);
		$px->SetShading(3);
		$px->SetHorizTickIncrement(1);
		$px->SetCssPrefix("cssboxes");
		$px->SetType(array (
				'pie'
		));
		$px->SetTitle($langs->trans('LeadNbLeadByType'));
		$result=$px->draw($filenamenb, $fileurlnb);
		if ($result<0) {
			setEventMessage($px->error,'errors');
		}
} else {
	setEventMessage($mesgs, 'errors');
}



$stringtoshow = '<div class="fichecenter"><div class="containercenter"><div class="fichehalfleft">';
$stringtoshow .= $px->show();
$stringtoshow .= '</div>';


$data1 = $stats_lead->getAllLeadByStatus();
if (!is_array($data1) && $data1<0) {
	setEventMessage($stats_lead->error,'errors');
}
if (empty($data1))
{
	$showpointvalue=0;
	$nocolor=1;
	$data1=array(array(0=>$langs->trans("None"),1=>1));
}

$filenamenb = $conf->lead->dir_output . "/stats/leadbystatus.png";
$fileurlnb = DOL_URL_ROOT . '/viewimage.php?modulepart=leadstats&amp;file=leadbystatus.png';
$px = new DolGraph();
$mesg = $px->isGraphKo();
if (empty($mesg)) {
	$i=0;$tot=count($data1);$legend=array();
	while ($i <= $tot)
	{
		$data1[$i][0]=$data1[$i][0];	// Required to avoid error "Could not draw pie with labels contained inside canvas"
		$legend[]=$data1[$i][0];
		$i++;
	}
	$px->SetData($data1);
	unset($data1);

	if ($nocolor)
		$px->SetDataColor(array (
				array (
						220,
						220,
						220
				)
		));
		$px->SetPrecisionY(0);
		$px->SetLegend($legend);
		$px->setShowLegend(0);
		$px->setShowPointValue($showpointvalue);
		$px->setShowPercent(1);
		$px->SetMaxValue($px->GetCeilMaxValue());
		$px->SetWidth(400);
		$px->SetHeight(400);
		$px->SetShading(3);
		$px->SetHorizTickIncrement(1);
		$px->SetCssPrefix("cssboxes");
		$px->SetType(array (
				'pie'
		));
		$px->SetTitle($langs->trans('LeadNbLeadByStatus'));
		$result=$px->draw($filenamenb, $fileurlnb);
		if ($result<0) {
			setEventMessage($px->error,'errors');
		}
} else {
	setEventMessage($mesgs, 'errors');
}


$stringtoshow .='<div class="fichehalfright">';
$stringtoshow .= $px->show();
$stringtoshow .= '</div></div></div>';

print $stringtoshow; 
llxFooter();
$db->close();
?>