import { createContext, useContext, useState, useEffect, useCallback } from 'react'
import * as authService from '../services/authService'
import { getStoredToken, setStoredToken } from '../services/api'

const AuthContext = createContext(null)

function normalizeUser(apiUser) {
  if (!apiUser) return null
  return {
    id: apiUser.id,
    email: apiUser.email,
    firstName: apiUser.first_name ?? apiUser.firstName,
    lastName: apiUser.last_name ?? apiUser.lastName,
    roles: apiUser.roles ?? [],
    isVerified: apiUser.is_verified ?? apiUser.isVerified,
  }
}

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [token, setToken] = useState(getStoredToken())
  const [loading, setLoading] = useState(true)

  const loadUser = useCallback(async () => {
    const t = getStoredToken()
    if (!t) {
      setUser(null)
      setToken(null)
      setLoading(false)
      return
    }
    try {
      const data = await authService.getMe()
      setUser(normalizeUser(data.user ?? data))
      setToken(t)
    } catch {
      setStoredToken(null)
      setUser(null)
      setToken(null)
    } finally {
      setLoading(false)
    }
  }, [])

  useEffect(() => {
    loadUser()
  }, [loadUser])

  useEffect(() => {
    const onLogout = () => {
      setUser(null)
      setToken(null)
    }
    const onCheckSession = () => {
      // Vérifier si le token est encore valide (getMe). Si 401, loadUser() déconnecte.
      loadUser()
    }
    window.addEventListener('auth:logout', onLogout)
    window.addEventListener('auth:check-session', onCheckSession)
    return () => {
      window.removeEventListener('auth:logout', onLogout)
      window.removeEventListener('auth:check-session', onCheckSession)
    }
  }, [loadUser])

  const login = useCallback(async ({ email, password }) => {
    const data = await authService.login({ email, password })
    setUser(normalizeUser(data.user ?? data))
    setToken(data.token)
    return data
  }, [])

  const register = useCallback(async ({ email, password, firstName, lastName, role }) => {
    const data = await authService.register({
      email,
      password,
      firstName,
      lastName,
      role: role || 'ROLE_STUDENT',
    })
    setUser(normalizeUser(data.user ?? data))
    setToken(data.token)
    return data
  }, [])

  const logout = useCallback(() => {
    setStoredToken(null)
    setUser(null)
    setToken(null)
  }, [])

  const value = {
    user,
    token,
    loading,
    isAuthenticated: !!token && !!user,
    login,
    register,
    logout,
    loadUser,
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) {
    throw new Error('useAuth must be used within AuthProvider')
  }
  return ctx
}
