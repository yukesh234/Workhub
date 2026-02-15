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
                <h1>Register</h1>
            </div>
              <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
            <div class="formbody">
                <form action="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/register" method="POST" id="registerForm">
                <input type="email" name="email" id="email" required placeholder="Email">
                <input type="password" name="password" id="password" required placeholder="Password">
                <input type="password" name="repassword" id="repassword" required placeholder="Confirm password">
                <button type="submit">Register</button>
                <p>Already have an account?<a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/login">login</a></p>                    
                </form>
            </div>
        </div>
    </main>
</body>
</html>
<script>
    const form = document.getElementById('registerform');
    form.addEventListener('submit',function(e){
        e.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const repassword = document.getElementById('repassword').value;
        if(emai.trim() === '' || password.trim()=== '' || repassword.trim() === ''){
            alert('please fill the form correctly');
            return
        }
        if(password.length<6 || repassword.length<6){
            alert('password must be at least 6 character long');
            return;
        }
        if(password !== repassword){
            alert('passwords do not match');
            return
        }
        form.submit();
    })
</script>