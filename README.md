graceful-db-failure - What to do when db connections fail
===================

This file will handle situations when (not if) your database is not available.
It will show some content to your users. If the page has been previously cached by WP Super Cache
that content will be shown. You can specify the full HTML coode that can be shown in case if the cache is not available.

Installation and Configuration
----------
Copy this file (db-error.php) in your wp-content folder
Then configure at least '$mail_to' variable which defaults to admin@yoursite


Quick Download
----------------

Option #1 : Download the package
<br/>
https://github.com/orbisius/graceful-db-failure/archive/master.zip

Extract db-error.php file.
Set the **$mail_to** variable and then upload that to wp-content via FTP.

Option #2 : Command Line
From the command line type

cd wp-content
<br/>
wget https://raw.github.com/orbisius/graceful-db-failure/master/db-error.php
<br/>
pico db-error.php

More info
---------
http://club.orbisius.com/howto/wordpress-tips/fail-gracefully-wordpress-database-goes-away/

Questions/suggestions 
---
should be sent via the github's issue tracker at
https://github.com/orbisius/graceful-db-failure

Slavi Marinov
<a href='http://orbisius.com/' target='_blank' title="WordPress Plugin Development, WordPress Plugins">http://orbisius.com</a> | http://twitter.com/orbisius
