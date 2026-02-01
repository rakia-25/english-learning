import { Navigate } from 'react-router-dom'
import { useAuth } from '../../hooks/useAuth'

export default function TeacherRoute({ children }) {
  const { user, loading } = useAuth()
  const isTeacher = user?.roles?.includes('ROLE_TEACHER')

  if (loading) {
    return (
      <div className="min-h-screen bg-slate-50 flex items-center justify-center">
        <div className="text-slate-600">Chargement…</div>
      </div>
    )
  }

  if (!isTeacher) {
    return <Navigate to="/" replace />
  }

  return children
}
