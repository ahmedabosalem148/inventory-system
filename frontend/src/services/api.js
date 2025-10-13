import axios from 'axios';

// Create axios instance with default config
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Request interceptor to add auth token
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor to handle errors
apiClient.interceptors.response.use(
  (response) => {
    return response;
  },
  (error) => {
    // Handle 401 Unauthorized (token expired or invalid)
    if (error.response?.status === 401) {
      console.log('🔒 Token expired or invalid - redirecting to login...');
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      
      // Add a flag to show message on login page
      localStorage.setItem('session_expired', 'true');
      
      window.location.href = '/login';
    }
    
    // Handle 403 Forbidden (no access rights)
    if (error.response?.status === 403) {
      console.error('❌ Access denied:', error.response.data);
      
      // Check if it's because of missing/invalid token
      const token = localStorage.getItem('token');
      if (!token || token.length < 20) {
        console.log('🔒 Invalid token detected - clearing and redirecting...');
        localStorage.clear();
        window.location.href = '/login';
      }
    }
    
    return Promise.reject(error);
  }
);

export default apiClient;
