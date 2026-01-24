<?php
// api/database.php
// Matikan laporan error fatal biar Vercel tidak langsung mematikan proses
error_reporting(0);
mysqli_report(MYSQLI_REPORT_OFF); 
date_default_timezone_set('Asia/Jakarta');

// Kredensial TiDB (Password Baru Kamu)
$host     = "gateway01.ap-southeast-1.prod.aws.tidbcloud.com";
$user     = "3UEpyYnuidk1vBW.root";
$password = "jgOOpNyO9rqRgqcH"; 
$dbname   = "absensi";
$port     = 4000;

$conn = mysqli_init();

// [SANGAT PENTING] Timeout maksimal 5 detik.
// Vercel limitnya 10 detik. Kita set 5 detik agar script sempat lapor error sebelum dibunuh Vercel.
mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
mysqli_options($conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);

// Coba Konek
$is_connected = @mysqli_real_connect($conn, $host, $user, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);

// Jika Gagal...
if (!$is_connected) {
    // Tampilkan pesan error cantik (HTML), BUKAN Error 500
    echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>";
    echo "<h1 style='color:red;'>⚠️ Koneksi Database Tertidur</h1>";
    echo "<p>Database TiDB Serverless sedang dalam mode 'Sleep'.</p>";
    echo "<p><b>Solusi:</b> Silakan <b>Refresh Halaman Ini</b> 2-3 kali sampai database bangun.</p>";
    echo "<hr><small>Error Teknis: " . mysqli_connect_error() . "</small>";
    echo "</div>";
    exit; // Stop script di sini
}
?>