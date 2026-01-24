<?php
session_start();
session_destroy(); // Hapus semua sesi login
echo "<script>window.location='../index.php';</script>";
?>