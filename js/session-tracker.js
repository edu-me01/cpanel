/**
 * Session Tracker Utility
 * Manages user sessions and tracks login/logout activities
 */
class SessionTracker {
  constructor() {
    this.sessions = this.loadSessions();
    this.currentSessionId = null;
  }

  /**
   * Load existing sessions from localStorage
   */
  loadSessions() {
    try {
      const sessions = sessionStorage.getItem("userSessions");
      return sessions ? JSON.parse(sessions) : [];
    } catch (error) {
      console.error("Error loading sessions:", error);
      return [];
    }
  }

  /**
   * Save sessions to localStorage
   */
  saveSessions() {
    try {
      sessionStorage.setItem("userSessions", JSON.stringify(this.sessions));
    } catch (error) {
      console.error("Error saving sessions:", error);
    }
  }

  /**
   * Add a new session
   */
  addSession(userData) {
    const sessionId =
      "session_" + Date.now() + "_" + Math.random().toString(36).substr(2, 9);
    const session = {
      id: sessionId,
      userId: userData.id,
      userType: userData.type,
      userName: userData.name,
      userEmail: userData.email,
      loginTime: new Date().toISOString(),
      lastActivity: new Date().toISOString(),
      isActive: true,
    };

    this.sessions.push(session);
    this.currentSessionId = sessionId;
    this.saveSessions();

    // Store current session ID in sessionStorage
    sessionStorage.setItem("currentSessionId", sessionId);

    console.log("Session started:", session);
    return sessionId;
  }

  /**
   * Remove a session
   */
  removeSession(sessionId) {
    const sessionIndex = this.sessions.findIndex((s) => s.id === sessionId);
    if (sessionIndex !== -1) {
      this.sessions[sessionIndex].logoutTime = new Date().toISOString();
      this.sessions[sessionIndex].isActive = false;
      this.saveSessions();
      console.log("Session ended:", this.sessions[sessionIndex]);
    }
  }

  /**
   * Update last activity for current session
   */
  updateActivity() {
    if (this.currentSessionId) {
      const session = this.sessions.find((s) => s.id === this.currentSessionId);
      if (session) {
        session.lastActivity = new Date().toISOString();
        this.saveSessions();
      }
    }
  }

  /**
   * Get current session
   */
  getCurrentSession() {
    if (this.currentSessionId) {
      return this.sessions.find((s) => s.id === this.currentSessionId);
    }
    return null;
  }

  /**
   * Get all active sessions
   */
  getActiveSessions() {
    return this.sessions.filter((s) => s.isActive);
  }

  /**
   * Get session history for a user
   */
  getUserSessions(userId) {
    return this.sessions.filter((s) => s.userId === userId);
  }

  /**
   * Clear all sessions
   */
  clearAllSessions() {
    this.sessions = [];
    this.currentSessionId = null;
    this.saveSessions();
    sessionStorage.removeItem("currentSessionId");
  }

  /**
   * Initialize session tracker
   */
  init() {
    // Load current session ID from sessionStorage
    const currentSessionId = sessionStorage.getItem("currentSessionId");
    if (currentSessionId) {
      this.currentSessionId = currentSessionId;
    }

    // Set up activity tracking
    this.setupActivityTracking();

    console.log("Session tracker initialized");
  }

  /**
   * Set up activity tracking
   */
  setupActivityTracking() {
    // Update activity on user interaction
    const events = [
      "mousedown",
      "mousemove",
      "keypress",
      "scroll",
      "touchstart",
      "click",
    ];

    events.forEach((event) => {
      document.addEventListener(
        event,
        () => {
          this.updateActivity();
        },
        { passive: true }
      );
    });

    // Update activity every 30 seconds
    setInterval(() => {
      this.updateActivity();
    }, 30000);
  }

  /**
   * Get session statistics
   */
  getSessionStats() {
    const totalSessions = this.sessions.length;
    const activeSessions = this.getActiveSessions().length;
    const todaySessions = this.sessions.filter((s) => {
      const today = new Date().toDateString();
      const sessionDate = new Date(s.loginTime).toDateString();
      return sessionDate === today;
    }).length;

    return {
      total: totalSessions,
      active: activeSessions,
      today: todaySessions,
    };
  }
}

// Create global instance
window.sessionTracker = new SessionTracker();

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", () => {
  window.sessionTracker.init();
});

// Export for module systems
if (typeof module !== "undefined" && module.exports) {
  module.exports = SessionTracker;
}
