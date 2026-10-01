<?php
// Konfigurasi Database (Railway atau XAMPP lokal)
$host = getenv('MYSQLHOST') ?: "localhost";
$user = getenv('MYSQLUSER') ?: "root";
$pass = getenv('MYSQLPASSWORD') ?: "";
$db   = getenv('MYSQLDATABASE') ?: "presensi_gps";
$port = getenv('MYSQLPORT') ?: "3306";

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Buat tabel presensi jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS presensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    nama VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    waktu DATETIME NOT NULL,
    lokasi VARCHAR(100) NOT NULL
)");

// Buat tabel users jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','pegawai') NOT NULL DEFAULT 'pegawai',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Buat akun admin default jika belum ada
$cekAdmin = $conn->query("SELECT id FROM users WHERE username='admin' LIMIT 1");
if ($cekAdmin->num_rows === 0) {
    $hashAdmin = password_hash('admin123', PASSWORD_BCRYPT);
    $conn->query("INSERT INTO users (nama, username, password, role) VALUES ('Administrator', 'admin', '$hashAdmin', 'admin')");
}
?>
