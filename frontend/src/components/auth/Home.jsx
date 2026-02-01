import { Link } from 'react-router-dom'
import { useAuth } from '../../hooks/useAuth'

export default function Home() {
  const { user, logout } = useAuth()
  const isTeacher = user?.roles?.includes('ROLE_TEACHER')

  return (
    <div className="min-h-screen bg-slate-50">
      <header className="bg-white border-b border-slate-200">
        <div className="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
          <h1 className="text-xl font-bold text-slate-800">E-Learning</h1>
          <div className="flex items-center gap-4">
            <span className="text-sm text-slate-600">
              {user?.firstName} {user?.lastName}
            </span>
            <button
              type="button"
              onClick={logout}
              className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
            >
              Déconnexion
            </button>
          </div>
        </div>
      </header>
      <main className="max-w-6xl mx-auto px-4 py-12">
        <h2 className="text-2xl font-bold text-slate-800 mb-6">Tableau de bord</h2>
        <div className="grid gap-4 sm:grid-cols-2">
          {isTeacher && (
            <Link
              to="/teacher"
              className="block p-6 rounded-xl bg-white border border-slate-200 shadow-sm hover:border-indigo-300 hover:shadow transition"
            >
              <h3 className="font-semibold text-slate-800">Espace enseignant</h3>
              <p className="mt-1 text-sm text-slate-600">Gérer vos classes et cours</p>
            </Link>
          )}
          <Link
            to="/student"
            className="block p-6 rounded-xl bg-white border border-slate-200 shadow-sm hover:border-indigo-300 hover:shadow transition"
          >
            <h3 className="font-semibold text-slate-800">Espace étudiant</h3>
            <p className="mt-1 text-sm text-slate-600">Mes classes, cours et examens</p>
          </Link>
        </div>
      </main>
    </div>
  )
}
