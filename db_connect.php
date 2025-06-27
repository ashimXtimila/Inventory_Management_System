<?php
$host='localhost';
$username = 'root';
$password = '';
$dbname='inventory_management_system';

try{
    $pdo = new POD("mysq;:host=$host;dbname=$dbname",$username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PODException $e){
    die("Database connection failed" . $e->getMessage()):


}
?>