import { useState, useEffect } from 'react'
import { Link } from 'react-router-dom'
import * as teacherService from '../../services/teacherService'
import ClassroomForm from './ClassroomForm'

export default function ClassroomList() {
  const [classrooms, setClassrooms] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [showNewForm, setShowNewForm] = useState(false)

  async function load() {
    setLoading(true)
    setError('')
    try {
      const list = await teacherService.getClassrooms()
      setClassrooms(list)
    } catch (err) {
      setError(err.response?.data?.message ?? 'Impossible de charger les classes.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    load()
  }, [])

  if (loading) {
    return <div className="text-slate-600">Chargement des classes…</div>
  }

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-slate-800">Mes classes</h1>
        <button
          type="button"
          onClick={() => setShowNewForm(true)}
          className="px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700"
        >
          Nouvelle classe
        </button>
      </div>

      {error && (
        <div className="mb-4 rounded-lg bg-red-50 text-red-700 px-4 py-3">{error}</div>
      )}

      {showNewForm && (
        <div className="mb-8">
          <ClassroomForm
            onSuccess={() => {
              setShowNewForm(false)
              load()
            }}
            onCancel={() => setShowNewForm(false)}
          />
        </div>
      )}

      {classrooms.length === 0 && !showNewForm ? (
        <div className="bg-white rounded-xl border border-slate-200 p-8 text-center text-slate-600">
          Aucune classe. Créez une classe pour commencer.
        </div>
      ) : (
        <ul className="grid gap-4 sm:grid-cols-2">
          {classrooms.map((c) => (
            <li key={c.id}>
              <Link
                to={`/teacher/classrooms/${c.id}`}
                className="block p-5 rounded-xl bg-white border border-slate-200 shadow-sm hover:border-indigo-300 hover:shadow transition"
              >
                <h3 className="font-semibold text-slate-800">{c.name}</h3>
                {c.description && (
                  <p className="mt-1 text-sm text-slate-600 line-clamp-2">{c.description}</p>
                )}
                <p className="mt-2 text-xs text-slate-500">Code : {c.code}</p>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
