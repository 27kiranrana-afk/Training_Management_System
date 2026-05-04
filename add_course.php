<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/csrf.php");
require_role('admin', 'trainer');

$success = $error = "";
if(isset($_GET['success'])) $success = "Course added successfully!";

if(isset($_POST['add'])){
    csrf_verify();
    $title       = trim($_POST['title']);
    $duration    = trim($_POST['duration']);
    $fees        = floatval($_POST['fees'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $created_by  = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO courses (title, duration, fees, description, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsi", $title, $duration, $fees, $description, $created_by);

    if($stmt->execute()){
        $course_id = $conn->insert_id;

        // Handle multiple materials
        $mat_titles  = $_POST['mat_title']  ?? [];
        $mat_types   = $_POST['mat_type']   ?? [];
        $mat_urls    = $_POST['mat_url']    ?? [];
        $mat_files   = $_FILES['mat_file']  ?? [];

        foreach($mat_titles as $i => $mat_title){
            $mat_title = trim($mat_title);
            if(empty($mat_title)) continue;

            $type    = $mat_types[$i] ?? 'video_url';
            $content = '';

            if($type === 'video_url' || $type === 'notes_url'){
                $content = trim($mat_urls[$i] ?? '');
            } elseif(($type === 'video_file' || $type === 'notes') && !empty($mat_files['name'][$i])){
                $fname    = $mat_files['name'][$i];
                $tmp      = $mat_files['tmp_name'][$i];
                $ext      = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
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
                } else {
                    continue;
                }
            }

            if($content){
                $ms = $conn->prepare("INSERT INTO course_materials (course_id, title, type, content, sort_order, uploaded_by) VALUES (?,?,?,?,?,?)");
                $ms->bind_param("isssis", $course_id, $mat_title, $type, $content, $i, $created_by);
                $ms->execute();
            }
        }

        // Redirect after POST to prevent duplicate on refresh
        header("Location: add_course.php?success=1");
        exit();
    } else {
        $error = "Failed to add course.";
    }
}
?>
<?php include("includes/header.php"); ?>

<h3>Add Course</h3>

<?php if($error): ?>
  <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if($success): ?>
  <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
  <?php csrf_field(); ?>
  <div class="row g-4">

    <!-- Left: Course Info -->
    <div class="col-md-6">
      <div class="card p-4">
        <h5 class="mb-3">Course Details</h5>
        <div class="mb-3">
          <label>Course Title <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
          <label>Duration (e.g. 3 months, 40 hours)</label>
          <input type="text" name="duration" class="form-control" required>
        </div>
        <div class="mb-3">
          <label>Fees (₹)</label>
          <div class="form-check mb-2">
            <input type="checkbox" class="form-check-input" id="is_free" onchange="toggleFees(this)">
            <label class="form-check-label" for="is_free">🆓 This is a Free Course</label>
          </div>
          <div class="input-group" id="fees_input">
            <span class="input-group-text">₹</span>
            <input type="number" name="fees" id="fees" class="form-control" min="0" step="0.01" value="0" required>
          </div>
        </div>
        <div class="mb-3">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="4" placeholder="What will students learn?"></textarea>
        </div>
      </div>
    </div>

    <!-- Right: Course Materials -->
    <div class="col-md-6">
      <div class="card p-4">
        <h5 class="mb-3">Course Materials</h5>
        <p class="text-muted small">Add videos (YouTube URL or upload) and notes/PDFs.</p>

        <div id="materials-container">
          <!-- Material Row Template -->
          <div class="material-row border rounded p-3 mb-3">
            <div class="mb-2">
              <input type="text" name="mat_title[]" class="form-control" placeholder="Material title (e.g. Intro Video)">
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
              <input type="text" name="mat_url[]" class="form-control" placeholder="Paste YouTube or URL here">
            </div>
            <div class="file-input d-none">
              <input type="file" name="mat_file[]" class="form-control">
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-row">✕ Remove</button>
          </div>
        </div>

        <button type="button" id="add-material" class="btn btn-outline-primary btn-sm">+ Add Another Material</button>
      </div>
    </div>

  </div>

  <div class="mt-4">
    <button type="submit" name="add" class="btn btn-primary px-5">Add Course</button>
    <a href="dashboard.php" class="btn btn-secondary ms-2">Back</a>
  </div>
</form>

<script>
// Toggle URL/file input based on type selection
function toggleFees(checkbox) {
    const feesInput = document.getElementById('fees_input');
    const feesField = document.getElementById('fees');
    if (checkbox.checked) {
        feesInput.classList.add('d-none');
        feesField.value = 0;
    } else {
        feesInput.classList.remove('d-none');
    }
}

document.addEventListener('change', function(e){
    if(e.target.classList.contains('mat-type-select')){
        const row   = e.target.closest('.material-row');
        const url   = row.querySelector('.url-input');
        const file  = row.querySelector('.file-input');
        const type  = e.target.value;
        if(type === 'video_file' || type === 'notes'){
            url.classList.add('d-none');
            file.classList.remove('d-none');
        } else {
            url.classList.remove('d-none');
            file.classList.add('d-none');
        }
    }
});

// Add new material row
document.getElementById('add-material').addEventListener('click', function(){
    const container = document.getElementById('materials-container');
    const first     = container.querySelector('.material-row');
    const clone     = first.cloneNode(true);
    // Reset values
    clone.querySelectorAll('input').forEach(i => i.value = '');
    clone.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
    clone.querySelector('.url-input').classList.remove('d-none');
    clone.querySelector('.file-input').classList.add('d-none');
    container.appendChild(clone);
});

// Remove row
document.addEventListener('click', function(e){
    if(e.target.classList.contains('remove-row')){
        const rows = document.querySelectorAll('.material-row');
        if(rows.length > 1) e.target.closest('.material-row').remove();
    }
});
</script>

<?php include("includes/footer.php"); ?>
