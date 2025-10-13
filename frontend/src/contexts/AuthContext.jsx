import { createContext, useContext, useState, useEffect } from 'react';
import axios from 'axios';

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(localStorage.getItem('token'));
  const [loading, setLoading] = useState(true);

  // Listen for localStorage changes
  useEffect(() => {
    const handleStorageChange = () => {
      const newToken = localStorage.getItem('token');
      if (newToken !== token) {
        setToken(newToken);
      }
    };

    // Listen for storage events
    window.addEventListener('storage', handleStorageChange);
    
    // Also check manually (for same-tab changes)
    const interval = setInterval(handleStorageChange, 1000);

    return () => {
      window.removeEventListener('storage', handleStorageChange);
      clearInterval(interval);
    };
  }, [token]);

  useEffect(() => {
    if (token) {
      // Set axios default header
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      
      // Fetch user data
      fetchUser();
    } else {
      setLoading(false);
      // Clear axios header if no token
      delete axios.defaults.headers.common['Authorization'];
    }
  }, [token]);

  const fetchUser = async () => {
    try {
      console.log('🔑 Fetching user with token:', token?.substring(0, 20) + '...');
      const response = await axios.get(`${import.meta.env.VITE_API_BASE_URL}/auth/me`);
      console.log('✅ User fetched successfully:', response.data);
      // Backend returns: { user: {...} }
      setUser(response.data.user || response.data.data);
    } catch (error) {
      console.error('❌ Failed to fetch user:', error.response?.status, error.response?.data);
      if (error.response?.status === 401) {
        console.log('🔄 Token expired, logging out...');
        logout();
      }
    } finally {
      setLoading(false);
    }
  };

  const login = async (email, password) => {
    try {
      const response = await axios.post(`${import.meta.env.VITE_API_BASE_URL}/auth/login`, {
        email,
        password
      });
      
      // Backend returns: { message, user, token, token_type }
      const { token: newToken, user: userData } = response.data;
      
      setToken(newToken);
      setUser(userData);
      localStorage.setItem('token', newToken);
      axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;
      
      return { success: true };
    } catch (error) {
      console.error('Login error:', error);
      
      // Handle validation errors
      if (error.response?.status === 422) {
        const errors = error.response?.data?.errors;
        const firstError = errors ? Object.values(errors)[0][0] : 'بيانات غير صحيحة';
        return {
          success: false,
          message: firstError
        };
      }
      
      return {
        success: false,
        message: error.response?.data?.message || 'فشل تسجيل الدخول'
      };
    }
  };

  const logout = () => {
    setToken(null);
    setUser(null);
    localStorage.removeItem('token');
    delete axios.defaults.headers.common['Authorization'];
  };

  // Manual token update function
  const updateToken = (newToken) => {
    if (newToken) {
      setToken(newToken);
      localStorage.setItem('token', newToken);
      axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;
    } else {
      logout();
    }
  };

  const value = {
    user,
    token,
    loading,
    login,
    logout,
    updateToken,
    isAuthenticated: !!token
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
};
