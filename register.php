<?php
// register.php
session_start();
require_once 'conexion.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_user = trim($_POST['name_user']);
    $email = trim($_POST['email']);
    $psword = password_hash(trim($_POST['psword']), PASSWORD_DEFAULT);
    $date_reg = date('Y-m-d H:i:s');

    // Verificar si el correo ya existe
    $stmt = $pdo->prepare("SELECT id_user FROM USUARIO WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $error = "El correo electrónico ya está registrado.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO USUARIO (name_user, date_reg, email, psword) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$name_user, $date_reg, $email, $psword])) {
            header("Location: login.php?registered=1");
            exit;
        } else {
            $error = "Ocurrió un error al registrarse. Intente de nuevo.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro - VirtualStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full border border-gray-200">
        <div class="text-center mb-6">
            <a href="index.php" class="text-3xl font-extrabold text-vs-black tracking-wider">Virtual<span class="text-yellow-600">Store</span></a>
            <h2 class="text-xl font-bold text-gray-700 mt-2">Crear Cuenta</h2>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4 text-sm"><?= $error ?></div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label>
                <input type="text" name="name_user" required class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo Electrónico</label>
                <input type="email" name="email" required class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                <input type="password" name="psword" required class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500">
            </div>
            <button type="submit" class="w-full bg-vs-yellow text-black font-semibold py-2 rounded-md hover:bg-vs-yellow-hover transition">Registrarse</button>
        </form>

        <p class="text-center text-sm text-gray-600 mt-6">
            ¿Ya tienes cuenta? <a href="login.php" class="text-blue-600 hover:underline">Inicia sesión</a>
        </p>
    </div>
</body>
</html>