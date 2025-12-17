<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("config.php");

/* ================= AUTH ================= */
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$admin_q = mysqli_query($conn, "SELECT * FROM admin WHERE id='$admin_id'");
$admin = mysqli_fetch_assoc($admin_q);

/* ================= DATA ================= */
$profile_photo = !empty($admin['profile_photo'])
    ? 'uploads/'.$admin['profile_photo']
    : 'uploads/admin.png';

$full_name = "HDMS Admin";
$email = $admin['email'];

/* ================= PENDING REPORTS ================= */
$report_count = mysqli_fetch_assoc(
    mysqli_query(
        $conn,
        "SELECT COUNT(*) AS total 
         FROM reports 
         WHERE admin_feedback IS NULL OR admin_feedback=''"
    )
)['total'] ?? 0;

/* ================= UPDATE PHOTO ================= */
if (isset($_POST['update_photo'])) {
    if (!empty($_FILES['profile_photo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];

        if (in_array($ext, $allowed)) {
            $newName = uniqid("admin_", true).".".$ext;
            move_uploaded_file($_FILES['profile_photo']['tmp_name'], "uploads/".$newName);
            mysqli_query($conn, "UPDATE admin SET profile_photo='$newName' WHERE id='$admin_id'");
            header("Refresh:0");
        }
    }
}

/* ================= CHANGE PASSWORD ================= */
if (isset($_POST['update_password'])) {
    if ($_POST['new_password'] === $_POST['confirm_password']) {
        if (password_verify($_POST['current_password'], $admin['password'])) {
            $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE admin SET password='$hash' WHERE id='$admin_id'");
        }
    }
}
?>

<!-- ================= SIDEBAR ================= -->
<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-indigo-700 to-indigo-900 text-white shadow-2xl
              transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out overflow-y-auto">

    <div class="flex flex-col h-full">

        <!-- PROFILE -->
        <div class="p-6 text-center border-b border-indigo-800">
            <div class="relative inline-block">
                <img src="<?= htmlspecialchars($profile_photo) ?>"
                     class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-white shadow-lg">
                <button data-bs-toggle="modal" data-bs-target="#changePhotoModal"
                        class="absolute bottom-0 right-2 bg-indigo-500 hover:bg-indigo-600 rounded-full p-2 shadow-md">
                    <i class="fas fa-camera text-white text-sm"></i>
                </button>
            </div>

            <h3 class="mt-4 text-xl font-semibold"><?= $full_name ?></h3>
            <p class="text-indigo-200 text-sm"><?= htmlspecialchars($email) ?></p>
        </div>

        <!-- MENU -->
        <nav class="flex-1 px-4 py-6">
            <ul class="space-y-2">

                <li>
                    <a href="admin_dashboard.php"
                       class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:text-black transition sidebar-item">
                        <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
                    </a>
                </li>

                <li>
                    <a href="see_students.php"
                       class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:text-black transition sidebar-item">
                        <i class="fas fa-user-graduate mr-3"></i> See Students
                    </a>
                </li>
                <!-- ================= DROPDOWN: Announcement ================= -->
<li class="relative">
    <!-- Main Dropdown Button -->
    <button id="announcementBtn"
            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-white hover:text-black transition sidebar-item">
        <span class="flex items-center">
            <i class="fas fa-bullhorn mr-3"></i> Announcement
        </span>
        <i class="fas fa-chevron-down text-sm transition-transform duration-200" id="announcementChevron"></i>
    </button>

    <!-- Dropdown Menu -->
    <ul class="mt-2 space-y-1 pl-10 hidden" id="announcementMenu">
        <li>
            <a href="announcement.php"
               class="flex items-center px-4 py-2 rounded-lg hover:bg-white hover:text-black transition text-sm">
                <i class="fas fa-plus-circle mr-3 text-xs"></i> Create New Announcement
            </a>
        </li>
        <li>
            <a href="view_announce.php"
               class="flex items-center px-4 py-2 rounded-lg hover:bg-white hover:text-black transition text-sm">
                <i class="fas fa-list-alt mr-3 text-xs"></i> View All Announcements
            </a>
        </li>
    </ul>
</li>

                <!-- ================= DROPDOWN: Manage Dorm ================= -->
                <li class="relative">
                    <!-- Main Dropdown Button -->
                    <button id="manageDormBtn"
                            class="w-full flex items-center justify-between px-4 py-3 rounded-lg hover:bg-white hover:text-black transition sidebar-item">
                        <span class="flex items-center">
                            <i class="fas fa-building mr-3"></i> Manage Dorm
                        </span>
                        <i class="fas fa-chevron-down text-sm transition-transform duration-200" id="dormChevron"></i>
                    </button>

                    <!-- Dropdown Menu -->
                    <ul class="mt-2 space-y-1 pl-10 hidden" id="manageDormMenu">
                        <li>
                            <a href="manage_dorm.php"
                               class="flex items-center px-4 py-2 rounded-lg hover:bg-white hover:text-black transition text-sm">
                                <i class="fas fa-plus-circle mr-3 text-xs"></i> Create & Assign Dorm
                            </a>
                        </li>
                        <li>
                            <a href="remove_assign.php"
                               class="flex items-center px-4 py-2 rounded-lg hover:bg-white hover:text-black transition text-sm">
                                <i class="fas fa-trash-restore mr-3 text-xs"></i> Unassign Students
                            </a>
                        </li>
                        <li>
                            <a href="view_nonassign.php"
                               class="flex items-center px-4 py-2 rounded-lg hover:bg-white hover:text-black transition text-sm">
                                <i class="fas fa-users-slash mr-3 text-xs"></i> View Unassigned
                            </a>
                        </li>
                    </ul>
                </li>

                <li>
                    <a href="view_report.php"
                       class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:text-black transition sidebar-item relative">
                        <i class="fas fa-file-alt mr-3"></i> View Report
                        <?php if ($report_count > 0): ?>
                            <span class="ml-auto bg-red-600 text-white text-xs font-bold px-2 py-1 rounded-full">
                                <?= $report_count ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>

                <li>
                    <a href="view_attendance.php"
                       class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:text-black transition sidebar-item">
                        <i class="fas fa-calendar-check mr-3"></i> View Attendance
                    </a>
                </li>

                <li>
                    <button data-bs-toggle="modal" data-bs-target="#changePasswordModal"
                            class="w-full flex items-center px-4 py-3 rounded-lg hover:bg-white hover:text-black transition text-left sidebar-item">
                        <i class="fas fa-key mr-3"></i> Change Password
                    </button>
                </li>

                

            </ul>
        </nav>
    </div>
</aside>

<!-- ================= MODALS ================= -->

<!-- CHANGE PHOTO MODAL -->
<div class="modal fade" id="changePhotoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-indigo-700 text-white">
                    <h5 class="modal-title">Change Profile Photo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="<?= htmlspecialchars($profile_photo) ?>"
                         class="w-40 h-40 rounded-full mx-auto object-cover mb-3">
                    <input type="file" name="profile_photo" class="form-control" accept="image/*" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button name="update_photo" class="btn bg-indigo-700 text-white">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- CHANGE PASSWORD MODAL -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-indigo-700 text-white">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body space-y-4">
                    <input type="password" name="current_password" class="form-control" placeholder="Current Password" required>
                    <input type="password" name="new_password" class="form-control" placeholder="New Password" required>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button name="update_password" class="btn bg-indigo-700 text-white">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= DROPDOWN TOGGLE SCRIPT ================= -->
<script>
    // Toggle Manage Dorm dropdown
    document.getElementById('manageDormBtn').addEventListener('click', function () {
        const menu = document.getElementById('manageDormMenu');
        const chevron = document.getElementById('dormChevron');

        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    });
 document.getElementById('announcementBtn').addEventListener('click', function () {
        const menu = document.getElementById('announcementMenu');
        const chevron = document.getElementById('announcementChevron');

        menu.classList.toggle('hidden');
        chevron.classList.toggle('rotate-180');
    });
</script>