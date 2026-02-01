import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import { AuthProvider } from './context/AuthContext'
import ProtectedRoute from './components/shared/ProtectedRoute'
import TeacherRoute from './components/shared/TeacherRoute'
import Login from './components/auth/Login'
import Register from './components/auth/Register'
import Home from './components/auth/Home'
import TeacherDashboard from './components/teacher/TeacherDashboard'
import ClassroomList from './components/teacher/ClassroomList'
import ClassroomForm from './components/teacher/ClassroomForm'
import ClassroomDetail from './components/teacher/ClassroomDetail'
import CourseExams from './components/teacher/CourseExams'

function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/register" element={<Register />} />
          <Route
            path="/"
            element={
              <ProtectedRoute>
                <Home />
              </ProtectedRoute>
            }
          />
          <Route
            path="/teacher"
            element={
              <ProtectedRoute>
                <TeacherRoute>
                  <TeacherDashboard />
                </TeacherRoute>
              </ProtectedRoute>
            }
          >
            <Route index element={<ClassroomList />} />
            <Route path="classrooms/new" element={<ClassroomForm />} />
            <Route path="classrooms/:id" element={<ClassroomDetail />} />
            <Route path="courses/:courseId/exams" element={<CourseExams />} />
          </Route>
          <Route path="/student" element={<ProtectedRoute><div className="p-8">Espace étudiant (étape 19)</div></ProtectedRoute>} />
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  )
}

export default App
