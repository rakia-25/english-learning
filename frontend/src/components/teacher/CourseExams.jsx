import { useState, useEffect, useCallback } from 'react'
import { useParams, Link } from 'react-router-dom'
import * as teacherService from '../../services/teacherService'
import ExamBuilder from './ExamBuilder'

export default function CourseExams() {
  const { courseId } = useParams()
  const [course, setCourse] = useState(null)
  const [exams, setExams] = useState([])
  const [loading, setLoading] = useState(true)
  const [showExamBuilder, setShowExamBuilder] = useState(false)

  const loadCourse = useCallback(() => {
    if (!courseId) return Promise.resolve(null)
    return teacherService.getCourse(courseId).then(setCourse).catch(() => setCourse(null))
  }, [courseId])

  const loadExams = useCallback(() => {
    if (!courseId) return Promise.resolve([])
    return teacherService.getCourseExams(courseId).then(setExams).catch(() => setExams([]))
  }, [courseId])

  useEffect(() => {
    if (!courseId) {
      setCourse(null)
      setExams([])
      setLoading(false)
      return
    }
    let cancelled = false
    Promise.all([loadCourse(), loadExams()])
      .finally(() => { if (!cancelled) setLoading(false) })
    return () => { cancelled = true }
  }, [courseId, loadCourse, loadExams])

  function handleExamCreated() {
    setShowExamBuilder(false)
    loadExams()
  }

  if (loading) return <div className="text-slate-600">Chargement…</div>
  if (!course) return <div className="text-red-600">Cours introuvable.</div>

  return (
    <div>
      <div className="mb-4">
        <Link
          to={`/teacher/classrooms/${course.classroom_id}`}
          className="text-sm text-indigo-600 hover:text-indigo-500"
        >
          ← Retour à la classe
        </Link>
      </div>
      <h1 className="text-2xl font-bold text-slate-800 mb-2">Examens · {course.title}</h1>
      <p className="text-slate-600 mb-6">Créez un examen pour ce cours.</p>

      {showExamBuilder ? (
        <ExamBuilder
          courseId={courseId}
          courseTitle={course.title}
          onSuccess={handleExamCreated}
          onCancel={() => setShowExamBuilder(false)}
        />
      ) : (
        <>
          {exams.length > 0 && (
            <div className="mb-8">
              <h2 className="text-lg font-semibold text-slate-800 mb-3">Examens créés</h2>
              <ul className="space-y-3">
                {exams.map((exam) => (
                  <li
                    key={exam.id}
                    className="flex items-center justify-between p-4 rounded-xl bg-white border border-slate-200 shadow-sm"
                  >
                    <div>
                      <h3 className="font-medium text-slate-800">{exam.title}</h3>
                      <p className="text-sm text-slate-600 mt-0.5">
                        {exam.questions_count} question(s) · Durée {exam.duration} min · Réussite {exam.passing_score} %
                        {exam.is_published && (
                          <span className="ml-2 text-indigo-600">· Publié</span>
                        )}
                      </p>
                    </div>
                  </li>
                ))}
              </ul>
            </div>
          )}

          <button
            type="button"
            onClick={() => setShowExamBuilder(true)}
            className="px-4 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700"
          >
            Créer un examen
          </button>
        </>
      )}
    </div>
  )
}
