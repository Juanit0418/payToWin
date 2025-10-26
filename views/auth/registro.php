<main class="registro">
  <h2 class="registro__heading">Registrate en PaytoWin</h2>

  <div class="registro__contenedor">
    <?php require_once __DIR__ . "/../templates/alertas.php"; ?>
  
    <form class="formulario" method="POST" action="/registro">
      <div class="formulario__campo">
        <label class="formulario__label" for="nombre">Nombre</label>
        <input class="formulario__input" type="text" id="nombre" name="nombre" value="<?php echo $usuario->nombre; ?>" placeholder="Tu nombre">
      </div> <!-- .formulario__campo -->
  
      <div class="formulario__campo">
        <label class="formulario__label" for="apellido">Apellido</label>
        <input class="formulario__input" type="text" id="apellido" name="apellido" value="<?php echo $usuario->apellido; ?>" placeholder="Tu apellido">
      </div> <!-- .formulario__campo -->
  
      <div class="formulario__campo">
        <label class="formulario__label" for="email">Email</label>
        <input class="formulario__input" type="email" id="email" name="email" value="<?php echo $usuario->email; ?>" placeholder="Tu email">
      </div> <!-- .formulario__campo -->
  
      <div class="formulario__campo">
        <label class="formulario__label" for="password">Contraseña</label>
        <input class="formulario__input" type="password" id="password" name="password" value="" placeholder="Tu contraseña">
      </div> <!-- .formulario__campo -->
  
      <input class="formulario__boton" type="submit" value="Crear Cuenta">
    </form>

    <div class="acciones">
      <a class="acciones__enlace" href="/olvide">¿Olvidaste tu contraseña?</a>
      <a class="acciones__enlace" href="/login">¿Ya tienes una cuenta? Inicia sesión</a>
        </div> <!-- .acciones -->
  </div> <!-- .registro__contenedor -->
</main>