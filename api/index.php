<?php
$page = $_GET['page'] ?? 'home';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIAKAD POLINES</title>
    
<style>
  * { box-sizing: border-box; font-family: Arial, Helvetica, sans-serif; }
  body { margin: 0; background: #f4f6fb; }

  header {
    background: #fff;
    padding: 16px 24px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 6px rgba(0,0,0,.05);
  }
  header img { height: 36px; margin-right: 12px; }

  .container {
    max-width: 1100px;
    margin: 50px auto;
    padding: 0 20px;
  }

  .card {
    background: #fff;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 10px 30px rgba(0,0,0,.08);
    /* PERBAIKAN: Tambahkan ini agar card tidak meluap dari container */
    width: 100%; 
  }

  .grid {
    display: grid;
    /* PERBAIKAN: Ubah 320px jadi 260px agar muat di HP kecil */
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 30px;
  }

  h1, h2, h3 { margin-top: 0; }

  input, select, button {
    width: 100%;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #ddd;
    margin-bottom: 14px;
  }

  button {
    background: #2563eb;
    color: #fff;
    border: none;
    font-weight: bold;
    cursor: pointer;
  }
  button:hover { opacity: .9; }

  .link { text-align: center; font-size: 14px; }
  .link a { text-decoration: none; color: #2563eb; }

  /* HOME */
  .home-header { text-align: center; margin-bottom: 40px; }
  .home-header img { width: 150px; margin-bottom: 18px; }
  .home-header h1 { font-size: 32px; margin-bottom: 8px; }
  .home-header p { color: #555; }

  .role-card { text-align: center; }
  
  /* PERBAIKAN: Tambahkan max-width agar gambar tidak memaksa lebar card */
  .role-card img { 
    width: 180px; 
    max-width: 100%; 
    height: auto; 
    margin-bottom: 18px; 
  }

  footer { text-align: center; font-size: 13px; color: #777; margin: 40px 0; }

  /* MEDIA QUERY (Tampilan HP) */
  @media (max-width: 768px) { 
      .container {
        /* Kurangi margin atas di HP */
        margin: 30px auto; 
        /* Kurangi padding container biar lega */
        padding: 0 16px; 
      }

      .card {
        /* Kurangi padding card di HP (32px itu terlalu tebal buat HP) */
        padding: 24px 20px; 
      }
      
      .grid {
        /* Paksa 1 kolom di HP biar rapi ke bawah */
        grid-template-columns: 1fr;
        gap: 20px;
      }

      h1 { font-size: 20px; }
      h2 { font-size: 20px; }
      
      .home-header img { width: 120px; } 
      .role-card img { width: 140px; }  
  }
</style>
</head>
<body>

<?php 
// Routing Halaman menggunakan Switch
switch($page): 

// ================= HOME (LOGIN UTAMA) =================
case 'home': ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <div class="container" style="max-width: 500px;">
        <div class="card">
            <div class="home-header" style="margin-bottom: 20px;">
                <img src="aset/img/polines.png" onerror="this.src='https://via.placeholder.com/100'" alt="Logo POLINES" style="width: 100px;">
                <h2 style="font-size: 24px;">Sistem Informasi Akademik</h2>
                <h2 style="font-size: 20px; margin-bottom: 20px; margin-top:-10px; color:#555;">Politeknik Negeri Semarang</h2>
            </div>
            
            <form action="login/login.php" method="POST">
                <label style="font-size:13px; font-weight:bold; color:#555;">NIM / NIP</label>
                <input type="text" name="username" placeholder="Masukkan NIM atau NIP" required style="width: 100%; margin-bottom: 15px;">
                
                <label style="font-size:13px; font-weight:bold; color:#555;">Kata Sandi</label>
                
                <div style="position: relative; margin-bottom: 15px;">
                    <input type="password" name="password" id="passwordInput" placeholder="Masukkan kata sandi" required style="width: 100%; padding-right: 40px;">
                    
                    <span onclick="togglePassword()" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #777;">
                        <i id="toggleIcon" class="fa-solid fa-eye"></i>
                    </span>
                </div>
                
                <button type="submit" style="margin-top: 10px;">Masuk</button>
            </form>

            <div class="link" style="margin-top: 15px;">
                <a href="?page=reset">Lupa kata sandi?</a>
                <br><br>
                Belum memiliki akun? <a href="?page=pilih_peran" style="font-weight:bold;">Buat Akun</a>
            </div>
            <div style="font-size: 14px;">
            <p><b>Akun Mahasiswa:</b></p>
            <p>NIM: 4.41.23.2.23 | Password: 123</p>
            <p><b>Akun Dosen:</b></p>
            <p>NIP: 1234567 | Password: 123</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            var input = document.getElementById("passwordInput");
            var icon = document.getElementById("toggleIcon");

            if (input.type === "password") {
                input.type = "text"; 
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash"); 
            } else {
                input.type = "password"; 
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye"); 
            }
        }
    </script>
<?php break; ?>

<?php 
// ================= PILIH PERAN (2 KARTU) =================
case 'pilih_peran': ?>
    <div class="container">
        <div class="card">
            <div class="home-header">
                <img src="aset/img/polines.png" alt="Logo POLINES">
                <h1>Pendaftaran Akun Baru</h1>
                <p>Silakan pilih peran Anda untuk mendaftar</p>
            </div>

            <div class="grid">
                <div class="card role-card">
                    <img src="aset/img/mahasiswa.png" alt="Mahasiswa">
                    <h3>Mahasiswa</h3>
                    <p>Daftar sebagai mahasiswa untuk mengakses informasi akademik</p>
                    <a href="?page=daftar_mhs"><button>Daftar sebagai Mahasiswa</button></a>
                </div>

                <div class="card role-card">
                    <img src="aset/img/dosen.png" alt="Dosen">
                    <h3>Dosen</h3>
                    <p>Daftar sebagai dosen untuk mengelola data akademik</p>
                    <a href="?page=daftar_dosen"><button>Daftar sebagai Dosen</button></a>
                </div>
            </div>
            
            <div class="link" style="margin-top: 30px;">
                Sudah punya akun? <a href="?page=home">Login di sini</a>
            </div>
        </div>
    </div>
<?php break; ?>

<?php 
// ================= DAFTAR DOSEN =================
case 'daftar_dosen': ?>
    <div class="container"><div class="card">
        <h2>Pendaftaran Akun Dosen</h2>
        <form action="login/daftar.php" method="POST">
            <input type="hidden" name="role" value="dosen">
            
            <input type="text" name="nip" placeholder="NIP" required>
            <input type="text" name="nama_dosen" placeholder="Nama Lengkap dan Gelar" required>
            <input type="text" name="jabatan" placeholder="Jabatan" required>
            <input type="email" name="email" placeholder="Email Aktif" required>
            <input type="password" name="password" placeholder="Kata Sandi" required>
            <input type="password" name="k_password" placeholder="Konfirmasi Kata Sandi" required>
            
            <button type="submit">Daftar Akun</button>
        </form>
        <div class="link"><a href="?page=pilih_peran">Kembali</a></div>
    </div></div>
<?php break; ?>

<?php 
// ================= DAFTAR MAHASISWA =================
case 'daftar_mhs': ?>
    <div class="container">
        <div class="card">
            <h2>Pendaftaran Akun Mahasiswa</h2>
            <form action="login/daftar.php" method="POST">
                <input type="hidden" name="role" value="mahasiswa">
                
                <input type="text" name="nim" placeholder="NIM" required style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:5px;">
                
                <input type="text" name="nama" placeholder="Nama Lengkap" required style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:5px;">
                
                <input type="text" name="kelas" placeholder="Kelas (Contoh: KA-3C)" required style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:5px;">
                
                <select name="jurusan" id="jurusan" required onchange="updateProdi()" style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:5px; background:white;">
                    <option value="">Pilih Jurusan</option>
                    <option value="Teknik Sipil">Teknik Sipil</option>
                    <option value="Teknik Mesin">Teknik Mesin</option>
                    <option value="Teknik Elektro">Teknik Elektro</option>
                    <option value="Akuntansi">Akuntansi</option>
                    <option value="Administrasi Bisnis">Administrasi Bisnis</option>
                </select>

                <select name="prodi" id="prodi" required style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:5px; background:white;">
                    <option value="">Pilih Prodi /option>
                </select>

                <input type="email" name="email" placeholder="Email Aktif" required style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:5px;">
                
                <input type="password" name="password" placeholder="Kata Sandi" required style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:5px;">
                <input type="password" name="k_password" placeholder="Konfirmasi Kata Sandi" required style="width:100%; padding:10px; margin-bottom:10px; border:1px solid #ddd; border-radius:5px;">
                
                <button type="submit" style="width:100%; padding:10px; background:#3b82f6; color:white; border:none; border-radius:5px; cursor:pointer;">Daftar Akun</button>
            </form>
            <div class="link" style="margin-top:10px; text-align:center;">
                <a href="?page=pilih_peran" style="text-decoration:none; color:#3b82f6;">Kembali</a>
            </div>
        </div>
    </div>

    <script>
        function updateProdi() {
            // Data Jurusan dan Prodi Polines
            const dataPolines = {
                "Teknik Sipil": [
                    "D3 Konstruksi Gedung",
                    "D3 Konstruksi Sipil",
                    "S.Tr Perancangan Jalan dan Jembatan",
                    "S.Tr Teknik Perawatan dan Perbaikan Gedung"
                ],
                "Teknik Mesin": [
                    "D3 Teknik Mesin",
                    "D3 Teknik Konversi Energi",
                    "S.Tr Teknik Mesin Produksi dan Perawatan",
                    "S.Tr Teknologi Rekayasa Pembangkit Energi"
                ],
                "Teknik Elektro": [
                    "D3 Teknik Listrik",
                    "D3 Teknik Elektronika",
                    "D3 Teknik Telekomunikasi",
                    "D3 Teknik Informatika",
                    "S.Tr Teknik Telekomunikasi",
                    "S.Tr Teknologi Rekayasa Instalasi Listrik",
                    "S.Tr Teknologi Rekayasa Komputer",
                    "S.Tr Teknologi Rekayasa Elektronika"
                ],
                "Akuntansi": [
                    "D3 Akuntansi",
                    "D3 Keuangan dan Perbankan",
                    "S.Tr Komputerisasi Akuntansi",
                    "S.Tr Perbankan Syariah",
                    "S.Tr Analis Keuangan",
                    "S.Tr Akuntansi Manajerial"
                ],
                "Administrasi Bisnis": [
                    "D3 Administrasi Bisnis",
                    "D3 Manajemen Pemasaran",
                    "S.Tr Manajemen Bisnis Internasional",
                    "S.Tr Administrasi Bisnis Terapan"
                ]
            };

            const jurusanSelect = document.getElementById("jurusan");
            const prodiSelect = document.getElementById("prodi");
            const selectedJurusan = jurusanSelect.value;

            // Kosongkan dropdown prodi
            prodiSelect.innerHTML = '<option value="">-- Pilih Program Studi --</option>';

            // Jika jurusan dipilih, isi prodi sesuai data
            if (selectedJurusan && dataPolines[selectedJurusan]) {
                dataPolines[selectedJurusan].forEach(function(prodi) {
                    const option = document.createElement("option");
                    option.value = prodi;
                    option.text = prodi;
                    prodiSelect.appendChild(option);
                });
            }
        }
    </script>
<?php break; ?>
<?php 
// ================= RESET PASSWORD =================
case 'reset': ?>
    <div class="container"><div class="card">
        <h2>Reset Kata Sandi</h2>
        <p style="color:#666; font-size:14px; margin-bottom:20px;">Masukkan email Anda untuk mereset kata sandi.</p>
        <form action="login/reset.php" method="POST">
            <input type="email" name="email" placeholder="Masukkan Email Terdaftar" required>
            <button type="submit">Kirim Link Reset</button>
        </form>
        <div class="link"><a href="?page=home">Kembali ke Login</a></div>
    </div></div>
<?php break; ?>

<?php endswitch; ?>

<footer>© 2026 Politeknik Negeri Semarang</footer>

</body>
</html>