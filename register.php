<?php
include("config.php");

$message = "";
$msgClass = "";

if (isset($_POST['register'])) {

    /* ================= SANITIZE INPUT ================= */
    $first_name  = mysqli_real_escape_string($conn, $_POST['first_name']);
    $middle_name = mysqli_real_escape_string($conn, $_POST['middle_name']);
    $last_name   = mysqli_real_escape_string($conn, $_POST['last_name']);
    $student_id  = mysqli_real_escape_string($conn, $_POST['student_id']);
    $department  = mysqli_real_escape_string($conn, $_POST['department']);
    $year_batch  = mysqli_real_escape_string($conn, $_POST['year_batch']);
    $password    = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    /* ================= PASSWORD VALIDATION ================= */
    if ($password !== $confirm_password) {
        $message = "Passwords do not match!";
        $msgClass = "alert alert-danger";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters!";
        $msgClass = "alert alert-danger";
    } else {

        /* ================= CHECK PHOTO EXISTS ================= */
        if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
            $message = "Please upload a profile photo.";
            $msgClass = "alert alert-danger";
        } else {

            $photoName = $_FILES['profile_photo']['name'];
            $photoTmp  = $_FILES['profile_photo']['tmp_name'];
            $photoSize = $_FILES['profile_photo']['size'];

            $uploadDir = "uploads/";
            $allowed   = ['jpg','jpeg','png','webp'];

            $ext = strtolower(pathinfo($photoName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $message = "Only JPG, JPEG, PNG or WEBP images allowed.";
                $msgClass = "alert alert-danger";

            } elseif ($photoSize > 2 * 1024 * 1024) {
                $message = "Profile photo must be less than 2MB.";
                $msgClass = "alert alert-danger";

            } else {

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $newPhotoName = uniqid("student_", true) . "." . $ext;

                if (!move_uploaded_file($photoTmp, $uploadDir . $newPhotoName)) {
                    $message = "Failed to upload photo.";
                    $msgClass = "alert alert-danger";
                } else {

                    /* ================= CHECK STUDENT ID ================= */
                    $check = "SELECT student_id FROM students WHERE student_id='$student_id'";
                    $result = mysqli_query($conn, $check);

                    if (mysqli_num_rows($result) > 0) {
                        $message = "Student ID already exists!";
                        $msgClass = "alert alert-danger";
                    } else {

                        /* ================= HASH PASSWORD ================= */
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);

                        /* ================= INSERT DATA ================= */
                        $sql = "INSERT INTO students 
                        (first_name, middle_name, last_name, student_id, department, year_batch, profile_photo, password)
                        VALUES 
                        ('$first_name','$middle_name','$last_name','$student_id','$department','$year_batch','$newPhotoName','$password_hash')";

                        if (mysqli_query($conn, $sql)) {
                            $message = "Student registered successfully!";
                            $msgClass = "alert alert-success";
                        } else {
                            $message = "Database error!";
                            $msgClass = "alert alert-danger";
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Student Registration</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{ background: #f4f6f9; padding-top: 80px; }
.navbar{ height: 70px; }
.form-wrapper{ width: 80%; margin:auto; }
@media(max-width:992px){ .form-wrapper{ width:95%; } }
.card{ border:none; border-radius:1rem; }
.form-header{
    background: linear-gradient(rgba(0,0,0,.6), rgba(0,0,0,.6)), url("image/dorm.png") center/cover no-repeat;
    text-align:center; padding:3.5rem 1rem; color:#fff; border-radius:1rem 1rem 0 0;
}
.form-header h4{ font-weight:700; font-size:1.8rem; }
.form-header p{ font-size:.95rem; color:#e5e7eb; }
.form-control,.form-select{ border-radius:.75rem; padding:.65rem .9rem; }
.btn-primary{ border-radius:50px; padding:.6rem 2.8rem; }
</style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="#">Haramaya University</a>
        <div class="ms-auto d-flex gap-2">
            <a href="login.php" class="btn btn-outline-light btn-sm">Login</a>
            <a href="contact.php" class="btn btn-outline-light btn-sm">Contact</a>
        </div>
    </div>
</nav>

<div class="form-wrapper my-4">

    <div class="card shadow-lg">
        <div class="form-header">
            <h4>Student Registration</h4>
            <p class="mb-0">Fill in student details carefully</p>
        </div>

        <div class="card-body p-4 p-md-5">

            <?php if ($message != "") { ?>
                <div class="<?php echo $msgClass; ?> text-center"><?php echo $message; ?></div>
            <?php } ?>

            <form method="post" enctype="multipart/form-data">

                <div class="row g-3">

                    <div class="col-md-4">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Student ID</label>
                        <input type="text" name="student_id" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Year / Batch</label>
                        <select name="year_batch" class="form-select" required>
                            <option value="">Select Year</option>
                            <option>1st Year</option>
                            <option>2nd Year</option>
                            <option>3rd Year</option>
                            <option>4th Year</option>
                            <option>5th Year</option>
                            <option>6th Year</option>
                            <option>7th Year</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Profile Photo</label>
                        <input type="file" name="profile_photo" class="form-control" accept="image/*" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                </div>

                <div class="text-center mt-5">
                    <button type="submit" name="register" class="btn btn-primary shadow">Register Student</button>
                </div>

            </form>

        </div>
    </div>
</div>

</body>
</html>
