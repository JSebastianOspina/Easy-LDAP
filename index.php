<?php
require 'vendor/autoload.php';

use Ospina\EasyLDAP\EasyLDAP;

//prepare LDAP and parse request inputs
$easyLDAP = new EasyLDAP(false, '/../');

$role = 0;
$user = 'yourusername';
$password = 'yourpassword';

//Check if the credentials match
try {
    $result = $easyLDAP->authenticate($user, $password, $role);
} catch (\Exception $e) {
   //Handle logic
}
