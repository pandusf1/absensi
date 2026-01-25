<?php
// ==========================================
// API AJAX DOSEN (FINAL VERSION - REKAP FIX)
// ==========================================
require_once __DIR__ . '/../database.php';
date_default_timezone_set('Asia/Jakarta');

$action = isset($_POST['action']) ? $_POST['action'] : '';

// ---------------------------------------------------------
// 1. EDIT MATA KULIAH
// ---------------------------------------------------------
if ($action == 'edit_matkul') {
    $kode_lama = mysqli_real_escape_string($conn, $_POST['kode_lama']);
    $kode_baru = mysqli_real_escape_string($conn, $_POST['kode_baru']);
    $nama      = mysqli_real_escape_string($conn, $_POST['nama']);
    $sks       = mysqli_real_escape_string($conn, $_POST['sks']);

    $q = "UPDATE matkul SET kode_matkul='$kode_baru', nama_matkul='$nama', sks='$sks' WHERE kode_matkul='$kode_lama'";
    if(mysqli_query($conn, $q)) echo "Mata Kuliah Berhasil Diupdate!";
    else echo "Gagal Update: " . mysqli_error($conn);
}

// ---------------------------------------------------------
// 2. HAPUS MATA KULIAH
// ---------------------------------------------------------
elseif ($action == 'hapus_matkul') {
    $kode = mysqli_real_escape_string($conn, $_POST['kode']);

    // Cari ID Jadwal yang terkait dengan matkul ini
    $cari_jadwal = mysqli_query($conn, "SELECT id_jadwal FROM jadwal WHERE kode_matkul='$kode'");
    
    while($row = mysqli_fetch_assoc($cari_jadwal)) {
        $id_j = $row['id_jadwal'];
        // Hapus Presensi
        mysqli_query($conn, "DELETE FROM presensi_kuliah WHERE id_jadwal='$id_j'");
        // Hapus Log Mengajar
        mysqli_query($conn, "DELETE FROM realisasi_mengajar WHERE id_jadwal='$id_j'");
    }

    // Hapus Jadwal
    mysqli_query($conn, "DELETE FROM jadwal WHERE kode_matkul='$kode'");

    // Hapus Matkul
    $q = "DELETE FROM matkul WHERE kode_matkul = '$kode'";
    
    if(mysqli_query($conn, $q)) echo "🗑️ Mata Kuliah (dan data terkait) Berhasil Dihapus!";
    else echo "❌ Gagal Hapus: " . mysqli_error($conn);
}

// ---------------------------------------------------------
// 3. MULAI KELAS
// ---------------------------------------------------------
elseif ($action == 'mulai_kelas') {
    $id = $_POST['id_jadwal'];
    $tgl = date('Y-m-d');
    $jam = date('H:i:s');
    
    $cek = mysqli_query($conn, "SELECT id_realisasi FROM realisasi_mengajar WHERE id_jadwal='$id' AND tanggal='$tgl'");
    if(mysqli_num_rows($cek) > 0) {
        echo "Kelas sudah dimulai sebelumnya!";
    } else {
        $q = "INSERT INTO realisasi_mengajar (id_jadwal, tanggal, jam_mulai_real, status) VALUES ('$id', '$tgl', '$jam', 'Berlangsung')";
        if(mysqli_query($conn, $q)) echo "Kelas DIMULAI!";
        else echo "Error: " . mysqli_error($conn);
    }
} 

// ---------------------------------------------------------
// 4. SELESAI KELAS
// ---------------------------------------------------------
elseif ($action == 'selesai_kelas') {
    $id = $_POST['id_jadwal'];
    $tgl = date('Y-m-d');
    $jam = date('H:i:s');

    $q = "UPDATE realisasi_mengajar SET jam_selesai_real = '$jam', status = 'Selesai' WHERE id_jadwal = '$id' AND tanggal = '$tgl'";
    if(mysqli_query($conn, $q)) echo "Kelas SELESAI!";
    else echo "Error: " . mysqli_error($conn);
}

// ---------------------------------------------------------
// 5. MONITORING LIVE PRESENSI
// ---------------------------------------------------------
elseif ($action == 'cek_monitoring') {
    $id = $_POST['id_jadwal'];
    $tgl = date('Y-m-d');

    // Hitung Total Hadir
    $q_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM presensi_kuliah WHERE id_jadwal = '$id' AND tanggal = '$tgl' AND status='Hadir'");
    $d_total = mysqli_fetch_assoc($q_total);
    
    // Ambil Daftar Mahasiswa
    $q_list = mysqli_query($conn, "SELECT p.waktu_hadir, d.nama, d.nim FROM presensi_kuliah p JOIN data d ON p.nim = d.nim WHERE p.id_jadwal = '$id' AND p.tanggal = '$tgl' ORDER BY p.waktu_hadir DESC");
    
    $list_mhs = [];
    while($row = mysqli_fetch_assoc($q_list)) {
        $list_mhs[] = ['nama' => $row['nama'], 'nim' => $row['nim'], 'jam' => substr($row['waktu_hadir'], 0, 5)];
    }
    echo json_encode(['jumlah_hadir' => $d_total['total'], 'list_mhs' => $list_mhs]);
}

// ---------------------------------------------------------
// 6. EDIT JADWAL
// ---------------------------------------------------------
elseif ($action == 'edit_jadwal') {
    $id = $_POST['id']; $hari = $_POST['hari']; $jam_m = $_POST['jam_m']; $jam_s = $_POST['jam_s'];
    $ruang = mysqli_real_escape_string($conn, $_POST['ruang']); 
    $kelas = mysqli_real_escape_string($conn, $_POST['kelas']); 
    $kuota = $_POST['kuota'];

    // Cek Bentrok Waktu (Overlap Logic)
    $cek = mysqli_query($conn, "SELECT * FROM jadwal WHERE hari='$hari' AND id_jadwal != '$id' AND ('$jam_m' < jam_selesai AND '$jam_s' > jam_mulai)");
    
    if(mysqli_num_rows($cek) > 0) {
        $d = mysqli_fetch_assoc($cek);
        echo "Gagal! Bentrok dengan jadwal " . $d['kode_matkul'] . " (" . substr($d['jam_mulai'],0,5) . ")";
    } else {
        $q = "UPDATE jadwal SET hari='$hari', jam_mulai='$jam_m', jam_selesai='$jam_s', ruang='$ruang', kelas='$kelas', kuota='$kuota' WHERE id_jadwal='$id'";
        if(mysqli_query($conn, $q)) echo "Jadwal Updated!"; else echo "Gagal: " . mysqli_error($conn);
    }
}

// ---------------------------------------------------------
// 7. HAPUS JADWAL
// ---------------------------------------------------------
elseif ($action == 'hapus_jadwal') {
    $id = $_POST['id'];
    mysqli_query($conn, "DELETE FROM presensi_kuliah WHERE id_jadwal='$id'");
    mysqli_query($conn, "DELETE FROM realisasi_mengajar WHERE id_jadwal='$id'");
    mysqli_query($conn, "DELETE FROM jadwal WHERE id_jadwal='$id'");
    echo "Jadwal Dihapus!";
}

// ---------------------------------------------------------
// 8. FILTER REKAP (Corrected: No Button, Clickable Row)
// ---------------------------------------------------------
elseif ($action == 'filter_rekap') {
    $nip = $_POST['nip'];
    $kw  = mysqli_real_escape_string($conn, $_POST['keyword']);
    $tm  = $_POST['tgl_mulai'];
    $ta  = $_POST['tgl_akhir'];

    // Query Dasar: Filter NIP & Status Selesai
    $where = "WHERE j.nip = '$nip' AND r.status = 'Selesai'";
    
// Filter Keyword (SUPER SMART SEARCH)
    if(!empty($kw)) {
        // 1. Bersihkan input dan pecah berdasarkan spasi
        // Contoh user ketik: "web  3c " -> jadi array ["web", "3c"]
        $words = explode(" ", trim($kw));
        
        foreach($words as $word) {
            // Skip jika cuma spasi kosong
            if(empty($word)) continue;
            
            $word = mysqli_real_escape_string($conn, $word);
            
            // 2. Logika: SETIAP kata yang diketik harus ada di (Matkul ATAU Kelas)
            // Menggunakan LOWER() agar "WEB", "Web", "web" dianggap sama
            $where .= " AND (
                LOWER(m.nama_matkul) LIKE LOWER('%$word%') OR 
                LOWER(j.kelas) LIKE LOWER('%$word%')
            )";
        }
    }
        // Filter Tanggal
    if(!empty($tm)) { $where .= " AND r.tanggal >= '$tm'"; }
    if(!empty($ta)) { $where .= " AND r.tanggal <= '$ta'"; }

    // Total Mhs diambil dari tabel 'data' (mahasiswa) yang kelasnya sama dengan jadwal
$sql = "SELECT r.*, m.nama_matkul, j.kelas, j.kuota as total_mhs,
        (SELECT COUNT(*) FROM presensi_kuliah pk WHERE pk.id_jadwal = r.id_jadwal AND pk.tanggal = r.tanggal AND pk.status = 'Hadir') as hadir
        FROM realisasi_mengajar r 
        JOIN jadwal j ON r.id_jadwal = j.id_jadwal 
        JOIN matkul m ON j.kode_matkul = m.kode_matkul 
        $where 
        ORDER BY r.tanggal DESC, r.jam_mulai_real DESC";

    $q = mysqli_query($conn, $sql);

    if(mysqli_num_rows($q) > 0) {
        while($row = mysqli_fetch_assoc($q)) {
            // Logika Baris diklik untuk detail
            echo "<tr class='tr-clickable' onclick=\"bukaDetail(" . $row['id_jadwal'] . ", '" . $row['tanggal'] . "', '" . htmlspecialchars($row['nama_matkul']) . "', '" . htmlspecialchars($row['kelas']) . "')\">";
            
            echo "<td>" . date('d/m/Y', strtotime($row['tanggal'])) . "</td>";
            echo "<td>" . $row['nama_matkul'] . "</td>";
            echo "<td>" . $row['kelas'] . "</td>";
            echo "<td><b style='color:#16a34a'>" . $row['hadir'] . "</b> / " . $row['total_mhs'] . "</td>";
            
            echo "</tr>";
        }
    } else {
        echo '<tr><td colspan="5" align="center" style="padding:20px; color:#94a3b8;">Tidak ada data ditemukan.</td></tr>';
    }
}

// ---------------------------------------------------------
// 9. LOAD DETAIL MAHASISWA
// ---------------------------------------------------------
elseif ($action == 'load_detail_mhs') {
    $id = $_POST['id_jadwal']; 
    $tgl = $_POST['tanggal'];

    // Ambil Data Presensi Join ke Data Mahasiswa
    $q = mysqli_query($conn, "SELECT p.*, d.nama FROM presensi_kuliah p JOIN data d ON p.nim = d.nim WHERE p.id_jadwal = '$id' AND p.tanggal = '$tgl' ORDER BY d.nama ASC");
    
    if(mysqli_num_rows($q) > 0) {
        while($r = mysqli_fetch_assoc($q)) {
            // Logic selected dropdown status
            $h=$r['status']=='Hadir'?'selected':''; 
            $s=$r['status']=='Sakit'?'selected':''; 
            $i=$r['status']=='Izin'?'selected':''; 
            $a=$r['status']=='Alpha'?'selected':'';
            
            echo "<tr>";
            echo "<td style='padding:8px;'>".$r['nim']."</td>";
            echo "<td style='padding:8px;'>".$r['nama']."</td>";
            echo "<td style='padding:8px;'>".substr($r['waktu_hadir'], 0, 5)."</td>";
            echo "<td style='padding:8px;'>".$r['id_presensi']."</td></tr>";
        }
    } else { 
        echo "<tr><td colspan='4' align='center' style='padding:15px;'>Belum ada data mahasiswa.</td></tr>"; 
    }
}

// ---------------------------------------------------------
// 10. UPDATE STATUS
// ---------------------------------------------------------
elseif ($action == 'update_status_presensi') {
    $id = $_POST['id_presensi']; 
    $stt = $_POST['status'];
    mysqli_query($conn, "UPDATE presensi_kuliah SET status='$stt' WHERE id_presensi='$id'");
}

// ---------------------------------------------------------
// 11. TAMBAH PRESENSI MANUAL
// ---------------------------------------------------------
elseif ($action == 'tambah_presensi_manual') {
    $id = $_POST['id_jadwal']; 
    $tgl = $_POST['tanggal']; 
    $nim = mysqli_real_escape_string($conn, $_POST['nim']); 
    $stt = $_POST['status']; 
    $jam = date('H:i:s');
    
    // Cek apakah NIM valid
    $cek_mhs = mysqli_query($conn, "SELECT nama FROM data WHERE nim='$nim'");
    
    if(mysqli_num_rows($cek_mhs) == 0) { 
        echo "NIM Tidak Ditemukan!"; 
    } else {
        // Cek apakah sudah absen sebelumnya (hindari duplikat)
        $cek_double = mysqli_query($conn, "SELECT id_presensi FROM presensi_kuliah WHERE id_jadwal='$id' AND tanggal='$tgl' AND nim='$nim'");
        
        if(mysqli_num_rows($cek_double) > 0) {
            echo "Mahasiswa ini sudah ada di daftar!";
        } else {
            $ins = "INSERT INTO presensi_kuliah (id_jadwal, nim, tanggal, waktu_hadir, status, koordinat) VALUES ('$id', '$nim', '$tgl', '$jam', '$stt', 'Manual')";
            if(mysqli_query($conn, $ins)) echo "Data Tersimpan!"; else echo "Gagal Menyimpan.";
        }
    }
}
?>