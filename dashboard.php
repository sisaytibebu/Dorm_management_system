<?php
session_start();
include("config.php");

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Delete
if(isset($_POST['delete_announcement'])){
    $id = intval($_POST['announcement_id']);
    mysqli_query($conn, "DELETE FROM announcement WHERE id='$id'");
    header("Location: view_announce.php");
}

// Handle Edit
if(isset($_POST['edit_announcement'])){
    $id = intval($_POST['announcement_id']);
    $header = mysqli_real_escape_string($conn, $_POST['header']);
    $subheader = mysqli_real_escape_string($conn, $_POST['subheader']);

    // Handle image upload
    if(isset($_FILES['image']) && $_FILES['image']['error'] === 0){
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','webp'];
        if(in_array(strtolower($ext), $allowed)){
            $newName = uniqid("announce_", true).".".$ext;
            move_uploaded_file($_FILES['image']['tmp_name'], "uploads/".$newName);
            mysqli_query($conn, "UPDATE announcement SET header='$header', subheader='$subheader', image='$newName' WHERE id='$id'");
        }
    } else {
        mysqli_query($conn, "UPDATE announcement SET header='$header', subheader='$subheader' WHERE id='$id'");
    }
    header("Location: announce.php");
}

// Fetch announcements
$announceQ = mysqli_query($conn, "SELECT * FROM announcement ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Announcements</title>

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
.card-text {
    display: -webkit-box;
    -webkit-line-clamp: 2; /* show only 2 lines */
    -webkit-box-orient: vertical;  
    overflow: hidden;
}
.see-more {
    color: #3b82f6;
    cursor: pointer;
}
.card-img-top {
    max-height: 200px;
    object-fit: cover;
}
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

<!-- Header -->
<header class="h-16 px-6 flex items-center justify-between
               bg-gradient-to-r from-emerald-600 via-green-600 to-teal-600
               shadow-lg">

    <!-- Title -->
    <h1 class="text-xl md:text-2xl font-semibold text-white tracking-wide">
    View Announcement
    </h1>

    <!-- Right Actions -->
    <div class="flex items-center gap-4">

        <!-- Logout Button -->
        <a href="logout.php"
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


<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6" style=" margin-top:100px; margin:20px;">

<?php while($row = mysqli_fetch_assoc($announceQ)): ?>
<div class="card bg-white shadow-lg rounded-lg overflow-hidden" >
    <?php if($row['image']): ?>
        <img src="uploads/<?= htmlspecialchars($row['image']) ?>" class="card-img-top w-full" alt="Announcement Image">
    <?php endif; ?>
    <div class="p-4">
        <h5 class="font-bold text-lg"><?= htmlspecialchars($row['header']) ?></h5>
        <p class="card-text" id="subheader<?= $row['id'] ?>"><?= htmlspecialchars($row['subheader']) ?></p>
        <span class="see-more" onclick="toggleText(<?= $row['id'] ?>)">See More</span>
    </div>
</div>




<?php endwhile; ?>
</div>
</div>

<script>
function toggleText(id){
    const el = document.getElementById('subheader'+id);
    const btn = el.nextElementSibling;
    if(el.style.webkitLineClamp == 'unset'){
        el.style.webkitLineClamp = '2';
        btn.textContent = 'See More';
    } else {
        el.style.webkitLineClamp = 'unset';
        btn.textContent = 'See Less';
    }
}
</script>
<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');
if(toggleBtn){
    toggleBtn.addEventListener('click', ()=> sidebar.classList.toggle('show'));
}
</script>

</body>
</html>
