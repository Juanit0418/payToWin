<main>
    <h2>Iniciar Sesion</h2>

    <?php require_once __DIR__ . "/../templates/alertas.php"; ?>

    <form id="loginForm" action="/login" class="formulario" method="POST">
        <label for="email">Email</label>
        <input type="email" name="email" placeholder="Tu email" id="email" value="<?php echo $usuario->email; ?>">

        <label for="password">Contraseña</label>
        <input type="password" name="password" placeholder="Tu contraseña" id="password">

        <button type="submit">Iniciar sesión</button>
    </form>

<!--    <div id="sliderContainer">
        <p class="sliderText">Desliza para continuar</p>
        <div class="sliderTrack">
            <div class="sliderButton" id="sliderButton">&#x27A4;</div>
        </div>
    </div> -->
</main>
