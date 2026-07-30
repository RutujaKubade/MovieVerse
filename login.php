<?php
session_start();
include 'config/db.php';

// If already logged in, go straight to homepage
if (isset($_SESSION['user_id'])) {
    header("Location:index.php");
    exit();
}

$error = '';

if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username === '' || $password === '') {
        $error = "Please enter both username/email and password.";
    } else {

        $usernameEsc = $conn->real_escape_string($username);

        $sql = "SELECT * FROM users WHERE username='$usernameEsc' OR email='$usernameEsc'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location:index.php");
                exit();
            } else {
                $error = "Incorrect username or password.";
            }
        } else {
            $error = "Incorrect username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Login | MovieVerse</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Poppins',sans-serif;
}

body{
background:
linear-gradient(rgba(7,7,18,.88), rgba(7,7,18,.92)),
url("https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?q=80&w=1800&auto=format&fit=crop")
center/cover fixed;
min-height:100vh;
color:white;
display:flex;
justify-content:center;
align-items:center;
padding:40px 20px;
}

.container{
width:440px;
background:rgba(255,255,255,.06);
border:1px solid rgba(255,255,255,.08);
backdrop-filter:blur(18px);
border-radius:20px;
padding:35px;
animation:fadeUp .8s ease;
box-shadow:0 0 30px rgba(0,0,0,.35);
}

.logo{
text-align:center;
font-size:30px;
font-weight:700;
text-decoration:none;
color:#8b5cf6;
display:block;
margin-bottom:10px;
}

.logo span{
color:white;
}

.container h2{
text-align:center;
margin-bottom:8px;
font-size:26px;
}

.container p.subtitle{
text-align:center;
color:#bdbdbd;
margin-bottom:25px;
font-size:14px;
}

.error-msg{
background:rgba(239,68,68,.15);
color:#fca5a5;
padding:12px 16px;
border-radius:10px;
font-size:14px;
margin-bottom:18px;
text-align:center;
}

label{
display:block;
margin-bottom:8px;
margin-top:16px;
font-size:15px;
}

.input-box{
position:relative;
}

.input-box i{
position:absolute;
left:15px;
top:15px;
color:#8b5cf6;
}

input{
width:100%;
padding:14px 14px 14px 45px;
background:#171727;
border:1px solid transparent;
border-radius:10px;
color:white;
font-size:15px;
transition:.3s;
}

input:focus{
outline:none;
border-color:#8b5cf6;
box-shadow:0 0 15px rgba(139,92,246,.35);
}

button{
width:100%;
margin-top:26px;
padding:15px;
border:none;
border-radius:12px;
background:#8b5cf6;
color:white;
font-size:16px;
font-weight:600;
cursor:pointer;
transition:.3s;
}

button:hover{
transform:translateY(-4px);
box-shadow:0 0 20px rgba(139,92,246,.45);
background:#7c3aed;
}

.switch-link{
display:block;
text-align:center;
margin-top:22px;
color:#bdbdbd;
font-size:14px;
}

.switch-link a{
color:#8b5cf6;
text-decoration:none;
font-weight:600;
}

.switch-link a:hover{
text-decoration:underline;
}

@keyframes fadeUp{
from{
opacity:0;
transform:translateY(40px);
}
to{
opacity:1;
transform:translateY(0);
}
}

</style>

</head>

<body>

<div class="container">

<a href="index.php" class="logo">Movie<span>Verse</span></a>

<h2>Welcome Back</h2>
<p class="subtitle">Login to continue to your library</p>

<?php if ($error !== ''): ?>
<div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form method="POST">

<label>Username or Email</label>
<div class="input-box">
<i class="fa-solid fa-user"></i>
<input type="text" name="username" placeholder="Enter username or email" required
value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
</div>

<label>Password</label>
<div class="input-box">
<i class="fa-solid fa-lock"></i>
<input type="password" name="password" placeholder="Enter password" required>
</div>

<button type="submit" name="login">
<i class="fa-solid fa-right-to-bracket"></i>
Login
</button>

</form>

<span class="switch-link">
Don't have an account? <a href="register.php">Register here</a>
</span>

</div>

</body>
</html>