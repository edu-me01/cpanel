<?php
// Settings Section
?>
<div id="settingsSection" class="section">
    <div class="section-header">
        <div class="header-content">
            <h1>Settings</h1>
            <p>Manage your account and application settings.</p>
        </div>
    </div>
    <form id="settingsForm">
        <div class="row">
            <div class="col-md-6">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="themeToggle">
                    <label class="form-check-label" for="themeToggle">Dark Theme</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="notificationToggle">
                    <label class="form-check-label" for="notificationToggle">Enable Notifications</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="autoSaveToggle">
                    <label class="form-check-label" for="autoSaveToggle">Auto Save</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="languageSelect" class="form-label">Language</label>
                    <select class="form-select" id="languageSelect">
                        <option value="en">English</option>
                        <option value="ar">Arabic</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="dateFormatSelect" class="form-label">Date Format</label>
                    <select class="form-select" id="dateFormatSelect">
                        <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                        <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                        <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="timeFormatSelect" class="form-label">Time Format</label>
                    <select class="form-select" id="timeFormatSelect">
                        <option value="12h">12 Hour</option>
                        <option value="24h">24 Hour</option>
                    </select>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Save Settings</button>
    </form>
</div> 