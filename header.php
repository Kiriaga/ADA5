<?php
// header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detectar el nombre del archivo actual (ej. 'index.php' o 'cart.php')
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'VirtualStore' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col justify-between" x-data="virtualStore">

    <!-- Header / Navbar -->
    <header class="bg-vs-black text-white sticky top-0 z-50 shadow-md">
        <div class="container mx-auto px-4 py-3 flex items-center justify-between">
            <a href="index.php" class="text-2xl font-bold tracking-wider text-vs-yellow flex items-center gap-2">
                <span>🛒</span> VirtualStore
            </a>
            <div class="flex items-center gap-6">
                <?php if (isset($_SESSION['user_name'])): ?>
                    <span class="text-sm text-gray-300">Hola, <strong class="text-white"><?= htmlspecialchars($_SESSION['user_name']) ?></strong></span>
                    
                    <!-- COMPORTAMIENTO DINÁMICO SEGÚN LA PÁGINA -->
                    <?php if ($current_page === 'index.php'): ?>
                        <!-- Si estamos en el index, mostramos el botón del Carrito -->
                        <a href="cart.php" class="relative bg-vs-dark px-3 py-2 rounded-md hover:bg-gray-700 transition flex items-center gap-1">
                            🛒 <span class="hidden md:inline">Carrito</span>
                            <?php 
                                $cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
                                if($cart_count > 0):
                            ?>
                                <span class="absolute -top-2 -right-2 bg-vs-yellow text-black text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center"><?= $cart_count ?></span>
                            <?php endif; ?>
                        </a>
                    <?php else: ?>
                        <!-- Si estamos en otra pantalla (ej. cart.php), mostramos un enlace para Seguir comprando -->
                        <a href="index.php" class="text-sm text-gray-300 hover:text-white underline">Seguir comprando</a>
                    <?php endif; ?>

                    <a href="logout.php" class="text-sm text-red-400 hover:underline">Cerrar Sesión</a>
                <?php else: ?>
                    <a href="login.php" class="bg-vs-yellow text-black font-semibold px-4 py-2 rounded hover:bg-vs-yellow-hover transition text-sm">Iniciar Sesión</a>
                    <a href="register.php" class="bg-gray-700 text-white font-semibold px-4 py-2 rounded hover:bg-gray-600 transition text-sm">Regístrate</a>
                <?php endif; ?>
            </div>
        </div>
    </header>