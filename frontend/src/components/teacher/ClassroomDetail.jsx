import { useState, useEffect } from 'react'
import { useParams, useNavigate, Link } from 'react-router-dom'
import * as teacherService from '../../services/teacherService'
import ClassroomForm from './ClassroomForm'
import CourseEditor from './CourseEditor'

export default function ClassroomDetail() {
  const { id } = useParams()
  const navigate = useNavigate()
  const [classroom, setClassroom] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [editing, setEditing] = useState(false)
  const [addingCourse, setAddingCourse] = useState(false)
  const [editingCourseId, setEditingCourseId] = useState(null)

  async function load() {
    if (!id) return
    setLoading(true)
    setError('')
    try {
      const data = await teacherService.getClassroom(id)
      setClassroom(data)
    } catch (err) {
      setError(err.response?.data?.message ?? 'Classe introuvable.')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    load()
  }, [id])

  async function handleDeleteClassroom() {
    if (!confirm('Supprimer cette classe ? Les cours et données associés seront perdus.')) return
    try {
      await teacherService.deleteClassroom(id)
      navigate('/teacher')
    } catch (err) {
      setError(err.response?.data?.message ?? 'Erreur lors de la suppression.')
    }
  }

  if (loading) return <div className="text-slate-600">Chargement…</div>
  if (!classroom) return <div className="text-red-600">{error || 'Classe introuvable'}</div>

  return (
    <div>
      <div className="mb-4">
        <Link to="/teacher" className="text-sm text-indigo-600 hover:text-indigo-500">
          ← Mes classes
        </Link>
      </div>

      {error && (
        <div className="mb-4 rounded-lg bg-red-50 text-red-700 px-4 py-3">{error}</div>
      )}

      {editing ? (
        <ClassroomForm
          classroomId={classroom.id}
          defaultValues={{ name: classroom.name, description: classroom.description ?? '' }}
          onSuccess={() => { setEditing(false); load() }}
          onCancel={() => setEditing(false)}
        />
      ) : (
        <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 mb-8">
          <div className="flex items-start justify-between gap-4">
            <div>
              <h1 className="text-2xl font-bold text-slate-800">{classroom.name}</h1>
              {classroom.description && (
                <p className="mt-1 text-slate-600">{classroom.description}</p>
              )}
              <p className="mt-2 text-sm text-slate-500">Code d’inscription : <strong>{classroom.code}</strong></p>
            </div>
            <div className="flex gap-2">
              <button
                type="button"
                onClick={() => setEditing(true)}
                className="px-3 py-1.5 rounded-lg border border-slate-300 text-slate-700 text-sm hover:bg-slate-50"
              >
                Modifier
              </button>
              <button
                type="button"
                onClick={handleDeleteClassroom}
                className="px-3 py-1.5 rounded-lg border border-red-200 text-red-600 text-sm hover:bg-red-50"
              >
                Supprimer
              </button>
            </div>
          </div>

          <div className="mt-6 pt-6 border-t border-slate-200">
            <h3 className="font-semibold text-slate-800 mb-2">Étudiants ({classroom.students?.length ?? 0})</h3>
            {classroom.students?.length > 0 ? (
              <ul className="text-sm text-slate-600 space-y-1">
                {classroom.students.map((s) => (
                  <li key={s.id}>
                    {s.first_name} {s.last_name} ({s.email})
                  </li>
                ))}
              </ul>
            ) : (
              <p className="text-sm text-slate-500">Aucun étudiant pour l’instant.</p>
            )}
          </div>
        </div>
      )}

      <div className="mt-8">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-bold text-slate-800">Cours</h2>
          {!editing && (
            <button
              type="button"
              onClick={() => { setAddingCourse(true); setEditingCourseId(null) }}
              className="px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 text-sm"
            >
              Ajouter un cours
            </button>
          )}
        </div>

        {addingCourse && (
          <div className="mb-6">
            <CourseEditor
              classroomId={id}
              onSuccess={() => { setAddingCourse(false); load() }}
              onCancel={() => setAddingCourse(false)}
            />
          </div>
        )}

        {editingCourseId && (
          <div className="mb-6">
            <CourseEditor
              courseId={editingCourseId}
              onSuccess={() => { setEditingCourseId(null); load() }}
              onCancel={() => setEditingCourseId(null)}
            />
          </div>
        )}

        {classroom.courses?.length === 0 && !addingCourse ? (
          <p className="text-slate-600">Aucun cours. Ajoutez un cours pour commencer.</p>
        ) : (
          <ul className="space-y-3">
            {classroom.courses?.map((course) => (
              <li
                key={course.id}
                className="flex items-center justify-between p-4 rounded-xl bg-white border border-slate-200"
              >
                <div>
                  <h4 className="font-medium text-slate-800">{course.title}</h4>
                  {course.description && (
                    <p className="text-sm text-slate-600 line-clamp-1">{course.description}</p>
                  )}
                  <span className="text-xs text-slate-500">
                    {course.is_published ? 'Publié' : 'Brouillon'}
                  </span>
                </div>
                <div className="flex gap-2">
                  <button
                    type="button"
                    onClick={() => { setEditingCourseId(course.id); setAddingCourse(false) }}
                    className="px-3 py-1 rounded-lg border border-slate-300 text-slate-700 text-sm hover:bg-slate-50"
                  >
                    Modifier
                  </button>
                  <Link
                    to={`/teacher/courses/${course.id}/exams`}
                    className="px-3 py-1 rounded-lg bg-slate-100 text-slate-700 text-sm hover:bg-slate-200"
                  >
                    Examens
                  </Link>
                </div>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  )
}
