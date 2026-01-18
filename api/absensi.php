<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Mahasiswa</title>
    
    <script src="aset/js/jquery-3.7.1.min.js"></script>
    <script src="aset/js/face-api.min.js"></script>
    
    <style>
        /* --- STYLE TIDAK DIUBAH (SAMA PERSIS) --- */
        body { font-family: 'Poppins', sans-serif; background: #eef2f5; display: flex; flex-direction: column; align-items: center; min-height: 100vh; margin: 0; padding: 20px; margin-left: 4%; margin-right: 4%; }
        .card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 500px; text-align: center; position: relative; }
        .logout-btn { display: block; width: 100%; padding: 7px; background-color: #f53b57; color: #fff; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: bold; transition: 0.2s; border: 1px solid #d63031; margin-bottom: 15px; cursor: pointer; box-sizing: border-box; }  
        .logout-btn:hover { background-color: #d63031; }  
        h2 { margin: 0 0 5px 0; color: #333; }
        p#jam { font-size: 16x; color: #666; margin-bottom: 20px; margin-top: 0; }
        .mode-selector { display: flex; gap: 10px; margin-bottom: 20px; }
        .btn-mode { flex: 1; padding: 15px; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; font-weight: bold; background: white; font-size: 14px; transition: 0.2s; }
        .btn-mode.active-masuk { background: #d4edda; border-color: #28a745; color: #155724; }
        .btn-mode.active-keluar { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .input-group { margin: 20px 0; display: none; } 
        input[type="text"] { width: 65%; padding: 14px; border: 1px solid #ccc; border-radius: 8px; font-size: 16px; text-align: center; }
        .btn-cek { width: 30%; padding: 14px; background: #007bff; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: bold; margin-top: 10px;}
        #video-container { position: relative; width: 100%; height: auto; min-height: 300px; background: #000; border-radius: 10px; display: none; overflow: hidden; margin-bottom: 15px; }
        video { width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1); }
        canvas { position: absolute; top: 0; left: 0; }
        #btn-absen-utama { display: none; width: 100%; padding: 15px; border: none; border-radius: 50px; font-size: 18px; font-weight: bold; cursor: pointer; color: white; margin-top: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.2); transition: 0.2s; }
        .bg-masuk { background: #28a745; } .bg-masuk:hover { background: #218838; }
        .bg-keluar { background: #dc3545; } .bg-keluar:hover { background: #c82333; }
        .status-txt { font-weight: bold; font-size: 16px; color: #555; margin-bottom: 5px; }
        .gps-status { font-size: 12px; color: #aaa; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="card">
        <button class="logout-btn" onclick="window.location.href='index.php'">Kembali</button>
        
        <h2>Absensi Wajah</h2>
        <p id="jam">--:--:--</p>

        <div class="mode-selector">
            <button id="btn-masuk" class="btn-mode" onclick="setMode('masuk')">MASUK</button>
            <button id="btn-keluar" class="btn-mode" onclick="setMode('keluar')">PULANG</button>
        </div>

        <div id="step-input" class="input-group">
            <input type="text" id="input-nim" placeholder="Masukkan NIM...">
            <button class="btn-cek" onclick="cekNIM()">Lanjut</button>
        </div>

        <div id="video-container">
            <video id="video" autoplay muted playsinline></video>
        </div>
        
        <div class="status-txt" id="status">Pilih Mode Absen</div>

        <button id="btn-absen-utama" onclick="prosesAbsen()">ABSEN SEKARANG</button>
        
        <div class="gps-status" id="gps-info">Mencari GPS...</div>
    </div>

<script>
    const TINY_FACE_OPTIONS = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.4 });
    let currentMode = '';
    let targetDescriptor = null; 
    let currentNIM = '';
    let userLat = '-', userLng = '-';
    let detectInterval; 

    // Jam Digital
    setInterval(() => document.getElementById('jam').innerText = new Date().toLocaleTimeString('id-ID'), 1000);

    // GPS (Optional, kalau error tetap jalan)
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
            userLat = pos.coords.latitude;
            userLng = pos.coords.longitude;
            $('#gps-info').text(`GPS Aktif: ${userLat.toFixed(4)}, ${userLng.toFixed(4)}`);
        }, err => $('#gps-info').text("GPS Mati / Tidak diizinkan."));
    } else {
        $('#gps-info').text("Browser tidak support GPS.");
    }

    // Load Model
    Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri('aset/models'),
        faceapi.nets.faceLandmark68Net.loadFromUri('aset/models'),
        faceapi.nets.faceRecognitionNet.loadFromUri('aset/models')
    ]).then(() => {
        console.log("✅ Model AI Loaded");
    }).catch(err => alert("Gagal Load Model AI. Cek folder aset/models."));

    function setMode(mode) {
        currentMode = mode;
        if(detectInterval) clearInterval(detectInterval);
        
        $('.btn-mode').removeClass('active-masuk active-keluar');
        $('#btn-absen-utama').hide().removeClass('bg-masuk bg-keluar');
        
        if(mode == 'masuk') {
            $('#btn-masuk').addClass('active-masuk');
            $('#btn-absen-utama').text("ABSEN MASUK").addClass('bg-masuk');
        } else {
            $('#btn-keluar').addClass('active-keluar');
            $('#btn-absen-utama').text("ABSEN PULANG").addClass('bg-keluar');
        }

        $('#step-input').fadeIn();
        $('#input-nim').val('').focus();
        $('#video-container').hide();
        $('#status').text("Masukkan NIM Anda");
        $('#status').css("color", "#555");
    }

    function cekNIM() {
        const nim = $('#input-nim').val().trim();
        if(!nim) return alert("Isi NIM dulu!");
        
        $('#status').text("Mengecek Data...");
        
        $.ajax({
            url: 'get_data.php',
            type: 'POST',
            data: { nim: nim },
            success: function(res) {
                if(res.success && res.data.face_descriptor) {
                    currentNIM = nim;
                    try {
                        let rawData = JSON.parse(res.data.face_descriptor);
                        // Konversi ke Float32Array agar bisa dibaca face-api
                        if (rawData && typeof rawData === 'object' && !Array.isArray(rawData)) {
                            rawData = Object.values(rawData);
                        }
                        const floatArray = new Float32Array(rawData);
                        
                        targetDescriptor = new faceapi.LabeledFaceDescriptors(nim, [floatArray]);
                        tampilkanKamera(res.data.nama, res.data.prodi);

                    } catch (e) {
                        console.error(e);
                        alert("Data wajah di database rusak. Harap daftar ulang.");
                    }
                } else {
                    alert("NIM Tidak ditemukan atau Belum Mendaftarkan Wajah!");
                }
            },
            error: function() { alert("Error Koneksi Database."); }
        });
    }

    function tampilkanKamera(nama, prodi) {
        $('#step-input').hide();
        $('#video-container').show();
        $('#btn-absen-utama').show();
        $('#status').html(`Halo, <b>${nama}</b><br><small>${prodi}</small>`);
        
        const videoEl = document.getElementById('video');

        navigator.mediaDevices.getUserMedia({ 
            video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: "user" } 
        })
        .then(stream => { 
            videoEl.srcObject = stream; 
            // Tunggu video play baru mulai deteksi
            videoEl.onloadedmetadata = () => {
                startVisualDetection(videoEl, nama);
            };
        })
        .catch(err => alert("Gagal Akses Kamera: " + err));
    }

    function startVisualDetection(video, namaMhs) {
        $('canvas').remove(); 

        const canvas = faceapi.createCanvasFromMedia(video);
        $('#video-container').append(canvas);
        
        const displaySize = { width: video.offsetWidth, height: video.offsetHeight };
        faceapi.matchDimensions(canvas, displaySize);

        if (detectInterval) clearInterval(detectInterval);

        detectInterval = setInterval(async () => {
            const detection = await faceapi.detectSingleFace(video, TINY_FACE_OPTIONS)
                                           .withFaceLandmarks()
                                           .withFaceDescriptor();
            
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            if (detection) {
                const resizedDetections = faceapi.resizeResults(detection, displaySize);
                const box = resizedDetections.detection.box;
                
                // Logic pencocokan realtime
                const faceMatcher = new faceapi.FaceMatcher(targetDescriptor, 0.45);
                const match = faceMatcher.findBestMatch(detection.descriptor);
                
                let labelText = "Mencocokkan...";
                let boxColor = "blue";
                let textColor = "white"; // Default putih

                if (match.label === currentNIM) {
                    const score = Math.round((1 - match.distance) * 100);
                    labelText = `${namaMhs} (${score}%)`;
                    boxColor = "#00ff00"; // Hijau
                    textColor = "black";  // UBAH JADI HITAM KHUSUS KOTAK HIJAU
                } else {
                    labelText = "Wajah Tidak Cocok";
                    boxColor = "red";
                    textColor = "white"; // Merah tetap putih biar kontras
                }

                // Setup Opsi Gambar Box
                const options = { 
                    label: labelText, 
                    boxColor: boxColor,
                    drawLabelOptions: {
                        fontColor: textColor, // Warna text diatur disini
                        backgroundColor: boxColor // Background tulisan ikut warna kotak
                    }
                };

                const drawBox = new faceapi.draw.DrawBox(box, options);
                drawBox.draw(canvas);
            }
        }, 200);
    }

    async function prosesAbsen() {
        if (detectInterval) clearInterval(detectInterval);

        const btn = $('#btn-absen-utama');
        const video = document.getElementById('video');
        
        btn.prop('disabled', true).text("Sedang Memverifikasi...");
        
        try {
            const detection = await faceapi.detectSingleFace(video, TINY_FACE_OPTIONS)
                                           .withFaceLandmarks()
                                           .withFaceDescriptor();

            if (detection) {
                const faceMatcher = new faceapi.FaceMatcher(targetDescriptor, 0.45);
                const match = faceMatcher.findBestMatch(detection.descriptor);

                if (match.label === currentNIM) {
                    kirimKeServer();
                } else {
                    alert(`Wajah TIDAK COCOK dengan data NIM ${currentNIM}.\nPastikan Anda adalah pemilik NIM.`);
                    resetTombol();
                    // Mulai lagi scan visual
                    startVisualDetection(video, $('#status b').text()); 
                }
            } else {
                alert("Wajah tidak terdeteksi! Pastikan cahaya cukup.");
                resetTombol();
                startVisualDetection(video, $('#status b').text());
            }

        } catch (error) {
            console.error(error);
            alert("Error Sistem AI: " + error);
            resetTombol();
        }
    }

    function kirimKeServer() {
        const urlTarget = (currentMode == 'masuk') ? 'simpan_absen.php' : 'simpan_keluar.php';
        
        $.ajax({
            url: urlTarget,
            type: 'POST',
            data: {
                nim: currentNIM,
                lat: userLat,
                long: userLng
            },
            success: function(res) {
                alert(res);
                window.location.href = 'index.php'; // Balik ke menu utama
            },
            error: function() {
                alert("Gagal koneksi ke server database.");
                resetTombol();
            }
        });
    }

    function resetTombol() {
        const btn = $('#btn-absen-utama');
        btn.prop('disabled', false);
        if(currentMode == 'masuk') btn.text("ABSEN MASUK");
        else btn.text("ABSEN PULANG");
    }
</script>

</body>
</html>