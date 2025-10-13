import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { Button, Input, Alert } from '../../components/atoms';
import { Mail, Lock, Eye, EyeOff } from 'lucide-react';

const LoginPage = () => {
  const navigate = useNavigate();
  const { login } = useAuth();
  
  const [formData, setFormData] = useState({
    email: '',
    password: '',
    remember: false,
  });
  
  const [showPassword, setShowPassword] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');
  const [sessionExpired, setSessionExpired] = useState(false);

  useEffect(() => {
    // Check if redirected due to session expiry
    const expired = localStorage.getItem('session_expired');
    if (expired) {
      setSessionExpired(true);
      localStorage.removeItem('session_expired');
      
      // Clear the message after 5 seconds
      setTimeout(() => setSessionExpired(false), 5000);
    }
  }, []);

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
    setError(''); // Clear error on input change
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setIsLoading(true);

    try {
      const result = await login(formData.email, formData.password);
      
      if (result.success) {
        navigate('/dashboard');
      } else {
        setError(result.message || 'فشل تسجيل الدخول. يرجى المحاولة مرة أخرى.');
      }
    } catch (err) {
      setError('حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 via-white to-blue-50 p-4">
      {/* Background Pattern */}
      <div className="absolute inset-0 bg-grid-pattern opacity-5" />
      
      <div className="w-full max-w-md relative">
        {/* Login Card */}
        <div className="bg-white rounded-2xl shadow-2xl p-8 space-y-6 relative z-10">
          {/* Logo & Header */}
          <div className="text-center space-y-2">
            <div className="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-xl mb-4">
              <svg className="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
            </div>
            <h1 className="text-3xl font-bold text-gray-900">
              نظام إدارة المخزون
            </h1>
            <p className="text-gray-500">
              مرحباً بك! سجل دخولك للمتابعة
            </p>
          </div>

          {/* Error Alert */}
          {error && (
            <Alert variant="error" onClose={() => setError('')}>
              <p className="text-sm font-medium">{error}</p>
            </Alert>
          )}

          {/* Session Expired Alert */}
          {sessionExpired && (
            <Alert variant="warning" onClose={() => setSessionExpired(false)}>
              <p className="text-sm font-medium">🔒 انتهت جلستك. يرجى تسجيل الدخول مرة أخرى.</p>
            </Alert>
          )}

          {/* Login Form */}
          <form onSubmit={handleSubmit} className="space-y-4">
            {/* Email Input */}
            <Input
              label="البريد الإلكتروني"
              type="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              placeholder="example@company.com"
              leftIcon={<Mail className="w-5 h-5" />}
              required
              fullWidth
              autoComplete="email"
              disabled={isLoading}
            />

            {/* Password Input */}
            <Input
              label="كلمة المرور"
              type={showPassword ? 'text' : 'password'}
              name="password"
              value={formData.password}
              onChange={handleChange}
              placeholder="••••••••"
              leftIcon={<Lock className="w-5 h-5" />}
              rightIcon={
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="focus:outline-none hover:text-gray-600 transition-colors"
                  tabIndex={-1}
                >
                  {showPassword ? (
                    <EyeOff className="w-5 h-5" />
                  ) : (
                    <Eye className="w-5 h-5" />
                  )}
                </button>
              }
              required
              fullWidth
              autoComplete="current-password"
              disabled={isLoading}
            />

            {/* Remember Me & Forgot Password */}
            <div className="flex items-center justify-between">
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  name="remember"
                  checked={formData.remember}
                  onChange={handleChange}
                  className="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 cursor-pointer"
                  disabled={isLoading}
                />
                <span className="text-sm text-gray-700">تذكرني</span>
              </label>
              
              <button
                type="button"
                className="text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors"
                disabled={isLoading}
              >
                نسيت كلمة المرور؟
              </button>
            </div>

            {/* Submit Button */}
            <Button
              type="submit"
              variant="primary"
              size="lg"
              fullWidth
              isLoading={isLoading}
              disabled={isLoading}
            >
              {isLoading ? 'جاري تسجيل الدخول...' : 'تسجيل الدخول'}
            </Button>
          </form>

          {/* Divider */}
          <div className="relative">
            <div className="absolute inset-0 flex items-center">
              <div className="w-full border-t border-gray-200" />
            </div>
            <div className="relative flex justify-center text-sm">
              <span className="px-4 bg-white text-gray-500">
                أو
              </span>
            </div>
          </div>

          {/* Demo Credentials Info */}
          <div className="bg-blue-50 border border-blue-100 rounded-lg p-4 text-center">
            <p className="text-sm text-blue-800 font-medium mb-2">
              🔐 بيانات تجريبية للدخول
            </p>
            <div className="text-xs text-blue-600 space-y-1">
              <p>Email: <span className="font-mono">test@example.com</span></p>
              <p>Password: <span className="font-mono">password</span></p>
            </div>
            <button
              type="button"
              onClick={() => {
                setFormData({ email: 'test@example.com', password: 'password', remember: false });
              }}
              className="mt-2 text-xs text-blue-700 hover:text-blue-900 underline"
            >
              انقر لملء البيانات تلقائياً
            </button>
          </div>
        </div>

        {/* Footer */}
        <div className="mt-6 text-center text-sm text-gray-600">
          <p>
            © 2025 نظام إدارة المخزون. جميع الحقوق محفوظة.
          </p>
        </div>
      </div>
    </div>
  );
};

export default LoginPage;
