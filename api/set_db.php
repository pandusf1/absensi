<?php
include 'database.php';

echo "<h2>🛠️ Setup Database Otomatis</h2>";

// --- KUMPULAN PERINTAH SQL ---
$queries = [
    // 1. Buat Tabel Data
    "CREATE TABLE IF NOT EXISTS data (
        nim varchar(20) NOT NULL,
        nama varchar(100) NOT NULL,
        jurusan varchar(50) NOT NULL,
        prodi varchar(50) NOT NULL,
        face_descriptor text NOT NULL,
        PRIMARY KEY (nim)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    // 2. Buat Tabel Absensi
    "CREATE TABLE IF NOT EXISTS absensi (
        id_absen int(11) NOT NULL AUTO_INCREMENT,
        nim varchar(20) DEFAULT NULL,
        tanggal date NOT NULL,
        jam_masuk time DEFAULT NULL,
        jam_keluar time DEFAULT NULL,
        status varchar(50) DEFAULT NULL,
        PRIMARY KEY (id_absen),
        KEY nim (nim),
        CONSTRAINT absensi_ibfk_1 FOREIGN KEY (nim) REFERENCES data (nim) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    // 3. Isi Data Dummy (Pancingan)
    "INSERT IGNORE INTO data (nim, nama, jurusan, prodi, face_descriptor) 
     VALUES ('123', 'Tes User', 'TI', 'Komputer', '[]')",
     
    "INSERT IGNORE INTO absensi (nim, tanggal, jam_masuk, status) 
     VALUES ('123', CURDATE(), '08:00:00', 'Hadir')"
];

// --- JALANKAN SATU PER SATU ---
foreach ($queries as $index => $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "✅ Query ke-" . ($index+1) . " Berhasil!<br>";
    } else {
        echo "❌ Gagal di Query ke-" . ($index+1) . ": " . mysqli_error($conn) . "<br>";
    }
}

echo "<hr><h3>🎉 SELESAI! Sekarang coba buka halaman utama.</h3>";
?>