import { useState } from 'react'
import * as teacherService from '../../services/teacherService'

const QUESTION_TYPES = [
  { value: 'multiple_choice', label: 'QCM' },
  { value: 'true_false', label: 'Vrai / Faux' },
  { value: 'short_answer', label: 'Réponse courte' },
]

const emptyQuestion = () => ({
  questionText: '',
  type: 'multiple_choice',
  points: 1,
  options: ['', ''],
  correctAnswer: [],
})

function buildCorrectAnswer(q) {
  if (q.type === 'true_false') return [q.trueFalseAnswer === true]
  if (q.type === 'short_answer') return [q.shortAnswer?.trim()].filter(Boolean)
  if (q.type === 'multiple_choice') {
    const opts = (q.options || []).filter(Boolean)
    const idx = opts.indexOf(q.multipleChoiceAnswer)
    return idx >= 0 ? [idx] : []
  }
  return []
}

function buildOptions(q) {
  if (q.type === 'multiple_choice') return (q.options || []).filter(Boolean)
  return null
}

export default function ExamBuilder({ courseId, courseTitle, onSuccess, onCancel }) {
  const [step, setStep] = useState(1)
  const [title, setTitle] = useState('')
  const [description, setDescription] = useState('')
  const [duration, setDuration] = useState(30)
  const [passingScore, setPassingScore] = useState(60)
  const [examId, setExamId] = useState(null)
  const [questions, setQuestions] = useState([emptyQuestion()])
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  async function handleCreateExam(e) {
    e.preventDefault()
    setError('')
    if (!courseId) {
      setError('Cours introuvable.')
      return
    }
    setSubmitting(true)
    try {
      const exam = await teacherService.createExam(courseId, {
        title: (title || '').trim() || 'Examen',
        description: (description || '').trim() || null,
        duration: Math.max(1, Number(duration) || 30),
        passingScore: Math.min(100, Math.max(0, Number(passingScore) || 60)),
      })
      setExamId(exam.id)
      setStep(2)
    } catch (err) {
      const msg = err.response?.data?.message ?? err.response?.data?.error
      const details = err.response?.data?.errors
      const full = msg
        ? (Array.isArray(details) ? `${msg}: ${details.join(', ')}` : msg)
        : err.message || 'Erreur lors de la création de l\'examen.'
      setError(full)
    } finally {
      setSubmitting(false)
    }
  }

  function updateQuestion(index, field, value) {
    setQuestions((prev) => {
      const next = [...prev]
      next[index] = { ...next[index], [field]: value }
      if (field === 'type') {
        next[index].options = value === 'multiple_choice' ? ['', ''] : undefined
        next[index].correctAnswer = []
        next[index].multipleChoiceAnswer = undefined
        next[index].trueFalseAnswer = undefined
        next[index].shortAnswer = undefined
      }
      return next
    })
  }

  function addQuestion() {
    setQuestions((prev) => [...prev, emptyQuestion()])
  }

  function removeQuestion(index) {
    setQuestions((prev) => prev.filter((_, i) => i !== index))
  }

  async function handleAddQuestions(e) {
    e.preventDefault()
    setError('')
    const payload = questions
      .filter((q) => q.questionText.trim())
      .map((q) => ({
        questionText: q.questionText.trim(),
        type: q.type,
        points: Math.max(0, Number(q.points) || 1),
        options: buildOptions(q),
        correctAnswer: buildCorrectAnswer(q),
      }))
    if (payload.length === 0) {
      setError('Ajoutez au moins une question avec un libellé.')
      return
    }
    setSubmitting(true)
    try {
      await teacherService.addExamQuestions(examId, { questions: payload })
      onSuccess?.()
    } catch (err) {
      setError(err.response?.data?.message ?? 'Erreur lors de l’enregistrement des questions.')
    } finally {
      setSubmitting(false)
    }
  }

  if (step === 1) {
    return (
      <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6 max-w-lg">
        <h3 className="text-lg font-bold text-slate-800 mb-4">Nouvel examen · {courseTitle}</h3>
        <form onSubmit={handleCreateExam} className="space-y-4">
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
              className="w-full rounded-lg border border-slate-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"
              placeholder="Ex. Contrôle chapitre 1"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-slate-700 mb-1">Description</label>
            <input
              type="text"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              className="w-full rounded-lg border border-slate-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"
            />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Durée (min)</label>
              <input
                type="number"
                min={1}
                value={duration}
                onChange={(e) => setDuration(e.target.value)}
                className="w-full rounded-lg border border-slate-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-slate-700 mb-1">Score de réussite (%)</label>
              <input
                type="number"
                min={0}
                max={100}
                value={passingScore}
                onChange={(e) => setPassingScore(e.target.value)}
                className="w-full rounded-lg border border-slate-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500 outline-none"
              />
            </div>
          </div>
          <div className="flex gap-3">
            <button
              type="submit"
              disabled={submitting}
              className="px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-50"
            >
              {submitting ? 'Création…' : 'Créer l’examen'}
            </button>
            <button type="button" onClick={onCancel} className="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50">
              Annuler
            </button>
          </div>
        </form>
      </div>
    )
  }

  return (
    <div className="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
      <h3 className="text-lg font-bold text-slate-800 mb-4">Ajouter les questions</h3>
      <form onSubmit={handleAddQuestions} className="space-y-6">
        {error && (
          <div className="rounded-lg bg-red-50 text-red-700 text-sm px-4 py-3">{error}</div>
        )}
        {questions.map((q, index) => (
          <div key={index} className="p-4 rounded-lg border border-slate-200 bg-slate-50/50 space-y-3">
            <div className="flex justify-between items-start">
              <span className="text-sm font-medium text-slate-600">Question {index + 1}</span>
              <button
                type="button"
                onClick={() => removeQuestion(index)}
                className="text-sm text-red-600 hover:text-red-700"
              >
                Supprimer
              </button>
            </div>
            <input
              type="text"
              value={q.questionText}
              onChange={(e) => updateQuestion(index, 'questionText', e.target.value)}
              placeholder="Énoncé de la question"
              className="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm"
            />
            <div className="flex gap-4 flex-wrap">
              <div>
                <label className="block text-xs text-slate-500 mb-0.5">Type</label>
                <select
                  value={q.type}
                  onChange={(e) => updateQuestion(index, 'type', e.target.value)}
                  className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm"
                >
                  {QUESTION_TYPES.map((t) => (
                    <option key={t.value} value={t.value}>{t.label}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-xs text-slate-500 mb-0.5">Points</label>
                <input
                  type="number"
                  min={0}
                  value={q.points}
                  onChange={(e) => updateQuestion(index, 'points', e.target.value)}
                  className="w-20 rounded-lg border border-slate-300 px-3 py-1.5 text-sm"
                />
              </div>
            </div>
            {q.type === 'multiple_choice' && (
              <div className="space-y-2">
                <label className="block text-xs text-slate-500">Options (une par ligne, la première = indice 0)</label>
                {(q.options || ['', '']).map((opt, i) => (
                  <div key={i} className="flex gap-2 items-center">
                    <input
                      type="text"
                      value={opt}
                      onChange={(e) => {
                        const opts = [...(q.options || ['', ''])]
                        opts[i] = e.target.value
                        updateQuestion(index, 'options', opts)
                      }}
                      placeholder={`Option ${i + 1}`}
                      className="flex-1 rounded-lg border border-slate-300 px-3 py-1.5 text-sm"
                    />
                  </div>
                ))}
                <button
                  type="button"
                  onClick={() => updateQuestion(index, 'options', [...(q.options || ['', '']), ''])}
                  className="text-xs text-indigo-600 hover:text-indigo-700"
                >
                  + Ajouter une option
                </button>
                <div>
                  <label className="block text-xs text-slate-500 mb-0.5">Indice de la réponse correcte (0, 1, 2…)</label>
                  <input
                    type="number"
                    min={0}
                    value={q.multipleChoiceAnswer ?? ''}
                    onChange={(e) => updateQuestion(index, 'multipleChoiceAnswer', e.target.value === '' ? undefined : Number(e.target.value))}
                    className="w-20 rounded-lg border border-slate-300 px-3 py-1.5 text-sm"
                    placeholder="0"
                  />
                </div>
              </div>
            )}
            {q.type === 'true_false' && (
              <div>
                <label className="block text-xs text-slate-500 mb-0.5">Réponse correcte</label>
                <select
                  value={q.trueFalseAnswer === true ? 'true' : q.trueFalseAnswer === false ? 'false' : ''}
                  onChange={(e) => updateQuestion(index, 'trueFalseAnswer', e.target.value === 'true')}
                  className="rounded-lg border border-slate-300 px-3 py-1.5 text-sm"
                >
                  <option value="">—</option>
                  <option value="true">Vrai</option>
                  <option value="false">Faux</option>
                </select>
              </div>
            )}
            {q.type === 'short_answer' && (
              <div>
                <label className="block text-xs text-slate-500 mb-0.5">Réponse correcte attendue</label>
                <input
                  type="text"
                  value={q.shortAnswer ?? ''}
                  onChange={(e) => updateQuestion(index, 'shortAnswer', e.target.value)}
                  className="w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm"
                  placeholder="Texte attendu"
                />
              </div>
            )}
          </div>
        ))}
        <div className="flex flex-wrap gap-3">
          <button type="button" onClick={addQuestion} className="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">
            + Ajouter une question
          </button>
          <button
            type="submit"
            disabled={submitting}
            className="px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 disabled:opacity-50 text-sm"
          >
            {submitting ? 'Enregistrement…' : 'Enregistrer les questions'}
          </button>
          <button type="button" onClick={onSuccess} className="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 text-sm">
            Terminer sans ajouter
          </button>
        </div>
      </form>
    </div>
  )
}
