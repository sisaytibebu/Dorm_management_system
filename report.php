<?php 
session_start();
include("config.php");

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

/* ================= FETCH STUDENT ================= */
$studentQ = mysqli_query($conn, "SELECT * FROM students WHERE student_id='$student_id'");
$student = mysqli_fetch_assoc($studentQ);

/* ---------------- ETHIOPIAN DATE CONVERSION ---------------- */
function gregorianToEthiopian($gy, $gm, $gd) {
    $ethiopianNewYear = 11;
    if (($gy % 4) === 0) $ethiopianNewYear = 12;

    $gMonthDays = [31,28,31,30,31,30,31,31,30,31,30,31];
    if (($gy % 4) === 0 && $gm > 2) $gd += 1;

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

    return [$ethiopianYear, $ethiopianMonth, $ethiopianDay];
}

/* ---------------- TIMEZONE ---------------- */
date_default_timezone_set("Africa/Addis_Ababa");

/* ================= SUBMIT REPORT ================= */
if (isset($_POST['submit_report'])) {
    $report = mysqli_real_escape_string($conn, $_POST['report']);

    $gy = date("Y");
    $gm = date("m");
    $gd = date("d");
    $h  = date("H");
    $i  = date("i");
    $s  = date("s");

    list($ey, $em, $ed) = gregorianToEthiopian($gy, $gm, $gd);
    $ethiopianDateTime = sprintf("%04d-%02d-%02d %02d:%02d:%02d", $ey, $em, $ed, $h, $i, $s);

    mysqli_query($conn, "
        INSERT INTO reports 
        (student_id, first_name, middle_name, last_name, building, dorm_no, report, report_date)
        VALUES
        (
            '{$student['student_id']}',
            '{$student['first_name']}',
            '{$student['middle_name']}',
            '{$student['last_name']}',
            '{$student['Building']}',
            '{$student['Dorm_no']}',
            '$report',
            '$ethiopianDateTime'
        )
    ");

    header("Refresh:0");
}

/* ================= STUDENT FEEDBACK ================= */
if (isset($_POST['student_feedback_submit'])) {
    $feedback = mysqli_real_escape_string($conn, $_POST['student_feedback']);
    $rid = intval($_POST['report_id']);

    mysqli_query($conn, "
        UPDATE reports 
        SET student_feedback='$feedback'
        WHERE id=$rid AND student_id='$student_id'
    ");

    header("Refresh:0");
}

/* ================= FETCH REPORTS ================= */
$reports = mysqli_query($conn, "
    SELECT * FROM reports 
    WHERE student_id='$student_id'
    ORDER BY STR_TO_DATE(report_date, '%Y-%m-%d %H:%i:%s') DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report Dorm Issue</title>

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

<header class="h-16 px-6 flex items-center justify-between
               bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600
               shadow-lg">
    <h1 class="text-xl md:text-2xl font-semibold text-white tracking-wide">Report Problem</h1>
    <div class="flex items-center gap-4">
        <a href="logout.php"
           class="flex items-center gap-2 px-4 py-2 rounded-lg
                  bg-white/20 backdrop-blur-md text-white
                  hover:bg-white/30 transition duration-300 shadow-md">
            <i class="fas fa-sign-out-alt"></i>
            <span class="hidden sm:inline">Logout</span>
        </a>
        <button id="sidebarToggle"
                class="lg:hidden p-2 rounded-lg
                       bg-white/20 backdrop-blur-md text-white
                       hover:bg-white/30 transition duration-300">
            <i class="fas fa-bars text-lg"></i>
        </button>
    </div>
</header>

<main class="p-6 space-y-6">

<!-- ================= NEW REPORT ================= -->
<div class="bg-white shadow-lg rounded-lg p-6" style="border-top:3px solid green;">
    <h2 class="text-xl font-semibold mb-3">Submit New Report</h2>
    <form method="POST">
        <textarea name="report" rows="4" class="form-control mb-3" placeholder="Describe your dorm problem..." required></textarea>
        <button type="submit" name="submit_report" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i> Submit Report
        </button>
    </form>
</div>

<!-- ================= REPORT HISTORY ================= -->
<div class="bg-white shadow-lg rounded-lg p-6" style="border-top:3px solid violet;">
    <h2 class="text-xl font-semibold mb-4">Your Reports</h2>

    <?php if (mysqli_num_rows($reports) == 0): ?>
        <p class="text-muted">No reports submitted yet.</p>
    <?php endif; ?>

    <?php while ($row = mysqli_fetch_assoc($reports)): ?>
        <div class="border rounded-lg p-4 mb-4">
            <p><strong>Your Report:</strong> <?php echo nl2br(htmlspecialchars($row['report'])); ?></p>

            <hr>

            <?php if ($row['admin_feedback']): ?>
                <div class="bg-green-50 p-3 rounded mb-3">
                    <strong>Admin</strong>
                    <p><?php echo nl2br(htmlspecialchars($row['admin_feedback'])); ?></p>
                </div>

                <?php if (!$row['student_feedback']): ?>
                    <form method="POST">
                        <input type="hidden" name="report_id" value="<?php echo $row['id']; ?>">
                        <textarea name="student_feedback" rows="2" class="form-control mb-2" placeholder="Write your feedback to admin..." required></textarea>
                        <button type="submit" name="student_feedback_submit" class="btn btn-success btn-sm">
                            Submit Feedback
                        </button>
                    </form>
                <?php else: ?>
                    <div class="bg-blue-50 p-3 rounded">
                        <strong>You</strong>
                        <p><?php echo nl2br(htmlspecialchars($row['student_feedback'])); ?></p>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <span class="badge bg-warning text-dark">Waiting for Admin Response</span>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
</div>

</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById("sidebarToggle").onclick = function(){
    document.getElementById("sidebar").classList.toggle("show");
};
</script>

</body>
</html>
