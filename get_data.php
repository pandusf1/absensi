<?php
include 'database.php'; // Path disesuaikan (di root)
header('Content-Type: application/json');

if (isset($_POST['nim'])) {
    $nim = mysqli_real_escape_string($conn, $_POST['nim']);
    
    // Ambil data mahasiswa dari tabel 'data'
    $query = mysqli_query($conn, "SELECT nama, jurusan, prodi, face_descriptor FROM data WHERE nim = '$nim'");
    $data = mysqli_fetch_assoc($query);
    
    if ($data) {
        echo json_encode(['success' => true, 'data' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'NIM tidak ditemukan!']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'NIM kosong']);
}
?>