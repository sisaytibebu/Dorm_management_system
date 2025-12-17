<?php
session_start();
include("config.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

date_default_timezone_set("Africa/Addis_Ababa");
$today = date('Y-m-d');

$filter = $_GET['filter'] ?? 'all';

$headerTitle = match($filter) {
    'today' => 'See Today',
    'absent_today' => 'Absent Today',
    'total_absent' => 'Total Absent',
    default => 'See All',
};

// Get total number of distinct attendance days
$totalDaysQuery = mysqli_query($conn, "SELECT COUNT(DISTINCT DATE(created_at)) AS total_days FROM attendance");
$totalDaysRow = mysqli_fetch_assoc($totalDaysQuery);
$total_attendance_days = $totalDaysRow['total_days'] ?? 0;

// Base subquery for total absent per student
$absentSubquery = "(
    SELECT COUNT(DISTINCT DATE(created_at)) 
    FROM attendance 
    WHERE student_id = s.student_id
)";

// Correct queries for each filter
if ($filter === 'today') {
    $query = "
        SELECT 
            s.student_id,
            s.first_name, 
            s.middle_name, 
            s.department, 
            s.building, 
            s.dorm_no, 
            s.year_batch,
            a.created_at,
            a.attendance_time,
            'Present' AS status,
            ($total_attendance_days - $absentSubquery) AS total_absent
        FROM students s
        JOIN attendance a ON s.student_id = a.student_id
        WHERE DATE(a.created_at) = '$today'
        GROUP BY s.student_id
        ORDER BY a.attendance_time DESC
    ";
} elseif ($filter === 'absent_today') {
    $query = "
        SELECT 
            s.student_id,
            s.first_name, 
            s.middle_name, 
            s.department, 
            s.building, 
            s.dorm_no, 
            s.year_batch,
            NULL AS created_at,
            NULL AS attendance_time,
            'Absent' AS status,
            ($total_attendance_days - $absentSubquery) AS total_absent
        FROM students s
        LEFT JOIN attendance a ON s.student_id = a.student_id AND DATE(a.created_at) = '$today'
        WHERE a.student_id IS NULL
        ORDER BY s.student_id ASC
    ";
} elseif ($filter === 'total_absent') {
    $query = "
        SELECT 
            s.student_id,
            s.first_name, 
            s.middle_name, 
            s.department, 
            s.building, 
            s.dorm_no, 
            s.year_batch,
            NULL AS created_at,
            NULL AS attendance_time,
            'Absent' AS status,
            ($total_attendance_days - $absentSubquery) AS total_absent
        FROM students s
        WHERE ($total_attendance_days - $absentSubquery) > 0
        ORDER BY total_absent DESC
    ";
} else { // 'all' - latest attendance per student
    $query = "
        SELECT 
            s.student_id,
            s.first_name, 
            s.middle_name, 
            s.department, 
            s.building, 
            s.dorm_no, 
            s.year_batch,
            a.created_at,
            a.attendance_time,
            'Present' AS status,
            ($total_attendance_days - $absentSubquery) AS total_absent
        FROM students s
        JOIN (
            SELECT student_id, MAX(created_at) AS latest_attendance
            FROM attendance
            GROUP BY student_id
        ) latest ON s.student_id = latest.student_id
        JOIN attendance a ON a.student_id = latest.student_id AND a.created_at = latest.latest_attendance
        ORDER BY a.created_at DESC
    ";
}

$records = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Attendance</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<style>
body { background:#f4f6f9; }
.table-wrapper { overflow-x:auto; }
.table th, .table td { border:1px solid #ccc; vertical-align:middle; text-align:center; }
.table th { background-color:#16a34a; color:white; }
.profile-img { width:40px; height:40px; object-fit:cover; border-radius:50%; }
.filter-btn { background:#1f2937; color:white; padding:0.5rem 1rem; border-radius:0.375rem; font-weight:500; transition:0.3s; }
.filter-btn.active { background:white; color:black; }
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
<header class="h-16 px-6 flex items-center justify-between bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600 shadow-lg">
    <h1 class="text-xl md:text-2xl font-semibold text-white tracking-wide">Dashboard</h1>
    <div class="flex items-center gap-4">
        <a href="logouts.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-white/20 backdrop-blur-md text-white hover:bg-white/30 transition duration-300 shadow-md">
            <i class="fas fa-sign-out-alt"></i>
            <span class="hidden sm:inline">Logout</span>
        </a>
        <button id="sidebarToggle" class="lg:hidden p-2 rounded-lg bg-white/20 backdrop-blur-md text-white hover:bg-white/30 transition duration-300">
            <i class="fas fa-bars text-lg"></i>
        </button>
    </div>
</header>

<header class="mb-4 flex flex-col md:flex-row items-center justify-between gap-4" style="margin-top:100px; margin:20px;">
    <h2 class="text-2xl font-bold text-green-700"><?= htmlspecialchars($headerTitle) ?></h2>
    <div class="flex gap-2 flex-wrap">
        <a href="view_attendance.php?filter=all" class="filter-btn <?= $filter==='all'?'active':'' ?>">See All</a>
        <a href="view_attendance.php?filter=today" class="filter-btn <?= $filter==='today'?'active':'' ?>">See Today</a>
        <a href="view_attendance.php?filter=absent_today" class="filter-btn <?= $filter==='absent_today'?'active':'' ?>">Absent Today</a>
        <a href="view_attendance.php?filter=total_absent" class="filter-btn <?= $filter==='total_absent'?'active':'' ?>">Total Absent</a>
    </div>
</header>

<?php if($filter === 'total_absent'): ?>
<button class="btn btn-success mb-4 mx-5" data-bs-toggle="modal" data-bs-target="#exportModal">
    <i class="fas fa-file-export"></i> Export Absent Students
</button>

<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="exportModalLabel">Export Absent Students</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label for="minAbsent" class="form-label">Minimum Absent Days:</label>
          <input type="number" id="minAbsent" class="form-control" value="1" min="0">
          <small class="text-muted">Export students with at least this many absent days.</small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" onclick="exportExcel()">Export to Excel</button>
        <button type="button" class="btn btn-secondary" onclick="exportPDF()">Export to PDF</button>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="bg-white shadow rounded-lg p-4 table-wrapper" style="margin:20px; border-top:3px solid violet;">
    <table id="attendanceTable" class="table w-full min-w-[1000px]">
        <thead>
            <tr>
                <th>No</th>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Department</th>
                <th>Building</th>
                <th>Dorm</th>
                <th>Year / Batch</th>
                <th>Attendance Date</th>
                <th>Attendance Time</th>
                <th>Status</th>
                <th>Total Absent</th>
            </tr>
        </thead>
        <tbody>
        <?php if(mysqli_num_rows($records) > 0): ?>
            <?php $i = 1; while($row = mysqli_fetch_assoc($records)): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['student_id'] ?? '-') ?></td>
                    <td><?= htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? ''))) ?></td>
                    <td><?= htmlspecialchars($row['department'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['building'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['dorm_no'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['year_batch'] ?? '-') ?></td>
                    <td><?= $row['created_at'] ? date('Y-m-d', strtotime($row['created_at'])) : '-' ?></td>
                    <td><?= $row['created_at'] ? date('H:i:s', strtotime($row['created_at'])) : '-' ?></td>
                    <td>
                        <span class="<?= $row['status'] === 'Absent' ? 'text-red-600' : 'text-green-600' ?> font-semibold">
                            <?= htmlspecialchars($row['status'] ?? 'Present') ?>
                        </span>
                    </td>
                    <td><?= (int)($row['total_absent'] ?? 0) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="11" class="text-center text-gray-500 py-4">No records found</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById("sidebarToggle")?.addEventListener('click', function(){
    document.getElementById("sidebar").classList.toggle("show");
});

// Fixed export functions - now find "Total Absent" by header text
function exportExcel() {
    const table = document.getElementById('attendanceTable');
    const minAbsent = parseInt(document.getElementById('minAbsent').value) || 0;

    // Find column index of "Total Absent"
    const headers = Array.from(table.querySelectorAll('thead th'));
    const totalAbsentIndex = headers.findIndex(th => th.innerText.trim() === 'Total Absent');
    const studentIdIndex = headers.findIndex(th => th.innerText.trim() === 'Student ID');

    if (totalAbsentIndex === -1) {
        alert("Could not find 'Total Absent' column.");
        return;
    }

    const filteredHeaders = headers.map(th => th.innerText.trim()).filter((_, i) => i !== studentIdIndex);

    let dataRows = [];
    let rowNum = 1;

    table.querySelectorAll('tbody tr').forEach(tr => {
        const cells = Array.from(tr.children);
        const totalAbsentText = cells[totalAbsentIndex]?.innerText.trim();
        const totalAbsent = parseInt(totalAbsentText) || 0;

        if (totalAbsent >= minAbsent) {
            let rowData = cells.map((td, i) => td.innerText.trim());
            rowData[0] = rowNum++; // Replace No with sequential
            rowData.splice(studentIdIndex, 1); // Remove Student ID
            dataRows.push(rowData);
        }
    });

    if (dataRows.length === 0) {
        alert("No students meet the criteria for export (minimum " + minAbsent + " absent days).");
        return;
    }

    const ws = XLSX.utils.aoa_to_sheet([filteredHeaders, ...dataRows]);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Absent Students");
    XLSX.writeFile(wb, "Absent_Students_Report.xlsx");
}

function exportPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('l', 'pt', 'a4');
    const table = document.getElementById('attendanceTable');
    const minAbsent = parseInt(document.getElementById('minAbsent').value) || 0;

    const headers = Array.from(table.querySelectorAll('thead th'));
    const totalAbsentIndex = headers.findIndex(th => th.innerText.trim() === 'Total Absent');
    const studentIdIndex = headers.findIndex(th => th.innerText.trim() === 'Student ID');

    if (totalAbsentIndex === -1) {
        alert("Could not find 'Total Absent' column.");
        return;
    }

    const filteredHeaders = headers.map(th => th.innerText.trim()).filter((_, i) => i !== studentIdIndex);

    let rows = [];
    let rowNum = 1;

    table.querySelectorAll('tbody tr').forEach(tr => {
        const cells = Array.from(tr.children);
        const totalAbsentText = cells[totalAbsentIndex]?.innerText.trim();
        const totalAbsent = parseInt(totalAbsentText) || 0;

        if (totalAbsent >= minAbsent) {
            let rowData = cells.map(td => td.innerText.trim());
            rowData[0] = rowNum++;
            rowData.splice(studentIdIndex, 1);
            rows.push(rowData);
        }
    });

    if (rows.length === 0) {
        alert("No students meet the criteria for export (minimum " + minAbsent + " absent days).");
        return;
    }

    doc.text("Absent Students Report", 40, 30);
    doc.autoTable({
        head: [filteredHeaders],
        body: rows,
        startY: 50,
        theme: 'grid',
        styles: { fontSize: 8 }
    });

    doc.save("Absent_Students_Report.pdf");
}
</script>

</body>
</html>