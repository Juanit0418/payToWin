<main class="reestablecer">
  <h2 class="reestablecer__heading">Reestablecer mi contraseña</h2>

  <div class="reestablecer__contenedor">

  <?php require_once __DIR__ . "/../templates/alertas.php"; ?>
  
  <?php if($token_valido){ ?>

  <form method="POST" action="" class="formulario">
    <div class="formulario__campo">
      <label for="password" class="formulario__label">Nueva Contraseña</label>
      <input type="password" id="password" class="formulario__input" placeholder="Tu Nueva Contraseña" name="password">
    </div>

    <input type="submit" class="formulario__boton" value="Reestablecer">
  </form>
  <?php } ?>

  <div class="acciones">
    <a href="/login" class="acciones__enlace">¿Ya tienes una cuenta? Iniciar Sesión</a>
    <a href="/registro" class="acciones__enlace">¿Aún no tienes cuenta? Crear una</a>
  </div>

  </div> <!-- .registro__contenedor -->
</main>