import api, { setStoredToken } from './api'

/**
 * Inscription : { email, password, firstName, lastName, role } → { user, token }
 */
export async function register({ email, password, firstName, lastName, role }) {
  const { data } = await api.post('/auth/register', {
    email,
    password,
    firstName,
    lastName,
    role: role || 'ROLE_STUDENT',
  })
  if (data.token) {
    setStoredToken(data.token)
  }
  return data
}

/**
 * Connexion : { email, password } → { user, token }
 */
export async function login({ email, password }) {
  const { data } = await api.post('/auth/login', { email, password })
  if (data.token) {
    setStoredToken(data.token)
  }
  return data
}

/**
 * Récupère l'utilisateur connecté (GET /api/users/me)
 */
export async function getMe() {
  const { data } = await api.get('/users/me')
  return data
}
