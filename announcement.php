<?php
session_start();
include("config.php");

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle form submission
if (isset($_POST['add_announcement'])) {
    $header = mysqli_real_escape_string($conn, $_POST['header']);
    $subheader = mysqli_real_escape_string($conn, $_POST['subheader']);
    $image_name = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array(strtolower($ext), $allowed)) {
            $image_name = uniqid("announcement_", true) . "." . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $image_name);
        }
    }

    mysqli_query($conn, "INSERT INTO announcement (header, subheader, image, created_at) VALUES ('$header', '$subheader', '$image_name', NOW())");
    header("Location: announcement.php");
}

// Fetch announcements (most recent first)
$announcements = mysqli_query($conn, "SELECT * FROM announcement ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Announcements</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
.card-img-top { object-fit: cover; height: 200px; }
.text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

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
    Manage Announcements
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

    <!-- Add Announcement Form -->
    <div class="card shadow-lg rounded-lg p-6 bg-white mb-6" style="border-top:3px solid green; margin-top:100px; margin:20px;">
        <h2 class="text-xl font-semibold mb-4" style="color:blue;">Add New Announcement</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="form-label font-semibold">Header</label>
                <input type="text" name="header" class="form-control" placeholder="Header text" required>
            </div>
            <div>
                <label class="form-label font-semibold">Subheader</label>
                <textarea name="subheader" class="form-control" rows="3" placeholder="Subheader text" required></textarea>
            </div>
            <div>
                <label class="form-label font-semibold">Image</label>
                <input type="file" name="image" accept="image/*" class="form-control">
            </div>
            <button type="submit" name="add_announcement" class="btn btn-success">Add Announcement</button>
            <button  class="btn btn-success" style="float:right !important; background-color:aqua; color:black;"><a href="view_announce.php"> View Uploaded<a></button>

        </form>
    </div>

   

<script>
function toggleText(id) {
    const el = document.getElementById(`subheader-${id}`);
    if (el.style.display === 'block') {
        el.style.display = '';
    } else {
        el.style.display = 'block';
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');
if(toggleBtn){
    toggleBtn.addEventListener('click', ()=> sidebar.classList.toggle('show'));
}
</script>

</body>
</html>
