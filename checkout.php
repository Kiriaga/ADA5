<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

$id_user = $_SESSION['id_user'];
$error = '';
$descuento_aplicado = 0;
$cupon_valido = null;

$stmt = $pdo->prepare("SELECT c.cantidad, p.id_prod, p.nombre_prod, p.precio, p.cant_dispo_prod FROM carrito c JOIN productos p ON c.id_prod = p.id_prod WHERE c.id_user = ?");
$stmt->execute([$id_user]);
$carrito_items = $stmt->fetchAll();

if (empty($carrito_items)) {
    header('Location: carrito.php');
    exit;
}

$subtotal = 0;
foreach($carrito_items as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['aplicar_cupon'])) {
        $clave = trim($_POST['clave_cupon']);
        $stmtC = $pdo->prepare("SELECT * FROM cupones_desc WHERE clave_cupon = ? AND CURDATE() BETWEEN fecha_inicio AND fecha_expira");
        $stmtC->execute([$clave]);
        $cupon = $stmtC->fetch();

        if ($cupon) {
            $calc_desc = $subtotal * ($cupon['descuento_porcentual'] / 100);
            $descuento_aplicado = min($calc_desc, $cupon['descuento_maximo_dinero']);
            $cupon_valido = $cupon['clave_cupon'];
        } else {
            $error = 'Cupón inválido o expirado.';
        }
    }

    if (isset($_POST['procesar_pago'])) {
        $total_final = floatval($_POST['total_final']);
        
        try {
            $pdo->beginTransaction();

            $stmtPed = $pdo->prepare("INSERT INTO pedidos (id_user, total) VALUES (?, ?)");
            $stmtPed->execute([$id_user, $total_final]);
            $id_pedido = $pdo->lastInsertId();

            foreach($carrito_items as $item) {
                if ($item['cant_dispo_prod'] < $item['cantidad']) {
                    throw new Exception("Stock insuficiente para " . $item['nombre_prod']);
                }

                $stmtDet = $pdo->prepare("INSERT INTO detalle_pedido (id_pedido, id_prod, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
                $stmtDet->execute([$id_pedido, $item['id_prod'], $item['cantidad'], $item['precio']]);

                $stmtUpd = $pdo->prepare("UPDATE productos SET cant_dispo_prod = cant_dispo_prod - ? WHERE id_prod = ?");
                $stmtUpd->execute([$item['cantidad'], $item['id_prod']]);
            }

            $stmtClear = $pdo->prepare("DELETE FROM carrito WHERE id_user = ?");
            $stmtClear->execute([$id_user]);

            $pdo->commit();
            header('Location: index.php?success=1');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error al procesar la compra: ' . $e->getMessage();
        }
    }
}

$total_a_pagar = max(0, $subtotal - $descuento_aplicado);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>VirtualStore - Checkout</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="nav-container">
            <a href="index.php" class="logo">⚡ VirtualStore</a>
        </div>
    </header>

    <main>
        <div class="form-container" style="max-width: 600px;">
            <h2 style="margin-bottom: 1.25rem;">Finalizar Compra</h2>
            <?php if($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <div style="margin-bottom: 1.5rem;">
                <p>Subtotal: <strong>$<?= number_format($subtotal, 2) ?></strong></p>
                <?php if($descuento_aplicado > 0): ?>
                    <p style="color: var(--success);">Descuento aplicado: -$<?= number_format($descuento_aplicado, 2) ?></p>
                <?php endif; ?>
                <h3>Total a Pagar: $<?= number_format($total_a_pagar, 2) ?></h3>
            </div>

            <form action="checkout.php" method="POST" class="form-group" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem;">
                <input type="text" name="clave_cupon" placeholder="Código de cupón" class="form-control" value="<?= htmlspecialchars($cupon_valido ?? '') ?>">
                <button type="submit" name="aplicar_cupon" class="btn btn-primary">Aplicar</button>
            </form>

            <form action="checkout.php" method="POST">
                <input type="hidden" name="total_final" value="<?= $total_a_pagar ?>">
                <button type="submit" name="procesar_pago" class="btn btn-primary" style="width: 100%;">Confirmar Pago</button>
            </form>
        </div>
    </main>

    <footer>
        <p>© 2026 VirtualStore</p>
    </footer>
</body>
</html>