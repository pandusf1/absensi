<?php
require_once __DIR__ . '/../database.php';
date_default_timezone_set('Asia/Jakarta');

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'get_face_descriptor') {
    $nim = $_POST['nim'];
    
    $q = mysqli_query($conn, "SELECT face_descriptor FROM data WHERE nim = '$nim'");
    $d = mysqli_fetch_assoc($q);
    
    if ($d && !empty($d['face_descriptor'])) {
        // Kembalikan string JSON (Array Angka)
        echo $d['face_descriptor']; 
    } else {
        echo "null"; 
    }
}

elseif ($action == 'simpan_absen') {
    $id_jadwal = $_POST['id_jadwal'];
    $nim = $_POST['nim'];
    $tgl = date('Y-m-d');
    $jam_sekarang = date('H:i:s');

    // 1. Ambil Jam Mulai Jadwal dari Database
    $q_jadwal = mysqli_query($conn, "SELECT jam_mulai FROM jadwal WHERE id_jadwal='$id_jadwal'");
    $r_jadwal = mysqli_fetch_assoc($q_jadwal);
    
    // 2. Hitung Selisih Waktu (Dalam Menit)
    $waktu_mulai = strtotime($r_jadwal['jam_mulai']);
    $waktu_absen = strtotime($jam_sekarang);
    $selisih_menit = ($waktu_absen - $waktu_mulai) / 60;

    if ($selisih_menit > 20) {
        $status_fix = 'Terlambat';
    } else {
        $status_fix = 'Hadir';
    }

    $cek = mysqli_query($conn, "SELECT id_presensi FROM presensi_kuliah WHERE id_jadwal='$id_jadwal' AND nim='$nim' AND tanggal='$tgl'");
    
    if (mysqli_num_rows($cek) == 0) {
        // Masukkan status hasil perhitungan ($status_fix)
        $q = "INSERT INTO presensi_kuliah (id_jadwal, nim, tanggal, waktu_hadir, status, koordinat) 
              VALUES ('$id_jadwal', '$nim', '$tgl', '$jam_sekarang', '$status_fix', 'Wajah')";
        
        if (mysqli_query($conn, $q)) {
            echo "Sukses: Status Anda tercatat sebagai " . $status_fix;
        } else {
            echo "Gagal database";
        }
    } else {
        echo "Sudah absen sebelumnya.";
    }
}


if ($action == 'update_face') {
    $nim = $_POST['nim'];
    $descriptor = $_POST['descriptor'];
    
    $descriptor = mysqli_real_escape_string($conn, $descriptor);
    
    $query = "UPDATE data SET face_descriptor = '$descriptor' WHERE nim = '$nim'";
    if(mysqli_query($conn, $query)) {
        echo "Berhasil update wajah!";
    } else {
        echo "Gagal database: " . mysqli_error($conn);
    }
}
?>