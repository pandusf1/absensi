<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Absen Mahasiswa</title>
  <style>
    * {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: "Poppins", sans-serif;
  scroll-behavior: smooth;
}

body {
  background-color: #f5f7fa;
  color: #1e1e1e;
}
  .content {
  padding-top: 24px;
  margin-left: 3%;
  margin-right: 3%;
}

/* Card container */
.cards {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 24px;
}

.card {
  background: #fff;
  border-radius: 16px;
  padding: 20px 24px;
  flex: 1;
  min-width: 250px;
  display: flex;
  align-items: center;
  gap: 16px;
  transition: 0.3s;
  box-shadow: 0 2px 5px rgba(0,0,0,0.08);
  z-index: 1; /* di bawah navbar */
}

.card:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

.icon {
  font-size: 32px;
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #eef5ff;
  color: #007aff;
}

.card h3 {
  font-size: 16px;
  color: #555;
  margin-bottom: 4px;
}

.card .number {
  font-size: 22px;
  font-weight: 700;
  color: #000;
}

.management {
  background: #fff;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.08);
  min-width: none;
  margin: 0 3%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

#management{
  margin: 3% 3%;
}

.management h2 {
  font-size: 20px;
  margin-bottom: 8px;
  color: #1e1e1e;
}

.management p {
  color: #666;
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
    background-color: #f2f2f4;
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
        .controls-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            margin: 13px 10px;
            border-radius: 12px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .filter-left, .filter-right { display: flex; align-items: center; gap: 10px; }
        
        .input-date, .select-limit {
            padding: 8px 12px;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            font-family: 'Inter', sans-serif;
        }

        .btn-filter {
            padding: 8px 20px;
            background-color: #3182ce;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-reset { color: #e53e3e; font-weight: bold; text-decoration: none; font-size: 14px; }

        /* Style Tombol Pagination Bawah */
        .pagination { margin-top: 20px; display: flex; justify-content: center; gap: 5px; }
        .page-link {
            padding: 8px 12px;
            border: 1px solid #ddd;
            background: white;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .page-link.active { background-color: #3182ce; color: white; border-color: #3182ce; }
        .page-link:hover:not(.active) { background-color: #f0f0f0; }

        img{width: 100px;margin-top:-10px; margin-bottom: 15px; justify-content: center;
        display: flex; align-items: center; margin-left: auto; margin-right: auto; margin-top: 20px;}
        .tengah{text-align: center;}
  </style>
</head>
<body>
    <img src="aset/img/polines.png" alt="Logo Polines">
    <div class="tengah">
    <h2>Selamat Datang di Sistem Absensi</h2>
    <p>Politeknik Negeri Semarang</p>
    </div>

  <main class="content">
    <div class="cards">
      <a href="absensi.php" style="text-decoration:none" class="card">
        <div class="icon">📨</div>
        <div><p class="number">Absensi</p></div>
      </a>

      <a href="daftar_wajah.php" style="text-decoration:none" class="card">
        <div class="icon">🤵‍♂️</div>
        <div><p class="number">Daftar / Update Wajah</p></div>
      </a>
    </main>

<section class="management">
    <?php 
    include 'database.php';    
    // Ambil jumlah record per halaman (Default 15)
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
    
    // Ambil halaman aktif (Default 1)
    $halaman = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    
    // Hitung mulai data (Offset)
    $mulai_dari = ($halaman > 1) ? ($halaman * $limit) - $limit : 0;

    // Logika Tanggal
// Logika Tanggal (Rentang Waktu)
    $where_clause = ""; 
    $param_tgl = ""; 
    $tgl_mulai_val = "";
    $tgl_akhir_val = "";
    
    // Cek apakah user mengisi kedua tanggal
    if(isset($_GET['tgl_mulai']) && isset($_GET['tgl_akhir']) && !empty($_GET['tgl_mulai']) && !empty($_GET['tgl_akhir'])) {
        $mulai = mysqli_real_escape_string($conn, $_GET['tgl_mulai']);
        $akhir = mysqli_real_escape_string($conn, $_GET['tgl_akhir']);
        
        $tgl_mulai_val = $mulai;
        $tgl_akhir_val = $akhir;
        
        // Query BETWEEN untuk rentang tanggal
        $where_clause = "WHERE absensi.tanggal BETWEEN '$mulai' AND '$akhir'";
        
        // Parameter untuk link pagination
        $param_tgl = "&tgl_mulai=".$mulai."&tgl_akhir=".$akhir;
    } else {
        $where_clause = ""; // Tampilkan Semua
    }
    ?>
    <h2 style="font-family: 'Poppins', sans-serif; margin-bottom: 15px; margin-left: 10px;">
        Rekap Absen Mahasiswa
    </h2>

    <div class="controls-wrapper">
        <form method="GET" class="filter-left">
            <label><b>Periode:</b></label>
            <input type="date" name="tgl_mulai" class="input-date" value="<?= $tgl_mulai_val ?>" required>
            <span> s/d </span>
            <input type="date" name="tgl_akhir" class="input-date" value="<?= $tgl_akhir_val ?>" required>
            
            <button type="submit" class="btn-filter">Cari</button>
            <a href="?limit=<?= $limit ?>" class="btn-reset">Reset</a>
            
            <input type="hidden" name="limit" value="<?= $limit ?>">
        </form>    
    </div>
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th>NIM</th>
                    <th>Nama Mahasiswa</th>
                    <th>Tanggal</th>
                    <th>Jam Masuk</th>
                    <th>Jam Keluar</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    // A. Hitung TOTAL data dulu (untuk pagination)
                    $query_count = mysqli_query($conn, "SELECT count(*) as total FROM absensi $where_clause");
                    $data_count = mysqli_fetch_assoc($query_count);
                    $total_records = $data_count['total'];
                    $total_halaman = ceil($total_records / $limit);

                    // B. Ambil Data dengan LIMIT
                    // Note: Saya tambah kolom 'tanggal' di select karena sekarang bisa menampilkan semua history
                    $query = mysqli_query($conn, "SELECT absensi.*, data.nama, data.prodi 
                                                  FROM absensi 
                                                  JOIN data ON absensi.nim = data.nim 
                                                  $where_clause 
                                                  ORDER BY absensi.tanggal DESC, absensi.jam_masuk DESC 
                                                  LIMIT $mulai_dari, $limit");
                    
                    // Penomoran (Melanjutkan nomor antar halaman)
                    $no = $mulai_dari + 1;
                    
                    while($row = mysqli_fetch_array($query)) {
                        $status_class = 'badge-hadir';
                        if($row['status'] == 'Terlambat') { $status_class = 'badge-telat'; }
                        elseif($row['status'] == 'Pulang') { $status_class = 'badge-pulang'; }
                        
                        // Format tanggal Indonesia
                        $tgl_row = date('d/m/Y', strtotime($row['tanggal']));
                ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td>
                        <b><?= $row['nim'] ?></b><br>
                        <small style="color:#718096;"><?= $row['prodi'] ?></small>
                    </td>
                    <td><?= $row['nama'] ?></td>
                    <td><?= $tgl_row ?></td> <td><?= $row['jam_masuk'] ?></td>
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
                        <td colspan="7" style="text-align:center; padding: 40px; color:#a0aec0;">
                            Tidak ada data absensi ditemukan.
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <form method="GET" class="filter-right" style="margin-top:10px;">
            <label><b>Tampilkan:</b></label>
            <select name="limit" class="select-limit" onchange="this.form.submit()">
                <option value="15" <?= $limit == 15 ? 'selected' : '' ?>>15</option>
                <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>25</option>
                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
            </select>
            <input type="hidden" name="tgl_mulai" value="<?= $tgl_mulai_val ?>">
            <input type="hidden" name="tgl_akhir" value="<?= $tgl_akhir_val ?>">
        </form>
    <?php if($total_halaman > 1) { ?>
    <div class="pagination">
        <?php if($halaman > 1) { ?>
            <a href="?page=<?= $halaman - 1 ?>&limit=<?= $limit ?><?= $param_tgl ?>" class="page-link">« Prev</a>
        <?php } ?>

        <?php 
        for($x = 1; $x <= $total_halaman; $x++) { 
            $active = ($x == $halaman) ? 'active' : '';
        ?>
            <a href="?page=<?= $x ?>&limit=<?= $limit ?><?= $param_tgl ?>" class="page-link <?= $active ?>"><?= $x ?></a>
        <?php } ?>

        <?php if($halaman < $total_halaman) { ?>
            <a href="?page=<?= $halaman + 1 ?>&limit=<?= $limit ?><?= $param_tgl ?>" class="page-link">Next »</a>
        <?php } ?>
    </div>
    <div style="text-align:center; margin-top:10px; color:#666; font-size:12px;">
        Total Data: <b><?= $total_records ?></b> | Halaman <?= $halaman ?> dari <?= $total_halaman ?>
    </div>
    <?php } ?>

</section>
</body>
</html>