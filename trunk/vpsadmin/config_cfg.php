<?php
define (PRIV_POORUSER, 1);
define (PRIV_USER, 2);
define (PRIV_POWERUSER, 3);
define (PRIV_ADMIN, 21);
define (PRIV_SUPERADMIN, 90);
define (PRIV_GOD, 99);

$cfg_privlevel[PRIV_USER] = 'User';

$cfg_privlevel[PRIV_POWERUSER] = 'Power User';
$cfg_privlevel[PRIV_ADMIN] = 'Admin';
$cfg_privlevel[PRIV_SUPERADMIN] = 'Super Admin';
$cfg_privlevel[PRIV_GOD] = 'Master Admin';

$cfg_transactions['per_page'] = 25;
$cfg_transactions['max_offset_listing'] = 6;

$langs = array(
  "en" => array(
    "code" => "en",
    "icon" => "us",
    "lang" => "US english"
  )
);
?>
