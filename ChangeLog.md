# Change Log
All notable changes to this project will be documented in this file.

## Version 2.3

- FIX : Compat v17 - *04/04/2023* - 2.3.15
- FIX : Compat v15 - *16/02/2023* - 2.3.14
- FIX : Compatibility PHP 8.1 - *02/08/2022* - 2.3.13
- FIX : Compatibility V16 - *14/06/2022* - 2.3.12
  - remove db_prefix in dictionnary definitions
  - add token to index
  - add token to list search form
  - add token to url and list sortfield
  - fix fatal : createfromclone does not work...
  - fix module family
- FIX : Multi Module Hook compatibility - *06/05/2022* - 2.3.11
- FIX : Compat V15 : newToken() - *20/04/2022* - 2.3.10
- FIX : change status invoice in lead card *2022-02-22* - 2.3.9
- FIX : Liaison automatique  d'une facture  à une affaire si la propale d'origine est liée à cette affaire.   - *2022-02-22* - 2.3.8
- FIX : Compat V13 : newToken() et $user->socid - *2021-01-11* - 2.3.7
- FIX : Compat V15 - *2021-12-21* - 2.3.6
- FIX : GETPOST (external fix by user @l00ptr) - *2021-12-06* - 2.3.5
- FIX : function name is now closeProposal() and not cloture(), then we use mthod_exists - *2021-10-28* - 2.3.4
- FIX : Compatibility with Dolibarr v14 (removal of `total` fields from
        the invoice table and the proposal table) - *2021-10-07* - 2.3.3
- FIX : List compatibility with Dolibarr v14 (empty value for sales
        person selector is now -1) - *2021-09-09* - 2.3.2
- FIX : Box compatibility for Dolibarr V13 - *2021-yy-mm* - 2.3.1
- NEW : Add tab count badges for tabs "Contacts", "Files" and "Notes"


---
## Old Changelog

***** ChangeLog for 2.2 compared to 2.1.7 *****
FIX : delete "setPrecisionY" function

***** ChangeLog for 2.0 compared to 1.16 *****
NEW : Better management of close or open leads 

***** ChangeLog for 1.16 compared to 1.15 *****
NEW : Only for 5.0 with good list management
NEW : Can create contract from lead

***** ChangeLog for 1.15 compared to 1.14 *****
FIX : error into log on addSearchEntry hook

***** ChangeLog for 1.14 compared to 1.12 *****
FIX : Lead visibility according user right on thirdparties
NEW : Can create propal on Lead page
NEW : Can clone propal on Lead page

***** ChangeLog for 1.12 compared to 1.11 *****
NEW : Add developer feature to manage template
NEW : Add quick search on Lead Ref

***** ChangeLog for 1.11 compared to 1.10 *****
NEW : Add feature to create event linked to lead

***** ChangeLog for 1.10 compared to 1.9 *****
NEW : Add more information into lead box under contract/propal/etc...
NEW : Add export on Propal not linked to Lead

***** ChangeLog for 1.9 compared to 1.8 *****
FIX : Add option to link multiple lead on contract (from contract card)

***** ChangeLog for 1.8 compared to 1.7 *****
NEW : Add setting to disabeld thirdparty mandatory field
NEW : Add option to link multiple lead on contract
FIX : Shouldn't propose disabled users #13 

***** ChangeLog for 1.7 compared to 1.6 *****
FIX : Fix problem with Dolibarr 3.8

***** ChangeLog for 1.5.1 compared to 1.5 *****
NEW : Italian language

***** ChangeLog for 1.5 compared to 1.3 *****
FIX : Problem on extrafields creation
NEW : Merge from GPC solution repository code review

***** ChangeLog for 1.3 compared to 1.2 *****
FIX : problem on tab File attached

***** ChangeLog for 1.2 compared to 1.1 *****
NEW : Can link to lead directly from proposal/order/invoice/contract
NEW : close lead (status lost) will update all proposal to status not signed
NEW : Some graph and data
NEW : New tabs notes and attached documents
NEW : Add list lead into cutomer tabs (thridparty module)
NEW : Add lead export into export module
