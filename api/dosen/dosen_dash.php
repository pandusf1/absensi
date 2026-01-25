<?php
// ==========================================
// 1. SETTING DEBUG & AUTH (COOKIE)
// ==========================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Jakarta');

// Cek Login Pakai COOKIE (Solusi agar tidak mental ke index)
if (!isset($_COOKIE['status_login']) || $_COOKIE['role'] != 'dosen') {
    header("Location: ../index.php"); 
    exit;
}

require_once __DIR__ . '/../database.php';

$nip_dosen = $_COOKIE['nip']; 

// Ambil Profil Dosen
$q_profil = mysqli_query($conn, "SELECT * FROM dosen WHERE nip = '$nip_dosen'");
$dosen = mysqli_fetch_assoc($q_profil);

// Setup Variabel Waktu & Page
$hari_inggris = date('l');
$map_hari = ['Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'];
$hari_ini = $map_hari[$hari_inggris];
$tgl_ini = date('Y-m-d');
$jam_sekarang = date('H:i:s');
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$swal_script = "";

// ==========================================
// 2. LOGIKA BACKEND (TETAP SAMA)
// ==========================================

// LOGIKA TAMBAH MATKUL
if(isset($_POST['tambah_matkul'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $sks  = mysqli_real_escape_string($conn, $_POST['sks']);
    
    // Auto Generate Kode Unik
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

    $q_simpan = "INSERT INTO matkul (kode_matkul, nama_matkul, sks, nip) VALUES ('$kode_baru', '$nama', '$sks', '$nip_dosen')";
    
    if(mysqli_query($conn, $q_simpan)) {
        $swal_script = "Swal.fire({title:'Berhasil!', html: 'Matkul Ditambahkan.<br>Kode: <b>$kode_baru</b>', icon:'success', timer:2000, showConfirmButton:false}).then(() => { window.location='?page=matkul'; });";
    } else {
        $swal_script = "Swal.fire('Gagal', 'Error: " . mysqli_error($conn) . "', 'error');";
    }
}

// LOGIKA SIMPAN JADWAL
if(isset($_POST['simpan_jadwal'])) {
    $kode = $_POST['kode_matkul']; $hari = $_POST['hari']; 
    $jam_m = $_POST['jam_mulai']; $jam_s = $_POST['jam_selesai'];
    $ruang = $_POST['ruang']; $kelas = $_POST['kelas']; $kuota = $_POST['kuota'];

    if($jam_m >= $jam_s) {
        $swal_script = "Swal.fire('Jam Error', 'Jam mulai tidak boleh melebihi jam selesai.', 'warning');";
    } else {
        // Cek Bentrok
        $cek_dosen = mysqli_query($conn, "SELECT * FROM jadwal WHERE nip = '$nip_dosen' AND hari = '$hari' AND ('$jam_m' < jam_selesai AND '$jam_s' > jam_mulai)");
        $cek_ruang = mysqli_query($conn, "SELECT * FROM jadwal WHERE ruang = '$ruang' AND hari = '$hari' AND ('$jam_m' < jam_selesai AND '$jam_s' > jam_mulai)");
        $cek_kelas = mysqli_query($conn, "SELECT * FROM jadwal WHERE kelas = '$kelas' AND hari = '$hari' AND ('$jam_m' < jam_selesai AND '$jam_s' > jam_mulai)");

        if(mysqli_num_rows($cek_dosen) > 0) {
            $dt = mysqli_fetch_assoc($cek_dosen);
            $swal_script = "Swal.fire('Jadwal Bentrok!', 'Anda sudah mengajar matkul <b>" . $dt['kode_matkul'] . "</b> di jam tersebut.', 'error');";
        } elseif(mysqli_num_rows($cek_ruang) > 0) {
            $dt = mysqli_fetch_assoc($cek_ruang);
            $swal_script = "Swal.fire('Ruangan Penuh!', 'Ruang <b>$ruang</b> dipakai matkul <b>" . $dt['kode_matkul'] . "</b>.', 'error');";
        } elseif(mysqli_num_rows($cek_kelas) > 0) {
            $dt = mysqli_fetch_assoc($cek_kelas);
            $swal_script = "Swal.fire('Kelas Sibuk!', 'Kelas <b>$kelas</b> ada kuliah <b>" . $dt['kode_matkul'] . "</b>.', 'error');";
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
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* === CSS DESIGN SYSTEM (MIRIP MAHASISWA) === */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; min-height: 100vh; font-size: 13px; color: #333; overflow-x: hidden; }
        
        /* SIDEBAR */
        .sidebar { 
            width: 250px; background: #1e293b; color: white; 
            position: fixed; height: 100vh; 
            left: -250px; top: 0; z-index: 1000; transition: 0.3s; 
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar.active { left: 0; }
        .sidebar-header { 
            padding: 20px; border-bottom: 1px solid #334155; 
            display: flex; align-items: center; gap: 10px; background: #0f172a;
        }
        .menu { list-style: none; padding-top: 10px; }
        .menu li a { 
            display: flex; align-items: center; padding: 14px 20px; 
            color: #cbd5e1; text-decoration: none; transition: 0.2s; 
            gap: 12px; font-size: 13px; border-left: 3px solid transparent;
        }
        .menu li a:hover, .menu li a.active { 
            background-color: #334155; color: #60a5fa; border-left-color: #60a5fa; 
        }

        /* MAIN CONTENT */
        .main-content { 
            flex: 1; margin-left: 0; padding: 20px; 
            width: 100%; transition: 0.3s; 
        }
        @media (min-width: 769px) {
            .main-content.active { margin-left: 250px; width: calc(100% - 250px); }
        }

        /* TOP BAR */
        .top-bar { 
            display: flex; justify-content: space-between; align-items: center; 
            background: white; padding: 15px 20px; border-radius: 10px; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.03); margin-bottom: 25px;
        }
        .btn-burger { display: block; background: none; border: none; font-size: 20px; cursor: pointer; color: #333; }

        /* CARDS & STATS */
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 20px; }
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); text-align: center; border-bottom: 4px solid #ddd; }
        .stat-card h3 { font-size: 28px; margin-bottom: 5px; color: #1e293b; }
        .stat-card p { color: #64748b; font-size: 12px; margin: 0; }

        /* TABLES */
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; white-space: nowrap; }
        th, td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; text-align: left; }
        th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 12px; }
        td { color: #334155; }

        /* FORMS */
        .input-form { width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; margin-bottom: 10px; }
        .btn { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-weight: 500; font-size: 12px; color: white; transition: 0.2s; }
        .btn-green { background: #10b981; } .btn-blue { background: #3b82f6; } .btn-red { background: #ef4444; } .btn-disabled { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; }

        /* MODAL */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 2000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 25px; border-radius: 15px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; position: relative; }
        
        .overlay-sidebar { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 900; }
        .overlay-sidebar.active { display: block; }

        @media (max-width: 768px) { 
            .sidebar { left: -250px; } 
            .sidebar.active { left: 0; }
            .btn-burger { display: block; }
            .stat-grid { display: grid;grid-template-columns: 1fr; gap: 15px; margin-bottom: 20px; }
        }

        div.swal2-container {
            z-index: 99999 !important; /* Angka harus lebih besar dari 2000 */
        }

    </style>
</head>
<body>

    <div class="overlay-sidebar" onclick="toggleSidebar()"></div>

    <nav class="sidebar" id="mySidebar">
        <div class="sidebar-header">
            <img src="../aset/img/polines.png" onerror="this.src='https://via.placeholder.com/40'" alt="Logo" style="width: 35px;">
            <div>
                <h3 style="margin:0; font-size:14px; color:white;">PORTAL DOSEN</h3>
                <small style="font-size:11px; color:#94a3b8;">Sistem Akademik</small>
            </div>
        </div>
        <ul class="menu">
            <li><a href="?page=home" class="<?= $page=='home'?'active':'' ?>"><i class="fa-solid fa-home"></i> Dashboard</a></li>
            <li><a href="?page=matkul" class="<?= $page=='matkul'?'active':'' ?>"><i class="fa-solid fa-book"></i> Mata Kuliah</a></li>
            <li><a href="?page=jadwal" class="<?= $page=='jadwal'?'active':'' ?>"><i class="fa-solid fa-chalkboard-user"></i> Jadwal Mengajar</a></li>
            <li><a href="?page=rekap" class="<?= $page=='rekap'?'active':'' ?>"><i class="fa-solid fa-file-contract"></i> Laporan Presensi</a></li>
            <li style="margin-top: 20px;"><a href="#" onclick="logout()" style="color:#ef4444;"><i class="fa-solid fa-sign-out-alt"></i> Keluar</a></li>
        </ul>
    </nav>

    <div class="main-content" id="mainContent">
        <div class="top-bar">
            <div style="display:flex; align-items:center; gap:15px;">
                <button class="btn-burger" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
                <h3 style="margin:0; color:#1e293b;">
                    <?php 
                        if($page=='home') echo 'Dashboard Overview';
                        elseif($page=='matkul') echo 'Manajemen Matkul';
                        elseif($page=='jadwal') echo 'Jadwal & Kontrol Kelas';
                        elseif($page=='rekap') echo 'Laporan Presensi';
                    ?>
                </h3>
            </div>
        </div>

        <?php if ($page == 'home'): ?>
            <?php
            // Statistik
            $q1 = mysqli_query($conn, "SELECT COUNT(*) as total FROM matkul WHERE nip='$nip_dosen'"); $d1 = mysqli_fetch_assoc($q1);
            $q2 = mysqli_query($conn, "SELECT COALESCE(SUM(m.sks), 0) as total FROM jadwal j JOIN matkul m ON j.kode_matkul = m.kode_matkul WHERE j.nip='$nip_dosen'"); $d2 = mysqli_fetch_assoc($q2);
            $q3 = mysqli_query($conn, "SELECT COUNT(*) as total FROM realisasi_mengajar r JOIN jadwal j ON r.id_jadwal = j.id_jadwal WHERE j.nip='$nip_dosen' AND r.status='Selesai'"); $d3 = mysqli_fetch_assoc($q3);
            ?>
            <div class="stat-grid">
                <div class="stat-card" style="border-bottom-color: #3b82f6;"><h3><?= $d1['total'] ?></h3><p>Matkul Diampu</p></div>
                <div class="stat-card" style="border-bottom-color: #8b5cf6;"><h3><?= $d2['total'] ?></h3><p>Total SKS Ajar</p></div>
                <div class="stat-card" style="border-bottom-color: #f59e0b;"><h3><?= $d3['total'] ?></h3><p>Pertemuan Selesai</p></div>
            </div>

            <div class="card">
                <h3>Biodata Dosen</h3>
                <div class="table-responsive">
                    <table style="max-width: 600px;">
                        <tr><td width="150"><strong>NIP</strong></td><td><?= $dosen['nip'] ?></td></tr>
                        <tr><td><strong>Nama Lengkap</strong></td><td><?= $dosen['nama_dosen'] ?></td></tr>
                        <tr><td><strong>Jabatan</strong></td><td><?= $dosen['jabatan'] ?></td></tr>
                        <tr><td><strong>Email</strong></td><td><?= $dosen['email'] ?></td></tr>
                    </table>
                </div>
            </div>

        <?php elseif ($page == 'matkul'): ?>
            <div class="card">
                <h3 style="margin-bottom:15px; color:#3b82f6;">+ Tambah Mata Kuliah</h3>
                <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap;">
                    <input type="text" name="nama" placeholder="Nama Mata Kuliah" required class="input-form" style="flex:1; margin:0;">
                    <input type="number" name="sks" placeholder="SKS" required class="input-form" style="width:80px; margin:0;">
                    <button type="submit" name="tambah_matkul" class="btn btn-blue"><i class="fa-solid fa-save"></i> Simpan</button>
                </form>
            </div>
            
            <div class="card">
                <h3>Daftar Mata Kuliah</h3>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>Kode</th><th>Nama Mata Kuliah</th><th>SKS</th></tr></thead>
                        <tbody>
                            <?php 
                            $qm = mysqli_query($conn, "SELECT * FROM matkul WHERE nip='$nip_dosen' ORDER BY kode_matkul ASC"); 
                            while($m = mysqli_fetch_assoc($qm)): 
                            ?>
                            <tr style="cursor: pointer; transition:0.2s;" 
                                id="tr"
                                onmouseover="this.style.background='#f1f5f9'" 
                                onmouseout="this.style.background='white'"
                                onclick="bukaModalMatkul(this)" 
                                data-kode="<?= $m['kode_matkul'] ?>" 
                                data-nama="<?= htmlspecialchars($m['nama_matkul']) ?>" 
                                data-sks="<?= $m['sks'] ?>">
                                <td><b><?= $m['kode_matkul'] ?></b></td>
                                <td><?= $m['nama_matkul'] ?></td>
                                <td><?= $m['sks'] ?> SKS</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>                    
                    </table>
                </div>
            </div>

            <div id="modalEditMatkul" class="modal" onclick="tutupModal(event)">
                <div class="modal-content">
                    <h3 style="margin-bottom:15px;">Edit Mata Kuliah</h3>
                    <form onsubmit="simpanEditMatkul(event)">
                        <input type="hidden" id="edit_kode_lama">
                        <label style="display:block; text-align:left; font-weight:600;">Kode Matkul</label>
                        <input type="text" id="edit_kode" readonly class="input-form" style="background:#f1f5f9;">
                        
                        <label style="display:block; text-align:left; font-weight:600;">Nama Matkul</label>
                        <input type="text" id="edit_nama" required class="input-form">
                        
                        <label style="display:block; text-align:left; font-weight:600;">SKS</label>
                        <input type="number" id="edit_sks" required class="input-form">
                        
                        <div style="display:flex; justify-content:space-between; margin-top:15px;">
                            <button type="button" class="btn btn-red" onclick="hapusMatkulCurrent()"><i class="fa-solid fa-trash"></i> Hapus</button>
                            <div style="display:flex; gap:10px;">
                                <button type="button" class="btn" style="background:#cbd5e1; color:#333;" onclick="$('#modalEditMatkul').hide()">Batal</button>
                                <button type="submit" class="btn btn-green"><i class="fa-solid fa-save"></i> Simpan</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
        <?php elseif ($page == 'jadwal'): ?>
            <div class="card">
                <h3 style="margin-bottom:15px; color:#3b82f6;">+ Tambah Jadwal Kelas</h3>
                <form method="POST">
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:10px;">
                        <select name="kode_matkul" required class="input-form" style="margin:0;">
                            <option value="">-- Pilih Matkul --</option>
                            <?php 
                            $qm = mysqli_query($conn, "SELECT * FROM matkul WHERE nip='$nip_dosen' ORDER BY nama_matkul ASC"); 
                            while($m = mysqli_fetch_assoc($qm)) { echo "<option value='".$m['kode_matkul']."'>".$m['nama_matkul']."</option>"; } 
                            ?>
                        </select>
                        <select name="hari" required class="input-form" style="margin:0;">
                            <option value="Senin">Senin</option><option value="Selasa">Selasa</option><option value="Rabu">Rabu</option><option value="Kamis">Kamis</option><option value="Jumat">Jumat</option>
                        </select>
                        <input type="text" name="kelas" placeholder="Kelas (mis: KA-3C)" required class="input-form" style="margin:0;">
                        <input type="number" name="kuota" placeholder="Kuota" value="30" required class="input-form" style="margin:0;">
                        <input type="text" name="ruang" placeholder="Ruang" required class="input-form" style="margin:0;">
                        <div style="display:flex; gap:5px;">
                            <input type="time" name="jam_mulai" required class="input-form" style="margin:0;">
                            <input type="time" name="jam_selesai" required class="input-form" style="margin:0;">
                        </div>
                    </div>
                    <button type="submit" name="simpan_jadwal" class="btn btn-blue" style="margin-top:10px; width:100%;"><i class="fa-solid fa-plus-circle"></i> Tambah Jadwal</button>
                </form>
            </div>

            <div class="card">
                <h3>Jadwal Mengajar</h3>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>Hari/Jam</th><th>Mata Kuliah</th><th>Kelas</th><th>Ruang</th><th style="text-align:center;">Status</th></tr></thead>
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
                            <tr style="cursor: pointer;" onclick="bukaModalJadwal(this)" 
                                id="tr"
                                data-id="<?= $row['id_jadwal'] ?>" data-status="<?= $status_kelas ?>" data-matkul="<?= htmlspecialchars($row['nama_matkul']) ?>" 
                                data-hari="<?= $row['hari'] ?>" data-mulai="<?= $row['jam_mulai'] ?>" data-selesai="<?= $row['jam_selesai'] ?>" 
                                data-ruang="<?= $row['ruang'] ?>" data-kelas="<?= $row['kelas'] ?>" data-kuota="<?= $row['kuota'] ?>">
                                
                                <td><span style="background:#f1f5f9; padding:2px 6px; border-radius:4px; font-weight:bold;"><?= $row['hari'] ?></span><br><small><?= substr($row['jam_mulai'],0,5) ?> - <?= substr($row['jam_selesai'],0,5) ?></small></td>
                                <td><?= $row['nama_matkul'] ?></td>
                                <td><?= $row['kelas'] ?> <small>(<?= $row['kuota'] ?>)</small></td>
                                <td><?= $row['ruang'] ?></td>
                                <td style="text-align:center;" onclick="event.stopPropagation()">
                                    <?php if($status_kelas == 'Belum'): ?>
                                        <?php if($jam_sekarang >= $row['jam_mulai'] && $jam_sekarang <= $row['jam_selesai']): ?>
                                            <button class="btn btn-green" onclick="aksiKelas('mulai_kelas', <?= $row['id_jadwal'] ?>)">▶ MULAI</button>
                                        <?php else: ?><button class="btn btn-disabled">Belum</button><?php endif; ?>
                                    <?php elseif($status_kelas == 'Berlangsung'): ?>
                                        <button class="btn btn-red" onclick="aksiKelas('selesai_kelas', <?= $row['id_jadwal'] ?>)">⏹ SELESAI</button>
                                    <?php else: ?><span style="color:#64748b; font-weight:bold;">✅ Selesai</span><?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="modalEditJadwal" class="modal" onclick="tutupModal(event)">
                <div class="modal-content">
                    <h3 style="margin-bottom:5px;">Kontrol Kelas</h3>
                    <p id="judulMatkulModal" style="color:#666; font-size:12px; margin-bottom:15px;"></p>
                    
                    <div style="background:#f8fafc; padding:15px; border:1px solid #e2e8f0; border-radius:10px; margin-bottom:15px;">
                        <h4 style="margin-bottom:10px; color:#3b82f6;">Presensi Real-Time</h4>
                        <h2 id="txtHadir" style="margin:0; font-size:24px;">0</h2>
                        <div style="max-height:120px; overflow-y:auto; border:1px solid #ddd; background:white; margin-top:5px;">
                            <table style="margin:0; font-size:11px;"><tbody id="listMahasiswaBody"></tbody></table>
                        </div>
                    </div>

                    <div id="formEditJadwalArea">
                        <form onsubmit="simpanEditJadwal(event)">
                            <input type="hidden" id="edit_id_jadwal">
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                <div><label>Hari</label><select id="edit_hari" class="input-form"><option value="Senin">Senin</option><option value="Selasa">Selasa</option><option value="Rabu">Rabu</option><option value="Kamis">Kamis</option><option value="Jumat">Jumat</option></select></div>
                                <div><label>Ruang</label><input type="text" id="edit_ruang" class="input-form"></div>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                <div><label>Mulai</label><input type="time" id="edit_jam_mulai" class="input-form"></div>
                                <div><label>Selesai</label><input type="time" id="edit_jam_selesai" class="input-form"></div>
                            </div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                <div><label>Kelas</label><input type="text" id="edit_kelas" class="input-form"></div>
                                <div><label>Kuota</label><input type="number" id="edit_kuota" class="input-form"></div>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-top:10px;">
                                <button type="button" class="btn btn-red" onclick="hapusJadwalCurrent()"><i class="fa-solid fa-trash"></i> Hapus</button>
                                <button type="submit" class="btn btn-green"><i class="fa-solid fa-save"></i> Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

<?php elseif ($page == 'rekap'): ?>
    <div class="card">        
        <div style="background:#f8fafc; padding:15px; border-radius:8px; border:1px solid #e2e8f0; margin-bottom:15px;">
            <div style="margin-bottom:10px;">
                <label style="font-size:11px; font-weight:bold; color:#64748b; display:block; margin-bottom:5px;">Pencarian (Matkul/Kelas)</label>
                <input type="text" id="filter_keyword" placeholder="Ketik nama matkul..." onkeyup="loadRekap()" class="input-form" style="width:100%; margin:0;">
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; align-items:end;">
                <div>
                    <label style="font-size:11px; font-weight:bold; color:#64748b; display:block; margin-bottom:5px;">Dari Tanggal</label>
                    <input type="date" id="filter_tgl_mulai" onchange="loadRekap()" class="input-form" style="width:100%; margin:0;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:bold; color:#64748b; display:block; margin-bottom:5px;">Sampai Tanggal</label>
                    <input type="date" id="filter_tgl_akhir" onchange="loadRekap()" class="input-form" style="width:100%; margin:0;">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <style>
                .tr-clickable:hover { background-color: #f1f5f9; cursor: pointer; }
            </style>
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Mata Kuliah</th>
                        <th>Kelas</th>
                        <th>Hadir / Total</th>
                    </tr>
                </thead>
                <tbody id="tabelRekapBody">
                    <tr><td colspan="5" align="center" style="padding:20px; color:#94a3b8;">Sedang memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modalDetailRekap" class="modal" onclick="tutupModal(event)">
        <div class="modal-content">
            <h3 style="margin-bottom:5px;">Detail Kehadiran</h3>
            <p id="judulDetail" style="color:#666; font-size:12px; margin-bottom:15px;"></p>
            <div style="max-height:250px; overflow-y:auto; border:1px solid #eee; margin-bottom:15px;">
                <table style="margin:0;">
                    <thead><tr style="background:#f8fafc;"><th>NIM</th><th>Nama</th><th>Jam</th><th>Status</th></tr></thead>
                    <tbody id="bodyDetailMhs"></tbody>
                </table>
            </div>
            <div style="background:#f0fdf4; padding:10px; border-radius:8px; border:1px dashed #22c55e;">
                <h4 style="font-size:12px; margin-bottom:5px; color:#166534;">+ Input Manual</h4>
                <form onsubmit="tambahManual(event)" style="display:flex; gap:5px;">
                    <input type="hidden" id="id_jadwal_detail"><input type="hidden" id="tgl_detail">
                    <input type="text" id="manual_nim" placeholder="NIM Mhs" class="input-form" style="flex:1; margin:0;">
                    <select id="manual_status" class="input-form" style="width:80px; margin:0;">
                        <option value="Hadir">Hadir</option>
                        <option value="Sakit">Sakit</option>
                        <option value="Izin">Izin</option>
                    </select>
                    <button type="submit" class="btn btn-green">Simpan</button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    <?= $swal_script ?>

    $(document).ready(function(){
        // Cek apakah kita ada di halaman rekap (agar tidak error di halaman lain)
        if ($('#tabelRekapBody').length > 0) {
            loadRekap();
        }
    });

    // --- FUNGSI UMUM ---
    function toggleSidebar() { 
        document.getElementById('mySidebar').classList.toggle('active'); 
        document.getElementById('mainContent').classList.toggle('active');
        document.querySelector('.overlay-sidebar').classList.toggle('active'); 
    }

    function logout() {
        document.cookie = "status_login=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        document.cookie = "nip=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        document.cookie = "role=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        window.location.href = '../index.php';
    }

    function tutupModal(e) { 
        if (e.target.classList.contains('modal')) e.target.style.display = 'none'; 
    }

    // --- FUNGSI HALAMAN REKAP ---
    function loadRekap() {
        let kw = $('#filter_keyword').val();
        let tm = $('#filter_tgl_mulai').val();
        let ta = $('#filter_tgl_akhir').val();

        $('#tabelRekapBody').html('<tr><td colspan="6" align="center">Memuat riwayat kelas...</td></tr>');

        $.post('dosen_ajax.php', { 
            action: 'filter_rekap', 
            keyword: kw, 
            tgl_mulai: tm, 
            tgl_akhir: ta,
            nip: '<?= $nip_dosen ?>' // Kirim NIP dari session/cookie
        }, function(res) {
            $('#tabelRekapBody').html(res);
        });
    }

function bukaDetail(id, tgl, matkul, kelas) {
        $('#judulDetail').text(matkul + " - " + tgl); 
        $('#id_jadwal_detail').val(id); 
        $('#tgl_detail').val(tgl);
        loadDetailIsi(id, tgl); 
        $('#modalDetailRekap').css('display', 'flex');
    }

    function loadDetailIsi(id, tgl) {
        $('#bodyDetailMhs').html('<tr><td align="center">Loading...</td></tr>');
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
            Swal.fire('Info', res, 'info'); 
            $('#manual_nim').val(''); 
            loadDetailIsi(id, tgl); 
        });
    }
    
    // --- FUNGSI HALAMAN MATKUL ---
    function bukaModalMatkul(el) {
        var kode = $(el).data('kode');
        var nama = $(el).data('nama');
        var sks = $(el).data('sks');

        $('#edit_kode_lama').val(kode); 
        $('#edit_kode').val(kode);      
        $('#edit_nama').val(nama);
        $('#edit_sks').val(sks);

        $('#modalEditMatkul').css('display', 'flex');
    }

    function simpanEditMatkul(e) {
        e.preventDefault();
        $.post('dosen_ajax.php', {
            action: 'edit_matkul', 
            kode_lama: $('#edit_kode_lama').val(), 
            kode_baru: $('#edit_kode').val(),
            nama: $('#edit_nama').val(), 
            sks: $('#edit_sks').val()
        }, function(res) { 
            Swal.fire({title:'Info', text:res, icon:'info', timer:1500, showConfirmButton:false})
            .then(() => { location.reload(); }); 
        });
    }

    function hapusMatkulCurrent() {
        let kode = $('#edit_kode_lama').val();
        Swal.fire({
            title: 'Hapus Mata Kuliah?', text: "Data jadwal terkait juga akan terhapus!", icon: 'warning', 
            showCancelButton: true, confirmButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('dosen_ajax.php', { action: 'hapus_matkul', kode: kode }, function(res) { 
                    Swal.fire('Terhapus!', res, 'success').then(() => { location.reload(); });
                });
            }
        });
    }

    // --- FUNGSI HALAMAN JADWAL ---
    function aksiKelas(act, id) {
        Swal.fire({title:'Konfirmasi?', icon:'question', showCancelButton:true}).then((r)=>{
            if(r.isConfirmed) $.post('dosen_ajax.php', {action:act, id_jadwal:id}, function(res){ 
                Swal.fire('Sukses',res,'success').then(()=>location.reload()); 
            });
        });
    }

    function bukaModalJadwal(el) {
        let id = $(el).data('id'); window.currentIdJadwal = id;
        $('#edit_id_jadwal').val(id); 
        $('#judulMatkulModal').text($(el).data('matkul') + " (" + $(el).data('kelas') + ")");
        $('#edit_hari').val($(el).data('hari')); 
        $('#edit_jam_mulai').val($(el).data('mulai'));
        $('#edit_jam_selesai').val($(el).data('selesai')); 
        $('#edit_ruang').val($(el).data('ruang'));
        $('#edit_kelas').val($(el).data('kelas')); 
        $('#edit_kuota').val($(el).data('kuota'));

        if ($(el).data('status') === 'Berlangsung') $('#formEditJadwalArea').hide(); else $('#formEditJadwalArea').show();

        $('#listMahasiswaBody').html('<tr><td align="center">Loading...</td></tr>');
        $.post('dosen_ajax.php', { action: 'cek_monitoring', id_jadwal: id }, function(res) {
            let d = JSON.parse(res); 
            $('#txtHadir').text(d.jumlah_hadir);
            let html = ''; 
            d.list_mhs.forEach(m => html += `<tr><td style="padding:5px;">${m.jam}</td><td style="padding:5px;">${m.nim}</td><td style="padding:5px;">${m.nama}</td></tr>`);
            $('#listMahasiswaBody').html(html || '<tr><td align="center" style="color:#999;">Belum ada presensi</td></tr>');
        });
        $('#modalEditJadwal').css('display', 'flex');
    }

    function simpanEditJadwal(e) {
        e.preventDefault();
        $.post('dosen_ajax.php', { 
            action: 'edit_jadwal', 
            id: $('#edit_id_jadwal').val(), 
            hari: $('#edit_hari').val(), 
            jam_m: $('#edit_jam_mulai').val(), 
            jam_s: $('#edit_jam_selesai').val(), 
            ruang: $('#edit_ruang').val(), 
            kelas: $('#edit_kelas').val(), 
            kuota: $('#edit_kuota').val() 
        }, function(res) { 
            Swal.fire('Info',res,'success').then(()=>location.reload()); 
        });
    }

    function hapusJadwalCurrent() {
        Swal.fire({title:'Hapus Jadwal?', icon:'warning', showCancelButton:true, confirmButtonColor:'#d33'}).then((r)=>{
            if(r.isConfirmed) $.post('dosen_ajax.php', {action:'hapus_jadwal', id:window.currentIdJadwal}, function(res){ 
                Swal.fire('Terhapus',res,'success').then(()=>location.reload()); 
            });
        });
    }
</script>
</body>
</html>