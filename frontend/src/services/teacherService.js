import api from './api'

const prefix = '/teacher'

/** GET /api/teacher/classrooms */
export async function getClassrooms() {
  const { data } = await api.get(`${prefix}/classrooms`)
  return data.classrooms ?? []
}

/** POST /api/teacher/classrooms */
export async function createClassroom({ name, description }) {
  const { data } = await api.post(`${prefix}/classrooms`, { name, description: description || null })
  return data
}

/** GET /api/teacher/classrooms/{id} */
export async function getClassroom(id) {
  const { data } = await api.get(`${prefix}/classrooms/${id}`)
  return data
}

/** PATCH /api/teacher/classrooms/{id} */
export async function updateClassroom(id, payload) {
  const { data } = await api.patch(`${prefix}/classrooms/${id}`, payload)
  return data
}

/** DELETE /api/teacher/classrooms/{id} */
export async function deleteClassroom(id) {
  await api.delete(`${prefix}/classrooms/${id}`)
}

/** GET /api/teacher/courses */
export async function getCourses() {
  const { data } = await api.get(`${prefix}/courses`)
  return data.courses ?? []
}

/** GET /api/teacher/courses/{id} */
export async function getCourse(id) {
  const { data } = await api.get(`${prefix}/courses/${id}`)
  return data
}

/** POST /api/teacher/classrooms/{id}/courses */
export async function createCourse(classroomId, { title, description, content }) {
  const { data } = await api.post(`${prefix}/classrooms/${classroomId}/courses`, {
    title,
    description: description || null,
    content: content || null,
  })
  return data
}

/** PATCH /api/teacher/courses/{id} */
export async function updateCourse(id, payload) {
  const { data } = await api.patch(`${prefix}/courses/${id}`, payload)
  return data
}

/** DELETE /api/teacher/courses/{id} */
export async function deleteCourse(id) {
  await api.delete(`${prefix}/courses/${id}`)
}

/** GET /api/teacher/courses/{id}/exams */
export async function getCourseExams(courseId) {
  const { data } = await api.get(`${prefix}/courses/${courseId}/exams`)
  return data.exams ?? []
}

/** POST /api/teacher/courses/{id}/exams */
export async function createExam(courseId, { title, description, duration, passingScore }) {
  const { data } = await api.post(`${prefix}/courses/${courseId}/exams`, {
    title,
    description: description || null,
    duration: duration ?? 30,
    passingScore: passingScore ?? 60,
  })
  return data
}

/** POST /api/teacher/exams/{id}/questions */
export async function addExamQuestions(examId, { questions }) {
  const { data } = await api.post(`${prefix}/exams/${examId}/questions`, { questions })
  return data
}

/** GET /api/teacher/exams/{id}/results */
export async function getExamResults(examId) {
  const { data } = await api.get(`${prefix}/exams/${examId}/results`)
  return data
}
