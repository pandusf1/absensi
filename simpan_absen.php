<?php
include 'database.php';
date_default_timezone_set('Asia/Jakarta');

if (isset($_POST['nim'])) {
    
    $nim  = mysqli_real_escape_string($conn, $_POST['nim']);
    $lat  = isset($_POST['lat']) ? $_POST['lat'] : '-';
    $long = isset($_POST['long']) ? $_POST['long'] : '-';
    
    $tanggal_hari_ini = date('Y-m-d');
    $jam_sekarang     = date('H:i:s');
    $jam_ini          = date('H'); // Ambil jam saja (00-23)

    // --- LOGIKA SAPAAN OTOMATIS ---
    if ($jam_ini >= 3 && $jam_ini < 10) {
        $sapaan = "Selamat Pagi";
    } elseif ($jam_ini >= 10 && $jam_ini < 15) {
        $sapaan = "Selamat Siang";
    } elseif ($jam_ini >= 15 && $jam_ini < 18) {
        $sapaan = "Selamat Sore";
    } else {
        $sapaan = "Selamat Malam";
    }
    // -----------------------------

    // 1. Cek Data Mahasiswa
    $cari_mhs = mysqli_query($conn, "SELECT nama FROM data WHERE nim = '$nim'");
    $data_mhs = mysqli_fetch_assoc($cari_mhs);

    if ($data_mhs) {
        $nama = $data_mhs['nama'];

        // 2. Cek Apakah Sudah Absen Masuk Hari Ini?
        $cek_absen = mysqli_query($conn, "SELECT id_absen FROM absensi WHERE nim = '$nim' AND tanggal = '$tanggal_hari_ini'");

        if (mysqli_num_rows($cek_absen) > 0) {
            echo "⚠️ Halo $nama, Anda SUDAH absen masuk hari ini!";
        } else {
            // 3. INSERT DATA BARU
            $insert = "INSERT INTO absensi (nim, tanggal, jam_masuk, status) 
                       VALUES ('$nim', '$tanggal_hari_ini', '$jam_sekarang', 'Hadir')";
            
            if (mysqli_query($conn, $insert)) {
                // Pakai variabel $sapaan di sini
                echo "✅ $sapaan $nama! Absen Masuk berhasil pukul $jam_sekarang.";
            } else {
                echo "❌ Gagal Absen: " . mysqli_error($conn);
            }
        }
    } else {
        echo "❌ NIM tidak terdaftar di database!";
    }

} else {
    echo "❌ Data tidak lengkap.";
}
?>