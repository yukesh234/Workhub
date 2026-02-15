<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/global.css">
    <title>Dashboard - WorkHub</title>
    <style>
        .btn-logout {
            padding: 10px 20px;
            background: #6A0031;
            color: white;
            border-radius: 5px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: #8a1144;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
    <p>This is a dashboard page for administrators.</p>
    <p>
        <?php 
        echo "Welcome, " . htmlspecialchars($_SESSION['admin_email'] ?? 'Admin') . "!";
        echo "<br>Admin ID: " . htmlspecialchars($_SESSION['admin_Id'] ?? 'Unknown');
        ?>
    </p>
    <button class="btn-logout" onclick="handleLogout()">Logout</button>

    <script>
        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '<?= $basePath ?>/logout';
            }
        }
    </script>
</body>
</html>