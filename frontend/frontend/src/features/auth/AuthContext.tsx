import { createContext, useContext, useState, useEffect } from 'react'
import type { ReactNode } from 'react'
import axios from '@/app/axios'
import type { User } from '@/types'

interface AuthContextType {
  user: User | null
  isAuthenticated: boolean
  isLoading: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => void
  refreshUser: () => Promise<void>
}

const AuthContext = createContext<AuthContextType | undefined>(undefined)

interface AuthProviderProps {
  children: ReactNode
}

export function AuthProvider({ children }: AuthProviderProps) {
  const [user, setUser] = useState<User | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  // Check if user is logged in on mount
  useEffect(() => {
    const token = localStorage.getItem('access_token')
    if (token) {
      fetchUser()
    } else {
      setIsLoading(false)
    }
  }, [])

  // Fetch user data from /auth/me
  const fetchUser = async () => {
    try {
      const { data } = await axios.get('/auth/me')
      setUser(data.user) // API returns { user: {...} }
    } catch (error) {
      console.error('Failed to fetch user:', error)
      localStorage.removeItem('access_token')
      setUser(null)
    } finally {
      setIsLoading(false)
    }
  }

  // Login function
  const login = async (email: string, password: string) => {
    try {
      const { data } = await axios.post('/auth/login', { email, password })
      
      // Save token (Laravel returns 'token' not 'access_token')
      localStorage.setItem('access_token', data.token)
      
      // Set user
      setUser(data.user)
      
      return Promise.resolve()
    } catch (error: unknown) {
      const message = error instanceof Error && 'response' in error 
        ? (error as any).response?.data?.message || 'فشل تسجيل الدخول'
        : 'فشل تسجيل الدخول'
      return Promise.reject(new Error(message))
    }
  }

  // Logout function
  const logout = async () => {
    try {
      await axios.post('/auth/logout')
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      localStorage.removeItem('access_token')
      setUser(null)
      window.location.href = '/login'
    }
  }

  // Refresh user data
  const refreshUser = async () => {
    await fetchUser()
  }

  const value: AuthContextType = {
    user,
    isAuthenticated: !!user,
    isLoading,
    login,
    logout,
    refreshUser,
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

// Custom hook to use auth context
export function useAuth() {
  const context = useContext(AuthContext)
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider')
  }
  return context
}
