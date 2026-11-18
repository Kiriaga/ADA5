<?php
// login.php
session_start();
require_once 'conexion.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $psword = trim($_POST['psword']);

    $stmt = $pdo->prepare("SELECT * FROM USUARIO WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($psword, $user['psword'])) {
        $_SESSION['user_id'] = $user['id_user'];
        $_SESSION['user_name'] = $user['name_user'];
        
        $redirect = $_GET['redirect'] ?? 'index';
        if ($redirect === 'cart') {
            header("Location: cart.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Credenciales incorrectas. Verifique su correo y contraseña.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión - VirtualStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full border border-gray-200">
        <div class="text-center mb-6">
            <a href="index.php" class="text-3xl font-extrabold text-vs-black tracking-wider">Virtual<span class="text-yellow-600">Store</span></a>
            <h2 class="text-xl font-bold text-gray-700 mt-2">Iniciar Sesión</h2>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4 text-sm"><?= $error ?></div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
                <input type="email" name="email" required class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                <input type="password" name="psword" required class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
            </div>
            <button type="submit" class="w-full bg-vs-yellow text-black font-semibold py-2 rounded-md hover:bg-vs-yellow-hover transition">Entrar</button>
        </form>

        <p class="text-center text-sm text-gray-600 mt-6">
            ¿No tienes cuenta? <a href="register.php" class="text-blue-600 hover:underline">Regístrate aquí</a>
        </p>
    </div>
</body>
</html>