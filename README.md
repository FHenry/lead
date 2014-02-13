My Module
=========

This is a full featured module template for Dolibarr

Licence
-------
GPLv3 or (at your option) any later version.

See COPYING for more information.

INSTALL
-------

To install this module, Dolibarr (v >= 3.3) have to be already installed and configured on your server.

- In your Dolibarr installation directory: edit the htdocs/conf/conf.php file
- Find the following lines:

	\#$=dolibarr_main_url_root_alt ...

	\#$=dolibarr_main_document_root_alt ...

	or

	//$=dolibarr_main_url_root_alt ...

	//$=dolibarr_main_document_root_alt ...

- Delete the first "#" (or "//") of these lines and assign a value consistent with your Dolibarr installation

	$dolibarr_main_url_root = ...

	and

	$dolibarr_main_document_root = ...

for example on UNIX systems:

	$dolibarr_main_url_root = 'http://localhost/Dolibarr/htdocs';

	$dolibarr_main_document_root = '/var/www/Dolibarr/htdocs';

	$dolibarr_main_url_root_alt = 'http://localhost/Dolibarr/htdocs/custom';

	$dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';

for example on a Windows system:

	$dolibarr_main_url_root = 'http://localhost/Dolibarr/htdocs';

	$dolibarr_main_document_root = 'C:/My Web Sites/Dolibarr/htdocs';

	$dolibarr_main_url_root_alt = 'http://localhost/Dolibarr/htdocs/custom';

	$dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';

For more information about the conf.php file take a look at the conf.php.example file.

- Extract the module's files in the $dolibarr_main_document_root_alt directory.
(You may have to create the custom directory first if it doesn't exist yet.)

for example on UNIX systems: /var/www/Dolibarr/htdocs/custom

for example on a Windows system: C:/My Web Sites/Dolibarr/htdocs/custom

From your browser:
- log in as a Dolibarr administrator
- under "Setup" -> "Other setup", set "MAIN_FEATURES_LEVEL" to "2"
- go to "Setup" -> "Modules"
- the module is under one of the tabs
- you should now be able to enable the new module

Other Licences
--------------
Uses Michel Fortin's PHP Markdown Licensed under BSD to display this README.
