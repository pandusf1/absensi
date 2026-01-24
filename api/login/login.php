<?php
// ==========================================
// 1. NYALAKAN PELACAK ERROR (Wajib saat Development)
// ==========================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Cek Path Database
$db_path = __DIR__ . '/../database.php';
if (!file_exists($db_path)) {
    die("<h3>CRITICAL ERROR:</h3> File database.php tidak ditemukan di: " . $db_path);
}

require_once $db_path;

// Cek Koneksi Database ($conn)
if (!isset($conn) || !$conn) {
    die("<h3>DATABASE ERROR:</h3> Variabel <b>\$conn</b> tidak ada atau koneksi gagal. Cek isi file database.php!");
}

// Fungsi Error Cantik
function show_error($text) {
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <style>body { font-family: sans-serif; background: #eee; }</style>
    </head>
    <body>
        <script>
            Swal.fire({
                title: "Login Gagal",
                text: "' . $text . '",
                icon: "error",
                confirmButtonText: "OK"
            }).then(() => {
                window.location = "../index.php";
            });
        </script>
    </body>
    </html>';
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Ambil Input
    $username = mysqli_real_escape_string($conn, $_POST['username']); 
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Debugging: Tampilkan apa yang dikirim (Nanti dihapus kalau sudah fix)
    // echo "Mencari user: " . $username . "<br>"; 

    // ==========================================
    // 2. CEK TABEL MAHASISWA
    // ==========================================
    $query_mhs = "SELECT * FROM data WHERE nim='$username' OR email='$username'";
    $cek_mhs = mysqli_query($conn, $query_mhs);

    // Cek jika query error (misal nama tabel salah)
    if (!$cek_mhs) {
        die("<h3>SQL ERROR (Mahasiswa):</h3> " . mysqli_error($conn));
    }

    if (mysqli_num_rows($cek_mhs) > 0) {
        $row = mysqli_fetch_assoc($cek_mhs);
        
        // Cek Password Hash vs Plain
        // Jika password di database belum di-hash (masih tulisan biasa), password_verify pasti GAGAL.
        // Uncomment baris bawah ini untuk debug hash password:
        // var_dump($password, $row['password'], password_verify($password, $row['password'])); die();

        if (password_verify($password, $row['password'])) {
            // Login Sukses Mahasiswa
            $_SESSION['role'] = 'mahasiswa';
            $_SESSION['nim'] = $row['nim'];
            $_SESSION['nama'] = $row['nama']; // Pastikan kolom 'nama' ada di tabel 'data'
            $_SESSION['status_login'] = true;

            header("Location: ../mahasiswa/mahasiswa_dash.php");
            exit;
        } else {
            show_error('Password Mahasiswa Salah (Atau password di database belum di-encrypt).');
        }
    }

    // ==========================================
    // 3. CEK TABEL DOSEN
    // ==========================================
    $query_dosen = "SELECT * FROM dosen WHERE nip='$username' OR email='$username'";
    $cek_dosen = mysqli_query($conn, $query_dosen);

    // Cek Error Query
    if (!$cek_dosen) {
        die("<h3>SQL ERROR (Dosen):</h3> " . mysqli_error($conn));
    }

    if (mysqli_num_rows($cek_dosen) > 0) {
        $row = mysqli_fetch_assoc($cek_dosen);

        if (password_verify($password, $row['password'])) {
            // Login Sukses Dosen
            $_SESSION['role'] = 'dosen';
            $_SESSION['nip'] = $row['nip'];
            $_SESSION['nama'] = $row['nama_dosen']; // Pastikan kolom ini benar
            $_SESSION['status_login'] = true;

            header("Location: ../dosen/dosen_dash.php");
            exit;
        } else {
            show_error('Password Dosen Salah.');
        }
    }

    // ==========================================
    // 4. JIKA TIDAK DITEMUKAN DI KEDUANYA
    // ==========================================
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            Swal.fire({
                title: "Akun Tidak Ditemukan",
                text: "NIM/NIP tidak terdaftar di database.",
                icon: "warning"
            }).then(() => {
                window.location = "../index.php";
            });
        </script>
    </body>
    </html>';

} else {
    echo "Halaman ini hanya menerima method POST.";
}
?>