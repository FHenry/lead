-- Manage Lead
-- Copyright (C) 2014  Florian HENRY <florian.henry@atm-consulting.fr>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.


CREATE TABLE IF NOT EXISTS llx_lead (
rowid 			integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
ref			varchar(50) NOT NULL,
entity			integer NOT NULL DEFAULT 0,
ref_ext 		text,
ref_int 		text,
fk_soc	 		integer NOT NULL,
fk_c_status 		integer NOT NULL,
fk_c_type 		integer  NOT NULL,
date_closure 		datetime NOT NULL,
amount_prosp 		double(24,8) NOT NULL,
fk_user_resp 		integer NOT NULL,
description 		text,
note_public 		text,
note_private 		text,
fk_user_author		integer	NOT NULL,
datec			datetime  NOT NULL,
fk_user_mod 		integer NOT NULL,
tms 			timestamp NOT NULL
)ENGINE=InnoDB;

