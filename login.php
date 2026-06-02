<?php
session_start();
include 'koneksi.php';

// Jika sudah login, langsung ke dashboard
if(isset($_SESSION['admin'])){
    header("Location: index.php");
    exit();
}

// Proses Login
if(isset($_POST['login'])){

    $username = $_POST['username'];
    $password = $_POST['password'];

    $query = mysqli_query($koneksi,
    "SELECT * FROM admin WHERE username='$username' AND password='$password'");

    if(mysqli_num_rows($query) > 0){

        $_SESSION['admin'] = $username;

        header("Location: index.php");
        exit();

    } else {

        $error = "Username atau Password salah!";

    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin MYEGO</title>

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    background:#f5f5f5;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
}

.login-box{
    width:400px;
    background:#fff;
    padding:35px;
    border-radius:15px;
    box-shadow:0 5px 20px rgba(0,0,0,0.15);
}

.login-box h2{
    text-align:center;
    margin-bottom:30px;
}

label{
    display:block;
    margin-bottom:8px;
    font-size:16px;
}

input{
    width:100%;
    padding:12px;
    border:1px solid #ccc;
    border-radius:8px;
    margin-bottom:20px;
}

input:focus{
    outline:none;
    border-color:gold;
}

button{
    width:100%;
    padding:12px;
    background:gold;
    border:none;
    border-radius:8px;
    font-size:16px;
    cursor:pointer;
}

button:hover{
    opacity:0.9;
}

.error{
    background:#ffdddd;
    color:red;
    padding:10px;
    margin-bottom:15px;
    border-radius:5px;
    text-align:center;
}

</style>

</head>
<body>

<div class="login-box">

    <h2>Login Admin MYEGO</h2>

    <?php
    if(isset($error)){
        echo "<div class='error'>$error</div>";
    }
    ?>

    <form method="POST">

        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit" name="login">
            Login
        </button>

    </form>

</div>

</body>
</html>