<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>P2W | <?php echo $titulo; ?></title>
    <link rel="stylesheet" href="/build/css/app.css">
    </head>
<body>
    <?php 
        include_once __DIR__ .'/templates/header.php';
        echo $contenido;
        include_once __DIR__ .'/templates/footer.php'; 
    ?>
    <script src="/build/js/bundle.min.js" defer></script>
</body>
</html>