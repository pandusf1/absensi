<?php
date_default_timezone_set('Asia/Jakarta');

$host     = "sql212.infinityfree.com"; 
$user     = "if0_40977291";            
$password = "UaVB5dbkyADR";       
$dbname   = "if0_40977291_absensi"; 
$PORT    = 3306;   

$conn = mysqli_init();

// Koneksi Standar
$conn = mysqli_connect($host, $user, $password, $dbname);

if (!$conn) {
    die("Gagal Konek Database: " . mysqli_connect_error());
}
?>