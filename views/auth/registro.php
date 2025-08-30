<main>
  <h2>Registrate en PaytoWin</h2>

  <?php require_once __DIR__ . "/../templates/alertas.php"; ?>

  <form class="formulario" method="POST" action="/registro">
    <label for="nombre">Nombre</label>
    <input type="text" id="nombre" name="nombre" value="<?php echo $usuario->nombre; ?>" placeholder="Tu nombre">

    <label for="apellido">Apellido</label>
    <input type="text" id="apellido" name="apellido" value="<?php echo $usuario->apellido; ?>" placeholder="Tu apellido">

    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?php echo $usuario->email; ?>" placeholder="Tu email">

    <label for="password">Contraseña</label>
    <input type="password" id="password" name="password" value="" placeholder="Tu contraseña">

    <input type="submit" value="Crear Cuenta">
  </form>
</main>