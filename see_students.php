<?php 
session_start();
include("config.php");

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch students - now including profile_photo
$studentsQ = mysqli_query($conn, "SELECT id, first_name, middle_name, last_name, student_id, department, Building, Dorm_no, year_batch, created_at, profile_photo FROM students ORDER BY created_at DESC");

// Handle Edit
if(isset($_POST['edit_student'])){
    $id = intval($_POST['student_id_modal']);
    $first = mysqli_real_escape_string($conn, $_POST['first_name']);
    $middle = mysqli_real_escape_string($conn, $_POST['middle_name']);
    $last = mysqli_real_escape_string($conn, $_POST['last_name']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $building = mysqli_real_escape_string($conn, $_POST['building']);
    $dorm_no = mysqli_real_escape_string($conn, $_POST['dorm_no']);
    $year_batch = mysqli_real_escape_string($conn, $_POST['year_batch']);

    mysqli_query($conn,"UPDATE students SET first_name='$first', middle_name='$middle', last_name='$last', department='$department', Building='$building', Dorm_no='$dorm_no', year_batch='$year_batch' WHERE id='$id'");
    header("Location: see_students.php");
    exit();
}

// Handle Delete
if(isset($_POST['delete_student'])){
    $id = intval($_POST['student_id_delete']);
    
    // Optional: delete photo file when deleting student
    $photo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT profile_photo FROM students WHERE id='$id'"));
    if ($photo && $photo['profile_photo'] && file_exists("uploads/" . $photo['profile_photo'])) {
        unlink("uploads/" . $photo['profile_photo']);
    }
    
    mysqli_query($conn,"DELETE FROM students WHERE id='$id'");
    header("Location: see_students.php");
    exit();
}

// Handle Reset Password
if(isset($_POST['reset_password'])){
    $id = intval($_POST['student_id_reset']);
    $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    mysqli_query($conn,"UPDATE students SET password='$new_pass' WHERE id='$id'");
    header("Location: see_students.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>See Students</title>

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- FontAwesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<style>
body { background: #f4f6f9; }
.table-bordered { border: 1px solid #ddd; }
.table-bordered th{
    background:blue !important;
    color:white !important;
}
.table-bordered th, .table-bordered td { border: 1px solid #ddd; white-space: nowrap;}
.dataTables_wrapper .dt-buttons .btn { margin-right: 0.5rem; }

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

/* Profile image style */
.profile-img {
    width: 90px;
    height: 90px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
</style>
</head>
<body class="bg-gray-100">

<?php include('sidebar_admin.php'); ?>

<div class="lg:ml-64 min-h-screen">

<!-- Header -->
<header class="h-16 px-6 flex items-center justify-between
               bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600
               shadow-lg">

    <h1 class="text-xl md:text-2xl font-semibold text-white tracking-wide">
        Student Data
    </h1>

    <div class="flex items-center gap-4">
        <a href="logouts.php"
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

    <!-- Table Card -->
    <div class="card shadow-lg rounded-lg p-4 bg-white" style="border-top:3px solid green; margin-top:70px !important; margin:20px;">
        <div class="table-responsive overflow-x-auto">
            <table id="studentsTable" class="table table-striped table-bordered w-full align-middle">
                <thead class="bg-green-600 text-white" style="background-color:green !important;">
                    <tr>
                        <th>ID</th>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Profile</th> <!-- New column after name -->
                        <th>Department</th>
                        <th>Building</th>
                        <th>Dorm No</th>
                        <th>Year Batch</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($student = mysqli_fetch_assoc($studentsQ)): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['id']) ?></td>
                        <td><?= htmlspecialchars($student['student_id']) ?></td>
                        <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']) ?></td>
                        
                        <!-- Profile Photo Column -->
                        <td class="text-center">
                            <?php if (!empty($student['profile_photo'])): ?>
                                <img src="uploads/<?= htmlspecialchars($student['profile_photo']) ?>" 
                                     alt="Profile" 
                                     class="profile-img">
                            <?php else: ?>
                                <div class="bg-gray-200 border-2 border-dashed rounded-full w-20 h-20 mx-auto flex items-center justify-center">
                                    <span class="text-gray-500 text-xs">No Photo</span>
                                </div>
                            <?php endif; ?>
                        </td>
                        
                        <td><?= htmlspecialchars($student['department']) ?></td>
                        <td><?= htmlspecialchars($student['Building']) ?></td>
                        <td><?= htmlspecialchars($student['Dorm_no']) ?></td>
                        <td><?= htmlspecialchars($student['year_batch']) ?></td>
                        <td><?= htmlspecialchars($student['created_at']) ?></td>
                        <td>
                            <div class="flex gap-1 justify-center">
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $student['id'] ?>"><i class="fas fa-edit"></i></button>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $student['id'] ?>"><i class="fas fa-trash"></i></button>
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#resetModal<?= $student['id'] ?>"><i class="fas fa-key"></i></button>
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Modal (unchanged) -->
                    <div class="modal fade" id="editModal<?= $student['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header bg-indigo-700 text-white">
                                        <h5 class="modal-title">Edit Student</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body space-y-4">
                                        <input type="hidden" name="student_id_modal" value="<?= $student['id'] ?>">
                                        <input type="text" name="first_name" class="form-control" placeholder="First Name" value="<?= htmlspecialchars($student['first_name']) ?>" required>
                                        <input type="text" name="middle_name" class="form-control" placeholder="Middle Name" value="<?= htmlspecialchars($student['middle_name']) ?>">
                                        <input type="text" name="last_name" class="form-control" placeholder="Last Name" value="<?= htmlspecialchars($student['last_name']) ?>" required>
                                        <input type="text" name="department" class="form-control" placeholder="Department" value="<?= htmlspecialchars($student['department']) ?>" required>
                                        <input type="text" name="building" class="form-control" placeholder="Building" value="<?= htmlspecialchars($student['Building']) ?>" required>
                                        <input type="text" name="dorm_no" class="form-control" placeholder="Dorm No" value="<?= htmlspecialchars($student['Dorm_no']) ?>" required>
                                        <input type="text" name="year_batch" class="form-control" placeholder="Year Batch" value="<?= htmlspecialchars($student['year_batch']) ?>" required>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="edit_student" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Delete Modal -->
                    <div class="modal fade" id="deleteModal<?= $student['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header bg-red-700 text-white">
                                        <h5 class="modal-title">Delete Student</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="student_id_delete" value="<?= $student['id'] ?>">
                                        <p>Are you sure you want to delete <strong><?= htmlspecialchars($student['first_name'].' '.$student['middle_name'].' '.$student['last_name']) ?></strong>?</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="delete_student" class="btn btn-danger">Delete</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Reset Password Modal -->
                    <div class="modal fade" id="resetModal<?= $student['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form method="POST">
                                    <div class="modal-header bg-yellow-600 text-white">
                                        <h5 class="modal-title">Reset Password</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="student_id_reset" value="<?= $student['id'] ?>">
                                        <input type="password" name="new_password" class="form-control" placeholder="Enter New Password" required>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="reset_password" class="btn btn-warning text-black">Reset Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
$(document).ready(function() {
    var table = $('#studentsTable').DataTable({
        dom: 'Bfrtip',
        buttons: [
            { 
                extend: 'excelHtml5', 
                className: 'btn btn-success mb-2',
                title: 'Students',
                exportOptions: { columns: [0,1,2,4,5,6,7,8] } // exclude Profile image and Actions
            },
            { 
                extend: 'csvHtml5', 
                className: 'btn btn-info mb-2',
                title: 'Students',
                exportOptions: { columns: [0,1,2,4,5,6,7,8] }
            },
            { 
                extend: 'pdfHtml5', 
                className: 'btn btn-danger mb-2',
                title: 'Students',
                exportOptions: { columns: [0,1,2,4,5,6,7,8] },
                customize: function (doc) {
                    doc.styles.tableHeader.fillColor = '#4ade80';
                    doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                    doc.content[1].table.body.forEach(function(row){
                        row.forEach(function(cell){
                            if (cell.border === undefined) cell.border = [true,true,true,true];
                        });
                    });
                }
            }
        ],
        scrollX: true,
        ordering: false
    });
});

const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');
if(toggleBtn){
    toggleBtn.addEventListener('click', ()=> sidebar.classList.toggle('show'));
}
</script>

</body>
</html>