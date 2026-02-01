import axios from 'axios'

const api = axios.create({
  baseURL: '/api',
  headers: {
    'Content-Type': 'application/json',
  },
})

const TOKEN_KEY = 'elearning_token'

export function getStoredToken() {
  return localStorage.getItem(TOKEN_KEY)
}

export function setStoredToken(token) {
  if (token) {
    localStorage.setItem(TOKEN_KEY, token)
  } else {
    localStorage.removeItem(TOKEN_KEY)
  }
}

api.interceptors.request.use((config) => {
  const token = getStoredToken()
  if (token) {
    const value = `Bearer ${token}`
    config.headers.Authorization = value
    // En-tête utilisé par le backend (Lexik JWT) pour contourner les proxies qui ne transmettent pas Authorization
    config.headers['X-Auth-Token'] = value
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    // En cas de 401, on demande une vérification de session (getMe) avant de déconnecter.
    // Évite une déconnexion intempestive si une route renvoie 401 par erreur.
    if (error.response?.status === 401) {
      window.dispatchEvent(new CustomEvent('auth:check-session'))
    }
    return Promise.reject(error)
  }
)

export default api
