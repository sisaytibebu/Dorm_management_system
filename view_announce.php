<?php
session_start();
include("config.php");

// Ensure admin is logged in
if(!isset($_SESSION['admin_id'])){
    header("Location: admin_login.php");
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
    header("Location: view_announce.php");
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

<?php include('sidebar_admin.php'); ?>

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

        <div class="flex justify-end gap-2 mt-3">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>"><i class="fas fa-edit"></i> Edit</button>
            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>"><i class="fas fa-trash"></i> Delete</button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-indigo-700 text-white">
                    <h5 class="modal-title">Edit Announcement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body space-y-3">
                    <input type="hidden" name="announcement_id" value="<?= $row['id'] ?>">
                    <input type="text" name="header" class="form-control" placeholder="Header" value="<?= htmlspecialchars($row['header']) ?>" required>
                    <textarea name="subheader" class="form-control" placeholder="Subheader" rows="3" required><?= htmlspecialchars($row['subheader']) ?></textarea>
                    <input type="file" name="image" class="form-control">
                    <?php if($row['image']): ?>
                        <img src="uploads/<?= htmlspecialchars($row['image']) ?>" class="w-full mt-2" style="max-height:150px; object-fit:cover;">
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_announcement" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-red-700 text-white">
                    <h5 class="modal-title">Delete Announcement</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="announcement_id" value="<?= $row['id'] ?>">
                    <p>Are you sure you want to delete <strong><?= htmlspecialchars($row['header']) ?></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_announcement" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
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
