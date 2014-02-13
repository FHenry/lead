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
/*
dol_include_once ( '/agefodd/class/agefodd_index.class.php' );
dol_include_once ( '/agefodd/class/agefodd_sessadm.class.php' );
dol_include_once ( '/agefodd/lib/agefodd.lib.php' );
dol_include_once ( '/core/lib/date.lib.php' );
*/

// Security check
if (! $user->rights->lead->read)
	accessforbidden ();

$langs->load ( 'lead@lead' );

llxHeader ( '', $langs->trans ( 'Module103111Name' ) );

print 'Welcome Lead';


llxFooter ();
$db->close ();
?>