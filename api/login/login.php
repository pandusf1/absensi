<?php
require_once __DIR__ . '/../database.php';

function show_error($text) {
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>body { font-family: sans-serif; background: #f4f6fb; }</style>
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
    
    // Waktu expired cookie (misal: 24 jam)
    $expire = time() + (24 * 60 * 60); 

    // ===============================================
    // 1. CEK MAHASISWA (TABEL 'data')
    // ===============================================
    $q_mhs = "SELECT * FROM `data` WHERE nim='$username' OR email='$username'";
    $cek_mhs = mysqli_query($conn, $q_mhs);

    if (mysqli_num_rows($cek_mhs) > 0) {
        $row = mysqli_fetch_assoc($cek_mhs);
        
        if (password_verify($password, $row['password'])) {
            // [GANTI SESSION DENGAN COOKIE]
            // Simpan data di browser user agar tahan banting di Vercel
            setcookie('role', 'mahasiswa', $expire, '/');
            setcookie('nim', $row['nim'], $expire, '/');
            setcookie('nama', $row['nama'], $expire, '/');
            setcookie('status_login', 'true', $expire, '/');

            header("Location: ../mahasiswa/mahasiswa_dash.php");
            exit;
        } else {
            show_error('Kata sandi Mahasiswa salah.');
        }
    }

    // ===============================================
    // 2. CEK DOSEN (TABEL 'dosen')
    // ===============================================
    $q_dosen = "SELECT * FROM dosen WHERE nip='$username' OR email='$username'";
    $cek_dosen = mysqli_query($conn, $q_dosen);

    if (mysqli_num_rows($cek_dosen) > 0) {
        $row = mysqli_fetch_assoc($cek_dosen);

        if (password_verify($password, $row['password'])) {
            // [GANTI SESSION DENGAN COOKIE]
            setcookie('role', 'dosen', $expire, '/');
            setcookie('nip', $row['nip'], $expire, '/');
            setcookie('nama', $row['nama_dosen'], $expire, '/');
            setcookie('status_login', 'true', $expire, '/');

            header("Location: ../dosen/dosen_dash.php");
            exit;
        } else {
            show_error('Kata sandi Dosen salah.');
        }
    }

    // ===============================================
    // 3. TIDAK KETEMU
    // ===============================================
    show_error('NIM/NIP tidak terdaftar.');
}
?>