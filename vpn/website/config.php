<?php

/**
 * @author henry
 * @copyright 2011
 */
include 'Zend/Db.php';
try{
    $params = array('host'=>'localhost','username'=>'radius','password'=>'radius','dbname'=>'radius');
    $db = Zend_Db::factory('Pdo_Mysql',$params);
    $db->query("set names utf8");
}catch(Zend_Db_Adapter_Exception $e){
    //print $e;
}

?>