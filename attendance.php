<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include("config.php");

/* ---------------- SECURITY ---------------- */
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

/* ---------------- ETHIOPIAN DATE CONVERSION ---------------- */
function gregorianToEthiopian($gy, $gm, $gd) {
    $ethiopianNewYear = 11;
    if (($gy % 4) === 0) {
        $ethiopianNewYear = 12;
    }

    $gMonthDays = [31,28,31,30,31,30,31,31,30,31,30,31];

    if (($gy % 4) === 0 && $gm > 2) {
        $gd += 1;
    }

    $days = 0;

    if ($gm < 9 || ($gm == 9 && $gd < $ethiopianNewYear)) {
        for ($i = 9; $i < 12; $i++) { $days += $gMonthDays[$i-1]; }
        for ($i = 1; $i < $gm; $i++) { $days += $gMonthDays[$i-1]; }
        $days += $gd;
        $days -= $ethiopianNewYear;
        $ethiopianYear = $gy - 8;
    } else {
        for ($i = 9; $i < $gm; $i++) { $days += $gMonthDays[$i-1]; }
        $days += $gd;
        $days -= $ethiopianNewYear;
        $ethiopianYear = $gy - 7;
    }

    $ethiopianMonth = floor($days / 30) + 1;
    $ethiopianDay   = ($days % 30) + 1;

    return sprintf("%04d-%02d-%02d", $ethiopianYear, $ethiopianMonth, $ethiopianDay);
}

/* ---------------- TIMEZONE ---------------- */
date_default_timezone_set("Africa/Addis_Ababa");

/* ---------------- AUTO DATE & TIME ---------------- */
$attendance_date = gregorianToEthiopian(date("Y"), date("m"), date("d"));
$attendance_time = date("H:i:s");

/* ---------------- FETCH STUDENT ---------------- */
$student_id = $_SESSION['student_id'];
$studentQ = mysqli_query($conn, "SELECT * FROM students WHERE student_id='$student_id'");
$student = mysqli_fetch_assoc($studentQ);

/* ---------------- 24-HOUR RULE ---------------- */
$canSubmit = true;

$checkQ = mysqli_query($conn, "
    SELECT created_at FROM attendance
    WHERE student_id='$student_id'
    ORDER BY created_at DESC
    LIMIT 1
");

if (mysqli_num_rows($checkQ) > 0) {
    $last = mysqli_fetch_assoc($checkQ);
    if ((time() - strtotime($last['created_at'])) < 86400) {
        $canSubmit = false;
    }
}

/* ---------------- SUBMIT ATTENDANCE ---------------- */
if (isset($_POST['submit_attendance']) && $canSubmit) {

    mysqli_query($conn, "
        INSERT INTO attendance (
            student_id, first_name, middle_name, last_name,
            department, building, dorm_no, year_batch,
            profile_photo, attendance_date, attendance_time
        ) VALUES (
            '{$student['student_id']}',
            '{$student['first_name']}',
            '{$student['middle_name']}',
            '{$student['last_name']}',
            '{$student['department']}',
            '{$student['Building']}',
            '{$student['Dorm_no']}',
            '{$student['year_batch']}',
            '{$student['profile_photo']}',
            '$attendance_date',
            '$attendance_time'
        )
    ");

    header("Location: attendance.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance</title>

<!-- Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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

<?php include('sidebar.php'); ?>

<div class="lg:ml-64 min-h-screen">

<!-- Header -->
<header class="h-16 px-6 flex items-center justify-between
               bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600
               shadow-lg">

    <!-- Title -->
    <h1 class="text-xl md:text-2xl font-semibold text-white tracking-wide">
        Submit Attendance
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


<!-- Main Content -->
<main class="flex-1 p-4">
    <div class="max-w-xl mx-auto bg-white rounded-xl shadow-lg p-6" style="border:2px dashed blue;">

        <h2 class="text-2xl font-bold text-center mb-4">Daily Attendance</h2>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 text-green-700 p-3 rounded mb-4 text-center">
                Attendance submitted successfully.
            </div>
        <?php endif; ?>

        <div class="text-center space-y-2 mb-6">
            <p><strong>Name:</strong> <?= htmlspecialchars($student['first_name'].' '.$student['middle_name']) ?></p>
            <p><strong>Ethiopian Date:</strong> <?= $attendance_date ?></p>
            <p><strong>Time:</strong> <?= $attendance_time ?></p>
        </div>

        <?php if ($canSubmit): ?>
            <form method="POST" class="text-center">
                <button type="submit" name="submit_attendance"
                        class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-lg">
                    Submit Attendance
                </button>
            </form>
        <?php else: ?>
            <div class="bg-yellow-100 text-yellow-700 p-3 rounded text-center">
                Attendance already submitted.<br>
                You can submit again after 24 hours.
            </div>
        <?php endif; ?>

    </div>
</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle sidebar on mobile
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');
toggleBtn.addEventListener('click', () => sidebar.classList.toggle('show'));
</script>
</body>
</html>
