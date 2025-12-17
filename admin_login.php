<?php
session_start();
include("config.php"); // your DB connection

$message = "";

/* ---------------- CREATE DEFAULT ADMIN IF NOT EXISTS ---------------- */
$default_email = "managedorm@gmail.com";
$default_password = "dorm123";

// Check if admin exists
$check = mysqli_query($conn, "SELECT * FROM admin WHERE email='$default_email'");
if(mysqli_num_rows($check) == 0){
    $hash = password_hash($default_password, PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO admin (email, password) VALUES ('$default_email', '$hash')");
}

/* ---------------- HANDLE LOGIN ---------------- */
if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM admin WHERE email='$email' LIMIT 1");

    if (mysqli_num_rows($query) == 1) {
        $admin = mysqli_fetch_assoc($query);

        // Verify hashed password
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            header("Location: admin_dashboard.php"); // redirect to admin panel
            exit();
        } else {
            $message = "Incorrect password!";
        }
    } else {
        $message = "Admin not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{ background: #f4f6f9; padding-top: 80px; }
.form-wrapper{ width: 80%; margin:auto; }
@media(max-width:992px){ .form-wrapper{ width:95%; } }
.card{ border:none; border-radius:1rem; }
.form-header{
    background: linear-gradient(rgba(0,0,0,.6), rgba(0,0,0,.6)), url("image/dorm.png") center/cover no-repeat;
    text-align:center; padding:3.5rem 1rem; color:#fff; border-radius:1rem 1rem 0 0;
}
.form-header h4{ font-weight:700; font-size:1.8rem; }
.form-header p{ font-size:.95rem; color:#e5e7eb; }
.form-control{ border-radius:.75rem; padding:.65rem .9rem; }
.btn-primary{ border-radius:50px; padding:.6rem 2.8rem; }
</style>
</head>

<body>

<!-- ================= NAVBAR ================= -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="#">Haramaya University Admin</a>
        <div class="ms-auto d-flex gap-2">
            <a href="contact.php" class="btn btn-outline-light btn-sm">Contact</a>
        </div>
    </div>
</nav>

<div class="form-wrapper my-4">

    <div class="card shadow-lg">
        <div class="form-header">
            <h4>Admin Login</h4> 
            <p class="mb-0">Enter your admin credentials</p>
        </div>

        <div class="card-body p-4 p-md-5">

            <?php if ($message != "") { ?>
                <div class="alert alert-danger text-center"><?php echo $message; ?></div>
            <?php } ?>

            <form method="post">

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                </div>

            </form>

        </div>
    </div>
</div>

</body>
</html>
