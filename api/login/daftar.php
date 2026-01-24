<?php
include '../database.php'; // Pastikan koneksi database benar

// Fungsi bantuan untuk menampilkan SweetAlert & Redirect
function show_alert($title, $text, $icon, $redirect) {
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Proses Pendaftaran</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>body { font-family: "Poppins", sans-serif; background: #f4f6fb; }</style>
    </head>
    <body>
        <script>
            Swal.fire({
                title: "' . $title . '",
                text: "' . $text . '",
                icon: "' . $icon . '",
                // Timer dihapus untuk error agar user sempat membaca pesan
                // Kecuali success, kita kasih timer
                ' . ($icon == 'success' ? 'timer: 1500, showConfirmButton: false' : 'showConfirmButton: true') . '
            }).then(() => {
                window.location = "' . $redirect . '";
            });
        </script>
    </body>
    </html>';
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $role = $_POST['role'];
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // 1. Enkripsi Password (WAJIB)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 2. Generate Token Verifikasi
    $token = bin2hex(random_bytes(32)); 

    if ($role == 'mahasiswa') {
        $nim = mysqli_real_escape_string($conn, $_POST['nim']);
        $nama = mysqli_real_escape_string($conn, $_POST['nama']);
        $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
        $jurusan = mysqli_real_escape_string($conn, $_POST['jurusan']);
        $prodi = mysqli_real_escape_string($conn, $_POST['prodi']);

        // Cek apakah NIM atau Email sudah ada?
        $cek = mysqli_query($conn, "SELECT nim FROM data WHERE nim='$nim' OR email='$email'");
        if (mysqli_num_rows($cek) > 0) {
            show_alert('Gagal!', 'NIM atau Email sudah terdaftar!', 'warning', '../index.php?page=daftar_mhs');
        }

        // Simpan ke Tabel Data
        $query = "INSERT INTO data (nim, nama, kelas, jurusan, prodi, email, password, remember_token) 
                  VALUES ('$nim', '$nama', '$kelas', '$jurusan', '$prodi', '$email', '$hashed_password', '$token')";
        
    } elseif ($role == 'dosen') {
        $nip = mysqli_real_escape_string($conn, $_POST['nip']);
        $nama = mysqli_real_escape_string($conn, $_POST['nama_dosen']);
        $jabatan = mysqli_real_escape_string($conn, $_POST['jabatan']);

        // Cek NIP/Email
        $cek = mysqli_query($conn, "SELECT nip FROM dosen WHERE nip='$nip' OR email='$email'");
        if (mysqli_num_rows($cek) > 0) {
            show_alert('Gagal!', 'NIP atau Email sudah terdaftar!', 'warning', '../index.php?page=daftar_dosen');
        }

        // Simpan ke Tabel Dosen
        $query = "INSERT INTO dosen (nip, nama_dosen, jabatan, email, password, remember_token) 
                  VALUES ('$nip', '$nama', '$jabatan', '$email', '$hashed_password', '$token')";
    }

    // Eksekusi Query
    if (mysqli_query($conn, $query)) {
        // Pendaftaran Berhasil
        show_alert('Berhasil!', 'Pendaftaran Akun Berhasil! Silakan Login.', 'success', '../index.php?page=home');
    } else {
        // Gagal Database
        $error_msg = mysqli_error($conn);
        show_alert('Error Sistem', 'Terjadi kesalahan database: ' . $error_msg, 'error', '../index.php?page=pilih_peran');
    }
}
?>