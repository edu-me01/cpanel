<?php
// Settings section content
$user = getCurrentUser();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        
        if (empty($name) || empty($email)) {
            $error = 'Name and email are required.';
        } else {
            try {
                $user['name'] = $name;
                $user['email'] = $email;
                Database::update('users', $user['id'], $user);
                $message = 'Profile updated successfully!';
            } catch (Exception $e) {
                $error = 'Error updating profile: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All password fields are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $error = 'Current password is incorrect.';
        } else {
            try {
                $user['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                Database::update('users', $user['id'], $user);
                $message = 'Password changed successfully!';
            } catch (Exception $e) {
                $error = 'Error changing password: ' . $e->getMessage();
            }
        }
    }
}
?>

<!-- Settings Content -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="card-title fw-bold mb-0">
                            <i class="fas fa-cog me-2 text-primary"></i>Settings
                        </h2>
                        <p class="text-muted mb-0">Manage your account settings and preferences</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Messages -->
<?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Settings Tabs -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">
                            <i class="fas fa-user me-2"></i>Profile
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab">
                            <i class="fas fa-lock me-2"></i>Password
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="preferences-tab" data-bs-toggle="tab" data-bs-target="#preferences" type="button" role="tab">
                            <i class="fas fa-sliders-h me-2"></i>Preferences
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="settingsTabContent">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile" role="tabpanel">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_profile">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="userType" class="form-label">User Type</label>
                                <input type="text" class="form-control" id="userType" value="<?php echo ucfirst($user['userType']); ?>" readonly>
                                <small class="text-muted">User type cannot be changed</small>
                            </div>
                            <div class="mb-3">
                                <label for="createdAt" class="form-label">Account Created</label>
                                <input type="text" class="form-control" id="createdAt" value="<?php echo date('M d, Y H:i', strtotime($user['createdAt'])); ?>" readonly>
                            </div>
                            <button type="submit" class="btn btn-primary btn-modern">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                        </form>
                    </div>

                    <!-- Password Tab -->
                    <div class="tab-pane fade" id="password" role="tabpanel">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="change_password">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Password Requirements:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>At least 8 characters long</li>
                                    <li>Include uppercase and lowercase letters</li>
                                    <li>Include at least one number</li>
                                    <li>Include at least one special character</li>
                                </ul>
                            </div>
                            <button type="submit" class="btn btn-warning btn-modern">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </form>
                    </div>

                    <!-- Preferences Tab -->
                    <div class="tab-pane fade" id="preferences" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">Notification Settings</h6>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                    <label class="form-check-label" for="emailNotifications">
                                        Email Notifications
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="taskReminders" checked>
                                    <label class="form-check-label" for="taskReminders">
                                        Task Reminders
                                    </label>
                                </div>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="gradeNotifications" checked>
                                    <label class="form-check-label" for="gradeNotifications">
                                        Grade Notifications
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3">Display Settings</h6>
                                <div class="mb-3">
                                    <label for="theme" class="form-label">Theme</label>
                                    <select class="form-control" id="theme">
                                        <option value="light" selected>Light</option>
                                        <option value="dark">Dark</option>
                                        <option value="auto">Auto</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="language" class="form-label">Language</label>
                                    <select class="form-control" id="language">
                                        <option value="en" selected>English</option>
                                        <option value="es">Spanish</option>
                                        <option value="fr">French</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-success btn-modern" onclick="savePreferences()">
                            <i class="fas fa-save me-2"></i>Save Preferences
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function savePreferences() {
    // TODO: Implement preferences saving
    alert('Preferences saved successfully!');
}
</script> 