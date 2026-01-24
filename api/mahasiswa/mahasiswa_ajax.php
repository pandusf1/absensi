<?php
// Include koneksi database (naik satu folder)
require_once __DIR__ . '/../database.php';
date_default_timezone_set('Asia/Jakarta');

// Ambil action dari request AJAX
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'simpan_absen') {
    $id_jadwal = $_POST['id_jadwal'];
    $nim       = $_POST['nim'];
    
    $tgl_ini   = date('Y-m-d');
    $jam_ini   = date('H:i:s');

    // --- VALIDASI 1: CEK DUPLIKASI ---
    // Pastikan mahasiswa belum absen di jadwal & tanggal yang sama
    $cek_duplikat = mysqli_query($conn, "SELECT id_presensi FROM presensi_kuliah 
                                         WHERE id_jadwal = '$id_jadwal' 
                                         AND nim = '$nim' 
                                         AND tanggal = '$tgl_ini'");

    if (mysqli_num_rows($cek_duplikat) > 0) {
        // Jika sudah ada, jangan insert lagi (untuk mencegah double data jika Face API mendeteksi berkali-kali)
        echo "Anda sudah absen sebelumnya.";
    } else {
        // --- PROSES INSERT ---
        // Status otomatis 'Hadir' karena lewat verifikasi wajah
        // Koordinat kita isi 'Face-API' sebagai penanda metode absen
        $query = "INSERT INTO presensi_kuliah 
                  (id_jadwal, nim, tanggal, waktu_hadir, status, koordinat) 
                  VALUES 
                  ('$id_jadwal', '$nim', '$tgl_ini', '$jam_ini', 'Hadir', 'Face-API')";

        if (mysqli_query($conn, $query)) {
            echo "Berhasil! Presensi tercatat.";
        } else {
            echo "Gagal: " . mysqli_error($conn);
        }
    }
}

if ($action == 'get_face_descriptor') {
    $nim = $_POST['nim'];
    
    $q = mysqli_query($conn, "SELECT face_descriptor FROM data WHERE nim = '$nim'");
    $d = mysqli_fetch_assoc($q);
    
    if ($d && !empty($d['face_descriptor'])) {
        // Kembalikan string JSON (Array Angka)
        echo $d['face_descriptor']; 
    } else {
        echo "null"; // Belum ada data wajah
    }
}

if ($action == 'update_face') {
    $nim = $_POST['nim'];
    $descriptor = $_POST['descriptor']; // Ini string JSON array
    
    // Pastikan aman dari SQL Injection
    $descriptor = mysqli_real_escape_string($conn, $descriptor);
    
    $query = "UPDATE data SET face_descriptor = '$descriptor' WHERE nim = '$nim'";
    if(mysqli_query($conn, $query)) {
        echo "Berhasil update wajah!";
    } else {
        echo "Gagal database: " . mysqli_error($conn);
    }
}
?>