<?php
include 'database.php';

// Pastikan semua parameter terkirim
if (isset($_POST['nim']) && isset($_POST['nama']) && isset($_POST['descriptor'])) {
    
    // Sanitasi Input
    $nim      = mysqli_real_escape_string($conn, $_POST['nim']);
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $jurusan  = mysqli_real_escape_string($conn, $_POST['jurusan']);
    $prodi    = mysqli_real_escape_string($conn, $_POST['prodi']);
    $desc     = mysqli_real_escape_string($conn, $_POST['descriptor']);

    // 1. CEK APAKAH NIM SUDAH ADA?
    $cek = mysqli_query($conn, "SELECT nim FROM data WHERE nim = '$nim'");
    
    if (mysqli_num_rows($cek) > 0) {
        // --- JIKA SUDAH ADA -> UPDATE (TIMPA DATA LAMA) ---
        $query = "UPDATE data SET 
                  nama = '$nama',
                  jurusan = '$jurusan',
                  prodi = '$prodi',
                  face_descriptor = '$desc'
                  WHERE nim = '$nim'";
        
        if (mysqli_query($conn, $query)) {
            echo "BERHASIL UPDATE! Data wajah mahasiswa $nama ($nim) telah diperbarui.";
        } else {
            echo "Gagal Update: " . mysqli_error($conn);
        }
    
    } else {
        // --- JIKA BELUM ADA -> INSERT (BUAT BARU) ---
        $query = "INSERT INTO data (nim, nama, jurusan, prodi, face_descriptor) 
                  VALUES ('$nim', '$nama', '$jurusan', '$prodi', '$desc')";
        
        if (mysqli_query($conn, $query)) {
            echo "BERHASIL SIMPAN! Mahasiswa baru $nama ($nim) telah didaftarkan.";
        } else {
            echo "Gagal Simpan: " . mysqli_error($conn);
        }
    }

} else {
    echo "Data tidak lengkap dikirim dari browser.";
}
?>