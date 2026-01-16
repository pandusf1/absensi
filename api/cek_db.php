<?php
include 'database.php';

echo "<h1>🔍 Diagnosa Database</h1>";
echo "Host: " . $host . "<br>";
echo "Target Database: <b>" . $db . "</b><br><hr>";

// 1. Cek kita sedang konek ke database apa
$result_db = mysqli_query($conn, "SELECT DATABASE()");
$row_db = mysqli_fetch_row($result_db);
echo "Database yang Aktif Sekarang: <b>" . $row_db[0] . "</b><br>";

// 2. Cek Daftar Tabel yang ada di sini
echo "<h3>Daftar Tabel di Database ini:</h3>";
$result_tabel = mysqli_query($conn, "SHOW TABLES");

if (mysqli_num_rows($result_tabel) > 0) {
    echo "<ul>";
    while($row = mysqli_fetch_row($result_tabel)) {
        echo "<li>✅ " . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "❌ <b>TIDAK ADA TABEL SAMA SEKALI!</b> (Database Kosong)<br>";
    echo "Saran: Jalankan script CREATE TABLE lagi di database ini.";
}
?>