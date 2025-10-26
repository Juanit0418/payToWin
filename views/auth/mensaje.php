<main class="mensaje">

  <div class="mensaje__contenedor">
    <?php if($tipo === "confirmacion"){ ?>
        <h2 class="mensaje__heading">Confirma tu cuenta</h2>
        <p class="mensaje__descripcion">Hemos enviado un correo de confirmación a tu cuenta. Por favor, revisa tu bandeja de entrada y haz clic en el enlace proporcionado para activar tu cuenta.</p>
    <?php } else if($tipo === "reestablecer"){ ?>
        <h2 class="mensaje__heading">Reestablece tu contraseña</h2>
        <p class="mensaje__descripcion">Hemos enviado un correo con las instrucciones para reestablecer tu contraseña. Por favor, revisa tu bandeja de entrada y sigue el enlace proporcionado.</p>
    <?php } else {
        header('Location: /login');
        exit;
    }; ?>

    <div class="acciones">
      <a class="acciones__enlace" href="/login">¿Ya tienes una cuenta? Inicia sesión</a>
      <a class="acciones__enlace" href="/registro">¿No tienes una cuenta? Registrate</a>
        </div> <!-- .acciones -->
  </div> <!-- .mensaje__contenedor -->
</main>