<?php
include 'database.php';

// --- BAGIAN 1: PEMROSESAN AJAX (DIJALANKAN SAAT USER MENGETIK) ---
if (isset($_GET['ajax_request'])) {
    
    // Ambil data dari JavaScript
    $keyword   = isset($_GET['keyword']) ? $_GET['keyword'] : '';
    $tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : '';
    $tgl_akhir = isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '';
    $limit     = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
    $halaman   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $mulai_dari = ($halaman > 1) ? ($halaman * $limit) - $limit : 0;

    // --- LOGIKA FILTER (SAMA SEPERTI SEBELUMNYA) ---
    $where_conditions = [];
    
    if (!empty($tgl_mulai) && !empty($tgl_akhir)) {
        $where_conditions[] = "absensi.tanggal BETWEEN '$tgl_mulai' AND '$tgl_akhir'";
    }

    if (!empty($keyword)) {
        $words = explode(" ", $keyword);
        foreach ($words as $word) {
            $safe_word = mysqli_real_escape_string($conn, $word);
            $where_conditions[] = "(data.nama LIKE '%$safe_word%' OR data.nim LIKE '%$safe_word%' OR data.prodi LIKE '%$safe_word%')";
        }
    }

    $sql_where = "";
    if (count($where_conditions) > 0) {
        $sql_where = "WHERE " . implode(" AND ", $where_conditions);
    }

    // --- QUERY DATA ---
    $sql = "SELECT absensi.*, data.nama, data.prodi 
            FROM absensi 
            JOIN data ON absensi.nim = data.nim 
            $sql_where
            ORDER BY absensi.tanggal DESC, absensi.jam_masuk DESC
            LIMIT $mulai_dari, $limit";
            
    $result = mysqli_query($conn, $sql);

    // --- QUERY TOTAL DATA (UNTUK PAGINATION) ---
    $sql_count = "SELECT count(*) as total FROM absensi JOIN data ON absensi.nim = data.nim $sql_where";
    $query_count = mysqli_query($conn, $sql_count);
    $data_count = mysqli_fetch_assoc($query_count);
    $total_records = $data_count['total'];
    $total_halaman = ceil($total_records / $limit);

    // --- OUTPUTKAN HASIL TABEL (INI YANG AKAN DITANGKAP JAVASCRIPT) ---
    $no = $mulai_dari + 1;
    
    if(mysqli_num_rows($result) > 0) {
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
                <td><span class="badge <?= $status_class ?>"><?= $row['status'] ?></span></td>
            </tr>
            <?php
        }
    } else {
        echo '<tr><td colspan="8" style="text-align:center; padding: 40px; color:#a0aec0;">Data tidak ditemukan.</td></tr>';
    }

    // Pisahkan output Data dan Pagination dengan separator khusus (misal: |###|)
    echo "|###|"; 

    // --- OUTPUT PAGINATION (AGAR PAGINATION JUGA UPDATE) ---
    if($total_halaman > 1) {
        echo '<div class="pagination">';
        // Prev
        if($halaman > 1) {
            echo '<a href="#" onclick="gantiHalaman('.($halaman-1).'); return false;" class="page-link">« Prev</a>';
        }
        // Angka
        for($x = 1; $x <= $total_halaman; $x++) { 
            $active = ($x == $halaman) ? 'active' : '';
            echo '<a href="#" onclick="gantiHalaman('.$x.'); return false;" class="page-link '.$active.'">'.$x.'</a>';
        }
        // Next
        if($halaman < $total_halaman) {
            echo '<a href="#" onclick="gantiHalaman('.($halaman+1).'); return false;" class="page-link">Next »</a>';
        }
        echo '</div>';
    }
    
    echo '<div style="text-align:right; margin-top:10px; color:#666; font-size:12px;">Total Data: <b>'.$total_records.'</b></div>';

    // STOP EKSEKUSI DI SINI AGAR TIDAK LOAD HALAMAN HTML DI BAWAHNYA
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Absen Mahasiswa</title>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  
  <style>
    /* --- CSS GLOBAL --- */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", sans-serif; }
    body { background-color: #f5f7fa; color: #1e1e1e; }
    .content { padding-top: 24px; margin-left: 3%; margin-right: 3%; }
    
    /* HEADER & CARD */
    img { width: 100px; margin: 20px auto 10px; display: block; }
    .tengah { text-align: center; margin-bottom: 20px; }
    .cards { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 24px; }
    .card { background: #fff; border-radius: 16px; padding: 20px 24px; flex: 1; min-width: 250px; display: flex; align-items: center; gap: 16px; transition: 0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.08); text-decoration: none; }
    .card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
    .icon { font-size: 32px; width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background-color: #eef5ff; color: #007aff; }
    .card h3 { font-size: 16px; color: #555; margin-bottom: 4px; }
    .card .number { font-size: 22px; font-weight: 700; color: #000; }

    /* TABEL */
    .management { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 2px 5px rgba(0,0,0,0.08); margin: 0 3%; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; min-width: 600px; margin-top: 15px; }
    thead tr { background-color: #f8f9fa; text-align: left; border-bottom: 2px solid #edf2f7; }
    th { padding: 16px; font-weight: 600; font-size: 14px; color: #4a5568; text-transform: uppercase; }
    td { padding: 16px; border-bottom: 1px solid #edf2f7; font-size: 14px; color: #2d3748; vertical-align: middle; }
    tbody tr:hover { background-color: #f2f2f4; }
    
    /* BADGES */
    .badge { padding: 6px 12px; border-radius: 50px; font-weight: 600; font-size: 12px; min-width: 80px; display: inline-block; text-align: center; }
    .badge-hadir { background-color: #c6f6d5; color: #22543d; }
    .badge-telat { background-color: #fed7d7; color: #742a2a; }
    .badge-pulang { background-color: #bee3f8; color: #2c5282; }

    /* FILTER FORM */
    .filter-form { display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px; }
    .search-row { width: 100%; }
    .input-search { width: 100%; padding: 12px 15px; border: 1px solid #cbd5e0; border-radius: 8px; font-size: 14px; background-color: #f8fafc; }
    .input-search:focus { outline: none; border-color: #3182ce; background: #fff; }

    .filter-row { display: flex; flex-wrap: wrap; gap: 15px; align-items: end; }
    .form-group { display: flex; flex-direction: column; gap: 5px; }
    .form-group label { font-size: 14px; font-weight: 600; color: #64748b; }
    .input-date { padding: 10px; border: 1px solid #cbd5e0; border-radius: 8px; font-size: 14px; }

    /* Pagination */
    .pagination { margin-top: 20px; display: flex; justify-content: center; gap: 5px; }
    .page-link { padding: 8px 12px; border: 1px solid #ddd; background: white; color: #333; text-decoration: none; border-radius: 4px; font-size: 14px; }
    .page-link.active { background-color: #3182ce; color: white; border-color: #3182ce; }
    .page-link:hover { background-color: #f1f1f1; }

    /* LOADER (Animasi saat loading data) */
    .loading-overlay {
        display: none; position: absolute; left: 50%; margin-top: 50px; transform: translateX(-50%);
        font-weight: bold; color: #3182ce; background: rgba(255,255,255,0.9); padding: 10px 20px; border-radius: 20px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    /* RESPONSIVE HP */
    @media (max-width: 768px) {
        .filter-row { flex-direction: column; align-items: stretch; gap: 10px; }
        .form-group, .input-date { width: 100%; }
    }
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
        <div><p class="number">Daftar / Update Wajah</p></div>
      </a>
    </div>
  </main>

<section class="management">
    <h2 style="font-family: 'Poppins', sans-serif; margin-bottom: 20px;">
        Rekap Absen Mahasiswa
    </h2>

    <div class="filter-form">
        
        <div class="search-row">
            <div class="form-group">
                <label>Cari:</label>
                <input type="text" id="keyword" class="input-search" 
                       placeholder="Nama / NIM / Prodi" 
                       onkeyup="loadData(1)">
            </div>
        </div>

        <div class="filter-row">
            <div class="form-group">
                <label>Dari Tanggal:</label>
                <input type="date" id="tgl_mulai" class="input-date" onchange="loadData(1)">
            </div>
            
            <div class="form-group">
                <label>Sampai Tanggal:</label>
                <input type="date" id="tgl_akhir" class="input-date" onchange="loadData(1)">
            </div>
        </div>
    </div>    
    
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
        <tbody id="data-body">
            <div id="loader" class="loading-overlay">Memuat Data...</div>
        </tbody>
    </table>
    <div style="margin-left:auto">
        <label>Tampilkan</label>
        <select id="limit" onchange="loadData(1)" style="padding:10px; border-radius:8px; border:1px solid #cbd5e0;">
            <option value="15">15</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
    </div>

    <div id="pagination-area"></div>

</section>

<script>
    // 1. Jalankan saat halaman pertama kali dibuka
    $(document).ready(function(){
        loadData(1);
    });

    // 2. Fungsi Utama: Mengambil Data dari PHP tanpa Reload
    function loadData(page) {
        // Tampilkan loader biar user tau sistem sedang bekerja
        $("#loader").show();
        $("#data-body").css("opacity", "0.5");

        // Ambil nilai dari inputan
        var keyword = $("#keyword").val();
        var tgl_mulai = $("#tgl_mulai").val();
        var tgl_akhir = $("#tgl_akhir").val();
        var limit = $("#limit").val();

        $.ajax({
            url: '', // Request ke file ini sendiri
            type: 'GET',
            data: {
                ajax_request: true, // Penanda bahwa ini request AJAX
                keyword: keyword,
                tgl_mulai: tgl_mulai,
                tgl_akhir: tgl_akhir,
                limit: limit,
                page: page
            },
            success: function(response) {
                // Sembunyikan loader
                $("#loader").hide();
                $("#data-body").css("opacity", "1");

                // Pecah respon (Tabel |###| Pagination)
                var data = response.split("|###|");
                
                // Masukkan data ke HTML
                $("#data-body").html(data[0]);
                $("#pagination-area").html(data[1]);
            }
        });
    }

    // 3. Fungsi untuk tombol Pagination
    function gantiHalaman(page) {
        loadData(page);
    }

    // 4. Fungsi Reset Filter
    function resetFilter() {
        $("#keyword").val('');
        $("#tgl_mulai").val('');
        $("#tgl_akhir").val('');
        $("#limit").val('15');
        loadData(1); // Muat ulang data bersih
    }
</script>

</body>
</html>