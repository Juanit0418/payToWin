<main class="olvide">
  <h2 class="olvide__heading">Olvidé mi contraseña</h2>

  <div class="olvide__contenedor">
    <?php require_once __DIR__ . "/../templates/alertas.php"; ?>
  
    <form class="formulario" method="POST" action="/olvide">
      <div class="formulario__campo">
        <label class="formulario__label" for="email">Email</label>
        <input class="formulario__input" type="email" id="email" name="email" placeholder="Tu email">
      </div> <!-- .formulario__campo -->

      <input class="formulario__boton" type="submit" value="Enviar instrucciones">
    </form>

    <div class="acciones">
      <a class="acciones__enlace" href="/login">¿Ya tienes una cuenta? Inicia sesión</a>
      <a class="acciones__enlace" href="/registro">¿Aún no tienes una cuenta? Registrate</a>
        </div> <!-- .acciones -->
  </div> <!-- .registro__contenedor -->
</main>