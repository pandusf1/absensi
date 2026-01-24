<?php
session_start();

// 1. Panggil Database dengan Path yang Aman (__DIR__)
require_once __DIR__ . '/../database.php';

// Fungsi untuk menampilkan pesan error dengan SweetAlert
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
                window.location = "../index.php";
            });
        </script>
    </body>
    </html>';
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitasi input
    $username = mysqli_real_escape_string($conn, $_POST['username']); 
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // ===============================================
    // 1. CEK MAHASISWA (TABEL 'data')
    // ===============================================
    // PENTING: Pakai tanda backtick (`) di sekitar kata data
    $query_mhs = "SELECT * FROM `data` WHERE nim='$username' OR email='$username'";
    $cek_mhs = mysqli_query($conn, $query_mhs);
    
    // Cek error query
    if (!$cek_mhs) {
        die("Error Query Mahasiswa: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($cek_mhs) > 0) {
        $row = mysqli_fetch_assoc($cek_mhs);
        
        if (password_verify($password, $row['password'])) {
            // SET SESSION MAHASISWA
            $_SESSION['role'] = 'mahasiswa';
            $_SESSION['nim'] = $row['nim'];
            $_SESSION['nama'] = $row['nama'];
            $_SESSION['status_login'] = true;

            // REDIRECT KE DASHBOARD MAHASISWA
            header("Location: ../mahasiswa/mahasiswa_dash.php");
            exit;

        } else {
            show_error('Kata sandi Mahasiswa salah.');
        }
    }

    // ===============================================
    // 2. CEK DOSEN (TABEL 'dosen')
    // ===============================================
    $query_dosen = "SELECT * FROM dosen WHERE nip='$username' OR email='$username'";
    $cek_dosen = mysqli_query($conn, $query_dosen);

    if (mysqli_num_rows($cek_dosen) > 0) {
        $row = mysqli_fetch_assoc($cek_dosen);

        if (password_verify($password, $row['password'])) {
            // SET SESSION DOSEN
            $_SESSION['role'] = 'dosen';
            $_SESSION['nip'] = $row['nip'];
            $_SESSION['nama'] = $row['nama_dosen'];
            $_SESSION['status_login'] = true;

            // REDIRECT KE DASHBOARD DOSEN
            header("Location: ../dosen/dosen_dash.php");
            exit;

        } else {
            show_error('Kata sandi Dosen salah.');
        }
    }

    // ===============================================
    // 3. JIKA TIDAK DITEMUKAN
    // ===============================================
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
}
?>