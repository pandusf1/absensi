<?php
include '../database.php';

// --- FUNGSI ALERT SERAGAM ---
function show_alert($title, $text, $icon, $redirect) {
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Proses Ganti Password</title>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>body { font-family: "Arial", sans-serif; background: #f4f6fb; }</style>
    </head>
    <body>
        <script>
            Swal.fire({
                title: "' . $title . '",
                text: "' . $text . '",
                icon: "' . $icon . '",
                ' . ($icon == 'success' ? 'timer: 2000, showConfirmButton: false' : 'showConfirmButton: true') . '
            }).then(() => {
                window.location = "' . $redirect . '";
            });
        </script>
    </body>
    </html>';
    exit;
}

// 1. AMBIL TOKEN DARI URL
if (!isset($_GET['token']) && !isset($_POST['token'])) {
    show_alert('Error', 'Token tidak ditemukan!', 'error', '../index.php');
}

$token = $_GET['token'] ?? $_POST['token'];

// 2. CEK VALIDITAS TOKEN DI DATABASE
$cek_token = mysqli_query($conn, "SELECT * FROM password_resets WHERE token='$token'");
if (mysqli_num_rows($cek_token) == 0) {
    show_alert('Expired', 'Link reset password sudah tidak berlaku atau salah.', 'error', '../index.php?page=reset');
}

// Ambil data dari token untuk dipakai update nanti
$data_reset = mysqli_fetch_assoc($cek_token);
$email_user = $data_reset['email'];
$role_user  = $data_reset['role'];

// 3. PROSES SIMPAN PASSWORD BARU (JIKA TOMBOL DIKLIK)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $pass_baru = $_POST['password'];
    $pass_konf = $_POST['k_password'];

    // Cek kecocokan password
    if ($pass_baru !== $pass_konf) {
        // Balik ke form ini lagi tapi bawa tokennya
        show_alert('Gagal', 'Konfirmasi kata sandi tidak cocok!', 'warning', 'form_reset.php?token=' . $token);
    }

    // Hash Password Baru
    $hashed_password = password_hash($pass_baru, PASSWORD_DEFAULT);

    // Update Password di Tabel Utama (Sesuai Role)
    if ($role_user == 'mahasiswa') {
        $update = mysqli_query($conn, "UPDATE data SET password='$hashed_password' WHERE email='$email_user'");
    } else {
        $update = mysqli_query($conn, "UPDATE dosen SET password='$hashed_password' WHERE email='$email_user'");
    }

    if ($update) {
        // Hapus Token agar tidak bisa dipakai lagi (One Time Use)
        mysqli_query($conn, "DELETE FROM password_resets WHERE email='$email_user'");
        
        show_alert('Berhasil!', 'Kata sandi telah diperbarui. Silakan Login.', 'success', '../index.php');
    } else {
        show_alert('Error', 'Gagal mengupdate database.', 'error', '../index.php');
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Kata Sandi</title>
    <style>
      /* STYLE SAMA PERSIS DENGAN INDEX.PHP */
      *{box-sizing:border-box;font-family:Arial, Helvetica, sans-serif}
      body{margin:0;background:#f4f6fb}
      .container{max-width:500px;margin:80px auto;padding:0 20px}
      .card{
        background:#fff;
        border-radius:16px;
        padding:32px;
        box-shadow:0 10px 30px rgba(0,0,0,.08);
        text-align: center;
      }
      h2{margin-top:0; margin-bottom: 10px;}
      p {color:#666; font-size:14px; margin-bottom:20px;}
      
      input, button{
        width:100%;
        padding:12px;
        border-radius:8px;
        border:1px solid #ddd;
        margin-bottom:14px;
      }
      button{
        background:#2563eb;
        color:#fff;
        border:none;
        font-weight:bold;
        cursor:pointer;
      }
      button:hover{opacity:.9}
      .link{text-align:center;font-size:14px; margin-top:10px;}
      .link a{text-decoration:none;color:#2563eb}
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <h2>Buat Kata Sandi Baru</h2>
        <p>Silakan masukkan kata sandi baru untuk akun <b><?= $email_user ?></b></p>
        
        <form action="" method="POST">
            <input type="hidden" name="token" value="<?= $token ?>">
            
            <input type="password" name="password" placeholder="Kata Sandi Baru" required>
            <input type="password" name="k_password" placeholder="Konfirmasi Kata Sandi Baru" required>
            
            <button type="submit">Simpan Perubahan</button>
        </form>

        <div class="link"><a href="../index.php">Batal</a></div>
    </div>
</div>

</body>
</html>