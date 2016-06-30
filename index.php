<?php
/* Lead
 * Copyright (C) 2014-2015 Florian HENRY <florian.henry@atm-consulting.fr>
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

$res = @include '../main.inc.php'; // For root directory
if (! $res)
	$res = @include '../../main.inc.php'; // For "custom" directory
if (! $res)
	die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once 'lib/lead.lib.php';

require_once './class/leadstats.class.php';
	
// Security check
if (! $user->rights->lead->read)
	accessforbidden();


$WIDTH=DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT=DolGraph::getDefaultGraphSizeForStats('height');

$userid=GETPOST('userid','int');
$socid=GETPOST('socid','int');
// Security check
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}
$nowyear=strftime("%Y", dol_now());
$year = GETPOST('year')>0?GETPOST('year'):$nowyear;
//$startyear=$year-2;
$startyear=$year-1;
$endyear=$year;


$includeuserlist=array();
if (!empty($conf->global->LEAD_GRP_USER_AFFECT)) {
	$usergroup=new UserGroup($db);
	$result=$usergroup->fetch($conf->global->LEAD_GRP_USER_AFFECT);
	if ($result < 0)
		setEventMessage($usergroup->error, 'errors');

	$includeuserlisttmp=$usergroup->listUsersForGroup();
	if (is_array($includeuserlisttmp) && count($includeuserlisttmp)>0) {
		foreach($includeuserlisttmp as $usertmp) {
			$includeuserlist[]=$usertmp->id;
		}

	}
}

$langs->load('lead@lead');

llxHeader('', $langs->trans('Module103111Name'));

$stats_lead= new LeadStats($db);
if (!empty($userid) && $userid!=-1) $stats_lead->userid=$userid;
if (!empty($socid)  && $socid!=-1) $stats_lead->socid=$socid;
if (!empty($year)) $stats_lead->year=$year;

$stats_lead->year=0;
$data_all_year = $stats_lead->getAllByYear();
if (!empty($year)) $stats_lead->year=$year;
$arrayyears=array();
foreach($data_all_year as $val) {
	$arrayyears[$val['year']]=$val['year'];
}
if (! count($arrayyears)) $arrayyears[$nowyear]=$nowyear;


$data1 = $stats_lead->getAllLeadByType();
if (!is_array($data1) && $data1<0) {
	setEventMessages($stats_lead->error, null, 'errors');
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
		$px->SetWidth(300);
		$px->SetHeight(300);
		$px->SetShading(3);
		$px->SetHorizTickIncrement(1);
		$px->SetCssPrefix("cssboxes");
		$px->SetType(array (
				'pie'
		));
		$px->SetTitle($langs->trans('LeadNbLeadByType'));
		$result=$px->draw($filenamenb, $fileurlnb);
		if ($result<0) {
			setEventMessages($px->error, null, 'errors');
		}
} else {
	setEventMessages(null, $mesgs, 'errors');
}



$stringtoshow = '<div class="fichecenter"><div class="containercenter"><div class="fichehalfleft">';
$stringtoshow .= $px->show();
$stringtoshow .= '</div>';


$data1 = $stats_lead->getAllLeadByStatus();
if (!is_array($data1) && $data1<0) {
	setEventMessages($stats_lead->error, null, 'errors');
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
		$px->SetWidth(300);
		$px->SetHeight(300);
		$px->SetShading(3);
		$px->SetHorizTickIncrement(1);
		$px->SetCssPrefix("cssboxes");
		$px->SetType(array (
				'pie'
		));
		$px->SetTitle($langs->trans('LeadNbLeadByStatus'));
		$result=$px->draw($filenamenb, $fileurlnb);
		if ($result<0) {
			setEventMessages($px->error, null, 'errors');
		}
} else {
	setEventMessages(null, $mesgs, 'errors');
}


$stringtoshow .='<div class="fichehalfright">';
$stringtoshow .= $px->show();
$stringtoshow .= '</div></div></div>';

// Build graphic number of object
// $data = array(array('Lib',val1,val2,val3),...)
$data = $stats_lead->getNbByMonthWithPrevYear($endyear,$startyear);
//var_dump($data);

$filenamenb = $conf->lead->dir_output . "/stats/leadnbprevyear-".$year.".png";
$fileurlnb = DOL_URL_ROOT . '/viewimage.php?modulepart=leadstats&amp;file=leadnbprevyear-'.$year.'.png';

$px1 = new DolGraph();
$mesg = $px1->isGraphKo();
if (! $mesg)
{
	$px1->SetData($data);
	$px1->SetPrecisionY(0);
	$i=$startyear;$legend=array();
	while ($i <= $endyear)
	{
		$legend[]=$i;
		$i++;
	}
	$px1->SetLegend($legend);
	$px1->SetMaxValue($px1->GetCeilMaxValue());
	$px1->SetWidth($WIDTH);
	$px1->SetHeight($HEIGHT);
	$px1->SetYLabel($langs->trans("LeadNbLead"));
	$px1->SetShading(3);
	$px1->SetHorizTickIncrement(1);
	$px1->SetPrecisionY(0);
	$px1->mode='depth';
	$px1->SetTitle($langs->trans("LeadNbLeadByMonth"));

	$px1->draw($filenamenb,$fileurlnb);
}

// Build graphic amount of object
$data = $stats_lead->getAmountByMonthWithPrevYear($endyear,$startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

$filenamenb = $conf->lead->dir_output . "/stats/leadamountprevyear-".$year.".png";
$fileurlnb = DOL_URL_ROOT . '/viewimage.php?modulepart=leadstats&amp;file=leadamountprevyear-'.$year.'.png';

$px2 = new DolGraph();
$mesg = $px2->isGraphKo();
if (! $mesg)
{
	$px2->SetData($data);
	$i=$startyear;$legend=array();
	while ($i <= $endyear)
	{
		$legend[]=$i;
		$i++;
	}
	$px2->SetLegend($legend);
	$px2->SetMaxValue($px2->GetCeilMaxValue());
	$px2->SetMinValue(min(0,$px2->GetFloorMinValue()));
	$px2->SetWidth($WIDTH);
	$px2->SetHeight($HEIGHT);
	$px2->SetYLabel($langs->trans("LeadAmountOfLead"));
	$px2->SetShading(3);
	$px2->SetHorizTickIncrement(1);
	$px2->SetPrecisionY(0);
	$px2->mode='depth';
	$px2->SetTitle($langs->trans("LeadAmountOfLeadsByMonth"));

	$px2->draw($filenamenb,$fileurlnb);
}

// Build graphic with transformation rate
$data = $stats_lead->getTransformRateByMonthWithPrevYear($endyear,$startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

$filenamenb = $conf->lead->dir_output . "/stats/leadtransrateprevyear-".$year.".png";
$fileurlnb = DOL_URL_ROOT . '/viewimage.php?modulepart=leadstats&amp;file=leadtransrateprevyear-'.$year.'.png';

$px3 = new DolGraph();
$mesg = $px3->isGraphKo();
if (! $mesg)
{
	$px3->SetData($data);
	$i=$startyear;$legend=array();
	while ($i <= $endyear)
	{
		$legend[]=$i;
		$i++;
	}
	$px3->SetLegend($legend);
	$px3->SetMaxValue($px3->GetCeilMaxValue());
	$px3->SetMinValue(min(0,$px3->GetFloorMinValue()));
	$px3->SetWidth($WIDTH);
	$px3->SetHeight($HEIGHT);
	$px3->SetYLabel($langs->trans("LeadTransRateOfLead"));
	$px3->SetShading(3);
	$px3->SetHorizTickIncrement(1);
	$px3->SetPrecisionY(0);
	$px3->mode='depth';
	$px3->SetTitle($langs->trans("LeadTransRateOfLeadsByMonth"));

	$px3->draw($filenamenb,$fileurlnb);
}

$stringtoshow.= '<table class="border" width="100%"><tr valign="top"><td align="center">';
if ($mesg) { print $mesg; }
else {
	$stringtoshow.= $px1->show();
	$stringtoshow.= "<br>\n";
	$stringtoshow.= $px2->show();
	$stringtoshow.= "<br>\n";
	$stringtoshow.= $px3->show();
}
$stringtoshow.= '</td></tr></table>';

$head = lead_stats_prepare_head();

dol_fiche_head($head,'stat',$langs->trans("Statistics"), 0, dol_buildpath('/lead/img/object_lead.png', 1), 1);

$form=new Form($db);

print '<div class="fichecenter"><div class="fichethirdleft">';

print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<table class="border" width="100%">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
// Company
print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
if ($mode == 'customer') $filter='s.client in (1,2,3)';
if ($mode == 'supplier') $filter='s.fournisseur = 1';
print $form->select_thirdparty_list($socid,'socid',$filter,1);
print '</td></tr>';
// User
print '<tr><td>'.$langs->trans("LeadCommercial").'</td><td>';
print $form->select_dolusers($userid, 'userid', 1, array(),0,$includeuserlist);
print '</td></tr>';
// Year
print '<tr><td>'.$langs->trans("Year").'</td><td>';
if (! in_array($year,$arrayyears)) $arrayyears[$year]=$year;
if (! in_array($nowyear,$arrayyears)) $arrayyears[$nowyear]=$nowyear;
arsort($arrayyears);
print $form->selectarray('year',$arrayyears,$year,0);
print '</td></tr>';
print '<tr><td align="center" colspan="2"><input type="submit" name="submit" class="button" value="'.$langs->trans("Refresh").'"></td></tr>';
print '</table>';
print '</form>';
print '<br><br>';

print '<table class="border" width="100%">';
print '<tr style="height:24px">';
print '<td align="center">'.$langs->trans("Year").'</td>';
print '<td align="center">'.$langs->trans("LeadNbLead").'</td>';
print '<td align="center">'.$langs->trans("AmountTotal").'</td>';
print '<td align="center">'.$langs->trans("AmountAverage").'</td>';
print '</tr>';

$oldyear=0;
foreach ($data_all_year as $val)
{
	$year = $val['year'];
	while ($year && $oldyear > $year+1)
	{	// If we have empty year
		$oldyear--;
		print '<tr style="height:24px">';
		print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$oldyear.'&amp;mode='.$mode.($socid>0?'&socid='.$socid:'').($userid>0?'&userid='.$userid:'').'">'.$oldyear.'</a></td>';
		print '<td align="right">0</td>';
		print '<td align="right">0</td>';
		print '<td align="right">0</td>';
		print '</tr>';
	}
	print '<tr style="height:24px">';
	print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&amp;mode='.$mode.($socid>0?'&socid='.$socid:'').($userid>0?'&userid='.$userid:'').'">'.$year.'</a></td>';
	print '<td align="right">'.$val['nb'].'</td>';
	print '<td align="right">'.price(price2num($val['total'],'MT'),1).'</td>';
	print '<td align="right">'.price(price2num($val['avg'],'MT'),1).'</td>';
	print '</tr>';
	$oldyear=$year;
}

print '</table>';

print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


// Show graphs
print '<table class="border" width="100%"><tr valign="top"><td align="center">';
if ($mesg) { print $mesg; }
else {
	print $stringtoshow;
}
print '</td></tr></table>';


print '</div></div></div>';
print '<div style="clear:both"></div>';







print '<table class="noborder" width="100%">';

print '</table>';

dol_fiche_end();
llxFooter();
$db->close();
