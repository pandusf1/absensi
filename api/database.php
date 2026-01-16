<?php
date_default_timezone_set('Asia/Jakarta');

$host = "gateway01.ap-southeast-1.prod.aws.tidbcloud.com";
$user = "3UEpyYnuidk1vBW.root";
$pass = "OvCZ7LN1zbK6Njq2";
$db   = "test";
$port = 4000;

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL); 
mysqli_real_connect($conn, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);

if (mysqli_connect_errno()) {
    die("Gagal Konek Database Cloud: " . mysqli_connect_error());
}
?>