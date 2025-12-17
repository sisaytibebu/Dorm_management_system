<?php
session_start();
include("config.php");

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch student data
$student_id = $_SESSION['student_id'];
$query = mysqli_query($conn, "SELECT * FROM students WHERE student_id='$student_id'");
$student = mysqli_fetch_assoc($query);

// Full name, dorm info, photo
$full_name = trim($student['first_name'] . ' ' . ($student['middle_name'] ?? '') . ' ' . $student['last_name']);
$dorm_info = $student['department'] . ' - ' . $student['year_batch'];
$profile_photo = $student['profile_photo'] ? 'uploads/' . $student['profile_photo'] : 'default-avatar.jpg';
$building = $student['Building'] ?? 'N/A';
$dprm_no = $student['Dorm_no'] ?? 'N/A';
$capacity = $student['capacity'] ?? 'N/A'; // Added capacity

// Handle profile photo upload
if(isset($_POST['update_photo'])){
    if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0){
        $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','webp'];
        if(in_array(strtolower($ext), $allowed)){
            $newName = uniqid("student_",true).".".$ext;
            move_uploaded_file($_FILES['profile_photo']['tmp_name'], "uploads/".$newName);
            mysqli_query($conn,"UPDATE students SET profile_photo='$newName' WHERE student_id='$student_id'");
            header("Refresh:0"); // reload page to update photo
        }
    }
}

// Handle password update
if(isset($_POST['update_password'])){
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];
    if($new === $confirm){
        if(password_verify($current, $student['password'])){
            $hash = password_hash($new, PASSWORD_DEFAULT);
            mysqli_query($conn,"UPDATE students SET password='$hash' WHERE student_id='$student_id'");
            echo "<script>alert('Password updated successfully!');</script>";
        } else {
            echo "<script>alert('Current password incorrect!');</script>";
        }
    } else {
        echo "<script>alert('New password and confirm password do not match!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Dorm</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
#sidebar { overflow-y:auto; transition: transform 0.3s; background: linear-gradient(to bottom, #4f46e5, #312e81); color:white;}
#sidebar::-webkit-scrollbar { width:6px; }
#sidebar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.3); border-radius:3px; }
.sidebar-item:hover { background:white !important; color:black !important; }
.badge-notice { background:red; color:white; font-size:0.75rem; font-weight:bold; padding:0.2rem 0.5rem; border-radius:9999px; margin-left:auto;}
@media(max-width:1023px){ 
    #sidebar{transform:translateX(-100%); position:fixed; z-index:50; height:100vh; width:16rem; top:0; left:0;} 
    #sidebar.show{transform:translateX(0);} 
}
@media(min-width:1024px){ #sidebar{transform:translateX(0); width:16rem;} }
</style>
</head>
<body class="bg-gray-100">

<?php include('sidebar.php'); ?>

<div class="lg:ml-64 min-h-screen">

<!-- Header -->
<header class="h-16 px-6 flex items-center justify-between
               bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600
               shadow-lg">

    <!-- Title -->
    <h1 class="text-xl md:text-2xl font-semibold text-white tracking-wide">
        View Dorm
    </h1>

    <!-- Right Actions -->
    <div class="flex items-center gap-4">

        <!-- Logout Button -->
        <a href="logout.php"
           class="flex items-center gap-2 px-4 py-2 rounded-lg
                  bg-white/20 backdrop-blur-md text-white
                  hover:bg-white/30 transition duration-300 shadow-md">
            <i class="fas fa-sign-out-alt"></i>
            <span class="hidden sm:inline">Logout</span>
        </a>

        <!-- Mobile Sidebar Toggle -->
        <button id="sidebarToggle"
                class="lg:hidden p-2 rounded-lg
                       bg-white/20 backdrop-blur-md text-white
                       hover:bg-white/30 transition duration-300">
            <i class="fas fa-bars text-lg"></i>
        </button>

    </div>
</header>

<main class="p-6">
    <div class="bg-white shadow-lg rounded-lg p-6" style="border-top:3px solid green;">
        <h2 class="text-xl font-semibold mb-4">Dorm Details</h2>
        <table class="table table-striped table-hover w-full">
            <tbody>
                <tr><th>Full Name</th><td><?php echo htmlspecialchars($full_name); ?></td></tr>
                <tr><th>Department / Year</th><td><?php echo htmlspecialchars($dorm_info); ?></td></tr>
                <tr><th>Building</th><td><?php echo htmlspecialchars($building); ?></td></tr>
                <tr><th>Dorm No</th><td><?php echo htmlspecialchars($dprm_no); ?></td></tr>
                <tr><th>Capacity</th><td><?php echo htmlspecialchars($capacity); ?></td></tr> <!-- Added capacity -->
            </tbody>
        </table>
    </div>
</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle sidebar
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');
toggleBtn.addEventListener('click', ()=> sidebar.classList.toggle('show'));
</script>
</body>
</html>
