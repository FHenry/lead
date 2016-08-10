LEAD
=========

Module LEAD

Licence
-------

GPLv3 or (at your option) any later version.

See COPYING for more information.

INSTALL
-------

- Make sure Dolibarr (v >= 3.8) is already installed and configured on your server.

- In your Dolibarr installation directory, edit the htdocs/conf/conf.php file

- Find the following lines:

		//$=dolibarr_main_url_root_alt ...
		//$=dolibarr_main_document_root_alt ...

- Uncomment these lines (delete the leading "//") and assign a sensible value according to your Dolibarr installation

	For example :

	- UNIX:

			$dolibarr_main_url_root = 'http://localhost/Dolibarr/htdocs';
			$dolibarr_main_document_root = '/var/www/Dolibarr/htdocs';
			$dolibarr_main_url_root_alt = '/custom';
			$dolibarr_main_document_root_alt = '/var/www/Dolibarr/htdocs/custom';

	- Windows:

			$dolibarr_main_url_root = 'http://localhost/Dolibarr/htdocs';
			$dolibarr_main_document_root = 'C:/My Web Sites/Dolibarr/htdocs';
			$dolibarr_main_url_root_alt = '/custom';
			$dolibarr_main_document_root_alt = 'C:/My Web Sites/Dolibarr/htdocs/custom';


- Clone the repository in $dolibarr\_main\_document\_root\_alt/mymodule

	*(You may have to create the custom directory first if it doesn't exist yet.)*

	```
	git clone --recursive https://github.com/ATM-Consulting/dolibarr_module_lead.git
	```

	**The template now uses a git submodule to fetch the PHP Markdown library.**

	If your git version is less than 1.6.5, the --recursive parameter won't work.

	Please use this instead to fetch the latest version:

		git clone https://github.com/ATM-Consulting/dolibarr_module_lead.git lead
		cd lead
		git submodule update --init
		php composer.php install
		php composer.php update

- From your browser:

	- log in as a Dolibarr administrator

	- go to "Setup" -> "Modules"

	- the module is under tabs "module interface"

	- Find module Lead and activate it

	- Go to module configuration and set it up


Contributions
-------------

Feel free to contribute and report defects at <https://github.com/ATM-Consulting/dolibarr_module_lead.git>

Other Licences
--------------

Uses [Michel Fortin's PHP Markdown](http://michelf.ca/projets/php-markdown/) Licensed under BSD to display this README in the module's about page.
