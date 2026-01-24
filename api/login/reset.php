<?php
require_once __DIR__ . '/../database.php';
// Fungsi bantuan untuk menampilkan SweetAlert
function show_alert_with_link($title, $text, $icon, $link_url) {
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reset Password</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>body { font-family: "Poppins", sans-serif; background: #f4f6fb; }</style>
    </head>
    <body>
        <script>
            Swal.fire({
                title: "' . $title . '",
                // Tampilkan pesan dan Link Simulasi
                html: "' . $text . '<br><br><a href=\'' . $link_url . '\' style=\'background:#3b82f6; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; font-weight:bold;\'>Klik Disini untuk Ganti Password</a>",
                icon: "' . $icon . '",
                showConfirmButton: false, // User harus klik link tombol di atas
                allowOutsideClick: false
            });
        </script>
    </body>
    </html>';
    exit;
}

// Fungsi Alert Biasa (Error/Warning)
function show_alert_error($title, $text) {
    echo '<!DOCTYPE html>
    <html>
    <head>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>body { font-family: "Poppins", sans-serif; background: #f4f6fb; }</style>
    </head>
    <body>
        <script>
            Swal.fire({ title: "' . $title . '", text: "' . $text . '", icon: "error" }).then(() => { window.location = "../index.php?page=reset"; });
        </script>
    </body>
    </html>';
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // 1. Cek Email di Tabel Mahasiswa
    $q_mhs = mysqli_query($conn, "SELECT email FROM data WHERE email='$email'");
    $is_mhs = mysqli_num_rows($q_mhs) > 0;

    // 2. Cek Email di Tabel Dosen
    $q_dosen = mysqli_query($conn, "SELECT email FROM dosen WHERE email='$email'");
    $is_dosen = mysqli_num_rows($q_dosen) > 0;

    // Jika Email tidak ada di keduanya
    if (!$is_mhs && !$is_dosen) {
        show_alert_error('Gagal!', 'Email tidak terdaftar dalam sistem kami.');
    }

    // Tentukan Role
    $role = $is_mhs ? 'mahasiswa' : 'dosen';

    // 3. Buat Token Unik
    $token = bin2hex(random_bytes(32));

    // 4. Simpan ke Database (Tabel password_resets)
    // Hapus token lama user ini biar bersih
    mysqli_query($conn, "DELETE FROM password_resets WHERE email='$email'");

    // Masukkan token baru
    $query = "INSERT INTO password_resets (email, token, role) VALUES ('$email', '$token', '$role')";

    if (mysqli_query($conn, $query)) {
        // --- SIMULASI KIRIM EMAIL ---
        // Karena di localhost susah kirim email asli, kita tampilkan linknya di layar.
        // Nanti file tujuannya adalah: form_ganti_password.php
        
        $link = "form_reset.php?token=" . $token;
        
        show_alert_with_link(
            'Link Reset Dibuat!', 
            'Karena ini di Localhost, silakan klik tombol di bawah ini untuk mereset password Anda (Simulasi Email):', 
            'success', 
            $link
        );

    } else {
        show_alert_error('Error Database', mysqli_error($conn));
    }
}
?>