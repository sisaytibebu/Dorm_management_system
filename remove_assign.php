<?php
// Include config.php for database connection
include('config.php');

            // Fetch departments and year batches
            $dept_result = mysqli_query($conn, "SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != '' ORDER BY department");
            $departments = mysqli_fetch_all($dept_result, MYSQLI_ASSOC);

            $year_result = mysqli_query($conn, "SELECT DISTINCT year_batch FROM students WHERE year_batch IS NOT NULL AND year_batch != '' ORDER BY year_batch DESC");
            $year_batches = mysqli_fetch_all($year_result, MYSQLI_ASSOC);

            $message = '';
            $modal_content = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $year_batch = trim($_POST['year_batch'] ?? '');
                $department = trim($_POST['department'] ?? '');
                $confirm = isset($_POST['confirm']) && $_POST['confirm'] === 'yes';

                if ($year_batch && $department) {
                    $department_esc = mysqli_real_escape_string($conn, $department);
                    $year_batch_esc = mysqli_real_escape_string($conn, $year_batch);

                    if (!$confirm) {
                        // Count assigned students
                        $count_sql = "SELECT COUNT(*) AS cnt FROM students 
                                      WHERE department = '$department_esc' 
                                        AND year_batch = '$year_batch_esc' 
                                        AND (Building IS NOT NULL OR Dorm_no IS NOT NULL)";
                        $count_res = mysqli_query($conn, $count_sql);
                        $row = mysqli_fetch_assoc($count_res);
                        $assigned_count = $row['cnt'] ?? 0;

                        if ($assigned_count == 0) {
                            $message = "No assigned students found in department '$department' (Year: $year_batch).";
                        } else {
                            $modal_content = "
                            <h3 class='text-2xl font-bold text-red-600 mb-4'>Confirm Removal</h3>
                            <p class='mb-4'>Are you sure you want to <strong>remove dorm assignments</strong> for</p>
                            <p class='text-3xl font-bold text-red-600 mb-4'>$assigned_count student(s)</p>
                            <p class='mb-6'>in <strong>$department - $year_batch</strong>?</p>
                            <p class='text-gray-700 mb-8'>This action cannot be undone.</p>
                            <div class='flex justify-end gap-4'>
                                <button type='button' onclick='closeModal()' class='px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition'>Cancel</button>
                                <button type='button' onclick=\"document.getElementById('confirmForm').submit()\" class='px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition'>Yes, Remove Assignments</button>
                            </div>";
                        }
                    } else {
                        // Perform unassignment
                        $update_sql = "UPDATE students 
                                       SET Building = NULL, Dorm_no = NULL 
                                       WHERE department = '$department_esc' 
                                         AND year_batch = '$year_batch_esc' 
                                         AND (Building IS NOT NULL OR Dorm_no IS NOT NULL)";

                        if (mysqli_query($conn, $update_sql)) {
                            $affected = mysqli_affected_rows($conn);
                            $message = "Successfully removed dorm assignments from $affected student(s) in $department - $year_batch.";
                        } else {
                            $message = "Error removing assignments: " . mysqli_error($conn);
                        }
                    }
                } else {
                    $message = "Please select both Department and Year/Batch.";
                }
            }
            ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unassign Dorm Students</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        #sidebar { 
            overflow-y: auto; 
            transition: transform 0.3s; 
            background: linear-gradient(to bottom, #4f46e5, #312e81); 
            color: white;
        }
        #sidebar::-webkit-scrollbar { width: 6px; }
        #sidebar::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.3); border-radius: 3px; }
        .sidebar-item:hover { background: white !important; color: black !important; }
        .badge-notice { 
            background: red; 
            color: white; 
            font-size: 0.75rem; 
            font-weight: bold; 
            padding: 0.2rem 0.5rem; 
            border-radius: 9999px; 
            margin-left: auto;
        }
        @media(max-width:1023px){ 
            #sidebar { 
                transform: translateX(-100%); 
                position: fixed; 
                z-index: 50; 
                height: 100vh; 
                width: 16rem; 
                top: 0; 
                left: 0; 
            } 
            #sidebar.show { transform: translateX(0); } 
        }
        @media(min-width:1024px){ 
            #sidebar { transform: translateX(0); width: 16rem; } 
        }
    </style>
</head>
<body class="bg-gray-100">

<?php include('sidebar_admin.php'); ?>

<div class="lg:ml-64 min-h-screen">
    <!-- Fixed Header -->
    <header class="h-16 px-6 flex items-center justify-between bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600 shadow-lg fixed top-0 left-0 right-0 z-40 lg:left-64">
        <h1 class="text-xl md:text-2xl font-semibold text-white tracking-wide">
            Unassign Dorm Students
        </h1>

        <div class="flex items-center gap-4">
            <a href="logouts.php" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-white/20 backdrop-blur-md text-white hover:bg-white/30 transition shadow-md">
                <i class="fas fa-sign-out-alt"></i>
                <span class="hidden sm:inline">Logout</span>
            </a>

            <button id="sidebarToggle" class="lg:hidden p-2 rounded-lg bg-white/20 backdrop-blur-md text-white hover:bg-white/30 transition">
                <i class="fas fa-bars text-lg"></i>
            </button>
        </div>
    </header>

    <!-- Main Content -->
        <div class="container-fluid px-6 py-8 max-w-4xl mx-auto" style="margin-top:70px;"">
            <div class="bg-white shadow-lg rounded-lg p-6 mb-8 border-l-4 border-purple-600">
                <em class="text-lg text-gray-700">
                Select a department and year/batch to remove all dorm and building assignments from currently assigned students.
                </em>
            </div>
            <?php
            // Fetch departments and year batches
            $dept_result = mysqli_query($conn, "SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != '' ORDER BY department");
            $departments = mysqli_fetch_all($dept_result, MYSQLI_ASSOC);

            $year_result = mysqli_query($conn, "SELECT DISTINCT year_batch FROM students WHERE year_batch IS NOT NULL AND year_batch != '' ORDER BY year_batch DESC");
            $year_batches = mysqli_fetch_all($year_result, MYSQLI_ASSOC);

            $message = '';
            $modal_content = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $year_batch = trim($_POST['year_batch'] ?? '');
                $department = trim($_POST['department'] ?? '');
                $confirm = isset($_POST['confirm']) && $_POST['confirm'] === 'yes';

                if ($year_batch && $department) {
                    $department_esc = mysqli_real_escape_string($conn, $department);
                    $year_batch_esc = mysqli_real_escape_string($conn, $year_batch);

                    if (!$confirm) {
                        // Count assigned students
                        $count_sql = "SELECT COUNT(*) AS cnt FROM students 
                                      WHERE department = '$department_esc' 
                                        AND year_batch = '$year_batch_esc' 
                                        AND (Building IS NOT NULL OR Dorm_no IS NOT NULL)";
                        $count_res = mysqli_query($conn, $count_sql);
                        $row = mysqli_fetch_assoc($count_res);
                        $assigned_count = $row['cnt'] ?? 0;

                        if ($assigned_count == 0) {
                            $message = "No assigned students found in department '$department' (Year: $year_batch).";
                        } else {
                            $modal_content = "
                            <h3 class='text-2xl font-bold text-red-600 mb-4'>Confirm Removal</h3>
                            <p class='mb-4'>Are you sure you want to <strong>remove dorm assignments</strong> for</p>
                            <p class='text-3xl font-bold text-red-600 mb-4'>$assigned_count student(s)</p>
                            <p class='mb-6'>in <strong>$department - $year_batch</strong>?</p>
                            <p class='text-gray-700 mb-8'>This action cannot be undone.</p>
                            <div class='flex justify-end gap-4'>
                                <button type='button' onclick='closeModal()' class='px-6 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition'>Cancel</button>
                                <button type='button' onclick=\"document.getElementById('confirmForm').submit()\" class='px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition'>Yes, Remove Assignments</button>
                            </div>";
                        }
                    } else {
                        // Perform unassignment
                        $update_sql = "UPDATE students 
                                       SET Building = NULL, Dorm_no = NULL 
                                       WHERE department = '$department_esc' 
                                         AND year_batch = '$year_batch_esc' 
                                         AND (Building IS NOT NULL OR Dorm_no IS NOT NULL)";

                        if (mysqli_query($conn, $update_sql)) {
                            $affected = mysqli_affected_rows($conn);
                            $message = "Successfully removed dorm assignments from $affected student(s) in $department - $year_batch.";
                        } else {
                            $message = "Error removing assignments: " . mysqli_error($conn);
                        }
                    }
                } else {
                    $message = "Please select both Department and Year/Batch.";
                }
            }
            ?>

            <!-- Message -->
            <?php if (!empty($message)): ?>
                <div class="mb-8 p-6 rounded-xl shadow-md text-lg font-medium text-center 
                    <?= strpos($message, 'Successfully') !== false ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-xl p-10">
                <form method="POST" id="checkForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">

                        <div>
                            <label class="block text-lg font-bold text-gray-700 mb-3">Select Year/Batch</label>
                            <select name="year_batch" required class="w-full border border-gray-300 rounded-xl text-lg focus:ring-4 focus:ring-red-200 focus:border-red-500" style="width:300px; height:50px;">
                                <option value="">-- Select Year/Batch --</option>
                                <?php foreach ($year_batches as $yb): ?>
                                    <option value="<?= htmlspecialchars($yb['year_batch']) ?>" <?= (isset($_POST['year_batch']) && $_POST['year_batch'] === $yb['year_batch']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($yb['year_batch']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-lg font-bold text-gray-700 mb-3">Select Department</label>
                            <select name="department" required class="w-full border border-gray-300 rounded-xl text-lg focus:ring-4 focus:ring-red-200 focus:border-red-500"style="width:300px; height:50px;">
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept['department']) ?>" <?= (isset($_POST['department']) && $_POST['department'] === $dept['department']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['department']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>

                    <div class="mt-12 text-center">
                        <button type="submit" class=" bg-gradient-to-r from-red-600 to-red-700 text-white font-bold text-2xl rounded-xl hover:from-red-700 hover:to-red-800 transition shadow-2xl transform hover:scale-105" style="padding:10px;">
                            <i class="fas fa-trash-restore-alt mr-4"></i>
                            Check & Remove Assignments
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 <?= $modal_content ? '' : 'hidden' ?>" id="confirmModal">
        <div class="bg-white rounded-2xl shadow-2xl p-10 max-w-lg w-full mx-4">
            <?= $modal_content ?>
        </div>
    </div>

    <!-- Hidden Confirmation Form -->
    <?php if ($modal_content): ?>
        <form method="POST" id="confirmForm">
            <input type="hidden" name="department" value="<?= htmlspecialchars($_POST['department']) ?>">
            <input type="hidden" name="year_batch" value="<?= htmlspecialchars($_POST['year_batch']) ?>">
            <input type="hidden" name="confirm" value="yes">
        </form>
    <?php endif; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function closeModal() {
            document.getElementById('confirmModal').classList.add('hidden');
        }

        // Close modal on outside click
        document.getElementById('confirmModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // Mobile sidebar toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('show');
        });
    </script>
</body>
</html>