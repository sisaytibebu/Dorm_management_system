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
    <style>
        body {
            background: #f0f2f5;
        }
      
        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
            }
        }

        /* Table overflow fix - horizontal scroll on small screens */
        .table-container {
            overflow-x: auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 20px;
        }

        table {
            width: 100%;
            min-width: 900px; /* Forces horizontal scroll if screen is smaller */
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 14px;
            text-align: left;
            border: 1px solid #999;
            white-space: nowrap;
        }

        th {
            background: #007bff;
            color: white;
            font-weight: bold;
        }

        tr:hover {
            background: #f1f1f1;
        }

        .count {
            text-align: center;
            font-weight: bold;
            color: #28a745;
            font-size: 22px;
            margin: 20px 0;
        }

        .no-data {
            text-align: center;
            padding: 60px;
            color: #666;
            font-size: 18px;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    <!-- Sidebar is already included and rendered from sidebar_admin.php -->

    <div class="main-content">
        <div class="container-fluid" style="margin-top:50px;">
            <h1 class=" text-center" style="font-size:35px;">View Unassigned Students</h1>
            <h2 class="text-center text-muted mb-4" style="font-size:28px;">Students who have not been assigned to any building or dorm yet</h2>

            <!-- Filters -->
            <div class="filters">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Department</label>
                        <select name="department" class="form-select">
                            <option value="">All Departments</option>
                            <?php
                            $dept_result = mysqli_query($conn, "SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != '' ORDER BY department");
                            while ($row = mysqli_fetch_assoc($dept_result)) {
                                $selected = ($_GET['department'] ?? '') === $row['department'] ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($row['department']) . "\" $selected>" . htmlspecialchars($row['department']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">Year/Batch</label>
                        <select name="year_batch" class="form-select">
                            <option value="">All Year/Batches</option>
                            <?php
                            $year_result = mysqli_query($conn, "SELECT DISTINCT year_batch FROM students WHERE year_batch IS NOT NULL AND year_batch != '' ORDER BY year_batch DESC");
                            while ($row = mysqli_fetch_assoc($year_result)) {
                                $selected = ($_GET['year_batch'] ?? '') === $row['year_batch'] ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($row['year_batch']) . "\" $selected>" . htmlspecialchars($row['year_batch']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Apply Filter</button>
                    </div>
                </form>

                <?php if (!empty($_GET)): ?>
                <div class="mt-3 text-end">
                    <a href="view_nonassign.php" class="btn btn-secondary">Clear Filter</a>
                </div>
                <?php endif; ?>
            </div>

            <?php
            // Build query
            $filter_dept = trim($_GET['department'] ?? '');
            $filter_year = trim($_GET['year_batch'] ?? '');

            $sql = "SELECT student_id, first_name, middle_name, last_name, department, year_batch 
                    FROM students 
                    WHERE Building IS NULL AND Dorm_no IS NULL";

            if ($filter_dept) $sql .= " AND department = '" . mysqli_real_escape_string($conn, $filter_dept) . "'";
            if ($filter_year) $sql .= " AND year_batch = '" . mysqli_real_escape_string($conn, $filter_year) . "'";

            $sql .= " ORDER BY department, year_batch, first_name ASC";

            $result = mysqli_query($conn, $sql);
            $total_unassigned = mysqli_num_rows($result);
            ?>

            <?php if ($total_unassigned > 0): ?>
                <div class="table-container">
                <div class="count">
                Total Unassigned Students: <?= $total_unassigned ?>
            </div>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student ID</th>
                                <th>First Name</th>
                                <th>Middle Name</th>
                                <th>Last Name</th>
                                <th>Department</th>
                                <th>Year/Batch</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($student = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($student['student_id'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($student['first_name']) ?></td>
                                <td><?= htmlspecialchars($student['middle_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($student['last_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($student['department']) ?></td>
                                <td><?= htmlspecialchars($student['year_batch']) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data bg-white rounded shadow">
                    <?php if ($filter_dept || $filter_year): ?>
                        No unassigned students found matching the selected filters.
                    <?php else: ?>
                        Excellent! All students have been assigned to dorms.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>