<?php
// ==========================================
// LOGIN DETEKTIF (MODE DEBUG PENUH)
// ==========================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// 1. Cek Koneksi Database
echo "<h3>1. Cek Koneksi Database...</h3>";
$db_path = __DIR__ . '/../database.php';
if (!file_exists($db_path)) { die("❌ CRITICAL: File database.php hilang!"); }
require_once $db_path;

if (!isset($conn)) { die("❌ CRITICAL: Variabel \$conn tidak ada!"); }
echo "✅ Koneksi Database OK.<br>";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $username = mysqli_real_escape_string($conn, $_POST['username']); 
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    echo "<h3>2. Data Masuk</h3>";
    echo "Username: " . $username . "<br>";
    echo "Password Input: " . $password . "<br>";

    // ===============================================
    // CEK MAHASISWA (TABEL 'data')
    // ===============================================
    echo "<h3>3. Mencari di Tabel Mahasiswa ('data')...</h3>";
    // PAKAI TANDA BACKTICK `data` AGAR AMAN
    $q_mhs = "SELECT * FROM `data` WHERE nim='$username' OR email='$username'";
    $cek_mhs = mysqli_query($conn, $q_mhs);

    if (!$cek_mhs) {
        echo "❌ Error SQL Mahasiswa: " . mysqli_error($conn) . "<br>";
    } else {
        if (mysqli_num_rows($cek_mhs) > 0) {
            echo "✅ Ketemu di Mahasiswa! Cek Password...<br>";
            $row = mysqli_fetch_assoc($cek_mhs);
            if (password_verify($password, $row['password'])) {
                die("<h1>🎉 LOGIN SUKSES (MAHASISWA)</h1>Silakan kembalikan script ke asal.");
            } else {
                echo "❌ Password Salah untuk Mahasiswa.<br>";
            }
        } else {
            echo "ℹ️ Tidak ditemukan di Mahasiswa.<br>";
        }
    }

    // ===============================================
    // CEK DOSEN (TABEL 'dosen')
    // ===============================================
    echo "<h3>4. Mencari di Tabel Dosen...</h3>";
    $q_dosen = "SELECT * FROM dosen WHERE nip='$username' OR email='$username'";
    $cek_dosen = mysqli_query($conn, $q_dosen);

    // INI YANG SERING BIKIN CRASH (Error Query)
    if (!$cek_dosen) {
        die("<h1 style='color:red'>❌ FATAL ERROR SQL DOSEN:</h1>" . mysqli_error($conn));
    }

    if (mysqli_num_rows($cek_dosen) > 0) {
        echo "✅ Ketemu di Tabel Dosen!<br>";
        $row = mysqli_fetch_assoc($cek_dosen);
        
        echo "Hash di Database: " . $row['password'] . "<br>";
        
        // Cek Password
        if (password_verify($password, $row['password'])) {
            echo "<h1>🎉 PASSWORD MATCH! (LOGIN SUKSES)</h1>";
            echo "Session Role diset ke: Dosen<br>";
            // $_SESSION set disini untuk tes
            $_SESSION['role'] = 'dosen';
            $_SESSION['status_login'] = true;
            echo "<a href='../dosen/dosen_dash.php'>👉 Klik Manual ke Dashboard Dosen</a>";
            exit;
        } else {
            die("<h1 style='color:red'>❌ PASSWORD DOSEN SALAH!</h1>Pastikan input '123' (tanpa kutip) dan database berisi hash yang benar.");
        }
    } else {
        echo "❌ NIP Tidak ditemukan di tabel Dosen.<br>";
    }

    echo "<hr><h3>⚠️ KESIMPULAN:</h3> User tidak ditemukan di Mahasiswa maupun Dosen.";

} else {
    echo "Silakan Login dari halaman index.php";
}
?>