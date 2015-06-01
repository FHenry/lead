-- Manage Lead
-- Copyright (C) 2014  Florian HENRY <florian.henry@open-concept.pro>
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

INSERT INTO llx_c_lead_status(rowid,code,label,active) VALUES (1,'PROSP','Prospection',1);
INSERT INTO llx_c_lead_status(rowid,code,label,active) VALUES (2,'CHIFF','Chiffrage',1);
INSERT INTO llx_c_lead_status(rowid,code,label,active) VALUES (3,'PROPO','Proposition',1);
INSERT INTO llx_c_lead_status(rowid,code,label,active) VALUES (4,'NEGO','Négociation',1);
INSERT INTO llx_c_lead_status(rowid,code,label,active) VALUES (5,'RECOND','Reconduction',1);
INSERT INTO llx_c_lead_status(rowid,code,label,active) VALUES (6,'WIN','Gagné',1);
INSERT INTO llx_c_lead_status(rowid,code,label,active) VALUES (7,'LOST','Perdu',1);

INSERT INTO llx_c_lead_type(rowid,code,label,active) VALUES (1,'SUPP','Support',1);
INSERT INTO llx_c_lead_type(rowid,code,label,active) VALUES (2,'TRAIN','Formation',1);
INSERT INTO llx_c_lead_type(rowid,code,label,active) VALUES (3,'ADVI','Conseil',1);

INSERT INTO llx_c_type_contact(rowid, element, source, code, libelle, active, module) VALUES (1031111,'lead','internal','ORIG','Commercial à l''origine de l''affaire','1',null);
INSERT INTO llx_c_type_contact(rowid, element, source, code, libelle, active, module) VALUES (1031112,'lead','external','SALESREPFOLL','Responsable suivi du paiement','1',null);



