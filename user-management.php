<?php
session_start();
include 'connection.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$email = $_SESSION['email'];

// Get user's full name for display
$user_query = "SELECT * FROM users WHERE user_id = '$user_id'";
$user_result = mysqli_query($conn, $user_query);
$user_data = mysqli_fetch_assoc($user_result);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        // Add new user
        $new_username = mysqli_real_escape_string($conn, $_POST['username']);
        $new_email = mysqli_real_escape_string($conn, $_POST['email']);
        $new_role = mysqli_real_escape_string($conn, $_POST['role']);
        $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $insert_query = "INSERT INTO users (username, email, password, role, created_at) 
                         VALUES ('$new_username', '$new_email', '$new_password', '$new_role', NOW())";

        if (mysqli_query($conn, $insert_query)) {
            $alert_message = "User added successfully!";
            $alert_type = "success";
        } else {
            $alert_message = "Error adding user: " . mysqli_error($conn);
            $alert_type = "error";
        }
    } elseif (isset($_POST['edit_user'])) {
        // Edit existing user
        $edit_id = mysqli_real_escape_string($conn, $_POST['user_id']);
        $edit_username = mysqli_real_escape_string($conn, $_POST['username']);
        $edit_email = mysqli_real_escape_string($conn, $_POST['email']);
        $edit_role = mysqli_real_escape_string($conn, $_POST['role']);

        $update_query = "UPDATE users SET 
                         username = '$edit_username', 
                         email = '$edit_email', 
                         role = '$edit_role' 
                         WHERE user_id = '$edit_id'";

        if (mysqli_query($conn, $update_query)) {
            $alert_message = "User updated successfully!";
            $alert_type = "success";
        } else {
            $alert_message = "Error updating user: " . mysqli_error($conn);
            $alert_type = "error";
        }
    } elseif (isset($_POST['delete_user'])) {
        // Delete user
        $delete_id = mysqli_real_escape_string($conn, $_POST['user_id']);

        // Check if user is trying to delete themselves
        if ($delete_id == $user_id) {
            $alert_message = "You cannot delete your own account!";
            $alert_type = "error";
        } else {
            $delete_query = "DELETE FROM users WHERE user_id = '$delete_id'";

            if (mysqli_query($conn, $delete_query)) {
                $alert_message = "User deleted successfully!";
                $alert_type = "success";
            } else {
                $alert_message = "Error deleting user: " . mysqli_error($conn);
                $alert_type = "error";
            }
        }
    }
}

// Get all users for display
$users_query = "SELECT * FROM users ORDER BY created_at DESC";
$users_result = mysqli_query($conn, $users_query);

// Get user statistics
$total_users = mysqli_num_rows($users_result);
$doctors_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'Doctor'")->fetch_assoc()['count'];
$nurses_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'Nurse'")->fetch_assoc()['count'];
$mothers_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'Mother'")->fetch_assoc()['count'];
$admins_count = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'Admin'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - MaternalCare AI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS styles from the previous HTML file */

        <?php include 'styles.css'; ?>

        /* Additional styles for user management */
        .user-management-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .user-stats {
            flex: 1;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .user-form-container {
            flex: 2;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .user-list-container {
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .stat-card {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #6c757d;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.2s;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0069d9;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-danger {
            background-color: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .user-table th,
        .user-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .user-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .user-table tr:hover {
            background-color: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .role-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .role-admin {
            background-color: #e9ecef;
            color: #495057;
        }

        .role-doctor {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .role-nurse {
            background-color: #d4edda;
            color: #155724;
        }

        .role-mother {
            background-color: #fff3cd;
            color: #856404;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            width: 500px;
            max-width: 90%;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #6c757d;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <!-- Main Application -->
    <div id="main-app">
        <!-- Header -->
        <header class="header">
            <div class="header-container">
                <div class="logo">
                    <i class="fas fa-baby"></i>
                    <span>MaternalCare AI</span>
                </div>
                <div class="user-info">
                    <span id="user-display-name">Welcome, <?php echo $user_data['username']; ?> (<?php echo $role; ?>)</span>
                </div>
            </div>
        </header>

        <!-- Navigation -->
        <nav class="nav-container">
            <ul class="nav-menu">
                <!-- Admin Navigation -->
                <div class="admin-nav" style="display: <?php echo $role == 'Admin' ? 'flex' : 'none'; ?>;">
                    <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="user-management.php" class="nav-link active">User Management</a></li>
                    <li class="nav-item"><a href="system-settings.php" class="nav-link">System Settings</a></li>
                    <li class="nav-item"><a href="reports.php" class="nav-link">Reports</a></li>
                </div>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Alert Container -->
            <div id="alert-container">
                <?php if (isset($alert_message)): ?>
                    <div class="alert alert-<?php echo $alert_type; ?>">
                        <?php echo $alert_message; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="page-content">
                <h1>User Management</h1>
                <p>Manage system users, roles, and permissions.</p>

                <div class="user-management-container">
                    <div class="user-stats">
                        <h3>User Statistics</h3>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $total_users; ?></div>
                                <div class="stat-label">Total Users</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $admins_count; ?></div>
                                <div class="stat-label">Admins</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $doctors_count; ?></div>
                                <div class="stat-label">Doctors</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $nurses_count; ?></div>
                                <div class="stat-label">Nurses</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $mothers_count; ?></div>
                                <div class="stat-label">Mothers</div>
                            </div>
                        </div>
                    </div>

                    <div class="user-form-container">
                        <h3>Add New User</h3>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="role">Role</label>
                                <select id="role" name="role" class="form-control" required>
                                    <option value="">Select Role</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Doctor">Doctor</option>
                                    <option value="Nurse">Nurse</option>
                                    <option value="Mother">Mother</option>
                                </select>
                            </div>

                            <div class="btn-group">
                                <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                                <button type="reset" class="btn btn-secondary">Reset</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="user-list-container">
                    <h3>All Users</h3>
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (mysqli_num_rows($users_result) > 0) {
                                while ($user = mysqli_fetch_assoc($users_result)) {
                                    $created_date = date('M j, Y', strtotime($user['created_at']));
                                    $role_class = 'role-' . strtolower($user['role']);

                                    echo "
                                    <tr>
                                        <td>{$user['user_id']}</td>
                                        <td>{$user['username']}</td>
                                        <td>{$user['email']}</td>
                                        <td><span class='role-badge {$role_class}'>{$user['role']}</span></td>
                                        <td>{$created_date}</td>
                                        <td class='action-buttons'>
                                            <button class='action-btn btn-primary edit-user' data-id='{$user['user_id']}' data-username='{$user['username']}' data-email='{$user['email']}' data-role='{$user['role']}'>
                                                <i class='fas fa-edit'></i> Edit
                                            </button>
                                            <button class='action-btn btn-danger delete-user' data-id='{$user['user_id']}' data-username='{$user['username']}'>
                                                <i class='fas fa-trash'></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                    ";
                                }
                            } else {
                                echo "
                                <tr>
                                    <td colspan='6' style='text-align: center; padding: 20px;'>
                                        <p>No users found in the system.</p>
                                    </td>
                                </tr>
                                ";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-container">
                <div class="footer-left">
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                    <div class="copyright">
                        &copy; 2024 MaternalCare AI. All rights reserved.
                    </div>
                </div>
                <div class="footer-right">
                    <div class="user-role-display">
                        Logged in as: <span id="footer-user-role"><?php echo $role; ?></span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit User</h3>
                <button class="close-btn">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" id="edit_user_id" name="user_id">

                <div class="form-group">
                    <label for="edit_username">Username</label>
                    <input type="text" id="edit_username" name="username" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="edit_role">Role</label>
                    <select id="edit_role" name="role" class="form-control" required>
                        <option value="">Select Role</option>
                        <option value="Admin">Admin</option>
                        <option value="Doctor">Doctor</option>
                        <option value="Nurse">Nurse</option>
                        <option value="Mother">Mother</option>
                    </select>
                </div>

                <div class="btn-group">
                    <button type="submit" name="edit_user" class="btn btn-primary">Update User</button>
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Delete User</h3>
                <button class="close-btn">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" id="delete_user_id" name="user_id">

                <p>Are you sure you want to delete user: <strong id="delete_username"></strong>?</p>
                <p class="text-danger">This action cannot be undone.</p>

                <div class="btn-group">
                    <button type="submit" name="delete_user" class="btn btn-danger">Delete User</button>
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Modal functionality
            const editModal = document.getElementById('editUserModal');
            const deleteModal = document.getElementById('deleteUserModal');
            const closeButtons = document.querySelectorAll('.close-btn, .close-modal');

            // Edit user buttons
            const editButtons = document.querySelectorAll('.edit-user');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const username = this.getAttribute('data-username');
                    const email = this.getAttribute('data-email');
                    const role = this.getAttribute('data-role');

                    document.getElementById('edit_user_id').value = userId;
                    document.getElementById('edit_username').value = username;
                    document.getElementById('edit_email').value = email;
                    document.getElementById('edit_role').value = role;

                    editModal.style.display = 'flex';
                });
            });

            // Delete user buttons
            const deleteButtons = document.querySelectorAll('.delete-user');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const username = this.getAttribute('data-username');

                    document.getElementById('delete_user_id').value = userId;
                    document.getElementById('delete_username').textContent = username;

                    deleteModal.style.display = 'flex';
                });
            });

            // Close modal buttons
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    editModal.style.display = 'none';
                    deleteModal.style.display = 'none';
                });
            });

            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === editModal) {
                    editModal.style.display = 'none';
                }
                if (event.target === deleteModal) {
                    deleteModal.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>