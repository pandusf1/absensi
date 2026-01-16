<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Wajah Mahasiswa</title>
    <script src="aset/js/jquery-3.7.1.min.js"></script>
    <script src="aset/js/face-api.min.js"></script>

    <style>
        /* --- STYLE ASLI (TIDAK DIUBAH) --- */
        body { font-family: 'Poppins', sans-serif; background: #f0f2f5; display: flex; flex-direction: column; align-items: center; padding-top: 30px; min-height: 100vh; margin: 0; }
        
        .container { 
            background: white; padding: 30px; border-radius: 15px; 
            box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center; 
            max-width: 500px; width: 78%; 
        }

        .logout-btn {
            padding: 8px 14px; background-color: #f53b57; color: #fff; text-decoration: none;
            border-radius: 8px; font-size: 14px; transition: 0.2s; border:none; cursor: pointer; float: left;
        }  
        .logout-btn:hover { background-color: #d63031; }  
        
        h2 { margin-bottom: 20px; color: #333; clear: both; padding-top: 10px; }
        
        /* Input Form */
        .input-group { margin-bottom: 15px; text-align: left; }
        label { font-weight: bold; font-size: 14px; color: #555; display: block; margin-bottom: 5px; }
        input[type="text"] { 
            width: 100%; padding: 12px; border: 1px solid #ddd; 
            border-radius: 8px; font-size: 14px; box-sizing: border-box;
        }

        /* --- AREA VIDEO & OVERLAY --- */
        #video-container { 
            position: relative; margin: 20px auto; width: 100%; height: 350px; 
            border-radius: 10px; overflow: hidden; background: #000; display: none; /* Hidden awal */
        }
        video { width: 100%; height: 100%; object-fit: cover; display: block; transform: scaleX(-1); }

        .face-overlay {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 220px; height: 280px; border: 3px dashed rgba(40, 167, 69, 0.8);
            border-radius: 50% / 60%; box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
            pointer-events: none; z-index: 10;
        }
        .face-overlay::after {
            content: "Posisikan Wajah di Sini"; position: absolute; top: -30px; left: 0; right: 0;
            text-align: center; color: white; font-weight: bold; font-size: 14px; text-shadow: 1px 1px 2px black;
        }

        button { 
            width: 100%; padding: 8px; border: none; border-radius: 10px; 
            font-size: 14px; font-weight: bold; cursor: pointer; transition: 0.3s; color: white; margin-top: 10px;
        }
        
        /* Tombol Step 1 */
        #btn-lanjut { background: #007bff; }
        #btn-lanjut:hover { background: #0056b3; }

        /* Tombol Step 2 */
        #btn-scan { background: #28a745; display: none; }
        #btn-scan:hover { background: #218838; }
        #btn-scan:disabled { background: #ccc; cursor: not-allowed; }
        
        #status { margin-top: 15px; font-weight: bold; color: #007bff; min-height: 24px; display: none;}
    </style>
</head>
<body>
<div class="container">
    <button class="logout-btn" onclick="window.location.href='index.php'">Kembali</button>
    <img src="aset/img/polines.png" alt="Logo Polines" style="width: 100px; margin-top: 20px; margin-bottom: -15px;">
    <h2>Registrasi Wajah</h2>
    
    <div id="step-1">
        <div class="input-group">
            <label>NIM (Nomor Induk Mahasiswa)</label>
            <input type="text" id="nim" placeholder="Contoh: 4.41.23.0.12">
        </div>
        
        <div class="input-group">
            <label>Nama Lengkap</label>
            <input type="text" id="nama" placeholder="Contoh: Budi Santoso">
        </div>

        <div class="input-group">
            <label>Jurusan</label>
            <input type="text" id="jurusan" placeholder="Contoh: Teknik Elektro">
        </div>

        <div class="input-group">
            <label>Program Studi (Prodi)</label>
            <input type="text" id="prodi" placeholder="Contoh: Teknologi Rekayasa Komputer">
        </div>

        <button id="btn-lanjut">Lanjut</button>
    </div>

    <div id="step-2" style="display: none;">
        <div id="video-container">
            <video id="video" autoplay muted playsinline></video>
            <div class="face-overlay"></div>
        </div>
        <div id="status">Memuat...</div>
        <button id="btn-scan" disabled>AMBIL DATA WAJAH</button>
    </div>
</div>

<script>
    const TINY_FACE_OPTIONS = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 });
    const video = document.getElementById('video');
    const statusTxt = document.getElementById('status');
    const btnScan = document.getElementById('btn-scan');
    const btnLanjut = document.getElementById('btn-lanjut');

    // --- LOGIKA TOMBOL LANJUT ---
    btnLanjut.addEventListener('click', () => {
        const nim = $('#nim').val().trim();
        const nama = $('#nama').val().trim();
        const jurusan = $('#jurusan').val().trim();
        const prodi = $('#prodi').val().trim();

        if (!nim || !nama || !jurusan || !prodi) {
            alert("Harap lengkapi semua data (NIM, Nama, Jurusan, Prodi)!");
            return;
        }

        // Pindah ke Step 2
        $('#step-1').slideUp();
        $('#step-2').fadeIn();
        $('#status').show();
        
        // Load Model & Kamera
        startSystem();
    });

    function startSystem() {
        Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('aset/models'),     
            faceapi.nets.faceLandmark68Net.loadFromUri('aset/models'),   
            faceapi.nets.faceRecognitionNet.loadFromUri('aset/models')   
        ]).then(() => {
            statusTxt.innerText = "Menyalakan Kamera...";
            initCamera();
        }).catch(err => {
            alert("Gagal memuat Model AI.");
        });
    }

    function initCamera() {
        navigator.mediaDevices.getUserMedia({ 
            video: { width: { ideal: 1280 }, height: { ideal: 720 }, facingMode: "user" } 
        })
        .then(stream => {
            video.srcObject = stream;
            $('#video-container').show();
            $('#btn-scan').show();
            statusTxt.innerText = "Posisikan wajah di dalam oval hijau.";
            statusTxt.style.color = "#28a745";
            btnScan.disabled = false;
        })
        .catch(err => {
            alert("Gagal Akses Kamera: " + err);
        });
    }

    // --- PROSES SCAN ---
    btnScan.addEventListener('click', async () => {
        statusTxt.innerText = "Menganalisa wajah...";
        statusTxt.style.color = "#e67e22";
        btnScan.disabled = true;
        btnScan.innerText = "Memproses...";

        try {
            const detection = await faceapi.detectSingleFace(video, TINY_FACE_OPTIONS)
                                           .withFaceLandmarks()
                                           .withFaceDescriptor();

            if (detection) {
                const descriptor = JSON.stringify(detection.descriptor);
                const dataKirim = {
                    nim: $('#nim').val(),
                    nama: $('#nama').val(),
                    jurusan: $('#jurusan').val(),
                    prodi: $('#prodi').val(),
                    descriptor: descriptor
                };
                
                statusTxt.innerText = "Mengirim ke database...";
                
                $.ajax({
                    url: 'simpan_wajah.php', 
                    type: 'POST',
                    data: dataKirim,
                    success: function(res) {
                        alert(res); 
                        window.location.href = 'index.php'; 
                    },
                    error: function(err) {
                        alert("Gagal terhubung ke server.");
                        resetTombol();
                    }
                });

            } else {
                alert("Wajah tidak terdeteksi! Pastikan cahaya cukup.");
                resetTombol();
            }

        } catch (error) {
            console.error(error);
            alert("Terjadi kesalahan AI.");
            resetTombol();
        }
    });

    function resetTombol() {
        statusTxt.innerText = "Gagal. Silakan coba lagi.";
        statusTxt.style.color = "red";
        btnScan.disabled = false;
        btnScan.innerText = "AMBIL DATA WAJAH";
    }
</script>

</body>
</html>