<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
</head>
<body>
    <script>
        localStorage.removeItem('user');
        window.location.href = '../index.php';
    </script>
</body>
</html>
