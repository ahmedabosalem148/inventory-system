/**
 * Environment configuration
 */

interface Config {
  apiUrl: string
  apiTimeout: number
  tokenKey: string
  refreshTokenKey: string
  environment: 'development' | 'production'
}

const config: Config = {
  apiUrl: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  apiTimeout: 30000, // 30 seconds
  tokenKey: 'auth_token',
  refreshTokenKey: 'refresh_token',
  environment: (import.meta.env.MODE as 'development' | 'production') || 'development',
}

export default config
