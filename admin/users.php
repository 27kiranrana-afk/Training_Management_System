<?php
session_start();
include("../config/db.php");
include("../includes/auth.php");
include("../includes/csrf.php");
require_role('admin');

$success = $error = "";

// Toggle active/inactive via POST
if(isset($_POST['toggle_user'])){
    csrf_verify();
    $uid = intval($_POST['user_id']);
    if($uid == $_SESSION['user_id']){
        $error = "You cannot disable your own account.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET is_active = 1 - is_active WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        header("Location: users.php?done=1"); exit();
    }
}

// Delete user via POST
if(isset($_POST['delete_user'])){
    csrf_verify();
    $uid = intval($_POST['user_id']);
    if($uid == $_SESSION['user_id']){
        $error = "You cannot delete your own account.";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        header("Location: users.php?deleted=1"); exit();
    }
}

$filter = $_GET['role'] ?? 'all';
$search = trim($_GET['search'] ?? '');

$where  = "WHERE role != 'admin'";
$params = [];
$types  = '';

if($filter != 'all'){
    $where   .= " AND role = ?";
    $types   .= 's';
    $params[] = $filter;
}
if($search){
    $like     = '%' . $search . '%';
    $where   .= " AND (name LIKE ? OR email LIKE ?)";
    $types   .= 'ss';
    $params[] = $like;
    $params[] = $like;
}

$stmt = $conn->prepare("SELECT * FROM users $where ORDER BY role, name");
if(!empty($params)){
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();
?>
<?php include("../includes/header.php"); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>👥 Manage Users</h3>
  <a href="dashboard.php" class="btn btn-secondary">← Back</a>
</div>

<?php if(isset($_GET['done'])): ?>
  <div class="alert alert-success alert-dismissible fade show">User status updated.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if(isset($_GET['deleted'])): ?>
  <div class="alert alert-warning alert-dismissible fade show">User deleted.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if($error): ?>
  <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Filters -->
<form method="GET" class="row g-2 mb-3">
  <div class="col-md-3">
    <select name="role" class="form-select" onchange="this.form.submit()">
      <option value="all"     <?php echo $filter=='all'?'selected':''; ?>>All Roles</option>
      <option value="student" <?php echo $filter=='student'?'selected':''; ?>>Students</option>
      <option value="trainer" <?php echo $filter=='trainer'?'selected':''; ?>>Trainers</option>
    </select>
  </div>
  <div class="col-md-4 d-flex gap-2">
    <input type="text" name="search" class="form-control" placeholder="Search name or email..." value="<?php echo htmlspecialchars($search); ?>">
    <button class="btn btn-outline-secondary">Search</button>
    <?php if($search || $filter!='all'): ?><a href="users.php" class="btn btn-outline-danger">Clear</a><?php endif; ?>
  </div>
</form>

<div class="table-responsive">
<table class="table table-bordered table-hover">
  <thead class="table-dark">
    <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Phone</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
  </thead>
  <tbody>
  <?php if($users && $users->num_rows > 0): ?>
    <?php $sno = 1; while($u = $users->fetch_assoc()): ?>
    <tr class="<?php echo !$u['is_active'] ? 'table-secondary' : ''; ?>">
      <td><?php echo $sno++; ?></td>
      <td><?php echo htmlspecialchars($u['name']); ?></td>
      <td><?php echo htmlspecialchars($u['email']); ?></td>
      <td><span class="badge <?php echo $u['role']=='trainer'?'bg-success':'bg-primary'; ?>"><?php echo ucfirst($u['role']); ?></span></td>
      <td><?php echo htmlspecialchars($u['phone'] ?? '—'); ?></td>
      <td>
        <?php if($u['is_active']): ?>
          <span class="badge bg-success">Active</span>
        <?php else: ?>
          <span class="badge bg-secondary">Disabled</span>
        <?php endif; ?>
      </td>
      <td><?php echo !empty($u['created_at']) ? date('d M Y', strtotime($u['created_at'])) : '—'; ?></td>
      <td class="d-flex gap-1">
        <!-- Toggle form -->
        <form method="POST">
          <?php csrf_field(); ?>
          <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
          <button type="submit" name="toggle_user"
                  class="btn btn-sm <?php echo $u['is_active']?'btn-warning':'btn-success'; ?>"
                  onclick="return confirm('<?php echo $u['is_active']?'Disable':'Enable'; ?> this user?')">
            <?php echo $u['is_active'] ? 'Disable' : 'Enable'; ?>
          </button>
        </form>
        <!-- Delete form -->
        <form method="POST">
          <?php csrf_field(); ?>
          <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
          <button type="submit" name="delete_user"
                  class="btn btn-sm btn-danger"
                  onclick="return confirm('Permanently delete <?php echo htmlspecialchars($u['name']); ?>?')">
            Delete
          </button>
        </form>
      </td>
    </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="8" class="text-center">No users found.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>

<?php include("../includes/footer.php"); ?>
