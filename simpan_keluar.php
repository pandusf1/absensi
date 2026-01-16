<?php
include 'database.php';
date_default_timezone_set('Asia/Jakarta');

if (isset($_POST['nim'])) {
    
    $nim = mysqli_real_escape_string($conn, $_POST['nim']);
    $tanggal_hari_ini = date('Y-m-d');
    $jam_sekarang     = date('H:i:s');

    // 1. Cari data absen hari ini yg belum pulang
    $cari_absen = mysqli_query($conn, "SELECT id_absen, jam_keluar, jam_masuk FROM absensi WHERE nim = '$nim' AND tanggal = '$tanggal_hari_ini'");
    $data_absen = mysqli_fetch_assoc($cari_absen);

    if ($data_absen) {
        // Cek apakah sudah absen pulang sebelumnya?
        if ($data_absen['jam_keluar'] != NULL && $data_absen['jam_keluar'] != '00:00:00') {
            echo "⚠️ Anda sudah absen pulang hari ini pada jam " . $data_absen['jam_keluar'];
        } else {
            // 2. UPDATE Jam Keluar
            $id_absen = $data_absen['id_absen'];
            
            $update = "UPDATE absensi SET 
                       jam_keluar = '$jam_sekarang',
                       status = 'Pulang'
                       WHERE id_absen = '$id_absen'";
            
            if (mysqli_query($conn, $update)) {
                echo "👋 Hati-hati di jalan! Absen pulang berhasil pada jam $jam_sekarang.";
            } else {
                echo "❌ Gagal Update: " . mysqli_error($conn);
            }
        }
    } else {
        echo "⚠️ Anda belum melakukan Absen Masuk hari ini!";
    }

} else {
    echo "❌ Data tidak lengkap.";
}
?>