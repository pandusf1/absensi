<?php
session_start();
require_once __DIR__ . '/../database.php';
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
    
    $username = mysqli_real_escape_string($conn, $_POST['username']); 
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $cek_mhs = mysqli_query($conn, "SELECT * FROM data WHERE nim='$username' OR email='$username'");
    
    if (mysqli_num_rows($cek_mhs) > 0) {
        $row = mysqli_fetch_assoc($cek_mhs);
        
        if (password_verify($password, $row['password'])) {
            // SET SESSION
            $_SESSION['role'] = 'mahasiswa';
            $_SESSION['nim'] = $row['nim'];
            $_SESSION['nama'] = $row['nama'];
            $_SESSION['status_login'] = true;

            // [UBAH DISINI] LANGSUNG REDIRECT TANPA ALERT
            header("Location: ../mahasiswa/mahasiswa_dash.php");
            exit;

        } else {
            show_error('Kata sandi Mahasiswa salah.');
        }
    }

    $cek_dosen = mysqli_query($conn, "SELECT * FROM dosen WHERE nip='$username' OR email='$username'");

    if (mysqli_num_rows($cek_dosen) > 0) {
        $row = mysqli_fetch_assoc($cek_dosen);

        if (password_verify($password, $row['password'])) {
            // SET SESSION
            $_SESSION['role'] = 'dosen';
            $_SESSION['nip'] = $row['nip'];
            $_SESSION['nama'] = $row['nama_dosen'];
            $_SESSION['status_login'] = true;

            // [UBAH DISINI] LANGSUNG REDIRECT TANPA ALERT
            header("Location: ../dosen/dosen_dash.php");
            exit;

        } else {
            show_error('Kata sandi Dosen salah.');
        }
    }

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