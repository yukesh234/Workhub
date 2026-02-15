<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <?php 
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    ?>
    <link rel="stylesheet" href="<?= $basePath ?>/css/auth.css">
   <link  rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>

    <title>Document</title>
</head>
<body>
    <main>
            <div class="wrapper">
                <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>"><i class="fa-solid fa-arrow-left"></i></a>
            <div class="title">
                <h1>Login</h1>
            </div>
              <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
            <div class="formbody">
                <form action="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/login" method="POST" id="loginForm">
                <input type="email" name="email" id="email" required placeholder="Email">
                <input type="password" name="password" id="password" required placeholder="Password">
                <button type="submit">Login</button>
                <p>Already have an account?<a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/register">Register</a></p>                    
                </form>
            </div>
        </div>
    </main>
</body>
</html>
<script>
    const form = document.getElementById('loginform');
    form.addEventListener('submit',function (event){
        event.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        if(email.trim()=== '' || password.trim()=== ''){
            alert('Please fill in all fields');
            return;
        }
        if(password.length<6)
        {
            alert('password must be at least 6 character long');
            return;
        }
        form.submit();
    })
</script>