<?php
// cart.php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=cart");
    exit;
}

$error_msg = '';
$success_redirect = false; // Bandera para activar la vista de éxito y redirección JS

// Manejo de acciones en el carrito (eliminar)
if (isset($_GET['action'])) {
    $id = intval($_GET['id'] ?? 0);
    if ($_GET['action'] === 'remove' && isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
    }
}

// Aplicar Cupón
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $key_discount = trim($_POST['key_discount']);
    $stmt = $pdo->prepare("SELECT * FROM CUPONES WHERE key_discount = ? AND date_start <= NOW() AND date_end >= NOW()");
    $stmt->execute([$key_discount]);
    $cupon = $stmt->fetch();

    if ($cupon) {
        $_SESSION['applied_coupon'] = $cupon;
        $success_coupon_msg = "¡Cupón aplicado con éxito!";
    } else {
        $error_msg = "El cupón es inválido o ha expirado.";
        unset($_SESSION['applied_coupon']);
    }
}

// Cálculo de montos
$subtotal = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
}

$discount_amount = 0;
if (isset($_SESSION['applied_coupon'])) {
    $c = $_SESSION['applied_coupon'];
    $calc_discount = $subtotal * ($c['percent_discount'] / 100);
    $discount_amount = min($calc_discount, $c['max_money_discount']);
}
$total = max(0, $subtotal - $discount_amount);

// Procesar la compra (Checkout)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    if (empty($_SESSION['cart'])) {
        $error_msg = "El carrito está vacío.";
    } else {
        $pdo->beginTransaction();
        try {
            foreach ($_SESSION['cart'] as $id_prod => $item) {
                // Verificar stock actual en BD
                $stmt_check = $pdo->prepare("SELECT quantity_remain, name_prod FROM PRODUCTOS WHERE id_prod = ? FOR UPDATE");
                $stmt_check->execute([$id_prod]);
                $prod_db = $stmt_check->fetch();

                if (!$prod_db || $prod_db['quantity_remain'] < $item['quantity']) {
                    $pdo->rollBack();
                    unset($_SESSION['cart'][$id_prod]);
                    throw new Exception("El producto '" . ($prod_db['name_prod'] ?? 'Desconocido') . "' ya no está disponible en la cantidad solicitada. Ha sido eliminado de su carrito.");
                }

                // Actualizar inventario restando la cantidad comprada
                $new_stock = $prod_db['quantity_remain'] - $item['quantity'];
                $stmt_update = $pdo->prepare("UPDATE PRODUCTOS SET quantity_remain = ? WHERE id_prod = ?");
                $stmt_update->execute([$new_stock, $id_prod]);
            }

            $pdo->commit();
            // Limpiar carrito y cupón
            unset($_SESSION['cart']);
            unset($_SESSION['applied_coupon']);
            
            $success_redirect = true; // Activa la vista de éxito con redirección JS en el carrito

        } catch (Exception $e) {
            $error_msg = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito de Compras - VirtualStore</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/styles.css">
    <?php if ($success_redirect): ?>
        <!-- Redirección automática al index después de 3 segundos -->
        <script>
            setTimeout(function() {
                window.location.href = 'index.php';
            }, 3000);
        </script>
    <?php endif; ?>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col justify-between">

	<!-- Header -->
    <?php
    include 'header.php';
    ?>

    <main class="container mx-auto px-4 py-8 flex-grow">
        <h1 class="text-3xl font-extrabold text-gray-800 mb-6">Carrito de Compras</h1>

        <?php if ($success_redirect): ?>
            <!-- Mensaje de Compra Exitosa exclusivo de la pantalla del carrito -->
            <div class="bg-green-100 border-2 border-green-500 text-green-800 p-8 rounded-lg text-center shadow-xl my-12" role="alert">
                <div class="text-5xl mb-3">✅</div>
                <h2 class="text-2xl font-black mb-2">¡Compra realizada con éxito!</h2>
                <p class="text-gray-700 text-sm">Gracias por su compra en VirtualStore. Redirigiendo al inicio en unos segundos...</p>
            </div>
        <?php else: ?>

            <?php if ($error_msg): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
                    <strong class="font-bold">Aviso del sistema:</strong>
                    <span class="block sm:inline"><?= $error_msg ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($success_coupon_msg)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6"><?= $success_coupon_msg ?></div>
            <?php endif; ?>

            <?php if (empty($_SESSION['cart'])): ?>
                <div class="bg-white p-12 rounded-lg shadow text-center">
                    <p class="text-gray-600 text-lg mb-4">Tu carrito de compras está vacío.</p>
                    <a href="index.php" class="bg-vs-yellow text-black font-semibold px-6 py-2 rounded shadow hover:bg-vs-yellow-hover">Ir a comprar</a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <!-- Desglose de Productos -->
                    <div class="lg:col-span-2 bg-white rounded-lg shadow overflow-hidden border border-gray-200">
                        <div class="px-6 py-4 bg-gray-50 border-b font-semibold text-gray-700">Artículos en el Carrito</div>
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($_SESSION['cart'] as $id => $item): ?>
                                <div class="p-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                    <div>
                                        <h3 class="font-bold text-gray-800 text-lg"><?= htmlspecialchars($item['name']) ?></h3>
                                        <p class="text-sm text-gray-500">Precio unitario: $<?= number_format($item['price'], 2) ?></p>
                                        <p class="text-sm text-gray-500">Cantidad: <span class="font-bold text-black"><?= $item['quantity'] ?></span></p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="font-bold text-gray-900">$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                                        <a href="cart.php?action=remove&id=<?= $id ?>" class="text-red-500 hover:text-red-700 text-sm font-semibold border border-red-200 px-3 py-1 rounded hover:bg-red-50 transition">Eliminar Producto</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Resumen de Orden y Pago -->
                    <div class="bg-white rounded-lg shadow p-6 border border-gray-200 h-fit">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Resumen de Orden</h2>
                        
                        <div class="space-y-3 mb-6 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <span>Monto de productos:</span>
                                <span class="font-medium">$<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="flex justify-between text-green-600">
                                <span>Descuento de cupón:</span>
                                <span class="font-medium">-$<?= number_format($discount_amount, 2) ?></span>
                            </div>
                        </div>

                        <!-- Aplicar Cupón -->
                        <form method="POST" class="mb-6 bg-gray-50 p-4 rounded-md border">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Aplicar Cupón por Clave:</label>
                            <div class="flex gap-2">
                                <input type="text" name="key_discount" placeholder="Ej. VERANO2026" class="w-full text-sm px-3 py-1.5 border rounded focus:ring-2 focus:ring-yellow-500" required>
                                <button type="submit" name="apply_coupon" class="bg-gray-800 text-white text-xs px-3 py-1.5 rounded hover:bg-gray-700 transition">Aplicar</button>
                            </div>
                            <?php if (isset($_SESSION['applied_coupon'])): ?>
                                <p class="text-xs text-green-600 mt-2">Cupón activo: <strong><?= htmlspecialchars($_SESSION['applied_coupon']['name_cupon']) ?></strong></p>
                            <?php endif; ?>
                        </form>

                        <!-- Monto Total Grande -->
                        <div class="border-t pt-4 mb-6 flex justify-between items-center">
                            <span class="text-base font-bold text-gray-700">Monto Total:</span>
                            <span class="text-2xl font-black text-gray-900">$<?= number_format($total, 2) ?></span>
                        </div>

                        <!-- Botón Comprar -->
                        <form method="POST">
                            <button type="submit" name="checkout" class="w-full bg-vs-yellow text-black font-bold py-3 rounded-lg shadow-md hover:bg-vs-yellow-hover transition text-center text-base">
                                Realizar la Compra
                            </button>
                        </form>
                    </div>

                </div>
            <?php endif; ?>

        <?php endif; ?>
    </main>

    <?php
    include 'footer.php';
    ?>

</body>
</html>
