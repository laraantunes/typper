<?php
// install.php - Setup first user for Typper Panel

$auth_file = __DIR__ . '/data/auth.enc';

if (file_exists($auth_file)) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($username) || empty($password) || empty($password_confirm)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif ($password !== $password_confirm) {
        $error = 'As senhas não coincidem.';
    } else {
        require_once 'encryption.php';
        
        if (!is_dir(__DIR__ . '/data')) {
            @mkdir(__DIR__ . '/data', 0755, true);
        }

        $authData = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];
        
        $encData = TypperEncryption::encrypt(json_encode($authData));
        if (file_put_contents($auth_file, $encData)) {
            $success = true;
        } else {
            $error = 'Erro ao salvar o arquivo de configuração.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Typper - Instalação</title>
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
        .install-card {
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .install-card svg {
            margin-bottom: 1rem;
            color: var(--color-cyan);
        }
        .install-card h2 {
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
    <div class="install-card glass-panel">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 20h9"></path>
            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
        </svg>
        <h2>Instalação Inicial</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success">Configuração concluída!</div>
            <a href="login.php" class="btn btn-primary btn-full">Ir para Login</a>
        <?php else: ?>
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
                <div class="form-group">
                    <label class="form-label" for="password_confirm">Confirmar Senha</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Concluir Instalação</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
