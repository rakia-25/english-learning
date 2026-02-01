import { Outlet, Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../../hooks/useAuth'

export default function TeacherDashboard() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  return (
    <div className="min-h-screen bg-slate-50">
      <header className="bg-white border-b border-slate-200">
        <div className="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
          <div className="flex items-center gap-6">
            <Link to="/teacher" className="text-lg font-bold text-slate-800">
              E-Learning · Enseignant
            </Link>
            <nav className="flex gap-4">
              <Link to="/teacher" className="text-sm text-slate-600 hover:text-indigo-600">
                Mes classes
              </Link>
              <Link to="/" className="text-sm text-slate-600 hover:text-indigo-600">
                Accueil
              </Link>
            </nav>
          </div>
          <div className="flex items-center gap-4">
            <span className="text-sm text-slate-600">
              {user?.firstName} {user?.lastName}
            </span>
            <button
              type="button"
              onClick={() => { logout(); navigate('/') }}
              className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
            >
              Déconnexion
            </button>
          </div>
        </div>
      </header>
      <main className="max-w-6xl mx-auto px-4 py-8">
        <Outlet />
      </main>
    </div>
  )
}
