<?php
session_start();
include("config.php");

$message = "";

if (isset($_POST['login'])) {

    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $password   = $_POST['password'];

    // Check if student exists
    $sql = "SELECT * FROM students WHERE student_id='$student_id'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);

        // Verify password
        if (password_verify($password, $row['password'])) {
            $_SESSION['student_id'] = $row['student_id'];
            $_SESSION['student_name'] = $row['first_name'] . " " . $row['last_name'];
            $_SESSION['profile_photo'] = $row['profile_photo'];

            header("Location: dashboard.php");
            exit();
        } else {
            $message = "Incorrect password!";
        }
    } else {
        $message = "Student ID not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Student Login</title>

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
        <a class="navbar-brand fw-bold" href="#">Haramaya University</a>
        <div class="ms-auto d-flex gap-2">
            <a href="Register.php" class="btn btn-outline-light btn-sm">Register</a>
            <a href="contact.php" class="btn btn-outline-light btn-sm">Contact</a>
        </div>
    </div>
</nav>

<div class="form-wrapper my-4">

    <div class="card shadow-lg">
        <div class="form-header">
            <h4>Student Login</h4> 
            <p class="mb-0">Fill in student details carefully</p>
        </div>

        <div class="card-body p-4 p-md-5">

            <?php if ($message != "") { ?>
                <div class="alert alert-danger text-center"><?php echo $message; ?></div>
            <?php } ?>

            <form method="post">

                <div class="mb-3">
                    <label class="form-label">Student ID</label>
                    <input type="text" name="student_id" class="form-control" required>
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
