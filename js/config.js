// Application Configuration
const config = {
  // Server configuration
  server: {
    host: 'localhost',
    port: 80,
    protocol: 'http'
  },

  // API endpoints
  api: {
    baseUrl: '/api',
    auth: '/api/login',
    tasks: '/api/tasks',
    students: '/api/students',
    attendance: '/api/attendance',
    submissions: '/api/submissions'
  },

  // WebSocket configuration
  websocket: {
    enabled: false,
    url: 'ws://localhost:5000',
    reconnectInterval: 5000,
    maxReconnectAttempts: 5
  },

  // Task configuration
  tasks: {
    priorities: ['low', 'medium', 'high'],
    statuses: ['pending', 'in-progress', 'completed', 'overdue'],
    defaultPriority: 'medium',
    defaultStatus: 'pending'
  },

  // Authentication
  auth: {
    tokenKey: 'token',
    userTypeKey: 'userType',
    userDataKey: 'userData',
    sessionTimeout: 24 * 60 * 60 * 1000 // 24 hours
  },

  // UI configuration
  ui: {
    refreshInterval: 30000, // 30 seconds
    notificationTimeout: 5000, // 5 seconds
    itemsPerPage: 10
  },

  // Demo credentials
  demo: {
    admin: {
      email: 'admin@school.com',
      password: 'admin123'
    },
    student: {
      email: 'student@school.com',
      password: 'student123'
    }
  }
};

// Helper functions
const apiHelpers = {
  // Get full API URL
  getApiUrl: (endpoint) => {
    return `${config.api.baseUrl}${endpoint}`;
  },

  // Get server URL
  getServerUrl: () => {
    return `${config.server.protocol}://${config.server.host}:${config.server.port}`;
  },

  // Get WebSocket URL
  getWebSocketUrl: () => {
    return config.websocket.enabled ? config.websocket.url : null;
  },

  // Get auth headers
  getAuthHeaders: () => {
    const token = sessionStorage.getItem(config.auth.tokenKey);
    return {
      'Content-Type': 'application/json',
      'Authorization': token ? `Bearer ${token}` : ''
    };
  },

  // Check if user is authenticated
  isAuthenticated: () => {
    return !!sessionStorage.getItem(config.auth.tokenKey);
  },

  // Get current user type
  getUserType: () => {
    return sessionStorage.getItem(config.auth.userTypeKey);
  },

  // Get current user data
  getUserData: () => {
    const userData = sessionStorage.getItem(config.auth.userDataKey);
    return userData ? JSON.parse(userData) : null;
  },

  // Clear session
  clearSession: () => {
    sessionStorage.removeItem(config.auth.tokenKey);
    sessionStorage.removeItem(config.auth.userTypeKey);
    sessionStorage.removeItem(config.auth.userDataKey);
  },

  // Make API request
  makeRequest: async (endpoint, options = {}) => {
    const url = apiHelpers.getApiUrl(endpoint);
    const headers = apiHelpers.getAuthHeaders();
    
    const defaultOptions = {
      method: 'GET',
      headers: headers,
      ...options
    };

    try {
      const response = await fetch(url, defaultOptions);
      const data = await response.json();
      
      if (!response.ok) {
        throw new Error(data.message || 'API request failed');
      }
      
      return data;
    } catch (error) {
      console.error('API request error:', error);
      throw error;
    }
  }
};

// Export for use in other modules
window.config = config;
window.apiHelpers = apiHelpers;
