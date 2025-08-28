<?php
include_once __DIR__ . '/base.php';
include_once './utils/modal.php';
require_once './common/rutas.php';

$ruta = obtenerRutas();

$isLoggedIn = isset($_COOKIE['user_id']) && isset($_COOKIE['username']) && isset($_COOKIE['role']);
$isHumanVerified = isset($_COOKIE['human_verified']);
?>
<!DOCTYPE html>
<html lang="es"> 
<head>
    <meta charset="UTF-8">
    <title>Pay To Win</title>
    <link rel="stylesheet" href="css/reset.css?v=<?=version()?>">
    <link rel="stylesheet" href="css/global.css?v=<?=version()?>">
    <script src="js/index.js?v=<?=version()?>" defer></script>
</head>
<body>

<header></header>
<main>
<?php if (!$isLoggedIn): ?>
    <form id="loginForm">
        <input type="text" name="username" placeholder="Usuario" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <button type="submit">Iniciar sesión</button>
    </form>

<?php elseif ($isLoggedIn && !$isHumanVerified): ?>
    <link rel="stylesheet" href="css/slider.css?v=<?=version()?>">
    <div id="sliderContainer">
        <p class="sliderText">Desliza para continuar</p>
        <div class="sliderTrack">
            <div class="sliderButton" id="sliderButton">&#x27A4;</div>
        </div>
    </div>
<?php else: ?>
    <p>Hola, <?= htmlspecialchars($_COOKIE['username']) ?>. Ya estás logueado.</p>
    <a href="<?= htmlspecialchars($ruta['LOGOUT']) ?>">Cerrar sesión</a>
<?php endif; ?>
</main>
<footer></footer>

<script>
    window.ROUTES = <?= json_encode($ruta, JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="./js/modal.js?v=<?=version()?>"></script>
<?php if (!$isLoggedIn): ?>
    <script src="./js/login.js?v=<?=version()?>"></script>
<?php elseif ($isLoggedIn && !$isHumanVerified): ?>
    <script src="./js/slider.js?v=<?=version()?>"></script>
<?php else: ?>
    <script src="./js/index.js?v=<?=version()?>"></script>
<?php endif; ?>
</body>
</html>
