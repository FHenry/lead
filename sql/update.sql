ALTER TABLE llx_lead ADD COLUMN note_public text AFTER description;
ALTER TABLE llx_lead ADD COLUMN note_private text AFTER note_public;
ALTER TABLE llx_lead ADD COLUMN position integer AFTER label;
ALTER TABLE llx_lead ADD COLUMN percent double(5,2) AFTER position;