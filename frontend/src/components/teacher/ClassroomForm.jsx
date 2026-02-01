import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import * as teacherService from '../../services/teacherService'

export default function ClassroomForm({ classroomId, defaultValues, onSuccess, onCancel }) {
  const navigate = useNavigate()
  const [name, setName] = useState(defaultValues?.name ?? '')
  const [description, setDescription] = useState(defaultValues?.description ?? '')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  const isEdit = !!classroomId

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      if (isEdit) {
        await teacherService.updateClassroom(classroomId, { name, description })
      } else {
        await teacherService.createClassroom({ name, description })
      }
      onSuccess?.()
      if (!onSuccess) navigate('/teacher')
    } catch (err) {
      setError(err.response?.data?.message ?? 'Erreur')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 max-w-lg">
      <h2 className="text-xl font-bold text-slate-800 mb-4">
        {isEdit ? 'Modifier la classe' : 'Nouvelle classe'}
      </h2>
      <form onSubmit={handleSubmit} className="space-y-4">
        {error && (
          <div className="rounded-lg bg-red-50 text-red-700 text-sm px-4 py-3" role="alert">
            {error}
          </div>
        )}
        <div>
          <label htmlFor="classroom-name" className="block text-sm font-medium text-slate-700 mb-1">
            Nom *
          </label>
          <input
            id="classroom-name"
            type="text"
            required
            value={name}
            onChange={(e) => setName(e.target.value)}
            className="w-full rounded-lg border border-slate-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
            placeholder="Ex. Terminale S 2024"
          />
        </div>
        <div>
          <label htmlFor="classroom-desc" className="block text-sm font-medium text-slate-700 mb-1">
            Description
          </label>
          <textarea
            id="classroom-desc"
            rows={3}
            value={description}
            onChange={(e) => setDescription(e.target.value)}
            className="w-full rounded-lg border border-slate-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
            placeholder="Optionnel"
          />
        </div>
        <div className="flex gap-3">
          <button
            type="submit"
            disabled={submitting}
            className="px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-50"
          >
            {submitting ? 'Enregistrement…' : isEdit ? 'Enregistrer' : 'Créer'}
          </button>
          {onCancel && (
            <button type="button" onClick={onCancel} className="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
              Annuler
            </button>
          )}
          {!onCancel && (
            <button
              type="button"
              onClick={() => navigate('/teacher')}
              className="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50"
            >
              Annuler
            </button>
          )}
        </div>
      </form>
    </div>
  )
}
