<?php
// index.php
session_start();
require_once 'conexion.php';

// Obtener productos
$stmt = $pdo->query("SELECT * FROM PRODUCTOS");
$productos = $stmt->fetchAll();

// Manejo de añadir al carrito con validación de stock máximo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php?redirect=index");
        exit;
    }
    
    $id_prod = intval($_POST['id_prod']);
    $stmt_p = $pdo->prepare("SELECT * FROM PRODUCTOS WHERE id_prod = ?");
    $stmt_p->execute([$id_prod]);
    $prod = $stmt_p->fetch();

    if ($prod) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Cantidad actual en el carrito para este producto
        $current_in_cart = isset($_SESSION['cart'][$id_prod]) ? $_SESSION['cart'][$id_prod]['quantity'] : 0;

        if ($prod['quantity_remain'] > 0 && $current_in_cart < $prod['quantity_remain']) {
            if (isset($_SESSION['cart'][$id_prod])) {
                $_SESSION['cart'][$id_prod]['quantity'] += 1;
            } else {
                $_SESSION['cart'][$id_prod] = [
                    'name' => $prod['name_prod'],
                    'price' => $prod['price'],
                    'quantity' => 1,
                    'max_stock' => $prod['quantity_remain']
                ];
            }
            $success_msg = "Producto añadido al carrito exitosamente.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VirtualStore - Inicio</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col justify-between" x-data="virtualStore">


    <!-- Header / Navbar -->
    <?php
    include 'header.php';
    ?>

    <!-- Alerta solo para cuando se añade un producto desde el index -->
    <?php if (isset($success_msg)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative container mx-auto mt-4" role="alert">
            <span class="block sm:inline"><?= $success_msg ?></span>
        </div>
    <?php endif; ?>

    <!-- Catálogo de Productos -->
    <main class="container mx-auto px-4 py-8 flex-grow">
        <div class="mb-6">
            <h1 class="text-3xl font-extrabold text-gray-800">Productos Disponibles</h1>
            <p class="text-gray-600 text-sm">Explora nuestra selección mas exclusiva.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($productos as $p): 
                $id_p = $p['id_prod'];
                $qty_in_cart = isset($_SESSION['cart'][$id_p]) ? $_SESSION['cart'][$id_p]['quantity'] : 0;
                $is_max_reached = ($qty_in_cart >= $p['quantity_remain']) && ($p['quantity_remain'] > 0);
            ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 flex flex-col justify-between hover:shadow-xl transition">
                    <div class="p-5 flex-grow">
                        <span class="text-xs uppercase tracking-wider text-gray-500 font-semibold"><?= htmlspecialchars($p['type_prod']) ?></span>
                        <h2 class="text-lg font-bold text-gray-800 mt-1 mb-2"><?= htmlspecialchars($p['name_prod']) ?></h2>
                        <div class="text-2xl font-black text-gray-900 mb-2">$<?= number_format($p['price'], 2) ?></div>
                        <p class="text-xs text-gray-500">Disponibles: <span class="font-bold"><?= $p['quantity_remain'] ?></span> unidades</p>
                        <?php if($qty_in_cart > 0): ?>
                            <p class="text-xs text-blue-600 mt-1 font-medium">En tu carrito: <?= $qty_in_cart ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="p-4 bg-gray-50 border-t border-gray-100">
                        <?php if ($p['quantity_remain'] <= 0): ?>
                            <button disabled class="w-full bg-gray-300 text-gray-600 font-medium py-2 rounded cursor-not-allowed text-sm">
                                Agotado
                            </button>
                        <?php elseif ($is_max_reached): ?>
                            <button disabled class="w-full bg-yellow-100 text-yellow-800 border border-yellow-300 font-medium py-2 rounded cursor-not-allowed text-sm">
                                Máximo Alcanzado
                            </button>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="id_prod" value="<?= $p['id_prod'] ?>">
                                <button type="submit" name="add_to_cart" class="w-full bg-vs-yellow text-black font-medium py-2 rounded shadow hover:bg-vs-yellow-hover transition text-sm">
                                    Añadir al carrito
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <?php
    include 'footer.php';
    ?>

</body>
</html>