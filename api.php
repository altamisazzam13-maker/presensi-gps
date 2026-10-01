<?php
session_start();
require 'config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

date_default_timezone_set('Asia/Jakarta');

if ($action == 'save') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $nama   = $conn->real_escape_string($data['nama'] ?? '');
    $status = $conn->real_escape_string($data['status'] ?? '');
    $lokasi = $conn->real_escape_string($data['lokasi'] ?? '');
    $userId = isset($data['user_id']) ? (int)$data['user_id'] : null;
    $waktu  = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO presensi (user_id, nama, status, waktu, lokasi) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $nama, $status, $waktu, $lokasi);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'waktu' => $waktu]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }

} elseif ($action == 'get') {
    $sql    = "SELECT * FROM presensi ORDER BY id DESC";
    $result = $conn->query($sql);
    $data   = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode($data);

} elseif ($action == 'clear') {
    // Hanya admin yang boleh clear
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
        exit;
    }
    if ($conn->query("TRUNCATE TABLE presensi") === TRUE) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
$conn->close();
?>
