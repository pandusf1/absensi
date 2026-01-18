<?php
    include 'database.php';    
    
    // --- 1. SETUP VARIABEL DEFAULT ---
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
    $halaman = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $mulai_dari = ($halaman > 1) ? ($halaman * $limit) - $limit : 0;

    // Ambil input dari URL (Default Kosong)
    $tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : ''; 
    $tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : ''; 
    $keyword   = isset($_GET['keyword']) ? $_GET['keyword'] : '';

    // --- 2. LOGIKA PENCARIAN & FILTER ---
    $where_conditions = [];

    // Filter A: Rentang Tanggal
    if (!empty($tgl_mulai) && !empty($tgl_akhir)) {
        $where_conditions[] = "absensi.tanggal BETWEEN '$tgl_mulai' AND '$tgl_akhir'";
    }

    // Filter B: Keyword (Nama / NIM / Prodi)
    if (!empty($keyword)) {
        $words = explode(" ", $keyword);
        foreach ($words as $word) {
            $safe_word = mysqli_real_escape_string($conn, $word);
            $where_conditions[] = "(data.nama LIKE '%$safe_word%' OR data.nim LIKE '%$safe_word%' OR data.prodi LIKE '%$safe_word%')";
        }
    }

    // Gabungkan filter
    $sql_where = "";
    if (count($where_conditions) > 0) {
        $sql_where = "WHERE " . implode(" AND ", $where_conditions);
    }

    // --- 3. QUERY UTAMA ---
    $sql = "SELECT absensi.*, data.nama, data.prodi 
            FROM absensi 
            JOIN data ON absensi.nim = data.nim 
            $sql_where
            ORDER BY absensi.tanggal DESC, absensi.jam_masuk DESC";

    $sql_limit = $sql . " LIMIT $mulai_dari, $limit";
    $result = mysqli_query($conn, $sql_limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Absen Mahasiswa</title>
  <style>
    /* --- CSS GLOBAL --- */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", sans-serif; }
    body { background-color: #f5f7fa; color: #1e1e1e; scroll-behavior: smooth; /* Smooth Scroll di CSS */ }
    .content { padding-top: 24px; margin-left: 3%; margin-right: 3%; }
    
    /* --- HEADER & CARD --- */
    img { width: 100px; margin: 20px auto 10px; display: block; }
    .tengah { text-align: center; margin-bottom: 20px; }
    
    .cards { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 24px; }
    .card { background: #fff; border-radius: 16px; padding: 20px 24px; flex: 1; min-width: 250px; display: flex; align-items: center; gap: 16px; transition: 0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.08); text-decoration: none; }
    .card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
    .icon { font-size: 32px; width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background-color: #eef5ff; color: #007aff; }
    .card h3 { font-size: 16px; color: #555; margin-bottom: 4px; }
    .card .number { font-size: 22px; font-weight: 700; color: #000; }

    /* --- TABEL & WRAPPER --- */
    .management { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 5px rgba(0,0,0,0.08); margin: 0 3%; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; min-width: 600px; margin-top: 15px; }
    thead tr { background-color: #f8f9fa; text-align: left; border-bottom: 2px solid #edf2f7; }
    th { padding: 16px; font-weight: 600; font-size: 14px; color: #4a5568; text-transform: uppercase; }
    td { padding: 16px; border-bottom: 1px solid #edf2f7; font-size: 14px; color: #2d3748; vertical-align: middle; }
    tbody tr:hover { background-color: #f2f2f4; }

    /* --- BADGES --- */
    .badge { padding: 6px 12px; border-radius: 50px; font-weight: 600; font-size: 12px; min-width: 80px; display: inline-block; text-align: center; }
    .badge-hadir { background-color: #c6f6d5; color: #22543d; }
    .badge-telat { background-color: #fed7d7; color: #742a2a; }
    .badge-pulang { background-color: #bee3f8; color: #2c5282; }

    /* --- FILTER FORM --- */    
    .filter-form { display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px; }
    .search-row { width: 100%; }
    .input-search { width: 100%; padding: 12px 15px; border: 1px solid #cbd5e0; border-radius: 8px; font-size: 14px; background-color: #f8fafc; }
    .input-search:focus { outline: none; border-color: #3182ce; background: #fff; }

    .filter-row { display: flex; flex-wrap: wrap; gap: 15px; align-items: end; }
    .form-group { display: flex; flex-direction: column; gap: 5px; }
    .form-group label { font-size: 14px; font-weight: 600; color: #64748b; }
    
    .input-date { padding: 10px; border: 1px solid #cbd5e0; border-radius: 8px; font-size: 14px; }

    .btn-filter { padding: 10px 24px; background-color: #3182ce; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.2s; }
    .btn-filter:hover { background-color: #2b6cb0; }

    .btn-reset { padding: 10px 15px; background-color: #edf2f7; color: #e53e3e; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px; border: 1px solid #e2e8f0; }
    .btn-reset:hover { background-color: #e2e8f0; }

    /* Pagination */
    .pagination { margin-top: 20px; display: flex; justify-content: center; gap: 5px; }
    .page-link { padding: 8px 12px; border: 1px solid #ddd; background: white; color: #333; text-decoration: none; border-radius: 4px; font-size: 14px; }
    .page-link.active { background-color: #3182ce; color: white; border-color: #3182ce; }
    
  </style>
</head>
<body>
    <img src="aset/img/polines.png" alt="Logo Polines">
    <div class="tengah">
        <h2>Sistem Absensi Mahasiswa</h2>
        <p>Politeknik Negeri Semarang</p>
    </div>

  <main class="content">
    <div class="cards">
      <a href="absensi.php" class="card">
        <div class="icon">📨</div>
        <div><p class="number">Absensi</p></div>
      </a>
      <a href="daftar_wajah.php" class="card">
        <div class="icon">🤵‍♂️</div>
        <div><p class="number">Registrasi Wajah</p></div>
      </a>
    </div>
  </main>

<section class="management" id="hasil-pencarian">
    <h2 style="font-family: 'Poppins', sans-serif; margin-bottom: 20px;">
        Rekap Absen Mahasiswa
    </h2>

    <form method="GET" class="filter-form">
        <div class="search-row">
            <div class="form-group">
                <label>Cari:</label>
                <input type="text" name="keyword" class="input-search" 
                       placeholder="Nama / NIM / Prodi" 
                       value="<?= htmlspecialchars($keyword) ?>">
            </div>
        </div>

        <div class="filter-row">
            <div class="form-group">
                <label>Dari Tanggal:</label>
                <input type="date" name="tgl_mulai" class="input-date" value="<?= $tgl_mulai ?>">
            </div>
            
            <div class="form-group">
                <label>Sampai Tanggal:</label>
                <input type="date" name="tgl_akhir" class="input-date" value="<?= $tgl_akhir ?>">
            </div>

            <button type="submit" class="btn-filter">Cari Data</button>
            <a href="?" class="btn-reset">Reset</a>
            <input type="hidden" name="limit" value="<?= $limit ?>">
        </div>
    </form>    

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th>NIM</th>
                <th>Nama Mahasiswa</th>
                <th>Prodi</th> <th>Tanggal</th>
                <th>Jam Masuk</th>
                <th>Jam Keluar</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                // Hitung Total Data (Pagination)
                $sql_count = "SELECT count(*) as total 
                              FROM absensi 
                              JOIN data ON absensi.nim = data.nim 
                              $sql_where";
                              
                $query_count = mysqli_query($conn, $sql_count);
                $data_count = mysqli_fetch_assoc($query_count);
                $total_records = $data_count['total'];
                $total_halaman = ceil($total_records / $limit);

                // Tampilkan Data
                $no = $mulai_dari + 1;
                while($row = mysqli_fetch_array($result)) {
                    $status_class = 'badge-hadir';
                    if($row['status'] == 'Terlambat') { $status_class = 'badge-telat'; }
                    elseif($row['status'] == 'Pulang') { $status_class = 'badge-pulang'; }
                    
                    $tgl_row = date('d/m/Y', strtotime($row['tanggal']));
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><b><?= $row['nim'] ?></b></td>
                <td><?= $row['nama'] ?></td>
                <td><?= $row['prodi'] ?></td>
                <td><?= $tgl_row ?></td> 
                <td><?= $row['jam_masuk'] ?></td>
                <td><?= ($row['jam_keluar']) ? $row['jam_keluar'] : '--:--' ?></td>
                <td>
                    <span class="badge <?= $status_class ?>"><?= $row['status'] ?></span>
                </td>
            </tr>
            <?php } ?>
            
            <?php if(mysqli_num_rows($result) == 0) { ?>
                <tr>
                    <td colspan="8" style="text-align:center; padding: 40px; color:#a0aec0;">
                        Tidak ada data ditemukan.
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; flex-wrap:wrap;">
        <form method="GET">
            <input type="hidden" name="tgl_mulai" value="<?= $tgl_mulai ?>">
            <input type="hidden" name="tgl_akhir" value="<?= $tgl_akhir ?>">
            <input type="hidden" name="keyword" value="<?= htmlspecialchars($keyword) ?>">
            <label style="font-size:14px;">Tampilkan: </label>
            <select name="limit" onchange="this.form.submit()" style="padding:5px; border-radius:5px;">
                <option value="15" <?= $limit == 15 ? 'selected' : '' ?>>15</option>
                <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
            </select>
        </form>

        <?php 
            $param_url = "&limit=$limit&tgl_mulai=$tgl_mulai&tgl_akhir=$tgl_akhir&keyword=$keyword";
        ?>

        <?php if($total_halaman > 1) { ?>
        <div class="pagination">
            <?php if($halaman > 1) { ?>
                <a href="?page=<?= $halaman - 1 ?><?= $param_url ?>" class="page-link">« Prev</a>
            <?php } ?>

            <?php for($x = 1; $x <= $total_halaman; $x++) { 
                $active = ($x == $halaman) ? 'active' : '';
            ?>
                <a href="?page=<?= $x ?><?= $param_url ?>" class="page-link <?= $active ?>"><?= $x ?></a>
            <?php } ?>

            <?php if($halaman < $total_halaman) { ?>
                <a href="?page=<?= $halaman + 1 ?><?= $param_url ?>" class="page-link">Next »</a>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
    
    <div style="text-align:right; margin-top:10px; color:#666; font-size:12px;">
        Total Data: <b><?= $total_records ?></b>
    </div>

</section>

<script>
    // Cek jika URL memiliki parameter (tanda habis search / klik page 2 dst)
    // window.location.search contohnya: "?keyword=budi&page=2"
    if (window.location.search.length > 1) {
        // Tunggu halaman render sebentar, lalu scroll
        setTimeout(function() {
            var element = document.getElementById("hasil-pencarian");
            if(element) {
                element.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        }, 300); // delay 300ms agar smooth
    }
</script>

</body>
</html>