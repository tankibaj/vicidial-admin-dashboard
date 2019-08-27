<?php
/**
 * Created by PhpStorm.
 * User: elomelo
 * Date: 24/4/18
 * Time: 5:14 PM
 */

$config = array(
    'driver'    => 'mysqli', // Db driver
    'host'      => 'localhost,
    'database'  => "asterisk",
    'username'  => "admin",
    'password'  => "password",
    'charset'   => 'utf8', // Optional
    'collation' => 'utf8_unicode_ci', // Optional
    'prefix'    => '', // Table prefix, optional
    'options'   => array( // PDO constructor options, optional
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_EMULATE_PREPARES => false,
    ),
);

try{
    new \Pixie\Connection('mysql', $config, 'PATHAODB');
}catch(Exception $e){
    die($e->getMessage());
}

?>