<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        /* Container agar tabel bisa discroll di HP */
.table-wrapper {
    width: 100%;
    overflow-x: auto; /* Scroll samping otomatis jika layar kecil */
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05); /* Bayangan halus */
    padding: 20px;
    box-sizing: border-box;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Inter', sans-serif; /* Font isi tabel */
    min-width: 600px; /* Agar tidak gepeng di HP */
}

/* Header Tabel */
thead tr {
    background-color: #f8f9fa;
    text-align: left;
    border-bottom: 2px solid #edf2f7;
}

th {
    padding: 16px;
    font-family: 'Poppins', sans-serif; /* Font Judul Kolom */
    font-weight: 600;
    font-size: 14px;
    color: #4a5568;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Isi Tabel */
td {
    padding: 16px;
    border-bottom: 1px solid #edf2f7;
    font-size: 14px;
    color: #2d3748;
    vertical-align: middle;
}

/* Efek Hover Baris */
tbody tr:hover {
    background-color: #f7fafc;
    transform: scale(1.005); /* Sedikit membesar biar keren */
    transition: 0.2s;
}

/* --- BADGES STATUS (Warna-warni Status) --- */
.badge {
    padding: 6px 12px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 12px;
    text-align: center;
    display: inline-block;
    min-width: 80px;
}

.badge-hadir {
    background-color: #c6f6d5; /* Hijau Muda */
    color: #22543d; /* Hijau Tua */
}

.badge-telat {
    background-color: #fed7d7; /* Merah Muda */
    color: #742a2a; /* Merah Tua */
}

.badge-pulang {
    background-color: #bee3f8; /* Biru Muda */
    color: #2c5282; /* Biru Tua */
}
    </style>
</head>
<body>
    <div class="table-wrapper">
    <h2>📋 Rekap Absensi Hari Ini</h2>
    <br>
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>NIM</th>
                <th>Nama Mahasiswa</th>
                <th>Jam Masuk</th>
                <th>Jam Keluar</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                include 'database.php';
                $tgl = date('Y-m-d');
                
                // JOIN tabel absensi dengan data mahasiswa
                $query = mysqli_query($conn, "SELECT absensi.*, data.nama 
                                              FROM absensi 
                                              JOIN data ON absensi.nim = data.nim 
                                              WHERE absensi.tanggal = '$tgl' 
                                              ORDER BY absensi.jam_masuk DESC");
                
                $no = 1;
                while($row = mysqli_fetch_array($query)) {
                    // Logika warna status
                    $status_class = 'badge-hadir'; // Default hijau
                    if($row['status'] == 'Terlambat') {
                        $status_class = 'badge-telat';
                    } elseif($row['status'] == 'Pulang') {
                        $status_class = 'badge-pulang';
                    }
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><b><?= $row['nim'] ?></b></td>
                <td><?= $row['nama'] ?></td>
                <td><?= $row['jam_masuk'] ?></td>
                <td><?= ($row['jam_keluar']) ? $row['jam_keluar'] : '--:--' ?></td>
                <td>
                    <span class="badge <?= $status_class ?>">
                        <?= $row['status'] ?>
                    </span>
                </td>
            </tr>
            <?php } ?>
            
            <?php if(mysqli_num_rows($query) == 0) { ?>
                <tr>
                    <td colspan="6" style="text-align:center; color:#aaa; padding: 30px;">
                        Belum ada data absensi hari ini.
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>