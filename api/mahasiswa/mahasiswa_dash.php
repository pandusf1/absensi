<?php
// SETTING DEBUG
ini_set('display_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Jakarta');

// 1. CEK LOGIN PAKAI COOKIE
if (!isset($_COOKIE['status_login']) || $_COOKIE['role'] != 'mahasiswa') {
    header("Location: ../index.php"); 
    exit;
}

require_once __DIR__ . '/../database.php';

// 2. AMBIL DATA
$nim_mhs = $_COOKIE['nim']; 

$q_mhs = mysqli_query($conn, "SELECT * FROM `data` WHERE nim = '$nim_mhs'");
if (!$q_mhs) { die("Error SQL: " . mysqli_error($conn)); }
$mhs = mysqli_fetch_assoc($q_mhs);

if(!$mhs) { 
    setcookie('status_login', '', time() - 3600, '/');
    header("Location: ../index.php");
    die("Error: Data NIM tidak ditemukan."); 
}

$kelas_mhs = $mhs['kelas'];

// Setup Variabel
$hari_inggris = date('l');
$map_hari = ['Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'];
$hari_ini = $map_hari[$hari_inggris];
$tgl_ini = date('Y-m-d');
$page = isset($_GET['page']) ? $_GET['page'] : 'home';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Mahasiswa</title>
    
    <link rel="icon" href="data:,">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="../aset/js/face-api.min.js"></script> 

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* === CSS UTAMA (TIDAK DIUBAH) === */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; min-height: 100vh; font-size: 13px; color: #333; overflow-x: hidden; }
        
        .sidebar { width: 250px; background: #1e293b; color: white; position: fixed; height: 100vh; left: -250px; top: 0; z-index: 1000; transition: 0.3s; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
        .sidebar.active { left: 0; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid #334155; display: flex; align-items: center; gap: 10px; background: #0f172a; }
        .menu { list-style: none; padding-top: 10px; }
        .menu li a { display: flex; align-items: center; padding: 14px 20px; color: #cbd5e1; text-decoration: none; transition: 0.2s; gap: 12px; font-size: 13px; border-left: 3px solid transparent; }
        .menu li a:hover, .menu li a.active { background-color: #334155; color: #60a5fa; border-left-color: #60a5fa; }
        
        .main-content { flex: 1; margin-left: 0; padding: 20px; width: 100%; transition: 0.3s; }
        
        .top-bar { display: flex; justify-content: space-between; align-items: center; background: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.03); margin-bottom: 25px; }
        .btn-burger { display: block; background: none; border: none; font-size: 20px; cursor: pointer; color: #333; }
        
        .overlay-sidebar { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 900; }
        .overlay-sidebar.active { display: block; }
        
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 20px; }
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; white-space: nowrap; }
        th, td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; text-align: left; }
        th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 12px; }
        .btn { padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer; font-weight: 500; font-size: 12px; color: white; transition: 0.2s; }
        .btn-green { background: #10b981; } .btn-blue { background: #3b82f6; } .btn-disabled { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; justify-content: center; align-items: center; }
        .modal-content { background: white; padding: 20px; border-radius: 15px; width: 90%; max-width: 450px; text-align: center; }
        #video-container { width: 100%; height: 300px; background: #000; border-radius: 10px; overflow: hidden; position: relative; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
        video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
        canvas { position: absolute; top: 0; left: 0; }
        
        .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); text-align: center; border-bottom: 4px solid #ddd; }
        .stat-card h3 { font-size: 28px; margin-bottom: 5px; color: #1e293b; }

        @media (min-width: 769px) {
            .main-content.active { margin-left: 250px; width: calc(100% - 250px); }
        }
    </style>
</head>
<body>

    <div class="overlay-sidebar" onclick="toggleSidebar()"></div>

    <nav class="sidebar" id="mySidebar">
        <div class="sidebar-header">
            <img src="../aset/img/polines.png" onerror="this.src='https://via.placeholder.com/40'" alt="Logo" style="width: 35px;">
            <div>
                <h3 style="margin:0; font-size:14px; color:white;">PORTAL MAHASISWA</h3>
                <small style="font-size:11px; color:#94a3b8;">Sistem Absensi Polines</small>
            </div>
        </div>
        <ul class="menu">
            <li><a href="?page=home" class="<?= $page=='home'?'active':'' ?>"><i class="fa-solid fa-home"></i> Dashboard</a></li>
            <li><a href="?page=jadwal" class="<?= $page=='jadwal'?'active':'' ?>"><i class="fa-solid fa-calendar-alt"></i> Jadwal & Absen</a></li>
            <li><a href="?page=riwayat" class="<?= $page=='riwayat'?'active':'' ?>"><i class="fa-solid fa-clock-rotate-left"></i> Riwayat</a></li>
            <li><a href="?page=update_wajah" class="<?= $page=='update_wajah'?'active':'' ?>"><i class="fa-solid fa-face-viewfinder"></i> Scan Wajah</a></li>
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
                        elseif($page=='jadwal') echo 'Jadwal Kuliah';
                        elseif($page=='riwayat') echo 'Riwayat Kehadiran';
                        elseif($page=='update_wajah') echo 'Registrasi Wajah';
                    ?>
                </h3>
            </div>
        </div>

        <?php if ($page == 'home'): ?>
            <?php
            $q_krs = mysqli_query($conn, "SELECT COALESCE(SUM(m.sks), 0) as total_sks, COUNT(j.id_jadwal) as total_mk FROM jadwal j JOIN matkul m ON j.kode_matkul = m.kode_matkul WHERE j.kelas = '$kelas_mhs'");
            $d_krs = $q_krs ? mysqli_fetch_assoc($q_krs) : ['total_sks'=>0, 'total_mk'=>0];

            $q_stat = mysqli_query($conn, "SELECT SUM(CASE WHEN status='Alpha' THEN 1 ELSE 0 END) as tot_alpha, SUM(CASE WHEN status='Izin' THEN 1 ELSE 0 END) as tot_izin, SUM(CASE WHEN status='Sakit' THEN 1 ELSE 0 END) as tot_sakit FROM presensi_kuliah WHERE nim = '$nim_mhs'");
            $d_stat = $q_stat ? mysqli_fetch_assoc($q_stat) : ['tot_alpha'=>0, 'tot_izin'=>0, 'tot_sakit'=>0];
            ?>

            <div class="stat-grid">
                <div class="stat-card" style="border-bottom-color: #8b5cf6;"><h3><?= $d_krs['total_sks'] ?></h3><p>Total SKS</p></div>
                <div class="stat-card" style="border-bottom-color: #3b82f6;"><h3><?= $d_krs['total_mk'] ?></h3><p>Mata Kuliah</p></div>
                <div class="stat-card" style="border-bottom-color: #ef4444;"><h3><?= $d_stat['tot_alpha'] ?? 0 ?></h3><p>Alpha</p></div>
                <div class="stat-card" style="border-bottom-color: #10b981;"><h3><?= $d_stat['tot_sakit'] ?? 0 ?></h3><p>Sakit</p></div>
            </div>

            <div class="card">
                <h3>Biodata Saya</h3>
                <div class="table-responsive">
                    <table style="max-width: 600px;">
                        <tr><td width="150"><strong>NIM</strong></td><td><?= $mhs['nim'] ?></td></tr>
                        <tr><td><strong>Nama Lengkap</strong></td><td><?= $mhs['nama'] ?></td></tr>
                        <tr><td><strong>Kelas</strong></td><td><span style="background:#e0f2fe; color:#0284c7; padding:2px 8px; border-radius:4px; font-weight:bold;"><?= $mhs['kelas'] ?></span></td></tr>
                        <tr><td><strong>Jurusan</strong></td><td><?= $mhs['jurusan'] ?></td></tr>
                        <tr><td><strong>Program Studi</strong></td><td><?= $mhs['prodi'] ?></td></tr>
                        <tr><td><strong>Email</strong></td><td><?= $mhs['email'] ?></td></tr>
                    </table>
                </div>
            </div>

        <?php elseif ($page == 'jadwal'): ?>
            <div class="card">
                <h3 style="margin-bottom:15px; color:#3b82f6;"><i class="fa-solid fa-calendar-day"></i> Jadwal Hari Ini (<?= $hari_ini . ', ' . date('d M Y') ?>)</h3>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>Jam</th><th>Mata Kuliah</th><th>Dosen</th><th>Ruang</th><th style="text-align:center;">Aksi Absen</th></tr></thead>
                        <tbody>
                            <?php
                            $qj = mysqli_query($conn, "SELECT j.*, m.nama_matkul, m.kode_matkul, d.nama_dosen FROM jadwal j JOIN matkul m ON j.kode_matkul = m.kode_matkul JOIN dosen d ON j.nip = d.nip WHERE j.kelas = '$kelas_mhs' AND j.hari = '$hari_ini' ORDER BY j.jam_mulai ASC");
                            if(mysqli_num_rows($qj) > 0):
                                while($r = mysqli_fetch_assoc($qj)):
                                    $q_real = mysqli_query($conn, "SELECT * FROM realisasi_mengajar WHERE id_jadwal='".$r['id_jadwal']."' AND tanggal='$tgl_ini' AND status='Berlangsung'");
                                    $is_mulai = (mysqli_num_rows($q_real) > 0);
                                    $q_absen = mysqli_query($conn, "SELECT * FROM presensi_kuliah WHERE id_jadwal='".$r['id_jadwal']."' AND tanggal='$tgl_ini' AND nim='$nim_mhs'");
                                    $sudah_absen = (mysqli_num_rows($q_absen) > 0);
                            ?>
                            <tr>
                                <td><span style="background:#f1f5f9; padding:4px 8px; border-radius:4px; font-weight:600;"><?= substr($r['jam_mulai'],0,5) ?> - <?= substr($r['jam_selesai'],0,5) ?></span></td>
                                <td><div style="font-weight:600;"><?= $r['nama_matkul'] ?></div><small style="color:#64748b;"><?= $r['kode_matkul'] ?></small></td>
                                <td><?= $r['nama_dosen'] ?></td>
                                <td><?= $r['ruang'] ?></td>
                                <td style="text-align:center;">
                                    <?php if($sudah_absen): ?><button class="btn btn-green" style="cursor:default;"><i class="fa-solid fa-check-circle"></i> Hadir</button>
                                    <?php elseif($is_mulai): ?><button class="btn btn-blue" onclick="bukaKamera(<?= $r['id_jadwal'] ?>)"><i class="fa-solid fa-camera"></i> Absen</button>
                                    <?php else: ?><button class="btn btn-disabled"><i class="fa-solid fa-lock"></i> Tutup</button><?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">Tidak ada jadwal kuliah hari ini.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div id="modalKamera" class="modal">
                <div class="modal-content">
                    <h3>Verifikasi Wajah</h3>
                    <div id="video-container"><video id="video" autoplay muted playsinline></video></div>
                    <div id="statusScan" style="font-weight:bold; color:#3b82f6; margin-bottom:15px;">Memuat AI...</div>
                    <button class="btn" style="background:#ef4444;" onclick="tutupKamera()">Batal</button>
                </div>
            </div>

        <?php elseif ($page == 'riwayat'): ?>
            <div class="card">
                <h3>Riwayat Kehadiran</h3>
                <div class="table-responsive">
                    <table>
                        <thead><tr><th>Tanggal</th><th>Jam</th><th>Mata Kuliah</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php
                            $qr = mysqli_query($conn, "SELECT p.*, m.nama_matkul FROM presensi_kuliah p JOIN jadwal j ON p.id_jadwal = j.id_jadwal JOIN matkul m ON j.kode_matkul = m.kode_matkul WHERE p.nim = '$nim_mhs' ORDER BY p.tanggal DESC LIMIT 20");
                            while($row = mysqli_fetch_assoc($qr)):
                                $st = $row['status']; $badge = ($st=='Hadir') ? '#dcfce7; color:#166534' : (($st=='Alpha') ? '#fee2e2; color:#991b1b' : '#fef3c7; color:#92400e');
                            ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                <td><?= substr($row['waktu_hadir'],0,5) ?></td>
                                <td><?= $row['nama_matkul'] ?></td>
                                <td><span style="background:<?= $badge ?>; padding:4px 10px; border-radius:15px; font-weight:bold; font-size:11px;"><?= $st ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($page == 'update_wajah'): ?>
            <div class="card" style="max-width:500px; margin:0 auto; text-align:center;">
                <h3>Registrasi Wajah</h3>
                <p style="color:#64748b; font-size:12px; margin-bottom:20px;">Pastikan wajah terlihat jelas.</p>
                <div style="width:100%; height:350px; background:#000; border-radius:10px; overflow:hidden; margin-bottom:15px; position:relative;">
                    <video id="videoReg" autoplay muted playsinline style="width:100%; height:100%; object-fit:cover; transform: scaleX(-1);"></video>
                </div>
                <div style="display:flex; justify-content:center; gap:10px;">
                    <button id="btnMulaiReg" class="btn btn-blue" onclick="mulaiKameraReg()"><i class="fa-solid fa-camera"></i> Mulai</button>
                    <button id="btnSimpanReg" class="btn btn-disabled" onclick="simpanWajah()" disabled><i class="fa-solid fa-save"></i> Simpan</button>
                </div>
                <p id="msgReg" style="margin-top:15px; font-weight:600; color:#3b82f6;"></p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleSidebar() { 
            document.getElementById('mySidebar').classList.toggle('active'); 
            document.getElementById('mainContent').classList.toggle('active');
            document.querySelector('.overlay-sidebar').classList.toggle('active'); 
        }

        function logout() {
            document.cookie = "status_login=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            document.cookie = "nim=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            document.cookie = "role=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            window.location.href = '../index.php';
        }

        // --- FACE API LOGIC ---
        const TINY_FACE_OPTIONS = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });
        let isModelLoaded = false;

        // PERBAIKAN PATH MODEL: ../../
        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('../aset/models'),
            faceapi.nets.faceLandmark68Net.loadFromUri('../aset/models'),
            faceapi.nets.faceRecognitionNet.loadFromUri('../aset/models')
        ]).then(() => { isModelLoaded = true; console.log("AI Loaded"); }).catch(err => { console.error(err); });

        <?php if ($page == 'jadwal'): ?>
        let currentJadwalId = null, video = document.getElementById('video'), stream = null, detectInterval;
        function bukaKamera(id) {
            if(!isModelLoaded) { Swal.fire("Tunggu", "Memuat AI...", "info"); return; }
            currentJadwalId = id; $('#modalKamera').css('display', 'flex');
            $.post('mahasiswa_ajax.php', { action: 'get_face_descriptor', nim: '<?= $nim_mhs ?>' }, function(res){
                try {
                    let rawData = JSON.parse(res);
                    if (rawData && typeof rawData === 'object' && !Array.isArray(rawData)) rawData = Object.values(rawData);
                    const targetDescriptor = new faceapi.LabeledFaceDescriptors('<?= $nim_mhs ?>', [new Float32Array(rawData)]);
                    $('#statusScan').text("Mencari wajah...");
                    navigator.mediaDevices.getUserMedia({ video: {} }).then(s => {
                        stream = s; video.srcObject = stream;
                        video.onloadedmetadata = () => { video.play(); startDetection(targetDescriptor); };
                    });
                } catch(e) { Swal.fire("Gagal", "Wajah belum terdaftar.", "warning"); tutupKamera(); }
            });
        }
        function startDetection(targetDescriptor) {
            $('canvas').remove();
            const canvas = faceapi.createCanvasFromMedia(video);
            $('#video-container').append(canvas);
            const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
            faceapi.matchDimensions(canvas, displaySize);
            detectInterval = setInterval(async () => {
                const detection = await faceapi.detectSingleFace(video, TINY_FACE_OPTIONS).withFaceLandmarks().withFaceDescriptor();
                const ctx = canvas.getContext('2d'); ctx.clearRect(0, 0, canvas.width, canvas.height);
                if (detection) {
                    const match = new faceapi.FaceMatcher(targetDescriptor, 0.45).findBestMatch(detection.descriptor);
                    new faceapi.draw.DrawBox(faceapi.resizeResults(detection, displaySize).detection.box, { label: match.toString(), boxColor: match.label === '<?= $nim_mhs ?>' ? "green" : "red" }).draw(canvas);
                    if (match.label === '<?= $nim_mhs ?>') { clearInterval(detectInterval); simpanAbsen(currentJadwalId); }
                }
            }, 300);
        }
        function simpanAbsen(id) {
            $.post('mahasiswa_ajax.php', { action: 'simpan_absen', id_jadwal: id, nim: '<?= $nim_mhs ?>' }, function(res){
                Swal.fire({ title: "Berhasil", text: "Absensi Berhasil!", icon: "success", timer: 1500, showConfirmButton: false }).then(() => location.reload());
            });
        }
        function tutupKamera() { $('#modalKamera').hide(); if(detectInterval) clearInterval(detectInterval); if(stream) stream.getTracks().forEach(t => t.stop()); $('canvas').remove(); }
        <?php endif; ?>

        <?php if ($page == 'update_wajah'): ?>
        let regStream, regInterval, lastDescriptor;
        function mulaiKameraReg() {
            if(!isModelLoaded) return;
            navigator.mediaDevices.getUserMedia({ video: {} }).then(s => {
                regStream = s; document.getElementById('videoReg').srcObject = regStream;
                document.getElementById('videoReg').onloadedmetadata = () => { document.getElementById('videoReg').play(); detectRegLoop(); };
                $('#btnMulaiReg').hide(); $('#msgReg').text("Lihat kamera...");
            });
        }
        function detectRegLoop() {
            regInterval = setInterval(async () => {
                const detection = await faceapi.detectSingleFace(document.getElementById('videoReg'), TINY_FACE_OPTIONS).withFaceLandmarks().withFaceDescriptor();
                if (detection) { lastDescriptor = detection.descriptor; $('#btnSimpanReg').prop('disabled', false).removeClass('btn-disabled').addClass('btn-green'); $('#msgReg').text("Wajah OK! Klik Simpan."); }
            }, 500);
        }
        function simpanWajah() {
            if(!lastDescriptor) return;
            $.post('mahasiswa_ajax.php', { action: 'update_face', nim: '<?= $nim_mhs ?>', descriptor: JSON.stringify(Array.from(lastDescriptor)) }, function(res){
                clearInterval(regInterval); if(regStream) regStream.getTracks().forEach(t => t.stop());
                Swal.fire("Sukses", "Data Wajah Disimpan!", "success").then(() => location.href='?page=home');
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>