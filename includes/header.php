<?php
// Top Navigation Bar
?>
<nav class="top-navbar">
  <div class="navbar-left">
    <button class="sidebar-toggle" id="sidebarToggle">
      <i class="fas fa-bars"></i>
    </button>
    <div class="brand">
      <i class="fas fa-tasks"></i>
      <span>Task Manager</span>
    </div>
  </div>
  <div class="navbar-center">
    <div class="breadcrumb-nav">
      <span id="currentSection">Dashboard</span>
    </div>
  </div>
  <div class="navbar-right">
    <div class="nav-actions">
      <button class="action-btn" id="notificationsBtn">
        <i class="fas fa-bell"></i>
        <span class="badge">3</span>
      </button>
      <button class="action-btn" id="themeToggle">
        <i class="fas fa-moon"></i>
      </button>
      <div class="user-menu" id="userInfo">
        <!-- Will be populated by auth.js -->
      </div>
    </div>
  </div>
</nav> 