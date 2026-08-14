<?php
// login.php - Typper Panel Login

require_once __DIR__ . '/session.php';

$auth_file = __DIR__ . '/data/auth.enc';

if (!file_exists($auth_file)) {
    header("Location: install.php");
    exit;
}

if (!empty($_SESSION['typper_logged_in'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'encryption.php';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        $encData = file_get_contents($auth_file);
        $decData = TypperEncryption::decrypt($encData);
        if ($decData) {
            $authData = json_decode($decData, true);
            if ($authData && $username === $authData['username'] && password_verify($password, $authData['password'])) {
                $_SESSION['typper_logged_in'] = true;
                header("Location: index.php");
                exit;
            } else {
                $error = 'Usuário ou senha incorretos.';
            }
        } else {
            $error = 'Erro ao ler o arquivo de autenticação.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Typper - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="icon.svg" type="image/svg+xml">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0f0c29">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js');
            });
        }
    </script>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-card {
            padding: 2.5rem;
            width: 100%;
            max-width: 380px;
            text-align: center;
        }
        .login-card svg {
            margin-bottom: 1rem;
            color: var(--color-cyan);
        }
        .login-card h2 {
            margin-bottom: 1.5rem;
            background: linear-gradient(to right, var(--color-cyan), var(--color-purple-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .form-group {
            text-align: left;
        }
        .btn-full {
            width: 100%;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="login-card glass-panel">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 20h9"></path>
            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
        </svg>
        <h2>Acesso ao Painel</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="username">Usuário</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Senha</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-full">Entrar</button>
        </form>
    </div>
</body>
</html>
