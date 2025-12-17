<?php
session_start();
include("config.php");

// Security
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

/* ================= COUNTS ================= */

// Total Students
$student_count = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM students")
)['total'] ?? 0;

// Pending Reports = Total report - admin_feedback
$report_count = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total 
         FROM reports 
         WHERE admin_feedback IS NULL OR admin_feedback = ''"
    )
)['total'] ?? 0;

// Today's Attendance
date_default_timezone_set("Africa/Addis_Ababa");
$today = date("Y-m-d");

$attendance_count = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total 
         FROM attendance 
         WHERE DATE(created_at) = '$today'"
    )
)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>

<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
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

<?php include('sidebar_admin.php'); ?>

<div class="lg:ml-64 min-h-screen">

<!-- Header -->
<header class="h-16 px-6 flex items-center justify-between
               bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600
               shadow-lg">

    <!-- Title -->
    <h1 class="text-xl md:text-2xl font-semibold text-white tracking-wide">
        Dashboard
    </h1>

    <!-- Right Actions -->
    <div class="flex items-center gap-4">

        <!-- Logout Button -->
        <a href="logouts.php"
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


<!-- Main Content -->
<main class="p-6">

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- Total Students -->
<!-- Total Students -->
<div class="relative overflow-hidden rounded-2xl p-6 shadow-lg bg-gradient-to-br from-indigo-500 to-purple-600 text-white">
    <div class="absolute top-0 right-0 opacity-20 text-8xl">
        <i class="fas fa-user-graduate"></i>
    </div>

    <div class="relative flex items-center justify-between">
        <div>
            <p class="text-sm uppercase tracking-wide opacity-80">Total Students</p>
            <h2 class="text-4xl font-extrabold mt-2">
                <?= $student_count ?>
            </h2>
        </div>

        <div class="bg-white/20 backdrop-blur-md p-4 rounded-xl">
            <i class="fas fa-user-graduate text-3xl"></i>
        </div>
    </div>
</div>

<!-- Pending Reports -->
<div class="relative overflow-hidden rounded-2xl p-6 shadow-lg bg-gradient-to-br from-rose-500 to-red-600 text-white">
    <div class="absolute top-0 right-0 opacity-20 text-8xl">
        <i class="fas fa-file-alt"></i>
    </div>

    <div class="relative flex items-center justify-between">
        <div>
            <p class="text-sm uppercase tracking-wide opacity-80">Student Reports</p>
            <h2 class="text-4xl font-extrabold mt-2">
                <?= $report_count ?>
            </h2>
        </div>

        <div class="bg-white/20 backdrop-blur-md p-4 rounded-xl">
            <i class="fas fa-file-alt text-3xl"></i>
        </div>
    </div>
</div>

<!-- Today's Attendance -->
<div class="relative overflow-hidden rounded-2xl p-6 shadow-lg bg-gradient-to-br from-emerald-500 to-teal-600 text-white">
    <div class="absolute top-0 right-0 opacity-20 text-8xl">
        <i class="fas fa-calendar-check"></i>
    </div>

    <div class="relative flex items-center justify-between">
        <div>
            <p class="text-sm uppercase tracking-wide opacity-80">Today's Attendance</p>
            <h2 class="text-4xl font-extrabold mt-2">
                <?= $attendance_count ?>
            </h2>
        </div>

        <div class="bg-white/20 backdrop-blur-md p-4 rounded-xl">
            <i class="fas fa-calendar-check text-3xl"></i>
        </div>
    </div>
</div>


    </div>

</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');
if(toggleBtn){
    toggleBtn.addEventListener('click', ()=> sidebar.classList.toggle('show'));
}
</script>

</body>
</html>
