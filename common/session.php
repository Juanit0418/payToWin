<?php
require_once '../base.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'] ?? '';
  $password = $_POST['password'] ?? '';

  if (!$username || !$password) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan Credenciales', 'title' => 'Error al loguearse', 'tipo' => 'error']);
    exit;
  }

  try {
    $stmt = $pdo->prepare('SELECT u.id, u.nombre, c.password, r.name as role
                           FROM usuario u
                           JOIN usuario_role ur ON ur.usuario_id = u.id
                           JOIN role r ON r.id = ur.role_id
                           JOIN credenciales c ON c.usuario_id = u.id
                           WHERE u.nombre = :username');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['password'] === $password) {
      setcookie('user_id', $user['id'], time() + (86400 * 7), "/");
      setcookie('username', $user['nombre'], time() + (86400 * 7), "/");
      setcookie('role', $user['role'], time() + (86400 * 7), "/");

      http_response_code(200);
      echo json_encode(['success' => true]);
    } else {
      http_response_code(401);
      echo json_encode(['error' => 'Credenciales incorrectas', 'title' => 'Error al loguearse', 'tipo' => 'error']);
    }
  } catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno', 'title' => 'Error inesperado', 'tipo' => 'error']);
  }
} else {
  http_response_code(405);
  echo json_encode(['error' => 'Acceso no permitido', 'title' => 'Error inesperado', 'tipo' => 'error']);
}
