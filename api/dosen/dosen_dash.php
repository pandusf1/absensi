<?php
session_start(); 
require_once __DIR__ . '/../database.php';
date_default_timezone_set('Asia/Jakarta');

// --- CEK LOGIN DOSEN ---
if (!isset($_SESSION['status_login']) || $_SESSION['role'] != 'dosen') {
    header("Location: ../index.php"); 
    exit;
}

$nip_dosen = $_SESSION['nip']; 
$nama_dosen_session = $_SESSION['nama']; 

// Ambil Data Profil
$q_profil = mysqli_query($conn, "SELECT * FROM dosen WHERE nip = '$nip_dosen'");
$dosen = mysqli_fetch_assoc($q_profil);

// Setup Waktu
$hari_inggris = date('l');
$map_hari = ['Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'];
$hari_ini = $map_hari[$hari_inggris];
$tgl_ini = date('Y-m-d');
$jam_sekarang = date('H:i:s');
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$swal_script = "";

if(isset($_POST['tambah_matkul'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $sks  = mysqli_real_escape_string($conn, $_POST['sks']);
    
    // Auto Generate Kode (Global Uniqueness - Biar tidak bentrok antar dosen)
    $words = explode(" ", strtoupper($nama));
    $inisial = "";
    foreach ($words as $w) { $inisial .= substr($w, 0, 1); }
    if(strlen($inisial) > 4) { $inisial = substr($inisial, 0, 4); }

    $q_cek = mysqli_query($conn, "SELECT kode_matkul FROM matkul WHERE kode_matkul LIKE '$inisial%' ORDER BY kode_matkul DESC LIMIT 1");
    
    if(mysqli_num_rows($q_cek) > 0) {
        $data = mysqli_fetch_assoc($q_cek);
        $last_no = (int) substr($data['kode_matkul'], strlen($inisial)); 
        $next_no = $last_no + 1;
    } else {
        $next_no = 1;
    }

    $kode_baru = $inisial . sprintf("%03s", $next_no);

    // Simpan dengan NIP Dosen (Agar jadi hak milik)
    // Pastikan kamu sudah update database tambah kolom NIP di tabel MATKUL
    $q_simpan = "INSERT INTO matkul (kode_matkul, nama_matkul, sks, nip) VALUES ('$kode_baru', '$nama', '$sks', '$nip_dosen')";
    
    if(mysqli_query($conn, $q_simpan)) {
        $swal_script = "Swal.fire({title:'Berhasil!', html: 'Matkul Ditambahkan.<br>Kode: <b>$kode_baru</b>', icon:'success', timer:2000, showConfirmButton:false}).then(() => { window.location='?page=matkul'; });";
    } else {
        $swal_script = "Swal.fire('Gagal', 'Terjadi kesalahan sistem: " . mysqli_error($conn) . "', 'error');";
    }
}

if(isset($_POST['simpan_jadwal'])) {
    $kode = $_POST['kode_matkul']; $hari = $_POST['hari']; 
    $jam_m = $_POST['jam_mulai']; $jam_s = $_POST['jam_selesai'];
    $ruang = $_POST['ruang']; $kelas = $_POST['kelas']; $kuota = $_POST['kuota'];

    if($jam_m >= $jam_s) {
        $swal_script = "Swal.fire('Jam Error', 'Jam mulai tidak boleh melebihi jam selesai.', 'warning');";
    } else {
        $cek_dosen = mysqli_query($conn, "SELECT * FROM jadwal WHERE nip = '$nip_dosen' AND hari = '$hari' AND ('$jam_m' < jam_selesai AND '$jam_s' > jam_mulai)");
        $cek_ruang = mysqli_query($conn, "SELECT * FROM jadwal WHERE ruang = '$ruang' AND hari = '$hari' AND ('$jam_m' < jam_selesai AND '$jam_s' > jam_mulai)");
        $cek_kelas = mysqli_query($conn, "SELECT * FROM jadwal WHERE kelas = '$kelas' AND hari = '$hari' AND ('$jam_m' < jam_selesai AND '$jam_s' > jam_mulai)");

        if(mysqli_num_rows($cek_dosen) > 0) {
            $dt = mysqli_fetch_assoc($cek_dosen);
            $swal_script = "Swal.fire('Jadwal Bentrok!', 'Anda sudah mengajar matkul <b>" . $dt['kode_matkul'] . "</b> di jam tersebut.', 'error');";
        } elseif(mysqli_num_rows($cek_ruang) > 0) {
            $dt = mysqli_fetch_assoc($cek_ruang);
            $swal_script = "Swal.fire('Ruangan Penuh!', 'Ruang <b>$ruang</b> sedang dipakai matkul <b>" . $dt['kode_matkul'] . "</b> oleh dosen lain.', 'error');";
        } elseif(mysqli_num_rows($cek_kelas) > 0) {
            $dt = mysqli_fetch_assoc($cek_kelas);
            $swal_script = "Swal.fire('Kelas Sibuk!', 'Kelas <b>$kelas</b> sedang ada kuliah <b>" . $dt['kode_matkul'] . "</b>.', 'error');";
        } else {
            $q_ins = "INSERT INTO jadwal (kode_matkul, hari, jam_mulai, jam_selesai, ruang, kelas, nip, kuota) VALUES ('$kode', '$hari', '$jam_m', '$jam_s', '$ruang', '$kelas', '$nip_dosen', '$kuota')";
            if(mysqli_query($conn, $q_ins)) { 
                $swal_script = "Swal.fire({title:'Berhasil!', text:'Jadwal Disimpan', icon:'success', timer:1500, showConfirmButton:false}).then(() => { window.location='?page=jadwal'; });";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen</title>
    <script src="../aset/js/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* CSS SAMA PERSIS */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; min-height: 100vh; overflow-x: hidden; font-size: 12px; }
        .sidebar { width: 240px; background: #0f172a; color: white; position: fixed; height: 100vh; left: -240px; top: 0; z-index: 2000; transition: 0.3s; box-shadow: 4px 0 15px rgba(0,0,0,0.1); }
        .sidebar.active { left: 0; }
        .sidebar-header { padding: 20px 15px; text-align: center; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; }
        .menu { list-style: none; }
        .menu li a { display: flex; align-items: center; padding: 12px 20px; color: #94a3b8; text-decoration: none; transition: 0.2s; gap: 10px; font-size: 12px; }
        .menu li a:hover, .menu li a.active { background-color: #3b82f6; color: white; border-left: 3px solid white; }
        .main-content { flex: 1; margin-left: 0; padding: 15px; width: 100%; transition: 0.3s; }
        .top-bar { display: flex; align-items: center; margin-bottom: 20px; background: white; padding: 10px 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); gap: 15px; }
        .btn-burger { background: none; border: none; font-size: 20px; cursor: pointer; color: #333; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-bottom: 3px solid #3b82f6; }
        .stat-card h3 { font-size: 28px; color: #333; margin-bottom: 2px; } 
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px 8px; border-bottom: 1px solid #eee; text-align: left; font-size: 12px; }
        th { background: #f8fafc; color: #64748b; font-weight: 600; }
        .btn { padding: 6px 12px; border-radius: 5px; border: none; cursor: pointer; font-weight: 600; font-size: 11px; color: white; }
        .btn-green { background: #22c55e; } .btn-red { background: #ef4444; } .btn-blue { background: #3b82f6; } .btn-disabled { background: #cbd5e1; cursor: not-allowed; color: #64748b; }
        .input-form { width:100%; padding: 6px; border:1px solid #ddd; border-radius:4px; box-sizing:border-box; font-size: 12px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2500; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 25px; border-radius: 10px; width: 90%; max-width: 450px; text-align: center; }
        .overlay-sidebar { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1500; }
        .overlay-sidebar.active { display: block; }
        .swal2-container { z-index: 10000 !important}
        @media (max-width: 768px) { .sidebar { display: none; } .main-content { margin-left: 0; } }
    </style>
</head>
<body>

    <div class="overlay-sidebar" onclick="toggleSidebar()"></div>

    <nav class="sidebar" id="mySidebar">
        <div class="sidebar-header">
            <div style="text-align: left;">
                <img src="../aset/img/polines.png" alt="Logo Polines" style="width: 35px; vertical-align: middle; margin-right: 5px;">
                <div style="display:inline-block; vertical-align: middle;">
                    <h3 style="margin:0; font-size:16px;">PORTAL DOSEN</h3>
                    <small style="font-size:10px;">Sistem Akademik</small>
                </div>
            </div>
        </div>
        <ul class="menu">
            <li><a href="?page=home" class="<?= $page=='home'?'active':'' ?>">Dashboard</a></li>
            <li><a href="?page=matkul" class="<?= $page=='matkul'?'active':'' ?>">Mata Kuliah</a></li>
            <li><a href="?page=jadwal" class="<?= $page=='jadwal'?'active':'' ?>">Jadwal Mengajar</a></li>
            <li><a href="?page=rekap" class="<?= $page=='rekap'?'active':'' ?>">Rekap Presensi</a></li>
            <li><a href="../login/logout.php" style="color:#ef4444;">Keluar</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <div class="top-bar">
            <button class="btn-burger" onclick="toggleSidebar()">☰</button>
            <h3 style="margin:0; color:#333;">
                <?php 
                    if($page=='home') echo 'Dashboard Overview';
                    elseif($page=='matkul') echo 'Manajemen Mata Kuliah';
                    elseif($page=='jadwal') echo 'Jadwal & Kontrol Kelas';
                    elseif($page=='rekap') echo 'Laporan Presensi';
                ?>
            </h3>
        </div>        

        <?php if ($page == 'home'): ?>
            <h2 style="margin-bottom:15px;">Ringkasan Kinerja</h2>
            <?php
            // Update Query: Filter matkul hanya yang diajar dosen ini
            $q1 = mysqli_query($conn, "SELECT COUNT(*) as total FROM matkul WHERE nip='$nip_dosen'"); 
            $d1 = mysqli_fetch_assoc($q1);
            
            $q2 = mysqli_query($conn, "SELECT SUM(m.sks) as total FROM jadwal j JOIN matkul m ON j.kode_matkul = m.kode_matkul WHERE j.nip='$nip_dosen'"); $d2 = mysqli_fetch_assoc($q2);
            $q3 = mysqli_query($conn, "SELECT COUNT(*) as total FROM realisasi_mengajar r JOIN jadwal j ON r.id_jadwal = j.id_jadwal WHERE j.nip='$nip_dosen' AND r.status='Selesai'"); $d3 = mysqli_fetch_assoc($q3);
            ?>
            <div class="dashboard-grid">
                <div class="stat-card"><h3><?= $d1['total'] ?? 0 ?></h3><p>Matkul Milik Anda</p></div>
                <div class="stat-card" style="border-color: #8b5cf6;"><h3><?= $d2['total'] ?? 0 ?></h3><p>Total SKS Ajar</p></div>
                <div class="stat-card" style="border-color: #f59e0b;"><h3><?= $d3['total'] ?? 0 ?></h3><p>Total Pertemuan</p></div>
            </div>
            <div class="card">
                <h3 style="margin-bottom:10px; border-bottom:1px solid #eee; padding-bottom:5px;">Profil Dosen</h3>
                <table style="width: 100%; max-width: 500px;">
                    <tr><td width="120" style="font-weight:bold;">NIP</td><td><?= $dosen['nip'] ?></td></tr>
                    <tr><td style="font-weight:bold;">Nama Lengkap</td><td><?= $dosen['nama_dosen'] ?></td></tr>
                    <tr><td style="font-weight:bold;">Jabatan</td><td><?= $dosen['jabatan'] ?></td></tr>
                </table>
            </div>

        <?php elseif ($page == 'matkul'): ?>
            <div class="card" style="margin-top:15px;">
                <h4 style="margin-bottom:10px;">+ Tambah Mata Kuliah Baru</h4>
                <form method="POST" style="display:flex; gap:8px; margin-bottom:15px; flex-wrap:wrap;">
                    <input type="text" name="nama" placeholder="Nama Mata Kuliah (Contoh: Pemrograman Web)" required style="flex:1; padding:8px; border:1px solid #ddd; border-radius:5px;">
                    <input type="number" name="sks" placeholder="SKS" required style="width:80px; padding:8px; border:1px solid #ddd; border-radius:5px;">
                    <button type="submit" name="tambah_matkul" class="btn btn-blue">Simpan</button>
                </form>
                <table>
                    <thead><tr><th width="120">Kode</th><th>Nama Mata Kuliah</th><th>SKS</th></tr></thead>
                    <tbody>
                        <?php 
                        // FILTER: HANYA TAMPILKAN MATKUL MILIK DOSEN LOGIN (nip = $nip_dosen)
                        $qm = mysqli_query($conn, "SELECT * FROM matkul WHERE nip='$nip_dosen' ORDER BY kode_matkul ASC"); 
                        
                        if(mysqli_num_rows($qm) > 0):
                            while($m = mysqli_fetch_assoc($qm)): 
                        ?>
                        <tr style="cursor:pointer; transition:0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'"
                            onclick="bukaModalMatkul(this)" data-kode="<?= $m['kode_matkul'] ?>" data-nama="<?= htmlspecialchars($m['nama_matkul']) ?>" data-sks="<?= $m['sks'] ?>">
                            <td><b><?= $m['kode_matkul'] ?></b></td><td><?= $m['nama_matkul'] ?></td><td><?= $m['sks'] ?> SKS</td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="3" align="center" style="padding:20px; color:#999;">Belum ada mata kuliah. Silakan tambah baru.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="modalEditMatkul" class="modal" onclick="tutupModal(event)">
                <div class="modal-content" style="text-align:left; max-width:400px;">
                    <h3 style="margin-bottom:10px;">Edit Mata Kuliah</h3>
                    <form onsubmit="simpanEditMatkul(event)">
                        <input type="hidden" id="edit_kode_lama">
                        <label>Kode Matkul</label><input type="text" id="edit_kode" required class="input-form" style="margin-bottom:8px;" readonly style="background:#eee;">
                        <label>Nama Matkul</label><input type="text" id="edit_nama" required class="input-form" style="margin-bottom:8px;">
                        <label>SKS</label><input type="number" id="edit_sks" required class="input-form" style="margin-bottom:15px;">
                        <hr style="margin:15px 0; border:0; border-top:1px solid #eee;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <button type="button" class="btn btn-red" onclick="hapusMatkulCurrent()">Hapus</button>
                            <div style="display:flex; gap:10px;">
                                <button type="button" class="btn" style="background:#cbd5e1; color:#333;" onclick="document.getElementById('modalEditMatkul').style.display='none'">Batal</button>
                                <button type="submit" class="btn btn-green">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <script>
                function bukaModalMatkul(el) {
                    $('#edit_kode_lama').val($(el).data('kode')); $('#edit_kode').val($(el).data('kode'));      
                    $('#edit_nama').val($(el).data('nama')); $('#edit_sks').val($(el).data('sks'));
                    $('#modalEditMatkul').css('display', 'flex');
                }
                function simpanEditMatkul(e) {
                    e.preventDefault();
                    $.post('dosen_ajax.php', {
                        action: 'edit_matkul', kode_lama: $('#edit_kode_lama').val(), kode_baru: $('#edit_kode').val(),
                        nama: $('#edit_nama').val(), sks: $('#edit_sks').val()
                    }, function(res) { Swal.fire({title:'Info', text:res, icon:'info', timer:1500, showConfirmButton:false}).then(() => { location.reload(); }); });
                }
                function hapusMatkulCurrent() {
                    let kode = $('#edit_kode_lama').val();
                    if(!kode) { Swal.fire('Error', 'ID tidak terbaca', 'error'); return; }
                    Swal.fire({
                        title: 'Yakin hapus ' + kode + '?', text: "Semua jadwal terkait akan ikut terhapus!", icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.post('dosen_ajax.php', { action: 'hapus_matkul', kode: kode }, function(res) { 
                                Swal.fire('Terhapus!', res, 'success').then(() => { location.reload(); });
                            });
                        }
                    });
                }
            </script>

        <?php elseif ($page == 'jadwal'): ?>
            <div class="card" style="margin-top:15px; background:#f8fafc;">
                <h4 style="margin-bottom:10px;">+ Tambah Jadwal Kelas Baru</h4>
                <form method="POST">
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <select name="kode_matkul" required style="padding:8px; border-radius:5px; border:1px solid #ddd; font-size:12px;">
                            <option value="">-- Pilih Mata Kuliah Anda --</option>
                            <?php 
                            // FILTER DROPDOWN: HANYA TAMPILKAN MATKUL MILIK SENDIRI
                            $qm = mysqli_query($conn, "SELECT * FROM matkul WHERE nip='$nip_dosen' ORDER BY nama_matkul ASC"); 
                            while($m = mysqli_fetch_assoc($qm)) { echo "<option value='".$m['kode_matkul']."'>".$m['nama_matkul']." (".$m['kode_matkul'].")</option>"; } 
                            ?>
                        </select>
                        <select name="hari" required style="padding:8px; border-radius:5px; border:1px solid #ddd; font-size:12px;">
                            <option value="Senin">Senin</option><option value="Selasa">Selasa</option><option value="Rabu">Rabu</option><option value="Kamis">Kamis</option><option value="Jumat">Jumat</option><option value="Sabtu">Sabtu</option>
                        </select>
                        <input type="text" name="kelas" placeholder="Kelas" required style="width:120px; padding:8px; border:1px solid #ddd; border-radius:5px; font-size:12px;">
                        <input type="number" name="kuota" placeholder="Kuota" value="30" required style="width:60px; padding:8px; border:1px solid #ddd; border-radius:5px; font-size:12px;">
                        <input type="text" name="ruang" placeholder="Ruang" required style="width:100px; padding:8px; border:1px solid #ddd; border-radius:5px; font-size:12px;">
                        <div style="display:flex; align-items:center; gap:5px;">
                            <input type="time" name="jam_mulai" required style="padding:8px; border:1px solid #ddd; border-radius:5px; font-size:12px;"><span>-</span>
                            <input type="time" name="jam_selesai" required style="padding:8px; border:1px solid #ddd; border-radius:5px; font-size:12px;">
                        </div>
                        <button type="submit" name="simpan_jadwal" class="btn btn-blue">Simpan</button>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <table>
                    <thead><tr><th>Hari/Jam</th><th>Mata Kuliah</th><th>Kelas (Kuota)</th><th>Ruang</th><th width="120" style="text-align:center;">Kontrol</th></tr></thead>
                    <tbody>
                        <?php
                        $today_idx = date('N'); 
                        $sql_jadwal = "SELECT j.*, m.nama_matkul FROM jadwal j JOIN matkul m ON j.kode_matkul = m.kode_matkul WHERE j.nip = '$nip_dosen' ORDER BY MOD(FIELD(j.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') - $today_idx + 7, 7) ASC, j.jam_mulai ASC";
                        $qj = mysqli_query($conn, $sql_jadwal);                        
                        while($row = mysqli_fetch_assoc($qj)):                            
                            $q_real = mysqli_query($conn, "SELECT * FROM realisasi_mengajar WHERE id_jadwal='".$row['id_jadwal']."' AND tanggal='$tgl_ini'");
                            $real = mysqli_fetch_assoc($q_real);
                            $status_kelas = $real ? $real['status'] : 'Belum';
                        ?>
                        <tr style="cursor: pointer; transition:0.2s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'"
                            onclick="bukaModal(this)" data-status="<?= $status_kelas ?>" data-id="<?= $row['id_jadwal'] ?>"
                            data-matkul="<?= htmlspecialchars($row['nama_matkul']) ?>" data-hari="<?= $row['hari'] ?>" data-mulai="<?= $row['jam_mulai'] ?>"
                            data-selesai="<?= $row['jam_selesai'] ?>" data-ruang="<?= htmlspecialchars($row['ruang']) ?>" data-kelas="<?= htmlspecialchars($row['kelas']) ?>" data-kuota="<?= $row['kuota'] ?>">
                            <td><b><?= $row['hari'] ?></b><br><small><?= substr($row['jam_mulai'],0,5) ?> - <?= substr($row['jam_selesai'],0,5) ?></small></td>
                            <td><?= $row['nama_matkul'] ?></td>
                            <td><?= $row['kelas'] ?> <small>(<?= $row['kuota'] ?>)</small></td>
                            <td><?= $row['ruang'] ?></td>
                            <td style="text-align:center;" onclick="event.stopPropagation()">
                                <?php if($status_kelas == 'Belum'): ?>
                                    <?php if($jam_sekarang >= $row['jam_mulai'] && $jam_sekarang <= $row['jam_selesai']): ?>
                                        <button class="btn btn-green" style="width:100%;" onclick="aksiKelas('mulai_kelas', <?= $row['id_jadwal'] ?>)">▶ MULAI</button>
                                    <?php else: ?><button class="btn btn-disabled" style="width:100%;">Belum</button><?php endif; ?>
                                <?php elseif($status_kelas == 'Berlangsung'): ?>
                                    <div style="font-size:10px; color:#22c55e; font-weight:bold; margin-bottom:3px;">🔴 Aktif</div>
                                    <button class="btn btn-red" style="width:100%;" onclick="aksiKelas('selesai_kelas', <?= $row['id_jadwal'] ?>)">⏹ SELESAI</button>
                                <?php else: ?><span style="color:#64748b; font-weight:bold;">✅ Selesai</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <div id="modalEditJadwal" class="modal" onclick="tutupModal(event)">
                <div class="modal-content" style="text-align:left; max-width:600px; max-height:90vh; overflow-y:auto;">
                    <h3 style="margin-bottom:5px;">Detail Kelas</h3>
                    <p id="judulMatkulModal" style="color:#666; font-size:13px; margin-bottom:15px;"></p>
                    <div style="background:#f8fafc; padding:15px; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:20px;">
                        <h4 style="margin-bottom:10px; border-bottom:1px solid #ddd; padding-bottom:5px;">Presensi Hari Ini</h4>
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                            <h2 style="color:#3b82f6; margin:0;" id="txtHadir">0</h2><span style="font-size:13px; color:#64748b;">Mahasiswa Hadir</span>
                        </div>
                        <div style="max-height:150px; overflow-y:auto; border:1px solid #ddd; background:white;">
                            <table style="margin:0; font-size:12px;"><thead style="position:sticky; top:0; background:#f1f5f9;"><tr><th style="padding:8px;">Jam</th><th style="padding:8px;">NIM</th><th style="padding:8px;">Nama Mahasiswa</th></tr></thead><tbody id="listMahasiswaBody"><tr><td colspan="3" align="center">Belum ada data...</td></tr></tbody></table>
                        </div>
                    </div>
                    <div id="msgInfo" style="display:none;"></div>
                    <form id="formEditJadwalArea" onsubmit="simpanEditJadwal(event)">
                        <h4 style="margin-bottom:10px;">Pengaturan Jadwal</h4>
                        <input type="hidden" id="edit_id_jadwal">
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                            <div><label>Hari</label><select id="edit_hari" class="input-form"><option value="Senin">Senin</option><option value="Selasa">Selasa</option><option value="Rabu">Rabu</option><option value="Kamis">Kamis</option><option value="Jumat">Jumat</option></select></div>
                            <div><label>Ruang</label><input type="text" id="edit_ruang" class="input-form"></div>
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:8px;">
                            <div><label>Jam Mulai</label><input type="time" id="edit_jam_mulai" class="input-form"></div>
                            <div><label>Jam Selesai</label><input type="time" id="edit_jam_selesai" class="input-form"></div>
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-top:8px;">
                            <div><label>Kelas</label><input type="text" id="edit_kelas" class="input-form"></div>
                            <div><label>Kuota</label><input type="number" id="edit_kuota" class="input-form"></div>
                        </div>
                        <hr style="margin:15px 0; border:0; border-top:1px solid #eee;">
                        <div style="display:flex; justify-content:space-between;">
                            <button type="button" id="btnHapusJadwal" class="btn btn-red" onclick="hapusJadwalCurrent()">Hapus</button>
                            <div style="display:flex; gap:10px;">
                                <button type="button" class="btn" style="background:#cbd5e1; color:#333;" onclick="document.getElementById('modalEditJadwal').style.display='none'">Tutup</button>
                                <button type="submit" id="btnSimpanJadwal" class="btn btn-green">Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <script>
                // ... (SCRIPT SAMA SEPERTI SEBELUMNYA)
                function aksiKelas(action, id_jadwal) {
                    Swal.fire({ title: 'Ubah status kelas?', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Batal' }).then((result) => {
                        if (result.isConfirmed) {
                            $.post('dosen_ajax.php', { action: action, id_jadwal: id_jadwal }, function(res) { 
                                Swal.fire({title:'Berhasil', text:res, icon:'success', timer:1000, showConfirmButton:false}).then(() => { location.reload(); });
                            });
                        }
                    });
                }
                function bukaModal(el) {
                    let id = $(el).data('id'); let statusKelas = $(el).data('status');
                    window.currentIdJadwal = id; 
                    $('#edit_id_jadwal').val(id);
                    $('#judulMatkulModal').text($(el).data('matkul') + " (" + $(el).data('kelas') + ")");
                    $('#edit_hari').val($(el).data('hari')); $('#edit_jam_mulai').val($(el).data('mulai'));
                    $('#edit_jam_selesai').val($(el).data('selesai')); $('#edit_ruang').val($(el).data('ruang'));
                    $('#edit_kelas').val($(el).data('kelas')); $('#edit_kuota').val($(el).data('kuota'));

                    if (statusKelas === 'Berlangsung') {
                        $('#formEditJadwalArea').hide();
                        $('#msgInfo').html('<div style="text-align:center; padding:15px; color:#22c55e; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:5px; margin-top:10px;">🔴 <b>Kelas Sedang Berlangsung</b></div>').show();
                    } else {
                        $('#formEditJadwalArea').show(); $('#msgInfo').hide();
                    }

                    $('#txtHadir').text("..."); $('#listMahasiswaBody').html('<tr><td colspan="3" align="center">Loading...</td></tr>');
                    $.post('dosen_ajax.php', { action: 'cek_monitoring', id_jadwal: id }, function(res) {
                        let data = JSON.parse(res);
                        $('#txtHadir').text(data.jumlah_hadir + " / " + $(el).data('kuota'));
                        let rows = '';
                        if(data.list_mhs.length > 0) {
                            data.list_mhs.forEach(mhs => { rows += `<tr style="border-bottom:1px solid #eee;"><td style="padding:8px; color:#22c55e; font-weight:bold;">${mhs.jam}</td><td style="padding:8px;">${mhs.nim}</td><td style="padding:8px;">${mhs.nama}</td></tr>`; });
                        } else { rows = '<tr><td colspan="3" align="center" style="padding:20px; color:#999;">Belum ada mahasiswa absen.</td></tr>'; }
                        $('#listMahasiswaBody').html(rows);
                    });
                    $('#modalEditJadwal').css('display', 'flex');
                }
                function simpanEditJadwal(e) {
                    e.preventDefault();
                    $.post('dosen_ajax.php', {
                        action: 'edit_jadwal', id: $('#edit_id_jadwal').val(), hari: $('#edit_hari').val(),
                        jam_m: $('#edit_jam_mulai').val(), jam_s: $('#edit_jam_selesai').val(),
                        ruang: $('#edit_ruang').val(), kelas: $('#edit_kelas').val(), kuota: $('#edit_kuota').val()
                    }, function(res) { 
                        if(res.includes("Updated")) Swal.fire({title:'Berhasil', text:res, icon:'success', timer:1000, showConfirmButton:false}).then(() => { location.reload(); });
                        else Swal.fire('Gagal', res, 'error');
                    });
                }
                function hapusJadwalCurrent() {
                    Swal.fire({ title: 'Hapus jadwal ini?', text: "Tidak bisa dikembalikan!", icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Ya, Hapus' }).then((result) => {
                        if (result.isConfirmed) {
                            $.post('dosen_ajax.php', { action: 'hapus_jadwal', id: window.currentIdJadwal }, function(res) { 
                                Swal.fire('Terhapus!', res, 'success').then(() => { location.reload(); });
                            });
                        }
                    });
                }
            </script>

<?php elseif ($page == 'rekap'): ?>
            <div class="card" style="margin-top:15px; padding:15px; background:#f8fafc;">
                <h4 style="margin-bottom:10px; color:#64748b;">Filter Data</h4>
                <form method="GET" action="">
                    <input type="hidden" name="page" value="rekap">
                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <div style="flex:1; min-width:180px;">
                            <input type="text" name="keyword" value="<?= isset($_GET['keyword']) ? $_GET['keyword'] : '' ?>" placeholder="Cari Matkul..." style="width:100%; padding:8px; border:1px solid #cbd5e0; border-radius:5px;">
                        </div>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <span style="font-size:12px; font-weight:bold; color:#666;">Dari:</span>
                            <input type="date" name="tgl_mulai" value="<?= isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : '' ?>" style="padding:8px; border:1px solid #cbd5e0; border-radius:5px;">
                        </div>
                        <div style="display:flex; align-items:center; gap:5px;">
                            <span style="font-size:12px; font-weight:bold; color:#666;">Sampai:</span>
                            <input type="date" name="tgl_akhir" value="<?= isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : '' ?>" style="padding:8px; border:1px solid #cbd5e0; border-radius:5px;">
                        </div>
                        <button type="submit" class="btn btn-blue">Filter</button>
                    </div>
                </form>
            </div>
            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Mata Kuliah</th>
                            <th>Kelas</th>
                            <th>Jam Realisasi</th>
                            <th>Materi / Catatan</th>
                            <th>Kehadiran</th>
                            <th width="100" style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where = "WHERE j.nip = '$nip_dosen'";
                        if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
                            $keyword = mysqli_real_escape_string($conn, $_GET['keyword']);
                            $where .= " AND m.nama_matkul LIKE '%$keyword%'";
                        }
                        if (isset($_GET['tgl_mulai']) && !empty($_GET['tgl_mulai'])) {
                            $tgl_mulai = mysqli_real_escape_string($conn, $_GET['tgl_mulai']);
                            $where .= " AND r.tanggal >= '$tgl_mulai'";
                        }
                        if (isset($_GET['tgl_akhir']) && !empty($_GET['tgl_akhir'])) {
                            $tgl_akhir = mysqli_real_escape_string($conn, $_GET['tgl_akhir']);
                            $where .= " AND r.tanggal <= '$tgl_akhir'";
                        }

                        $query_rekap = "SELECT r.*, m.nama_matkul, j.kelas, j.jam_mulai, j.jam_selesai, 
                                        (SELECT COUNT(*) FROM presensi_kuliah pk WHERE pk.id_jadwal = r.id_jadwal AND pk.tanggal = r.tanggal AND pk.status = 'Hadir') as hadir,
                                        (SELECT COUNT(*) FROM presensi_kuliah pk WHERE pk.id_jadwal = r.id_jadwal AND pk.tanggal = r.tanggal) as total_mhs
                                        FROM realisasi_mengajar r
                                        JOIN jadwal j ON r.id_jadwal = j.id_jadwal
                                        JOIN matkul m ON j.kode_matkul = m.kode_matkul
                                        $where
                                        ORDER BY r.tanggal DESC, j.jam_mulai DESC";
                        
                        $result_rekap = mysqli_query($conn, $query_rekap);

                        if (mysqli_num_rows($result_rekap) > 0) {
                            while ($row = mysqli_fetch_assoc($result_rekap)) {
                                echo "<tr>";
                                echo "<td>" . date('d/m/Y', strtotime($row['tanggal'])) . "</td>";
                                echo "<td>" . $row['nama_matkul'] . "</td>";
                                echo "<td>" . $row['kelas'] . "</td>";
                                echo "<td>" . substr($row['jam_mulai'], 0, 5) . " - " . substr($row['jam_selesai'], 0, 5) . "</td>";
                                echo "<td>" . $row['materi'] . "</td>";
                                echo "<td>" . $row['hadir'] . " / " . $row['total_mhs'] . "</td>";
                                echo '<td style="text-align:center;">
                                        <button class="btn btn-blue" onclick="bukaDetail(' . $row['id_jadwal'] . ', \'' . $row['tanggal'] . '\', \'' . htmlspecialchars($row['nama_matkul']) . '\', \'' . htmlspecialchars($row['kelas']) . '\')">Detail</button>
                                      </td>';
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' align='center'>Data tidak ditemukan.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div id="modalDetailRekap" class="modal" onclick="tutupModal(event)">
                <div class="modal-content" style="max-width:700px; text-align:left;">
                    <h3 style="margin-bottom:5px;">Detail Kehadiran</h3>
                    <p id="judulDetail" style="color:#666; font-size:13px; margin-bottom:15px;"></p>
                    <div style="max-height:250px; overflow-y:auto; border:1px solid #eee; margin-bottom:15px;">
                        <table style="margin:0;"><thead style="position:sticky; top:0; background:#f1f5f9; z-index:1;"><tr><th>NIM</th><th>Nama</th><th>Waktu Scan</th><th>Status</th></tr></thead><tbody id="bodyDetailMhs"></tbody></table>
                    </div>
                    <div style="background:#f0fdf4; padding:10px; border-radius:8px; border:1px dashed #22c55e;">
                        <h4 style="font-size:12px; margin-bottom:5px; color:#166534;">+ Input Manual (Sakit/Izin)</h4>
                        <form id="formManual" onsubmit="tambahManual(event)" style="display:flex; gap:5px;">
                            <input type="hidden" id="id_jadwal_detail"><input type="hidden" id="tgl_detail">
                            <input type="text" id="manual_nim" placeholder="NIM" required style="padding:5px; width:100px; border:1px solid #ddd; border-radius:4px; font-size:12px;">
                            <select id="manual_status" style="padding:5px; border:1px solid #ddd; border-radius:4px; font-size:12px;"><option value="Sakit">Sakit</option><option value="Izin">Izin</option><option value="Hadir">Hadir</option></select>
                            <button type="submit" class="btn btn-green" style="font-size:11px;">Simpan</button>
                        </form>
                    </div>
                    <div style="text-align:right; margin-top:15px;"><button class="btn btn-red" onclick="document.getElementById('modalDetailRekap').style.display='none'">Tutup</button></div>
                </div>
            </div>
            
            <script>
                function bukaDetail(id_jadwal, tanggal, matkul, kelas) {
                    $('#judulDetail').text(matkul + " (" + kelas + ") - " + tanggal);
                    $('#id_jadwal_detail').val(id_jadwal); $('#tgl_detail').val(tanggal);
                    loadDetailIsi(id_jadwal, tanggal); 
                    $('#modalDetailRekap').css('display', 'flex');
                }
                
                function loadDetailIsi(id, tgl) {
                    $('#bodyDetailMhs').html('<tr><td colspan="4" align="center">Loading...</td></tr>');
                    $.post('dosen_ajax.php', { action: 'load_detail_mhs', id_jadwal: id, tanggal: tgl }, function(res) { 
                        $('#bodyDetailMhs').html(res); 
                    });
                }
                
                function tambahManual(e) {
                    e.preventDefault();
                    let id = $('#id_jadwal_detail').val(); 
                    let tgl = $('#tgl_detail').val();
                    $.post('dosen_ajax.php', { 
                        action: 'tambah_presensi_manual', 
                        id_jadwal: id, 
                        tanggal: tgl, 
                        nim: $('#manual_nim').val(), 
                        status: $('#manual_status').val() 
                    }, function(res) { 
                        Swal.fire({title:'Info', text:res, icon:'info', timer:1500}); 
                        $('#manual_nim').val(''); 
                        loadDetailIsi(id, tgl); 
                    });
                }
            </script>
        <?php endif; ?>
    </div>

    <script>
        // MUNCULKAN NOTIFIKASI PHP (JIKA ADA)
        <?= $swal_script ?>

        function tutupModal(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        }
        function toggleSidebar() {
            document.getElementById('mySidebar').classList.toggle('active');
            document.querySelector('.overlay-sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>