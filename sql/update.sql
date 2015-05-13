ALTER TABLE llx_lead ADD COLUMN note_public text AFTER description;
ALTER TABLE llx_lead ADD COLUMN note_private text AFTER note_public;