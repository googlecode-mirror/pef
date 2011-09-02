<?php

$str = file_get_contents('paypal.txt');

echo $str;

print_r(unserialize($str));

?>

