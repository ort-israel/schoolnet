<?php

define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(__FILE__))).'/config.php');

if (!$users = $DB->get_records_sql('SELECT * FROM `mdl_user` WHERE username LIKE \'anonymous_%\'') )
{
    echo "Cool! , no redundant users";
}

foreach ($users as $user)
{
    echo "Found username: $user->username , created time = $user->lastaccess <br/>";
}

// e.k - Please note! php NOW() function and mySQL time are not the same! use the FROM_UNIXTIME() function.
if (!$deletedusers = $DB->get_records_sql("DELETE FROM mdl_user WHERE username LIKE 'anonymous_%' AND HOUR( TIMEDIFF( NOW() , FROM_UNIXTIME( lastaccess ) ) ) >=2 ") ) 
{
    echo "Cool! , no redundant users";
}

