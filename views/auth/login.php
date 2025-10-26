<main class="login">
    <h2 class="login__heading">Iniciar Sesión</h2>

    <div class="login__contenedor">

        <?php require_once __DIR__ . "/../templates/alertas.php"; ?>
    
        <form action="/login" class="formulario" method="POST">
            <div class="formulario__campo">
                <label class="formulario__label" for="email">Email</label>
                <input class="formulario__input" type="email" name="email" placeholder="Tu email" id="email">
            </div> <!-- .formulario__campo -->
    
            <div class="formulario__campo">
                <label class="formulario__label" for="password">Contraseña</label>
                <input class="formulario__input" type="password" name="password" placeholder="Tu contraseña" id="password">
            </div> <!-- .formulario__campo -->
    
            <button class="formulario__boton" type="submit">Iniciar sesión</button>
        </form>

        <div class="acciones">
            <a class="acciones__enlace" href="/registro">¿Aún no tienes una cuenta? Regístrate</a>
            <a class="acciones__enlace" href="/olvide">¿Olvidaste tu contraseña?</a>
        </div> <!-- .acciones -->
    </div> <!-- .login__contenedor -->
</main>