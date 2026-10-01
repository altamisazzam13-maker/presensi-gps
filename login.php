<?php
session_start();

// Jika sudah login, redirect sesuai role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: pegawai.php');
    }
    exit;
}

require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $conn->prepare("SELECT id, nama, password, role FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama']    = $user['nama'];
            $_SESSION['role']    = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: pegawai.php');
            }
            exit;
        } else {
            $error = 'Username atau password salah!';
        }
    } else {
        $error = 'Username dan password wajib diisi!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Sistem Presensi GPS</title>
  <meta name="description" content="Halaman login sistem presensi GPS online untuk admin dan pegawai.">
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #4f46e5;
      --primary-hover: #4338ca;
      --bg: #0f172a;
      --card-bg: #1e293b;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --success: #10b981;
      --error: #ef4444;
      --glass-border: rgba(255, 255, 255, 0.1);
    }

    * {
      box-sizing: border-box;
      font-family: 'Outfit', sans-serif;
      margin: 0;
      padding: 0;
    }

    body {
      background: var(--bg);
      background-image:
        radial-gradient(circle at 20% 50%, rgba(79, 70, 229, 0.2), transparent 35%),
        radial-gradient(circle at 80% 20%, rgba(16, 185, 129, 0.15), transparent 35%),
        radial-gradient(circle at 60% 80%, rgba(99, 102, 241, 0.1), transparent 30%);
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .login-wrapper {
      width: 100%;
      max-width: 440px;
      animation: fadeInUp 0.7s ease-out;
    }

    .logo-area {
      text-align: center;
      margin-bottom: 36px;
    }

    .logo-icon {
      width: 72px;
      height: 72px;
      background: linear-gradient(135deg, #4f46e5, #818cf8);
      border-radius: 20px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 32px;
      margin-bottom: 18px;
      box-shadow: 0 8px 32px rgba(79, 70, 229, 0.4);
      animation: pulse 3s infinite;
    }

    .logo-area h1 {
      font-size: 1.9rem;
      font-weight: 800;
      background: linear-gradient(to right, #818cf8, #34d399);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 6px;
    }

    .logo-area p {
      color: var(--text-muted);
      font-size: 14px;
    }

    .card {
      background: var(--card-bg);
      border-radius: 20px;
      padding: 36px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
      border: 1px solid var(--glass-border);
      backdrop-filter: blur(10px);
    }

    .card h2 {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 24px;
      color: var(--text-main);
    }

    .form-group {
      margin-bottom: 20px;
      position: relative;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: var(--text-muted);
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .input-wrap {
      position: relative;
    }

    .input-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      opacity: 0.5;
      pointer-events: none;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 14px 16px 14px 44px;
      background: rgba(15, 23, 42, 0.7);
      border: 1px solid var(--glass-border);
      border-radius: 12px;
      font-size: 15px;
      color: white;
      transition: all 0.3s ease;
    }

    input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.25);
      background: rgba(15, 23, 42, 0.9);
    }

    input::placeholder {
      color: #475569;
    }

    .toggle-pw {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: var(--text-muted);
      padding: 4px;
      width: auto;
      font-size: 14px;
      transition: color 0.2s;
    }

    .toggle-pw:hover {
      color: white;
    }

    .btn-login {
      width: 100%;
      padding: 16px;
      background: linear-gradient(135deg, var(--primary), #818cf8);
      color: white;
      border: none;
      border-radius: 12px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      letter-spacing: 0.5px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 20px rgba(79, 70, 229, 0.5);
      margin-top: 8px;
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(79, 70, 229, 0.7);
    }

    .btn-login:active {
      transform: translateY(1px);
    }

    .error-box {
      background: rgba(239, 68, 68, 0.12);
      border: 1px solid rgba(239, 68, 68, 0.3);
      color: #fca5a5;
      padding: 14px 16px;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
      animation: shake 0.4s ease;
    }

    .divider {
      margin: 28px 0 20px;
      text-align: center;
      position: relative;
      color: var(--text-muted);
      font-size: 13px;
    }

    .divider::before, .divider::after {
      content: '';
      position: absolute;
      top: 50%;
      width: 40%;
      height: 1px;
      background: var(--glass-border);
    }

    .divider::before { left: 0; }
    .divider::after { right: 0; }

    .demo-info {
      background: rgba(79, 70, 229, 0.08);
      border: 1px solid rgba(79, 70, 229, 0.2);
      border-radius: 12px;
      padding: 16px;
      font-size: 13px;
      color: #a5b4fc;
      line-height: 1.8;
    }

    .demo-info strong {
      color: #c7d2fe;
    }

    .demo-info code {
      background: rgba(79, 70, 229, 0.2);
      padding: 2px 8px;
      border-radius: 4px;
      font-family: monospace;
      font-size: 13px;
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes pulse {
      0%, 100% { box-shadow: 0 8px 32px rgba(79, 70, 229, 0.4); }
      50% { box-shadow: 0 8px 48px rgba(79, 70, 229, 0.7); }
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20% { transform: translateX(-8px); }
      40% { transform: translateX(8px); }
      60% { transform: translateX(-5px); }
      80% { transform: translateX(5px); }
    }
  </style>
</head>
<body>

<div class="login-wrapper">
  <div class="logo-area">
    <div class="logo-icon">📍</div>
    <h1>Presensi GPS</h1>
    <p>Sistem Absensi Online Berbasis Lokasi</p>
  </div>

  <div class="card">
    <h2>Masuk ke Akun</h2>

    <?php if ($error): ?>
    <div class="error-box">
      <span>⚠️</span> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="loginForm">
      <div class="form-group">
        <label for="username">Username</label>
        <div class="input-wrap">
          <span class="input-icon">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
          </span>
          <input type="text" id="username" name="username"
                 placeholder="Masukkan username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 required autocomplete="username">
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrap">
          <span class="input-icon">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </span>
          <input type="password" id="password" name="password"
                 placeholder="Masukkan password"
                 required autocomplete="current-password">
          <button type="button" class="toggle-pw" id="togglePw" title="Tampilkan/sembunyikan password">👁️</button>
        </div>
      </div>

      <button type="submit" class="btn-login" id="btnLogin">🔐 Masuk</button>
    </form>

    <div class="divider">Info Akun Default</div>

    <div class="demo-info">
      <strong>Admin:</strong> username <code>admin</code> / password <code>admin123</code><br>
      <strong>Pegawai:</strong> Dibuat oleh admin di panel manajemen
    </div>
  </div>
</div>

<script>
  // Toggle password visibility
  document.getElementById('togglePw').addEventListener('click', function() {
    const pw = document.getElementById('password');
    if (pw.type === 'password') {
      pw.type = 'text';
      this.textContent = '🙈';
    } else {
      pw.type = 'password';
      this.textContent = '👁️';
    }
  });

  // Tambahkan loading state saat submit
  document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnLogin');
    btn.textContent = '⏳ Memverifikasi...';
    btn.disabled = true;
  });
</script>

</body>
</html>
