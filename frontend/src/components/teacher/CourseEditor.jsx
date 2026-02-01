import { useState, useEffect } from 'react'
import * as teacherService from '../../services/teacherService'

export default function CourseEditor({ classroomId, courseId, onSuccess, onCancel }) {
  const [title, setTitle] = useState('')
  const [description, setDescription] = useState('')
  const [content, setContent] = useState('')
  const [isPublished, setIsPublished] = useState(false)
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)
  const [loading, setLoading] = useState(!!courseId)

  const isEdit = !!courseId

  useEffect(() => {
    if (!courseId) return
    let cancelled = false
    teacherService.getCourses()
      .then((courses) => {
        const course = courses.find((c) => c.id === courseId)
        if (course && !cancelled) {
          setTitle(course.title ?? '')
          setDescription(course.description ?? '')
          setContent(course.content ?? '')
          setIsPublished(course.is_published ?? false)
        }
      })
      .catch(() => { if (!cancelled) setError('Impossible de charger le cours') })
      .finally(() => { if (!cancelled) setLoading(false) })
    return () => { cancelled = true }
  }, [courseId])

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      if (isEdit) {
        await teacherService.updateCourse(courseId, {
          title,
          description: description || null,
          content: content || null,
          is_published: isPublished,
        })
      } else {
        await teacherService.createCourse(classroomId, {
          title,
          description: description || null,
          content: content || null,
        })
      }
      onSuccess?.()
    } catch (err) {
      setError(err.response?.data?.message ?? 'Erreur')
    } finally {
      setSubmitting(false)
    }
  }

  if (loading) return <div className="text-slate-600">Chargement…</div>

  return (
    <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <h3 className="text-lg font-bold text-slate-800 mb-4">
        {isEdit ? 'Modifier le cours' : 'Nouveau cours'}
      </h3>
      <form onSubmit={handleSubmit} className="space-y-4">
        {error && (
          <div className="rounded-lg bg-red-50 text-red-700 text-sm px-4 py-3">{error}</div>
        )}
        <div>
          <label className="block text-sm font-medium text-slate-700 mb-1">Titre *</label>
          <input
            type="text"
            required
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            className="w-full rounded-lg border border-slate-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
            placeholder="Titre du cours"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-slate-700 mb-1">Description</label>
          <textarea
            rows={2}
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            className="w-full rounded-lg border border-slate-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
            placeholder="Optionnel"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-slate-700 mb-1">Contenu (HTML ou texte)</label>
          <textarea
            rows={8}
            value={content}
            onChange={(e) => setContent(e.target.value)}
            className="w-full rounded-lg border border-slate-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none font-mono text-sm"
            placeholder="Contenu de la leçon…"
          />
        </div>
        {isEdit && (
          <div className="flex items-center gap-2">
            <input
              type="checkbox"
              id="course-published"
              checked={isPublished}
              onChange={(e) => setIsPublished(e.target.checked)}
              className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
            />
            <label htmlFor="course-published" className="text-sm text-slate-700">Publié (visible par les étudiants)</label>
          </div>
        )}
        <div className="flex gap-3">
          <button
            type="submit"
            disabled={submitting}
            className="px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-50"
          >
            {submitting ? 'Enregistrement…' : isEdit ? 'Enregistrer' : 'Créer le cours'}
          </button>
          <button type="button" onClick={onCancel} className="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
            Annuler
          </button>
        </div>
      </form>
    </div>
  )
}
