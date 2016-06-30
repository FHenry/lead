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

INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (1,'PROSP'  ,'Prospection', 10, 0,1);
INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (2,'QUAL'   ,'Chiffrage',20, 20,1);
INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (3,'PROPO'  ,'Proposition', 30, 40,1);
INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (4,'NEGO'   ,'Négociation', 40, 60,1);
INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (5,'PENDING','Reconduction', 50, 50,0);
INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (6,'WON'    ,'Gagné', 60, 100,1);
INSERT INTO llx_c_lead_status(rowid,code,label,position,percent,active) VALUES (7,'LOST'   ,'Perdu', 70, 0,1);

INSERT INTO llx_c_lead_type(rowid,code,label,active) VALUES (1,'SUPP','Support',1);
INSERT INTO llx_c_lead_type(rowid,code,label,active) VALUES (2,'TRAIN','Formation',1);
INSERT INTO llx_c_lead_type(rowid,code,label,active) VALUES (3,'ADVI','Conseil',1);

INSERT INTO llx_c_type_contact(rowid, element, source, code, libelle, active, module) VALUES (1031111,'lead','internal','ORIG','Commercial à l''origine de l''affaire','1',null);
INSERT INTO llx_c_type_contact(rowid, element, source, code, libelle, active, module) VALUES (1031112,'lead','external','SALESREPFOLL','Responsable suivi du paiement','1',null);



