import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider } from './context/AuthContext'
import ProtectedRoute from './components/shared/ProtectedRoute'
import Login from './components/auth/Login'
import Register from './components/auth/Register'
import Home from './components/auth/Home'

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route
            path="/"
            element={
              <ProtectedRoute>
                <Home />
              </ProtectedRoute>
            }
          />
          <Route path="/teacher" element={<ProtectedRoute><div className="p-8">Espace enseignant (étape 18)</div></ProtectedRoute>} />
          <Route path="/student" element={<ProtectedRoute><div className="p-8">Espace étudiant (étape 19)</div></ProtectedRoute>} />
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  )
}

export default App
