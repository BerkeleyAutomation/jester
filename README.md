steps to get jester joke recommender running on local machine (including db)

on rieff:
	- copy /var/www/html/jester4and5 to the local machine
	- do a sql dump and copy that file to local machine:
	  - `mysqldump -u root -p jester4and5 > jester4and5-2014-20-may.dbdump`

on local machine:
	- install php, at least version 5
	  - `cp /etc/php.ini.default /etc/php.ini`
	- install mysql server
	- start mysqlserver. On my Mac, the command is: `mysql.server start`
	- create the database called 'jester4and5':
	  - `mysql -u root -p`
	  - `CREATE DATABASE jester4and5; quit;`
	- `mysql -u root -p jester4and5 < jester4and5-2014-20-may.dbdump`
	- modify the includes/constants.php to have the correct mysql server username and password
	- install PEAR as a PHP package manager
		- mac instructions: http://jason.pureconcepts.net/2012/10/install-pear-pecl-mac-os-x/
		- may need to update /etc/php.ini's include path:
		 include_path=".:/usr/local/pear/share/pear"
	- with PEAR install Mail, Validate
		- `pear install Mail`
		- `pear install channel://pear.php.net/Validate-0.8.5`
	- from jester directory, run `php -S localhost:8000`
	- if you get this error: Warning: mysql_connect(): [2002] No such file or directory
		- ln -s /tmp/mysql.sock to /var/mysql/mysql.sock
		- full instructions here: http://stackoverflow.com/questions/4219970/warning-mysql-connect-2002-no-such-file-or-directory-trying-to-connect-vi
