graceful-db-failure
===================

How to handle when WordPress db connections fail

This file will handle situations when (not if) your database is not available.
It will show some content to your users. If the page has been previously cached by WP Super Cache
that content will be shown. You can specify the full HTML coode that can be shown in case if the cache is not available.

// Installation and Configuration
Copy this file (db-error.php) in your wp-content folder
Then configure at least '$mail_to' variable which defaults to admin@yoursite

Fore more information how to use this go to:
http://club.orbisius.com/howto/wordpress-tips/fail-gracefully-wordpress-database-goes-away/

// Questions/suggestions should be sent via the github's issue tracker.
// https://github.com/orbisius/graceful-db-failure

Quick Download
----------------

From the command line

`
cd wp-content

wget https://raw.github.com/orbisius/graceful-db-failure/master/db-error.php
`

Slavi Marinov
<a href='http://orbisius.com/' target='_blank' title="WordPress Plugin Development, WordPress Plugins">http://orbisius.com</a>
