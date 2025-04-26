<?php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

$db = Database::getInstance();
$message = '';

try {
    // Test database connection
    $db->query("SELECT 1");
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Get current settings
            $settings = $db->fetchAll("SELECT * FROM settings");
            $settingsMap = [];
            foreach ($settings as $setting) {
                $settingsMap[$setting['setting_key']] = $setting;
            }

            // Update settings
            foreach ($_POST['settings'] as $key => $value) {
                if (isset($settingsMap[$key])) {
                    $db->update(
                        'settings',
                        ['setting_value' => $value],
                        'setting_key = ?',
                        [$key]
                    );
                } else {
                    $db->insert('settings', [
                        'setting_key' => $key,
                        'setting_value' => $value
                    ]);
                }
            }

            $message = '<div class="alert alert-success">Settings updated successfully!</div>';
        } catch (Exception $e) {
            error_log("Settings update error: " . $e->getMessage());
            $message = '<div class="alert alert-danger">Error updating settings: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }

    // Get current settings
    $settings = $db->fetchAll("SELECT * FROM settings");
    if ($settings === false) {
        throw new Exception("Failed to fetch settings from database");
    }
    
    $settingsMap = [];
    foreach ($settings as $setting) {
        $settingsMap[$setting['setting_key']] = $setting['setting_value'];
    }

} catch (Exception $e) {
    error_log("Settings page error: " . $e->getMessage());
    $message = '<div class="alert alert-danger">Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    // Set default values to prevent PHP notices
    $settingsMap = [
        'site_name' => 'MbogaFasta CMS',
        'site_email' => 'admin@mbogafasta.com',
        'timezone' => 'Africa/Nairobi',
        'maintenance_mode' => '0',
        'items_per_page' => '10'
    ];
}

// Include header
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">System Settings</h1>
            </div>

            <?php echo $message; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="site_name" class="form-label">Site Name</label>
                            <input type="text" class="form-control" id="site_name" name="settings[site_name]" 
                                   value="<?php echo htmlspecialchars($settingsMap['site_name'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="site_email" class="form-label">Site Email</label>
                            <input type="email" class="form-control" id="site_email" name="settings[site_email]" 
                                   value="<?php echo htmlspecialchars($settingsMap['site_email'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="timezone" class="form-label">Timezone</label>
                            <select class="form-select" id="timezone" name="settings[timezone]" required>
                                <?php
                                $timezones = DateTimeZone::listIdentifiers();
                                $currentTimezone = $settingsMap['timezone'] ?? 'UTC';
                                foreach ($timezones as $tz) {
                                    $selected = ($tz === $currentTimezone) ? 'selected' : '';
                                    echo "<option value=\"$tz\" $selected>$tz</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="maintenance_mode" class="form-label">Maintenance Mode</label>
                            <select class="form-select" id="maintenance_mode" name="settings[maintenance_mode]" required>
                                <option value="0" <?php echo ($settingsMap['maintenance_mode'] ?? '0') === '0' ? 'selected' : ''; ?>>Disabled</option>
                                <option value="1" <?php echo ($settingsMap['maintenance_mode'] ?? '0') === '1' ? 'selected' : ''; ?>>Enabled</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="items_per_page" class="form-label">Items Per Page</label>
                            <input type="number" class="form-control" id="items_per_page" name="settings[items_per_page]" 
                                   value="<?php echo htmlspecialchars($settingsMap['items_per_page'] ?? '10'); ?>" required min="5" max="100">
                        </div>

                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 