<?php
// Pastikan path ini benar (naik satu folder ke database.php)
include '../database.php'; 
date_default_timezone_set('Asia/Jakarta');

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action == 'edit_matkul') {
    $kode_lama = $_POST['kode_lama'];
    $kode_baru = $_POST['kode_baru'];
    $nama      = $_POST['nama'];
    $sks       = $_POST['sks'];

    // Update Matkul (Otomatis update jadwal jika ON UPDATE CASCADE aktif, tapi kita handle manual jaga2)
    $q = "UPDATE matkul SET kode_matkul='$kode_baru', nama_matkul='$nama', sks='$sks' WHERE kode_matkul='$kode_lama'";
    if(mysqli_query($conn, $q)) echo "✅ Mata Kuliah Berhasil Diupdate!";
    else echo "❌ Gagal Update: " . mysqli_error($conn);
}

elseif ($action == 'hapus_matkul') {
    $kode = $_POST['kode'];

    // --- LOGIKA PAKSA HAPUS (MANUAL CASCADE) ---
    // MySQL kadang menolak hapus jika data dipakai di tabel lain.
    // Kita hapus dulu anak-anaknya (Jadwal & Presensi)
    
    // 1. Ambil semua ID Jadwal yang pakai Matkul ini
    $cari_jadwal = mysqli_query($conn, "SELECT id_jadwal FROM jadwal WHERE kode_matkul='$kode'");
    
    while($row = mysqli_fetch_assoc($cari_jadwal)) {
        $id_j = $row['id_jadwal'];
        // Hapus Presensi di jadwal ini
        mysqli_query($conn, "DELETE FROM presensi_kuliah WHERE id_jadwal='$id_j'");
        // Hapus Realisasi (Log Mengajar) di jadwal ini
        mysqli_query($conn, "DELETE FROM realisasi_mengajar WHERE id_jadwal='$id_j'");
    }

    // 2. Hapus Jadwalnya
    mysqli_query($conn, "DELETE FROM jadwal WHERE kode_matkul='$kode'");

    // 3. Terakhir, Hapus Matkulnya
    $q = "DELETE FROM matkul WHERE kode_matkul = '$kode'";
    
    if(mysqli_query($conn, $q)) {
        echo "🗑️ Mata Kuliah (beserta semua jadwal & presensinya) Berhasil Dihapus!";
    } else {
        echo "❌ Gagal Hapus: " . mysqli_error($conn);
    }
}

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

elseif ($action == 'selesai_kelas') {
    $id = $_POST['id_jadwal'];
    $tgl = date('Y-m-d');
    $jam = date('H:i:s');

    $q = "UPDATE realisasi_mengajar SET jam_selesai_real = '$jam', status = 'Selesai' WHERE id_jadwal = '$id' AND tanggal = '$tgl'";
    if(mysqli_query($conn, $q)) echo "Kelas SELESAI!";
    else echo "Error: " . mysqli_error($conn);
}

elseif ($action == 'cek_monitoring') {
    $id = $_POST['id_jadwal'];
    $tgl = date('Y-m-d');

    $q_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM presensi_kuliah WHERE id_jadwal = '$id' AND tanggal = '$tgl'");
    $d_total = mysqli_fetch_assoc($q_total);
    
    $q_list = mysqli_query($conn, "SELECT p.waktu_hadir, d.nama, d.nim FROM presensi_kuliah p JOIN data d ON p.nim = d.nim WHERE p.id_jadwal = '$id' AND p.tanggal = '$tgl' ORDER BY p.waktu_hadir DESC");
    
    $list_mhs = [];
    while($row = mysqli_fetch_assoc($q_list)) {
        $list_mhs[] = ['nama' => $row['nama'], 'nim' => $row['nim'], 'jam' => substr($row['waktu_hadir'], 0, 5)];
    }
    echo json_encode(['jumlah_hadir' => $d_total['total'], 'list_mhs' => $list_mhs]);
}

elseif ($action == 'edit_jadwal') {
    $id = $_POST['id']; $hari = $_POST['hari']; $jam_m = $_POST['jam_m']; $jam_s = $_POST['jam_s'];
    $ruang = $_POST['ruang']; $kelas = $_POST['kelas']; $kuota = $_POST['kuota'];

    $cek = mysqli_query($conn, "SELECT * FROM jadwal WHERE hari='$hari' AND id_jadwal != '$id' AND ('$jam_m' < jam_selesai AND '$jam_s' > jam_mulai)");
    
    if(mysqli_num_rows($cek) > 0) {
        $d = mysqli_fetch_assoc($cek);
        echo "❌ Gagal! Bentrok dengan jadwal " . $d['kode_matkul'] . " (" . substr($d['jam_mulai'],0,5) . ")";
    } else {
        $q = "UPDATE jadwal SET hari='$hari', jam_mulai='$jam_m', jam_selesai='$jam_s', ruang='$ruang', kelas='$kelas', kuota='$kuota' WHERE id_jadwal='$id'";
        if(mysqli_query($conn, $q)) echo "✅ Jadwal Updated!"; else echo "Gagal: " . mysqli_error($conn);
    }
}

elseif ($action == 'hapus_jadwal') {
    $id = $_POST['id'];
    // Hapus anak-anaknya dulu
    mysqli_query($conn, "DELETE FROM presensi_kuliah WHERE id_jadwal='$id'");
    mysqli_query($conn, "DELETE FROM realisasi_mengajar WHERE id_jadwal='$id'");
    mysqli_query($conn, "DELETE FROM jadwal WHERE id_jadwal='$id'");
    echo "Jadwal Dihapus!";
}

elseif ($action == 'load_rekap_list') {
    $kw = $_POST['keyword']; $tgl_a = $_POST['tgl_mulai']; $tgl_b = $_POST['tgl_akhir'];
    
    $sql = "SELECT r.*, j.kelas, m.nama_matkul, j.jam_mulai, j.jam_selesai, j.id_jadwal FROM realisasi_mengajar r JOIN jadwal j ON r.id_jadwal = j.id_jadwal JOIN matkul m ON j.kode_matkul = m.kode_matkul WHERE r.status = 'Selesai' AND (m.nama_matkul LIKE '%$kw%' OR j.kelas LIKE '%$kw%')";
    if (!empty($tgl_a) && !empty($tgl_b)) { $sql .= " AND r.tanggal BETWEEN '$tgl_a' AND '$tgl_b'"; }
    $sql .= " ORDER BY r.tanggal DESC, r.jam_mulai_real DESC";

    $q = mysqli_query($conn, $sql);
    if(mysqli_num_rows($q) > 0) {
        while($row = mysqli_fetch_assoc($q)) {
            $q_count = mysqli_query($conn, "SELECT COUNT(*) as hadir FROM presensi_kuliah WHERE id_jadwal='".$row['id_jadwal']."' AND tanggal='".$row['tanggal']."' AND status IN ('Hadir','Terlambat')");
            $d_count = mysqli_fetch_assoc($q_count);
            echo "<tr style='cursor:pointer;' onclick=\"bukaDetail(".$row['id_jadwal'].", '".$row['tanggal']."', '".$row['nama_matkul']."', '".$row['kelas']."')\"><td><b>".date('d/m/Y', strtotime($row['tanggal']))."</b></td><td>".$row['nama_matkul']."</td><td><span style='background:#e0f2fe; color:#0369a1; padding:2px 8px;'>".$row['kelas']."</span></td><td>".substr($row['jam_mulai_real'],0,5)." - ".substr($row['jam_selesai_real'],0,5)."</td><td>".($row['materi_pembahasan']?substr($row['materi_pembahasan'],0,30).'...':'-')."</td><td><b style='color:#16a34a;'>".$d_count['hadir']." Mhs</b></td></tr>";
        }
    } else { echo "<tr><td colspan='6' align='center'>Data tidak ditemukan.</td></tr>"; }
}

elseif ($action == 'load_detail_mhs') {
    $id = $_POST['id_jadwal']; $tgl = $_POST['tanggal'];
    $q = mysqli_query($conn, "SELECT p.*, d.nama FROM presensi_kuliah p JOIN data d ON p.nim = d.nim WHERE p.id_jadwal = '$id' AND p.tanggal = '$tgl' ORDER BY d.nama ASC");
    if(mysqli_num_rows($q) > 0) {
        while($r = mysqli_fetch_assoc($q)) {
            $h=$r['status']=='Hadir'?'selected':''; $s=$r['status']=='Sakit'?'selected':''; $i=$r['status']=='Izin'?'selected':''; $a=$r['status']=='Alpha'?'selected':'';
            echo "<tr><td>".$r['nim']."</td><td>".$r['nama']."</td><td>".substr($r['waktu_hadir'], 0, 5)."</td><td><select onchange=\"ubahStatus(".$r['id_presensi'].", this.value)\"><option value='Hadir' $h>Hadir</option><option value='Sakit' $s>Sakit</option><option value='Izin' $i>Izin</option><option value='Alpha' $a>Alpha</option></select></td></tr>";
        }
    } else { echo "<tr><td colspan='4' align='center'>Belum ada data.</td></tr>"; }
}

elseif ($action == 'update_status_presensi') {
    $id = $_POST['id_presensi']; $stt = $_POST['status'];
    mysqli_query($conn, "UPDATE presensi_kuliah SET status='$stt' WHERE id_presensi='$id'");
}

elseif ($action == 'tambah_presensi_manual') {
    $id = $_POST['id_jadwal']; $tgl = $_POST['tanggal']; $nim = $_POST['nim']; $stt = $_POST['status']; $jam = date('H:i:s');
    $cek_mhs = mysqli_query($conn, "SELECT nama FROM data WHERE nim='$nim'");
    if(mysqli_num_rows($cek_mhs) == 0) { echo "❌ NIM Tidak Ditemukan!"; exit; }
    $ins = "INSERT INTO presensi_kuliah (id_jadwal, nim, tanggal, waktu_hadir, status, koordinat) VALUES ('$id', '$nim', '$tgl', '$jam', '$stt', 'Manual')";
    if(mysqli_query($conn, $ins)) echo "✅ Berhasil!"; else echo "Gagal.";
}
?>