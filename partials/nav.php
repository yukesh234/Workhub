<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php 
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    ?>
    <link rel="stylesheet" href="<?= $basePath ?>/css/global.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <title>WorkHub - Home</title>
</head>
<body>
    <nav>
        <div class="navbar">
            <div class="logo">
                <p>WorkHub</p>
            </div>
            <div class="right">
                <a href="<?= $basePath ?>/register">Sign up</a>
                <a href="<?= $basePath ?>/login">Sign in</a>
            </div>
        </div>
    </nav>
</body>
</html>