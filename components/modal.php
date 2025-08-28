<?php
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$title = $input['title'] ?? null;
$text = $input['text'] ?? null;
$tipo = $input['tipo'] ?? 'message';

if (!$title && !$text) {
    http_response_code(400);
    exit('No hay datos para mostrar');
}

$modalClass = match($tipo) {
    'error' => 'modal-error',
    'warning' => 'modal-warning',
    'message' => 'modal-message',
    default => 'modal-message',
};
?>

<link rel="stylesheet" href="../css/modal.css">

<div class="modal-container">
    <div class="modal <?= htmlspecialchars($modalClass) ?>">
        <h5><?= htmlspecialchars($title) ?></h5>
        <p><?= htmlspecialchars($text) ?></p>
        <div>
            <button type="button" class="close">Cerrar</button>
        </div>
    </div>
</div>
