<?php
// Include config.php for database connection
include('config.php');
            // Fetch dropdown options
            $dept_result = mysqli_query($conn, "SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != '' ORDER BY department");
            $departments = mysqli_fetch_all($dept_result, MYSQLI_ASSOC);

            $year_result = mysqli_query($conn, "SELECT DISTINCT year_batch FROM students WHERE year_batch IS NOT NULL AND year_batch != '' ORDER BY year_batch DESC");
            $year_batches = mysqli_fetch_all($year_result, MYSQLI_ASSOC);

            $message = '';
            $selected_dept = $_POST['department'] ?? '';
            $selected_year = $_POST['year_batch'] ?? '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $building_name     = trim($_POST['building_name'] ?? '');
                $num_dorms         = (int)($_POST['num_dorms'] ?? 0);
                $capacity_per_dorm = (int)($_POST['capacity_per_dorm'] ?? 0);
                $start_dorm_no     = (int)($_POST['start_dorm_no'] ?? 0);
                $year_batch        = trim($_POST['year_batch'] ?? '');
                $department        = trim($_POST['department'] ?? '');

                if ($building_name && $num_dorms > 0 && $capacity_per_dorm > 0 && $start_dorm_no >= 1 && $year_batch && $department) {
                    $total_capacity = $num_dorms * $capacity_per_dorm;

                    // Escape inputs
                    $building_name = mysqli_real_escape_string($conn, $building_name);
                    $department    = mysqli_real_escape_string($conn, $department);
                    $year_batch    = mysqli_real_escape_string($conn, $year_batch);

                    mysqli_begin_transaction($conn);

                    try {
                        // Insert into buildings table
                        $insert_sql = "INSERT INTO buildings 
                                       (building_name, department, year_batch, num_dorms, capacity_per_dorm, start_dorm_no, total_capacity)
                                       VALUES ('$building_name', '$department', '$year_batch', $num_dorms, $capacity_per_dorm, $start_dorm_no, $total_capacity)";
                        mysqli_query($conn, $insert_sql) or throw new Exception(mysqli_error($conn));

                        // Get unassigned students
                        $students_sql = "SELECT id FROM students
                                         WHERE department = '$department'
                                           AND year_batch = '$year_batch'
                                           AND Building IS NULL AND Dorm_no IS NULL
                                         ORDER BY first_name ASC
                                         LIMIT $total_capacity";

                        $students_result = mysqli_query($conn, $students_sql);
                        $assigned_count = mysqli_num_rows($students_result);

                        if ($assigned_count == 0) {
                            mysqli_commit($conn);
                            $message = "Building '$building_name' created successfully, but no unassigned students found in $department - $year_batch.";
                        } else {
                            $student_ids = [];
                            while ($row = mysqli_fetch_assoc($students_result)) {
                                $student_ids[] = (int)$row['id'];
                            }

                            // Build dorm assignments
                            $assignments = [];
                            for ($i = 0; $i < $assigned_count; $i++) {
                                $dorm_index = floor($i / $capacity_per_dorm);
                                $dorm_no = $start_dorm_no + $dorm_index;
                                $assignments[] = "WHEN {$student_ids[$i]} THEN $dorm_no";
                            }

                            $case_statement = implode(" ", $assignments);
                            $ids_list = implode(",", $student_ids);

                            // Update Building, Dorm_no, AND capacity column
                            $update_sql = "UPDATE students
                                           SET Building = '$building_name',
                                               Dorm_no = CASE id $case_statement END,
                                               capacity = $capacity_per_dorm
                                           WHERE id IN ($ids_list)";

                            mysqli_query($conn, $update_sql) or throw new Exception("Assignment error: " . mysqli_error($conn));

                            mysqli_commit($conn);
                            $message = "Success! Building '$building_name' created and configured.<br>
                                        Assigned $assigned_count student(s) from $department - $year_batch.<br>
                                        <strong>Capacity per dorm ($capacity_per_dorm) saved to each student's record.</strong>";
                        }
                    } catch (Exception $e) {
                        mysqli_rollback($conn);
                        $message = "Error: " . $e->getMessage();
                    }
                } else {
                    $message = "Please fill all required fields correctly.";
                }
            }
            ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php 
        $header_title = 'Manage Dorm Building';
        if (!empty($_POST['department']) && !empty($_POST['year_batch'])) {
            $header_title = htmlspecialchars($_POST['department']) . ' - ' . htmlspecialchars($_POST['year_batch']);
        }
        echo $header_title . ' | Dorm Management';
        ?>
    </title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Bootstrap CSS -->
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
    <!-- Fixed Header -->
    <header class="h-16 px-6 flex items-center justify-between bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600 shadow-lg fixed top-0 left-0 right-0 z-40 lg:left-64">
        <h1 class="text-xl md:text-2xl font-semibold text-white tracking-wide">
            <?php 
            if (!empty($_POST['department']) && !empty($_POST['year_batch'])) {
                echo htmlspecialchars($_POST['department']) . ' - ' . htmlspecialchars($_POST['year_batch']);
            } else {
                echo 'Manage Dorm Building';
            }
            ?>
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
        <div class="container-fluid px-6 py-8 max-w-5xl mx-auto" style="margin:70px;">

            <div class="bg-white shadow-lg rounded-lg p-6 mb-8 border-l-4 border-purple-600">
                <em class="text-lg text-gray-700">
                    Create a new dorm building and automatically assign eligible unassigned students from the selected department and year/batch.
                </em>
            </div>
            <!-- Message Display -->
            <?php if (!empty($message)): ?>
                <div class="mb-8 p-6 rounded-xl shadow-md text-lg font-medium text-center 
                    <?= str_contains($message, 'Success') ? 'bg-green-100 text-green-800 border border-green-300' : 'bg-red-100 text-red-800 border border-red-300' ?>">
                    <?= nl2br($message) ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <div class="bg-white rounded-xl shadow-xl p-8" style="border-top: 5px solid #10b981;">
                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <div>
                            <label class="block text-lg font-bold text-gray-700 mb-3">Building Name</label>
                            <input type="text" name="building_name" value="<?= htmlspecialchars($_POST['building_name'] ?? '') ?>" required 
                                   class="w-full px-5 py-4 border border-gray-300 rounded-xl focus:ring-4 focus:ring-emerald-300 focus:border-emerald-500 text-lg" style="width:300px; height:50px;">
                        </div>

                        <div>
                            <label class="block text-lg font-bold text-gray-700 mb-3">Number of Dorms</label>
                            <input type="number" name="num_dorms" min="1" value="<?= $_POST['num_dorms'] ?? '10' ?>" required 
                                   class="w-full px-5 py-4 border border-gray-300 rounded-xl focus:ring-4 focus:ring-emerald-300 focus:border-emerald-500 text-lg" style="width:300px; height:50px;">
                        </div>

                        <div>
                            <label class="block text-lg font-bold text-gray-700 mb-3">Capacity per Dorm</label>
                            <input type="number" name="capacity_per_dorm" min="1" value="<?= $_POST['capacity_per_dorm'] ?? '4' ?>" required 
                                   class="w-full px-5 py-4 border border-gray-300 rounded-xl focus:ring-4 focus:ring-emerald-300 focus:border-emerald-500 text-lg" style="width:300px; height:50px;">
                        </div>

                        <div>
                            <label class="block text-lg font-bold text-gray-700 mb-3">Starting Dorm Number</label>
                            <input type="number" name="start_dorm_no" min="1" value="<?= $_POST['start_dorm_no'] ?? '101' ?>" required 
                                   class="w-full px-5 py-4 border border-gray-300 rounded-xl focus:ring-4 focus:ring-emerald-300 focus:border-emerald-500 text-lg" style="width:300px; height:50px;">
                        </div>

                        <div>
                            <label class="block text-lg font-bold text-gray-700 mb-3">Year/Batch</label>
                            <select name="year_batch" required class="w-full px-5 py-4 border border-gray-300 rounded-xl focus:ring-4 focus:ring-emerald-300 text-lg">
                                <option value="">-- Select Year/Batch --</option>
                                <?php foreach ($year_batches as $yb): ?>
                                    <option value="<?= htmlspecialchars($yb['year_batch']) ?>" <?= $selected_year === $yb['year_batch'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($yb['year_batch']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-lg font-bold text-gray-700 mb-3">Department</label>
                            <select name="department" required class="w-full px-5 py-4 border border-gray-300 rounded-xl focus:ring-4 focus:ring-emerald-300 text-lg" >
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept['department']) ?>" <?= $selected_dept === $dept['department'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['department']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>

                    <div class="mt-12 text-center">
                        <button type="submit" class=" bg-gradient-to-r from-emerald-600 to-green-600 text-white font-bold text-2xl rounded-xl hover:from-emerald-700 hover:to-green-700 transition shadow-2xl transform hover:scale-105" style="padding:10px;">
                            <i class="fas fa-building mr-4"></i>
                            Create Building & Assign Students
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle')?.addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('show');
        });
    </script>
</body>
</html>