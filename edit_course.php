<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/csrf.php");
require_role('admin', 'trainer');

$id  = intval($_GET['id'] ?? 0);
$uid = $_SESSION['user_id'];
if($id <= 0){ header("Location: view_courses.php"); exit(); }

$success = $error = "";

// Fetch course
if($_SESSION['role'] === 'trainer'){
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id=? AND (created_by=? OR created_by IS NULL)");
    $stmt->bind_param("ii", $id, $uid);
} else {
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id=?");
    $stmt->bind_param("i", $id);
}
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
if(!$course){ header("Location: view_courses.php?error=unauthorized"); exit(); }

// Delete a material
if(isset($_POST['delete_material'])){
    csrf_verify();
    $mid  = intval($_POST['material_id']);
    $conn->prepare("DELETE FROM course_materials WHERE id=? AND course_id=?")->execute() ;
    $dm   = $conn->prepare("DELETE FROM course_materials WHERE id=? AND course_id=?");
    $dm->bind_param("ii", $mid, $id);
    $dm->execute();
    header("Location: edit_course.php?id=$id&deleted_mat=1"); exit();
}

// Update course details + add new materials
if(isset($_POST['update'])){
    csrf_verify();
    $title       = trim($_POST['title']);
    $duration    = trim($_POST['duration']);
    $fees        = floatval($_POST['fees'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    $stmt = $conn->prepare("UPDATE courses SET title=?, duration=?, fees=?, description=?, created_by=COALESCE(created_by,?) WHERE id=?");
    $stmt->bind_param("ssdsii", $title, $duration, $fees, $description, $uid, $id);

    if($stmt->execute()){
        // Add new materials if provided
        $mat_titles = $_POST['mat_title'] ?? [];
        $mat_types  = $_POST['mat_type']  ?? [];
        $mat_urls   = $_POST['mat_url']   ?? [];
        $mat_files  = $_FILES['mat_file'] ?? [];

        foreach($mat_titles as $i => $mat_title){
            $mat_title = trim($mat_title);
            if(empty($mat_title)) continue;

            $type    = $mat_types[$i] ?? 'video_url';
            $content = '';

            if($type === 'video_url' || $type === 'notes_url'){
                $content = trim($mat_urls[$i] ?? '');
            } elseif(!empty($mat_files['name'][$i])){
                $fname = $mat_files['name'][$i];
                $tmp   = $mat_files['tmp_name'][$i];
                $ext   = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                $allowed_video = ['mp4','webm','mkv','avi'];
                $allowed_notes = ['pdf','doc','docx','ppt','pptx','txt'];

                if($type === 'video_file' && in_array($ext, $allowed_video)){
                    $dest = 'uploads/videos/' . uniqid() . '_' . basename($fname);
                    move_uploaded_file($tmp, $dest);
                    $content = $dest;
                } elseif($type === 'notes' && in_array($ext, $allowed_notes)){
                    $dest = 'uploads/notes/' . uniqid() . '_' . basename($fname);
                    move_uploaded_file($tmp, $dest);
                    $content = $dest;
                }
            }

            if($content){
                $ms = $conn->prepare("INSERT INTO course_materials (course_id, title, type, content, sort_order, uploaded_by) VALUES (?,?,?,?,?,?)");
                $ms->bind_param("isssis", $id, $mat_title, $type, $content, $i, $uid);
                $ms->execute();
            }
        }

        header("Location: edit_course.php?id=$id&success=1"); exit();
    } else {
        $error = "Update failed.";
    }
}

// Refresh course data
$stmt2 = $conn->prepare("SELECT * FROM courses WHERE id=?");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$course = $stmt2->get_result()->fetch_assoc();

// Fetch existing materials
$mats = $conn->prepare("SELECT * FROM course_materials WHERE course_id=? ORDER BY sort_order ASC");
$mats->bind_param("i", $id);
$mats->execute();
$materials = $mats->get_result();
?>
<?php include("includes/header.php"); ?>

<h3>Edit Course</h3>

<?php if(isset($_GET['success'])): ?>
  <div class="alert alert-success alert-dismissible fade show">Course updated successfully!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if(isset($_GET['deleted_mat'])): ?>
  <div class="alert alert-warning alert-dismissible fade show">Material deleted.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if($error): ?>
  <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
  <?php csrf_field(); ?>
  <div class="row g-4">

    <!-- Left: Course Details -->
    <div class="col-md-6">
      <div class="card p-4">
        <h5 class="mb-3">Course Details</h5>
        <div class="mb-3">
          <label>Course Title</label>
          <input type="text" name="title" class="form-control"
                 value="<?php echo htmlspecialchars($course['title']); ?>" required>
        </div>
        <div class="mb-3">
          <label>Duration</label>
          <input type="text" name="duration" class="form-control"
                 value="<?php echo htmlspecialchars($course['duration']); ?>" required>
        </div>
        <div class="mb-3">
          <label>Fees (₹)</label>
          <div class="form-check mb-2">
            <input type="checkbox" class="form-check-input" id="is_free"
                   <?php echo ($course['fees'] == 0) ? 'checked' : ''; ?>
                   onchange="toggleFees(this)">
            <label class="form-check-label" for="is_free">🆓 This is a Free Course</label>
          </div>
          <div class="input-group" id="fees_input" <?php echo ($course['fees'] == 0) ? 'style="display:none"' : ''; ?>>
            <span class="input-group-text">₹</span>
            <input type="number" name="fees" id="fees" class="form-control" min="0" step="0.01"
                   value="<?php echo $course['fees'] ?? 0; ?>">
          </div>
        </div>
        <div class="mb-3">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
        </div>
      </div>
    </div>

    <!-- Right: Add New Materials -->
    <div class="col-md-6">
      <div class="card p-4">
        <h5 class="mb-3">Add New Materials</h5>
        <p class="text-muted small">Add more videos or notes to this course.</p>

        <div id="materials-container">
          <div class="material-row border rounded p-3 mb-3">
            <div class="mb-2">
              <input type="text" name="mat_title[]" class="form-control"
                     placeholder="Material title (e.g. Lecture 1)">
            </div>
            <div class="mb-2">
              <select name="mat_type[]" class="form-select mat-type-select">
                <option value="video_url">🎬 YouTube / Video URL</option>
                <option value="video_file">📁 Upload Video File</option>
                <option value="notes">📄 Upload Notes/PDF</option>
                <option value="notes_url">🔗 Notes URL / Google Drive</option>
              </select>
            </div>
            <div class="url-input">
              <input type="text" name="mat_url[]" class="form-control"
                     placeholder="Paste YouTube or URL here">
            </div>
            <div class="file-input d-none">
              <input type="file" name="mat_file[]" class="form-control">
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-row">✕ Remove</button>
          </div>
        </div>
        <button type="button" id="add-material" class="btn btn-outline-primary btn-sm">+ Add Another</button>
      </div>
    </div>
  </div>

  <div class="mt-3">
    <button type="submit" name="update" class="btn btn-warning px-5">Update Course</button>
    <a href="view_courses.php" class="btn btn-secondary ms-2">Cancel</a>
  </div>
</form>

<!-- Existing Materials -->
<?php if($materials->num_rows > 0): ?>
<div class="mt-4">
  <h5>Existing Materials</h5>
  <table class="table table-bordered">
    <thead class="table-dark">
      <tr><th>Title</th><th>Type</th><th>Content</th><th>Delete</th></tr>
    </thead>
    <tbody>
    <?php while($m = $materials->fetch_assoc()): ?>
    <tr>
      <td><?php echo htmlspecialchars($m['title']); ?></td>
      <td><?php
        $labels = ['video_url'=>'🎬 YouTube URL','video_file'=>'📁 Video File',
                   'notes'=>'📄 Notes File','notes_url'=>'🔗 Notes URL'];
        echo $labels[$m['type']] ?? $m['type'];
      ?></td>
      <td class="text-truncate" style="max-width:200px">
        <?php echo htmlspecialchars($m['content']); ?>
      </td>
      <td>
        <form method="POST">
          <?php csrf_field(); ?>
          <input type="hidden" name="material_id" value="<?php echo $m['id']; ?>">
          <button type="submit" name="delete_material"
                  class="btn btn-sm btn-danger"
                  onclick="return confirm('Delete this material?')">Delete</button>
        </form>
      </td>
    </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<script>
function toggleFees(checkbox) {
    const feesInput = document.getElementById('fees_input');
    const feesField = document.getElementById('fees');
    if (checkbox.checked) {
        feesInput.style.display = 'none';
        feesField.value = 0;
    } else {
        feesInput.style.display = 'flex';
    }
}

document.addEventListener('change', function(e){
    if(e.target.classList.contains('mat-type-select')){
        const row  = e.target.closest('.material-row');
        const url  = row.querySelector('.url-input');
        const file = row.querySelector('.file-input');
        const type = e.target.value;
        if(type === 'video_file' || type === 'notes'){
            url.classList.add('d-none');
            file.classList.remove('d-none');
        } else {
            url.classList.remove('d-none');
            file.classList.add('d-none');
        }
    }
});

document.getElementById('add-material').addEventListener('click', function(){
    const container = document.getElementById('materials-container');
    const clone     = container.querySelector('.material-row').cloneNode(true);
    clone.querySelectorAll('input').forEach(i => i.value = '');
    clone.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    clone.querySelector('.url-input').classList.remove('d-none');
    clone.querySelector('.file-input').classList.add('d-none');
    container.appendChild(clone);
});

document.addEventListener('click', function(e){
    if(e.target.classList.contains('remove-row')){
        const rows = document.querySelectorAll('.material-row');
        if(rows.length > 1) e.target.closest('.material-row').remove();
    }
});
</script>

<?php include("includes/footer.php"); ?>
