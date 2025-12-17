<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include("config.php");

// Ensure user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch student data
$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$full_name = trim($student['first_name'] . ' ' . ($student['middle_name'] ?? '') . ' ' . $student['last_name']);
$dorm_info = $student['department'] . ' - ' . $student['year_batch'];
$profile_photo = $student['profile_photo'] ? 'uploads/' . htmlspecialchars($student['profile_photo']) : 'default-avatar.jpg';

// Messages and modal triggers
$photo_message = '';
$password_message = '';
$open_photo_modal = false;
$open_password_modal = false;

// ==================== HANDLE FORM SUBMISSIONS ====================

// Change Profile Photo
if (isset($_POST['update_photo'])) {
    $open_photo_modal = true; // Re-open photo modal to show message

    if (!empty($_FILES['profile_photo']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $file_name = $_FILES['profile_photo']['name'];
        $file_tmp = $_FILES['profile_photo']['tmp_name'];
        $file_size = $_FILES['profile_photo']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed)) {
            if ($file_size <= 5 * 1024 * 1024) {
                $new_file_name = $student_id . '_' . time() . '.' . $file_ext;
                $destination = 'uploads/' . $new_file_name;

                if (move_uploaded_file($file_tmp, $destination)) {
                    if ($student['profile_photo'] && file_exists('uploads/' . $student['profile_photo'])) {
                        unlink('uploads/' . $student['profile_photo']);
                    }

                    $update_stmt = $conn->prepare("UPDATE students SET profile_photo = ? WHERE student_id = ?");
                    $update_stmt->bind_param("ss", $new_file_name, $student_id);
                    $update_stmt->execute();
                    $update_stmt->close();

                    $photo_message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                        Profile photo updated successfully!
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                      </div>';
                    $profile_photo = 'uploads/' . htmlspecialchars($new_file_name);
                } else {
                    $photo_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        Failed to upload photo.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                      </div>';
                }
            } else {
                $photo_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    File size must be less than 5MB.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                  </div>';
            }
        } else {
            $photo_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                Only JPG, JPEG, and PNG files are allowed.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                              </div>';
        }
    } else {
        $photo_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            Please select a photo.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>';
    }
}

// Change Password
if (isset($_POST['update_password'])) {
    $open_password_modal = true; // Re-open password modal

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                All fields are required.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                              </div>';
    } elseif ($new_password !== $confirm_password) {
        $password_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                New password and confirm password do not match.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                              </div>';
    } elseif (strlen($new_password) < 6) {
        $password_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                New password must be at least 6 characters long.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                              </div>';
    } else {
        $pass_stmt = $conn->prepare("SELECT password FROM students WHERE student_id = ?");
        $pass_stmt->bind_param("s", $student_id);
        $pass_stmt->execute();
        $pass_stmt->bind_result($hashed_password);
        $pass_stmt->fetch();
        $pass_stmt->close();

        if (password_verify($current_password, $hashed_password)) {
            $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $update_stmt = $conn->prepare("UPDATE students SET password = ? WHERE student_id = ?");
            $update_stmt->bind_param("ss", $new_hashed, $student_id);
            if ($update_stmt->execute()) {
                $password_message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                        Password changed successfully!
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                      </div>';
            } else {
                $password_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        Failed to update password.
                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                      </div>';
            }
            $update_stmt->close();
        } else {
            $password_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    Current password is incorrect.
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                  </div>';
        }
    }
}
?>
<!-- Display Message -->
<?php if (!empty($message)): ?>
    <?php echo $message; ?>
<?php endif; ?>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-indigo-700 to-indigo-900 text-white shadow-2xl transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out overflow-y-auto">
    <div class="flex flex-col h-full">
        <div class="p-6 text-center border-b border-indigo-800">
            <div class="relative inline-block">
                <img src="<?php echo $profile_photo; ?>" class="w-32 h-32 rounded-full mx-auto object-cover border-4 border-white shadow-lg" alt="Profile Photo">
                <button data-bs-toggle="modal" data-bs-target="#changePhotoModal" class="absolute bottom-0 right-2 bg-indigo-500 hover:bg-indigo-600 rounded-full p-2 shadow-md">
                    <i class="fas fa-camera text-white text-sm"></i>
                </button>
            </div>
            <h3 class="mt-4 text-xl font-semibold"><?php echo htmlspecialchars($full_name); ?></h3>
            <p class="text-indigo-200 text-sm"><?php echo htmlspecialchars($dorm_info); ?></p>
        </div>

        <nav class="flex-1 px-4 py-6">
            <ul class="space-y-2">
                <li><a href="dashboard.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:text-black transition sidebar-item"><i class="fas fa-tachometer-alt mr-3"></i> Dashboard</a></li>
                <li><a href="attendance.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:text-black transition sidebar-item"><i class="fas fa-calendar-check mr-3"></i> Attendance</a></li>
                <li><a href="dorm.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:text-black transition sidebar-item"><i class="fas fa-building mr-3"></i> View Dorm</a></li>
                <li><a href="report.php" class="flex items-center px-4 py-3 rounded-lg hover:bg-white hover:text-black transition sidebar-item"><i class="fas fa-file-alt mr-3"></i> Report Issue</a></li>
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

<!-- Change Photo Modal -->
<div class="modal fade" id="changePhotoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-indigo-700 text-white">
                    <h5 class="modal-title">Change Profile Photo</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <img src="<?php echo $profile_photo; ?>" class="w-48 h-48 rounded-full mx-auto object-cover border-4 border-gray-300" alt="Current Photo">
                    </div>
                    <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/jpg" class="form-control" required>
                    <p class="text-sm text-gray-500 mt-2">Allowed: JPG, JPEG, PNG | Max: 5MB</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_photo" class="btn btn-primary bg-indigo-700 hover:bg-indigo-800">Upload Photo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-indigo-700 text-white">
                    <h5 class="modal-title">Change Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body space-y-4">
                    <div>
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_password" class="btn btn-primary bg-indigo-700 hover:bg-indigo-800">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>