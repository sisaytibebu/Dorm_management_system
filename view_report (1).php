<?php
session_start();
include("config.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

/* Handle admin reply */
if (isset($_POST['reply_report'])) {
    $id = intval($_POST['report_id']);
    $feedback = mysqli_real_escape_string($conn, $_POST['admin_feedback']);
    mysqli_query($conn, "UPDATE reports SET admin_feedback='$feedback' WHERE id='$id'");
    header("Location: view_report.php");
    exit();
}

/* Handle delete report */
if (isset($_POST['delete_report'])) {
    $id = intval($_POST['report_id']);
    mysqli_query($conn, "DELETE FROM reports WHERE id='$id'");
    header("Location: view_report.php");
    exit();
}

/* Fetch reports */
$reports = mysqli_query($conn, "
    SELECT 
        id,
        student_id,
        first_name,
        middle_name,
        building,
        dorm_no,
        report,
        admin_feedback,
        student_feedback,
        report_date
    FROM reports
    ORDER BY (admin_feedback IS NULL OR admin_feedback='') DESC, report_date DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Reports</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body { background: #f4f6f9; }
.table-wrapper { overflow-x: auto; }
.table th{
    background-color:blue;
    color:white;
}
.table th, .table td { border:1px solid #ccc; vertical-align: top; white-space: normal; }
.report-text {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;  
    overflow: hidden;
}
.see-more { color: #2563eb; cursor: pointer; font-size:0.875rem; }
.bg-unreplied { background-color: #fee2e2; }
.bg-replied { background-color: #dcfce7; }

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
        View Report
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

<div class="" style="margin:20px;">
    <div class="mb-6 flex flex-col md:flex-row justify-between gap-4 items-center">
        <h1 class="text-2xl font-bold text-green-700">Student Reports</h1>
        <input type="text" id="tableSearch" placeholder="Search by Student ID, Building, or Dorm No"
            class="w-full md:w-64 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-green-500 focus:outline-none" style="border:2px solid violet !important;">
    </div>

    <div class="bg-white shadow rounded-lg p-4 table-wrapper" style="border-top:3px solid green;">
      <table id="reportsTable" class="table w-full min-w-[1000px]">
            <thead class="bg-green-600 text-white">
                <tr>
                    <th>ID</th>
                    <th>Student</th>
                    <th>Building</th>
                    <th>Dorm</th>
                    <th>Report</th>
                    <th>Report Date</th>
                    <th>Admin Feedback</th>
                    <th>Student Feedback</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="tableBody">
            <?php if(mysqli_num_rows($reports) > 0): ?>
                <?php while($r = mysqli_fetch_assoc($reports)): 
                    $isReplied = !empty($r['admin_feedback']);
                    $rowClass = $isReplied ? 'bg-replied' : 'bg-unreplied';
                ?>
                <tr class="<?= $rowClass ?>">
                    <td><?= $r['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($r['first_name'].' '.$r['middle_name']) ?></strong><br>
                        <span class="text-sm text-gray-600">ID: <?= htmlspecialchars($r['student_id']) ?></span>
                    </td>
                    <td><?= htmlspecialchars($r['building']) ?></td>
                    <td><?= htmlspecialchars($r['dorm_no']) ?></td>
                    <td>
                        <div class="report-text" id="reportText<?= $r['id'] ?>"><?= nl2br(htmlspecialchars($r['report'])) ?></div>
                        <?php if(strlen($r['report']) > 100): ?>
                            <span class="see-more" onclick="toggleReport(<?= $r['id'] ?>, this)">See more</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('Y-m-d H:i:s', strtotime($r['report_date'])) ?></td>

                    <td>
                        <?php if($isReplied): ?>
                            <div class="whitespace-pre-line text-green-700 font-medium"><?= nl2br(htmlspecialchars($r['admin_feedback'])) ?></div>
                        <?php else: ?>
                            <span class="text-gray-400 italic">No reply yet</span>
                        <?php endif; ?>
                        <td><?= htmlspecialchars($r['student_feedback']) ?></td>
                    </td>
                    <td style="min-width:140px;">
                        <button class="btn btn-sm btn-primary w-full mb-1" data-bs-toggle="modal" data-bs-target="#replyModal<?= $r['id'] ?>">
                            <i class="fas fa-reply"></i> Reply
                        </button>
                        <button class="btn btn-sm btn-danger w-full" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $r['id'] ?>">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>

                <!-- Reply Modal -->
                <div class="modal fade" id="replyModal<?= $r['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header bg-indigo-700 text-white">
                                    <h5 class="modal-title">Reply to <?= htmlspecialchars($r['first_name'].' '.$r['middle_name']) ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                    <textarea name="admin_feedback" class="form-control" placeholder="Write feedback..." rows="5" required><?= htmlspecialchars($r['admin_feedback']) ?></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="reply_report" class="btn btn-primary">Send Feedback</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteModal<?= $r['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header bg-red-600 text-white">
                                    <h5 class="modal-title">Delete Report</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to delete the report from <strong><?= htmlspecialchars($r['first_name'].' '.$r['middle_name']) ?></strong>?</p>
                                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="delete_report" class="btn btn-danger">Delete</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center text-gray-500 py-4">No reports found</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Search filter
document.getElementById('tableSearch').addEventListener('input', function(){
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#tableBody tr');
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

// Toggle See More / See Less
function toggleReport(id, el){
    const reportDiv = document.getElementById('reportText'+id);
    if(reportDiv.style.display === '-webkit-box' || reportDiv.style.display === ''){
        reportDiv.style.display = 'block';
        el.innerText = 'See less';
    } else {
        reportDiv.style.display = '-webkit-box';
        el.innerText = 'See more';
    }
}
</script>
<script>
// Toggle sidebar
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');
toggleBtn.addEventListener('click', ()=> sidebar.classList.toggle('show'));
</script>
</body>
</html>