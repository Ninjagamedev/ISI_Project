<?php
require_once '../includes/config.php';
require_once '../includes/Auth.php';

$auth = Auth::getInstance();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'status':
            $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$_POST['status'], $_POST['user_id']]);
            break;
        case 'delete':
            $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            $stmt->execute([$_POST['user_id']]);
            break;
    }
    header('Location: users.php');
    exit;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (email LIKE ? OR full_name LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($role) {
    $query .= " AND role = ?";
    $params[] = $role;
}

if ($status) {
    $query .= " AND status = ?";
    $params[] = $status;
}

// Get total count
$count_stmt = $db->prepare(str_replace('*', 'COUNT(*)', $query));
$count_stmt->execute($params);
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $per_page);

// Get users
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="admin-layout">
        <?php include 'templates/sidebar.php'; ?>

        <main class="admin-main">
            <div class="admin-content">
                <div class="content-header">
                    <h1>User Management</h1>
                    <a href="add-user.php" class="btn btn-primary">Add New User</a>
                </div>

                <!-- Filters -->
                <div class="filters">
                    <form method="GET" class="filter-form">
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Search users..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>

                        <div class="form-group">
                            <select name="role">
                                <option value="">All Roles</option>
                                <option value="customer" <?php echo $role === 'customer' ? 'selected' : ''; ?>>
                                    Customer
                                </option>
                                <option value="vendor" <?php echo $role === 'vendor' ? 'selected' : ''; ?>>
                                    Vendor
                                </option>
                                <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>
                                    Admin
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>
                                    Active
                                </option>
                                <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>
                                    Inactive
                                </option>
                                <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>
                                    Suspended
                                </option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-secondary">Apply Filters</button>
                        <a href="users.php" class="btn btn-link">Clear Filters</a>
                    </form>
                </div>

                <!-- Users Table -->
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <div class="user-info">
                                            <?php if ($user['avatar_path']): ?>
                                                <img src="<?php echo htmlspecialchars($user['avatar_path']); ?>" 
                                                     alt="Avatar" class="user-avatar">
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $user['status']; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit-user.php?id=<?php echo $user['id']; ?>" 
                                               class="btn btn-small">Edit</a>
                                            
                                            <?php if ($user['role'] !== 'admin'): ?>
                                                <form method="POST" class="inline-form" 
                                                      onsubmit="return confirm('Are you sure?')">
                                                    <input type="hidden" name="action" value="status">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="status" 
                                                           value="<?php echo $user['status'] === 'active' ? 'suspended' : 'active'; ?>">
                                                    <button type="submit" class="btn btn-small btn-warning">
                                                        <?php echo $user['status'] === 'active' ? 'Suspend' : 'Activate'; ?>
                                                    </button>
                                                </form>

                                                <form method="POST" class="inline-form" 
                                                      onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-small btn-danger">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" 
                               class="btn btn-secondary">&laquo; Previous</a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" 
                               class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>&status=<?php echo urlencode($status); ?>" 
                               class="btn btn-secondary">Next &raquo;</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
