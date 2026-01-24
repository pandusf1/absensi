<?php
include '../database.php';
date_default_timezone_set('Asia/Jakarta');

$nim_mhs = '4.41.23.2.23'; // Sesuaikan NIM

// Ambil Data Profil
$q_mhs = mysqli_query($conn, "SELECT * FROM data WHERE nim = '$nim_mhs'");
$mhs = mysqli_fetch_assoc($q_mhs);
if(!$mhs) { die("Error: NIM tidak ditemukan."); }
$kelas_mhs = $mhs['kelas'];

// Cek status wajah
$status_wajah_text = !empty($mhs['face_descriptor']) ? "Sudah Ada" : "Belum Ada";
$status_wajah_color = !empty($mhs['face_descriptor']) ? "#10b981" : "#ef4444";

// Setup Waktu
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
    
    <script src="../aset/js/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../aset/js/face-api.min.js"></script> 

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        /* === STYLE TETAP SAMA (Hanya perbaikan responsif) === */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; min-height: 100vh; overflow-x: hidden; font-size: 12px; position: relative; }
        
        .sidebar { width: 240px; background: #0f172a; color: white; position: fixed; height: 100vh; left: -240px; top: 0; z-index: 2000; transition: 0.3s; }
        .sidebar.active { left: 0; }
        .sidebar-header { padding: 20px 15px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; }
        .menu { list-style: none; }
        .menu li a { display: flex; align-items: center; padding: 12px 20px; color: #94a3b8; text-decoration: none; transition: 0.2s; gap: 10px; font-size: 12px; }
        .menu li a:hover, .menu li a.active { background-color: #3b82f6; color: white; border-left: 3px solid white; }

        .main-content { flex: 1; margin-left: 0; padding: 15px; width: 100%; transition: 0.3s; }
        .top-bar { display: flex; align-items: center; margin-bottom: 20px; background: white; padding: 10px 15px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); gap: 15px; }
        .btn-burger { background: none; border: none; font-size: 20px; cursor: pointer; color: #333; }
        
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 15px; }
        
        /* TAMBAHAN: Agar tabel bisa discroll horizontal di HP */
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; white-space: nowrap; /* Mencegah teks turun baris */ }
        th, td { padding: 10px 8px; border-bottom: 1px solid #eee; text-align: left; font-size: 12px; }
        th { background: #f8fafc; color: #64748b; font-weight: 600; }
        
        .btn { padding: 6px 12px; border-radius: 5px; border: none; cursor: pointer; font-weight: 600; font-size: 11px; color: white; display:inline-block; }
        .btn-green { background: #22c55e; } .btn-blue { background: #3b82f6; } .btn-disabled { background: #cbd5e1; cursor: not-allowed; color: #64748b; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 15px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-bottom: 3px solid #3b82f6; }
        .stat-card h3 { font-size: 24px; color: #333; margin-bottom: 2px; }

        /* MODAL KHUSUS */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2500; align-items: center; justify-content: center; }
        .modal-content { background: white; padding: 10px; border-radius: 10px; width: 90%; max-width: 500px; text-align: center; }
        #video-container { position: relative; width: 100%; height: 350px; background: #000; border-radius: 8px; overflow: hidden; display:flex; justify-content:center; align-items:center; }
        video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
        canvas { position: absolute; top: 0; left: 0; }
        
        .overlay-sidebar { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1500; }
        .overlay-sidebar.active { display: block; }
        .swal2-container { z-index: 10000 !important; }
        
        /* PERBAIKAN RESPONSIF */
        @media (max-width: 768px) { 
            .main-content { margin-left: 0; }
            /* Hapus display:none pada sidebar agar animasi 'left' jalan */ 
        }
    </style>
</head>
<body>

    <div class="overlay-sidebar" onclick="toggleSidebar()"></div>

    <nav class="sidebar" id="mySidebar">
        <div class="sidebar-header">
            <div style="text-align: left;">
                <img src="../aset/img/polines.png" alt="Logo Polines" style="width: 35px; vertical-align: middle; margin-right: 5px;">
                <div style="display:inline-block; vertical-align: middle;">
                    <h3 style="margin:0; font-size:16px;">PORTAL MAHASISWA</h3>
                    <small style="font-size:10px;">Sistem Akademik</small>
                </div>
            </div>
        </div>
        <ul class="menu">
            <li><a href="?page=home" class="<?= $page=='home'?'active':'' ?>">Dashboard</a></li>
            <li><a href="?page=jadwal" class="<?= $page=='jadwal'?'active':'' ?>">Jadwal & Absen</a></li>
            <li><a href="?page=riwayat" class="<?= $page=='riwayat'?'active':'' ?>">Riwayat</a></li>
            <li><a href="?page=update_wajah" class="<?= $page=='update_wajah'?'active':'' ?>">Scan Wajah</a></li>
            <li><a href="../login/logout.php" style="color:#ef4444;">Keluar</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <div class="top-bar">
            <button class="btn-burger" onclick="toggleSidebar()">☰</button>
            <h3 style="margin:0; color:#333;">
                <?php 
                    if($page=='home') echo 'Dashboard Mahasiswa';
                    elseif($page=='jadwal') echo 'Jadwal Kuliah Hari Ini';
                    elseif($page=='riwayat') echo 'Riwayat Kehadiran';
                    elseif($page=='update_wajah') echo 'Daftar / Update Data Wajah';
                ?>
            </h3>
        </div>

        <?php if ($page == 'home'): ?>
            <?php
            $q_krs = mysqli_query($conn, "SELECT SUM(m.sks) as total_sks, COUNT(j.id_jadwal) as total_mk FROM jadwal j JOIN matkul m ON j.kode_matkul = m.kode_matkul WHERE j.kelas = '$kelas_mhs'");
            $d_krs = mysqli_fetch_assoc($q_krs);
            $q_stat = mysqli_query($conn, "SELECT SUM(CASE WHEN status='Alpha' THEN 1 ELSE 0 END) as tot_alpha, SUM(CASE WHEN status='Izin' THEN 1 ELSE 0 END) as tot_izin, SUM(CASE WHEN status='Sakit' THEN 1 ELSE 0 END) as tot_sakit FROM presensi_kuliah WHERE nim = '$nim_mhs'");
            $d_stat = mysqli_fetch_assoc($q_stat);
            ?>
            <div class="dashboard-grid">
                <div class="stat-card" style="border-color: #8b5cf6;"><h3><?= $d_krs['total_sks'] ?? 0 ?></h3><p>Total SKS</p></div>
                <div class="stat-card" style="border-color: #3b82f6;"><h3><?= $d_krs['total_mk'] ?? 0 ?></h3><p>Mata Kuliah</p></div>
                <div class="stat-card" style="border-color: #ef4444;"><h3><?= $d_stat['tot_alpha'] ?? 0 ?></h3><p>Alpha</p></div>
                <div class="stat-card" style="border-color: #f59e0b;"><h3><?= $d_stat['tot_izin'] ?? 0 ?></h3><p>Izin</p></div>
                <div class="stat-card" style="border-color: #10b981;"><h3><?= $d_stat['tot_sakit'] ?? 0 ?></h3><p>Sakit</p></div>
            </div>
            <div class="card">
                <h3 style="margin-bottom:10px;">Biodata Mahasiswa</h3>
                <div class="table-responsive"> <table style="width: 100%; max-width: 500px;">
                        <tr><td width="120">NIM</td><td><b><?= $mhs['nim'] ?></b></td></tr>
                        <tr><td>Nama</td><td><b><?= $mhs['nama'] ?></b></td></tr>
                        <tr><td>Kelas</td><td><b><?= $mhs['kelas'] ?></b></td></tr>
                        <tr><td>Jurusan</td><td><b><?= $mhs['jurusan'] ?></b></td></tr>
                        <tr><td>Prodi</td><td><b><?= $mhs['prodi'] ?></b></td></tr>
                        <tr><td>Email</td><td><b><?= $mhs['email'] ?></b></td></tr>
                    </table>
                </div>
            </div>

        <?php elseif ($page == 'jadwal'): ?>
            <div class="card" style="margin-bottom: 20px;">
                <h4 style="margin-bottom:10px; color:#3b82f6;">Jadwal Hari Ini (<?= $hari_ini . ', ' . date('d-m-Y') ?>)</h4>
                <div class="table-responsive"> <table>
                        <thead><tr><th>Jam</th><th>Mata Kuliah</th><th>Dosen</th><th>Ruang</th><th width="120" style="text-align:center;">Aksi</th></tr></thead>
<tbody>
                            <?php
                            // PERBAIKAN QUERY: Tambahkan JOIN ke tabel dosen
                            $qj = mysqli_query($conn, "SELECT j.*, m.nama_matkul, m.kode_matkul, d.nama_dosen 
                                                       FROM jadwal j 
                                                       JOIN matkul m ON j.kode_matkul = m.kode_matkul 
                                                       JOIN dosen d ON j.nip = d.nip 
                                                       WHERE j.kelas = '$kelas_mhs' AND j.hari = '$hari_ini' 
                                                       ORDER BY j.jam_mulai ASC");
                            
                            if(mysqli_num_rows($qj) > 0):
                                while($r = mysqli_fetch_assoc($qj)):
                                    $q_real = mysqli_query($conn, "SELECT * FROM realisasi_mengajar WHERE id_jadwal='".$r['id_jadwal']."' AND tanggal='$tgl_ini' AND status='Berlangsung'");
                                    $is_mulai = (mysqli_num_rows($q_real) > 0);
                                    $q_absen = mysqli_query($conn, "SELECT * FROM presensi_kuliah WHERE id_jadwal='".$r['id_jadwal']."' AND tanggal='$tgl_ini' AND nim='$nim_mhs'");
                                    $sudah_absen = (mysqli_num_rows($q_absen) > 0);
                            ?>
                            <tr>
                                <td><?= substr($r['jam_mulai'],0,5) ?> - <?= substr($r['jam_selesai'],0,5) ?></td>
                                <td><b><?= $r['nama_matkul'] ?></b><br><small><?= $r['kode_matkul'] ?></small></td>
                                
                                <td style="font-size:11px; color:#666;"><?= $r['nama_dosen'] ?></td>
                                
                                <td><span><?= $r['ruang'] ?></span></td>
                                <td style="text-align:center;">
                                    <?php if($sudah_absen): ?><span style="color:#10b981; font-weight:bold;">✅ Hadir</span>
                                    <?php elseif($is_mulai): ?><button class="btn btn-green" onclick="bukaKamera(<?= $r['id_jadwal'] ?>)">Absen</button>
                                    <?php else: ?><button class="btn btn-disabled">Menunggu</button><?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; else: echo "<tr><td colspan='5' align='center'>Tidak ada jadwal kuliah hari ini.</td></tr>"; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h4 style="margin-bottom:10px; color:#64748b;">Daftar Mata Kuliah</h4>
                <div class="table-responsive"> <table>
                        <thead><tr><th>Hari</th><th>Jam</th><th>Mata Kuliah</th><th>SKS</th><th>Dosen</th></tr></thead>
                        <tbody>
                            <?php
                            $today_idx = date('N'); 
                            $q_all = mysqli_query($conn, "SELECT j.*, m.nama_matkul, m.sks, d.nama_dosen FROM jadwal j JOIN matkul m ON j.kode_matkul = m.kode_matkul JOIN dosen d ON j.nip = d.nip WHERE j.kelas = '$kelas_mhs' ORDER BY MOD(FIELD(j.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') - $today_idx + 7, 7) ASC, j.jam_mulai ASC");
                            while($row = mysqli_fetch_assoc($q_all)):
                                $bg_style = ($row['hari'] == $hari_ini) ? "background:#f0f9ff;" : "";
                            ?>
                            <tr style="<?= $bg_style ?>">
                                <td><?= $row['hari'] ?></td><td><?= substr($row['jam_mulai'],0,5) ?></td><td><?= $row['nama_matkul'] ?></td><td><?= $row['sks'] ?></td><td><?= $row['nama_dosen'] ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="modalKamera" class="modal">
                <div class="modal-content">
                    <h4 style="margin-bottom:10px;">Verifikasi Wajah</h4>
                    <div id="video-container"><video id="video" autoplay muted playsinline></video></div>
                    <p id="statusScan" style="margin-top:10px;">Memuat Model AI...</p>
                    <button class="btn" style="background:#ef4444; color:white; margin-top:10px;" onclick="tutupKamera()">Batal</button>
                </div>
            </div>

        <?php elseif ($page == 'riwayat'): ?>
            <div class="card">
                <div class="table-responsive"> <table>
                        <thead><tr><th>Tanggal</th><th>Jam</th><th>Mata Kuliah</th><th>Dosen</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php
                            $qr = mysqli_query($conn, "SELECT p.*, m.nama_matkul, j.jam_mulai, d.nama_dosen FROM presensi_kuliah p JOIN jadwal j ON p.id_jadwal = j.id_jadwal JOIN matkul m ON j.kode_matkul = m.kode_matkul JOIN dosen d ON j.nip = d.nip WHERE p.nim = '$nim_mhs' ORDER BY p.tanggal DESC, p.waktu_hadir DESC");
                            while($row = mysqli_fetch_assoc($qr)):
                                $color = ($row['status']=='Hadir') ? '#10b981' : (($row['status']=='Alpha') ? '#ef4444' : '#f59e0b');
                            ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td><td><?= substr($row['waktu_hadir'],0,5) ?></td><td><?= $row['nama_matkul'] ?></td><td><?= $row['nama_dosen'] ?></td><td><span style="color:<?= $color ?>; font-weight:bold;"><?= $row['status'] ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($page == 'update_wajah'): ?>
            <div class="card" style="max-width: 600px; margin: 0 auto; text-align:center;">
                <h4 style="margin-bottom:10px;">Daftar / Update Data Wajah</h4>
                <p style="color:#666; margin-bottom:20px;">Pastikan pencahayaan cukup terang agar wajah terdeteksi.</p>
                
                <div style="position:relative; width:100%; max-width:400px; height:300px; background:#000; border-radius:10px; overflow:hidden; margin:0 auto 15px auto; display:flex; justify-content:center; align-items:center;">
                    <video id="videoReg" autoplay muted playsinline style="width:100%; height:100%; object-fit:cover; transform:scaleX(-1);"></video>
                </div>

                <div style="display:flex; justify-content:center; gap:10px;">
                    <button class="btn btn-blue" id="btnMulaiReg" onclick="mulaiKameraReg()">Buka Kamera</button>
                    <button class="btn btn-green" id="btnSimpanReg" onclick="simpanWajah()" style="display:none;">Simpan Wajah</button>
                </div>
                <p id="msgReg" style="margin-top:10px; font-weight:bold; color:#3b82f6;"></p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function toggleSidebar() { 
            document.getElementById('mySidebar').classList.toggle('active'); 
            document.querySelector('.overlay-sidebar').classList.toggle('active'); 
        }
        
        // --- KONFIGURASI UMUM FACE API ---
        const TINY_FACE_OPTIONS = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.4 });
        let isModelLoaded = false;

        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('../aset/models'),
            faceapi.nets.faceLandmark68Net.loadFromUri('../aset/models'),
            faceapi.nets.faceRecognitionNet.loadFromUri('../aset/models')
        ]).then(() => {
            isModelLoaded = true;
            console.log("✅ Model AI Loaded");
        }).catch(err => {
            console.error(err);
            Swal.fire("Error", "Gagal Load Model di '../aset/models'", "error");
        });

        <?php if ($page == 'jadwal'): ?>
        let currentJadwalId = null;
        let video = document.getElementById('video');
        let stream = null;
        let detectInterval;

        function bukaKamera(id_jadwal) {
            if(!isModelLoaded) { Swal.fire("Loading...", "AI Sedang dimuat, tunggu sebentar.", "info"); return; }
            currentJadwalId = id_jadwal;
            $('#modalKamera').css('display', 'flex');
            $('#statusScan').text("Mengambil Data Wajah...");

            $.post('mahasiswa_ajax.php', { action: 'get_face_descriptor', nim: '<?= $nim_mhs ?>' }, function(res) {
                if(res === 'null' || res === '') { Swal.fire("Gagal", "Wajah belum terdaftar! Silakan ke menu Update Wajah.", "warning"); tutupKamera(); return; }
                try {
                    let rawData = JSON.parse(res);
                    if (rawData && typeof rawData === 'object' && !Array.isArray(rawData)) rawData = Object.values(rawData);
                    const floatArray = new Float32Array(rawData);
                    const targetDescriptor = new faceapi.LabeledFaceDescriptors('<?= $nim_mhs ?>', [floatArray]);
                    $('#statusScan').text("Mencari wajah...");
                    startVideo(targetDescriptor);
                } catch(e) { Swal.fire("Error", "Format Data Wajah Rusak.", "error"); tutupKamera(); }
            });
        }

        function startVideo(targetDescriptor) {
            navigator.mediaDevices.getUserMedia({ video: {} }).then(s => {
                stream = s;
                video.srcObject = stream;
                video.onloadedmetadata = () => { video.play(); startDetectionLoop(targetDescriptor); };
            }).catch(err => Swal.fire("Error", "Kamera tidak diizinkan.", "error"));
        }

        function startDetectionLoop(targetDescriptor) {
            $('canvas').remove();
            const canvas = faceapi.createCanvasFromMedia(video);
            $('#video-container').append(canvas);
            const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
            faceapi.matchDimensions(canvas, displaySize);

            if(detectInterval) clearInterval(detectInterval);
            detectInterval = setInterval(async () => {
                const detection = await faceapi.detectSingleFace(video, TINY_FACE_OPTIONS).withFaceLandmarks().withFaceDescriptor();
                const ctx = canvas.getContext('2d'); ctx.clearRect(0, 0, canvas.width, canvas.height);

                if (detection) {
                    const resizedDetections = faceapi.resizeResults(detection, displaySize);
                    const box = resizedDetections.detection.box;
                    const match = new faceapi.FaceMatcher(targetDescriptor, 0.45).findBestMatch(detection.descriptor);

                    if (match.label === '<?= $nim_mhs ?>') {
                        new faceapi.draw.DrawBox(box, { label: "Cocok! ✅", boxColor: "#00ff00" }).draw(canvas);
                        $('#statusScan').text("Wajah Cocok! Menyimpan...");
                        simpanAbsen(currentJadwalId);
                        tutupKamera();
                    } else {
                        new faceapi.draw.DrawBox(box, { label: "Tidak Cocok ❌", boxColor: "red" }).draw(canvas);
                        $('#statusScan').text("Wajah Tidak Cocok ("+Math.round(match.distance*100)+")");
                    }
                }
            }, 200);
        }

        function tutupKamera() {
            $('#modalKamera').hide();
            if(detectInterval) clearInterval(detectInterval);
            if(stream) { stream.getTracks().forEach(track => track.stop()); }
            $('canvas').remove();
        }

        function simpanAbsen(id) {
            $.post('mahasiswa_ajax.php', { action: 'simpan_absen', id_jadwal: id, nim: '<?= $nim_mhs ?>' }, function(res) {
                Swal.fire({title: "Berhasil!", text: "Absen Tercatat.", icon: "success"}).then(() => location.reload());
            });
        }
        <?php endif; ?>

        <?php if ($page == 'update_wajah'): ?>
        let regStream = null;
        let regInterval;
        let lastDescriptor = null;

        function mulaiKameraReg() {
            if(!isModelLoaded) { Swal.fire("Loading AI...", "Tunggu sebentar.", "info"); return; }
            $('#msgReg').text("Menyalakan Kamera...");
            
            navigator.mediaDevices.getUserMedia({ video: {} }).then(s => {
                regStream = s;
                const v = document.getElementById('videoReg');
                v.srcObject = regStream;
                v.onloadedmetadata = () => {
                    v.play();
                    $('#btnMulaiReg').hide();
                    $('#btnSimpanReg').show().prop('disabled', true).text("Mendeteksi...").removeClass('btn-green').addClass('btn-disabled');
                    detectRegLoop();
                };
            }).catch(err => Swal.fire("Error", "Kamera ditolak.", "error"));
        }

        function detectRegLoop() {
            const v = document.getElementById('videoReg');
            if(regInterval) clearInterval(regInterval);

            regInterval = setInterval(async () => {
                const detection = await faceapi.detectSingleFace(v, TINY_FACE_OPTIONS).withFaceLandmarks().withFaceDescriptor();
                if (detection) {
                    lastDescriptor = detection.descriptor;
                    $('#btnSimpanReg').prop('disabled', false).text("📸 SIMPAN WAJAH").removeClass('btn-disabled').addClass('btn-green');
                    $('#msgReg').text("Wajah Terdeteksi! Silakan klik Simpan.");
                } else {
                    $('#btnSimpanReg').prop('disabled', true).text("Mencari Wajah...").removeClass('btn-green').addClass('btn-disabled');
                    $('#msgReg').text("Wajah tidak terlihat...");
                }
            }, 200);
        }

        function simpanWajah() {
            if(!lastDescriptor) return;
            const descriptorArray = Array.from(lastDescriptor);
            const jsonDescriptor = JSON.stringify(descriptorArray);

            $.post('mahasiswa_ajax.php', { action: 'update_face', nim: '<?= $nim_mhs ?>', descriptor: jsonDescriptor }, function(res) {
                if(regInterval) clearInterval(regInterval);
                if(regStream) regStream.getTracks().forEach(t => t.stop());
                Swal.fire("Sukses", "Data Wajah Berhasil Diperbarui!", "success").then(() => location.href = '?page=home');
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>