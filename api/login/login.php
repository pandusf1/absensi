<?php
// ==========================================
// 1. AKTIFKAN MODE DEBUG (Hanya untuk mencari Error)
// ==========================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cek apakah script berjalan
echo "";

// ==========================================
// 2. PANGGIL DATABASE DENGAN PENGECEKAN
// ==========================================
$path_db = __DIR__ . '/../database.php';

if (!file_exists($path_db)) {
    die("<h2 style='color:red'>CRITICAL ERROR:</h2> File database tidak ditemukan di: <b>$path_db</b>");
}

require_once $path_db;

// Cek apakah variabel $conn tersedia dari database.php
if (!isset($conn)) {
    die("<h2 style='color:red'>CRITICAL ERROR:</h2> Koneksi database berhasil dipanggil, tapi variabel <b>\$conn</b> tidak ada. Cek isi database.php!");
}

// ==========================================
// 3. FUNGSI ERROR (Style Lama Kamu)
// ==========================================
function show_error($text) {
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login Gagal</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>body { font-family: "Poppins", sans-serif; background: #f4f6fb; }</style>
    </head>
    <body>
        <script>
            Swal.fire({
                title: "Gagal Masuk",
                text: "' . $text . '",
                icon: "error",
                confirmButtonText: "Coba Lagi"
            }).then(() => {
                window.location = "../index.php"; // Pastikan path ini benar saat redirect
            });
        </script>
    </body>
    </html>';
    exit;
}

// ==========================================
// 4. LOGIKA LOGIN UTAMA
// ==========================================
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validasi input agar tidak kosong
    if (empty($_POST['username']) || empty($_POST['password'])) {
        show_error('Username atau Password tidak boleh kosong.');
    }

    $username = mysqli_real_escape_string($conn, $_POST['username']); 
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // --- CEK MAHASISWA ---
    $cek_mhs = mysqli_query($conn, "SELECT * FROM data WHERE nim='$username' OR email='$username'");
    
    // Cek Error Query (Penting jika nama tabel salah)
    if (!$cek_mhs) {
        die("Error Query Mahasiswa: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($cek_mhs) > 0) {
        $row = mysqli_fetch_assoc($cek_mhs);
        
        if (password_verify($password, $row['password'])) {
            $_SESSION['role'] = 'mahasiswa';
            $_SESSION['nim'] = $row['nim'];
            $_SESSION['nama'] = $row['nama'];
            $_SESSION['status_login'] = true;

            header("Location: ../mahasiswa/mahasiswa_dash.php");
            exit;
        } else {
            show_error('Kata sandi Mahasiswa salah.');
        }
    }

    // --- CEK DOSEN ---
    $cek_dosen = mysqli_query($conn, "SELECT * FROM dosen WHERE nip='$username' OR email='$username'");

    // Cek Error Query Dosen
    if (!$cek_dosen) {
        die("Error Query Dosen: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($cek_dosen) > 0) {
        $row = mysqli_fetch_assoc($cek_dosen);

        if (password_verify($password, $row['password'])) {
            $_SESSION['role'] = 'dosen';
            $_SESSION['nip'] = $row['nip'];
            $_SESSION['nama'] = $row['nama_dosen'];
            $_SESSION['status_login'] = true;

            header("Location: ../dosen/dosen_dash.php");
            exit;
        } else {
            show_error('Kata sandi Dosen salah.');
        }
    }

    // --- JIKA TIDAK DITEMUKAN ---
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            Swal.fire({
                title: "Tidak Ditemukan",
                text: "NIM/NIP tidak terdaftar.",
                icon: "warning"
            }).then(() => {
                window.location = "../index.php";
            });
        </script>
    </body>
    </html>';
} else {
    // Jika file ini dibuka langsung tanpa POST
    echo "Halaman Login API Ready. Silakan submit form dari index.php";
}
?>