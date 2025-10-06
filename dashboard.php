<?php
require_once "config.php";

// Bloquea acceso si no hay sesión
if (empty($_SESSION['logged_in'])) {
    header("Location: index.php");
    exit;
}
?>
<?php include "header.php"; ?>



<?php include "footer.php"; ?>


