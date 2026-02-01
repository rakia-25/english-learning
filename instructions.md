# PROMPT COMPLET POUR CURSOR

Copie-colle ce prompt dans Cursor Composer (Cmd/Ctrl + K) :

---

⚠️ IMPORTANT : Procède ÉTAPE PAR ÉTAPE. Demande-moi confirmation avant de passer à l'étape suivante. Ne génère pas tout d'un coup.

Crée une plateforme d'apprentissage en ligne complète (type Moodle) avec Symfony 6.4 + MySQL + React.

## Stack technique
- Backend: Symfony 6.4, API Platform, MySQL 8.0, JWT (LexikJWT)
- Frontend: React 18 + Vite, TailwindCSS, React Router, Axios
- Database: MySQL via phpMyAdmin

## Fonctionnalités

### Rôles
- **Enseignants** : créent des classes (avec code unique), publient des cours, créent des examens avec questions
- **Étudiants** : rejoignent les classes avec un code, consultent les cours, passent les examens

### Examens randomisés
- Mêmes questions pour tous les étudiants mais ordre aléatoire
- Options des QCM aussi mélangées
- Types de questions : QCM, Vrai/Faux, Texte court
- Correction automatique avec score et résultats détaillés

## Entités principales (MySQL)

**User** : id (UUID), email (unique), password (hashé), firstName, lastName, roles (JSON), isVerified, createdAt, updatedAt

**Classroom** : id (UUID), name, description, code (8 chars unique auto-généré), teacher_id (FK User), isActive, createdAt
- Table liaison : classroom_student (ManyToMany avec User)

**Course** : id (UUID), title, description, content (HTML), classroom_id (FK), order, isPublished, publishedAt, createdAt

**Exam** : id (UUID), title, description, course_id (FK), duration (minutes), passingScore (%), isPublished, availableFrom, availableTo, createdAt

**Question** : id (UUID), exam_id (FK), questionText, questionType (enum: multiple_choice, true_false, short_answer), points, options (JSON), correctAnswer (JSON), order

**StudentExamAttempt** : id (UUID), student_id (FK User), exam_id (FK), startedAt, submittedAt, score (%), isPassed, questionOrder (JSON - ordre aléatoire)

**StudentAnswer** : id (UUID), attempt_id (FK), question_id (FK), answer (JSON), isCorrect, pointsEarned

**StudentProgress** : id (UUID), student_id (FK), course_id (FK), completedLessons (JSON), lastAccessedAt, progressPercentage

## API Endpoints à créer

### Auth
- POST /api/auth/register : {email, password, firstName, lastName, role} → {user, token}
- POST /api/auth/login : {email, password} → {user, token}
- GET /api/users/me → {user}

### Teacher
- GET /api/teacher/classrooms → liste mes classes
- POST /api/teacher/classrooms : {name, description} → crée classe avec code auto
- GET /api/teacher/classrooms/{id} → détail avec students et courses
- PATCH /api/teacher/classrooms/{id}
- DELETE /api/teacher/classrooms/{id}

- POST /api/teacher/classrooms/{id}/courses : {title, description, content}
- PATCH /api/teacher/courses/{id}
- DELETE /api/teacher/courses/{id}

- POST /api/teacher/courses/{id}/exams : {title, duration, passingScore}
- POST /api/teacher/exams/{id}/questions : {questions: [{questionText, type, points, options}]}
- GET /api/teacher/exams/{id}/results → tous les résultats + stats

### Student
- POST /api/student/classrooms/join : {code} → rejoint la classe
- GET /api/student/classrooms → mes classes
- GET /api/student/classrooms/{id}/courses → cours de la classe

- GET /api/student/courses/{id} → détail cours + progression
- POST /api/student/courses/{id}/complete

- GET /api/student/exams/{id}/start → crée tentative avec ordre randomisé, retourne questions
- POST /api/student/exams/{id}/submit : {attemptId, answers[]} → calcule score, retourne résultat
- GET /api/student/exams/{id}/result → voir son résultat détaillé
- GET /api/student/progress → progression globale

## Configuration requise

### Backend (.env)
```
DATABASE_URL="mysql://root:@127.0.0.1:3306/elearning?serverVersion=10.4.32-MariaDB&charset=utf8mb4"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
CORS_ALLOW_ORIGIN='^http://localhost:5173$'
```

### Packages Symfony
- api-platform/core
- symfony/orm-pack
- lexik/jwt-authentication-bundle
- nelmio/cors-bundle
- ramsey/uuid-doctrine
- vich/uploader-bundle
- symfony/maker-bundle (dev)

### Permissions (Symfony Voters)
- ClassroomVoter : VIEW (teacher propriétaire OU student inscrit), EDIT/DELETE (teacher uniquement)
- CourseVoter : VIEW (teacher OU student inscrit), EDIT/DELETE (teacher uniquement)

### Service clé : ExamRandomizerService
Méthode `generateAttempt(Exam $exam, User $student)` qui :
1. Récupère les questions de l'examen
2. Les mélange (shuffle)
3. Mélange aussi les options des QCM
4. Crée StudentExamAttempt avec questionOrder en JSON
5. Retourne l'attempt

### Frontend React

Structure :
```
frontend/
├── src/
│   ├── components/
│   │   ├── auth/ (Login, Register)
│   │   ├── teacher/ (ClassroomList, CourseEditor, ExamBuilder)
│   │   ├── student/ (Dashboard, CourseViewer, ExamTaking)
│   │   └── shared/ (Navbar, Sidebar)
│   ├── services/ (api.js, authService.js)
│   ├── hooks/ (useAuth.js)
│   ├── context/ (AuthContext.jsx)
│   └── App.jsx
```

Pages principales :
- Login/Register avec redirection selon rôle
- Teacher Dashboard : liste classes, créer classe, gérer cours/examens
- Student Dashboard : mes classes, rejoindre classe, consulter cours, passer examens
- ExamTaking : affiche questions une par une, timer, soumission

Gestion auth :
- JWT stocké dans localStorage
- Axios interceptor pour ajouter token aux requêtes
- Protected routes selon rôle

## Instructions - PROCÈDE ÉTAPE PAR ÉTAPE

🔴 **NE FAIS PAS TOUT D'UN COUP** - Arrête-toi après chaque étape et demande confirmation.

### ÉTAPE 1 : Initialisation du projet
1. Initialise le projet backend :
   - Backend : `symfony new backend --version=6.4` ou `composer create-project symfony/skeleton:"6.4.*" backend`
   - Installe webapp : `composer require webapp`

2. Initialise le projet frontend :
   - Frontend : `npm create vite@latest frontend -- --template react`

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 1 terminée. Dois-je continuer avec l'étape 2 (Installation des packages) ?"**

---

### ÉTAPE 2 : Installation des packages Symfony
Installe tous les packages nécessaires :
```bash
composer require api symfony/orm-pack
composer require lexik/jwt-authentication-bundle
composer require nelmio/cors-bundle
composer require ramsey/uuid-doctrine
composer require vich/uploader-bundle
composer require --dev symfony/maker-bundle
```

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 2 terminée. Dois-je continuer avec l'étape 3 (Configuration) ?"**

---

### ÉTAPE 3 : Configuration de base
1. Configure Doctrine pour MySQL avec UUID (ramsey/uuid-doctrine) dans `config/packages/doctrine.yaml`
2. Configure `.env` avec DATABASE_URL pour MySQL
3. Génère les clés JWT : `php bin/console lexik:jwt:generate-keypair`
4. Configure CORS dans `config/packages/nelmio_cors.yaml` pour autoriser http://localhost:5173
5. Configure JWT dans `config/packages/lexik_jwt_authentication.yaml`
6. Configure Security dans `config/packages/security.yaml`

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 3 terminée. Dois-je continuer avec l'étape 4 (Entités) ?"**

---

### ÉTAPE 4 : Création des entités
Crée SEULEMENT les entités suivantes (avec UUID, validation, API Platform) :
1. User (avec UserInterface)
2. Classroom
3. Course

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 4 terminée. Dois-je continuer avec l'étape 5 (Entités examens) ?"**

---

### ÉTAPE 5 : Entités pour les examens
Crée les entités restantes :
1. Exam
2. Question
3. StudentExamAttempt
4. StudentAnswer
5. StudentProgress

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 5 terminée. Dois-je continuer avec l'étape 6 (Migrations) ?"**

---

### ÉTAPE 6 : Migrations et base de données
1. Crée la base de données : `php bin/console doctrine:database:create`
2. Génère les migrations : `php bin/console make:migration`
3. Exécute les migrations : `php bin/console doctrine:migrations:migrate`

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 6 terminée. Dois-je continuer avec l'étape 7 (Fixtures) ?"**

---

### ÉTAPE 7 : Fixtures de test
Crée les fixtures avec :
- 2 teachers (teacher1@test.com, teacher2@test.com, password: password123)
- 5 students (student1@test.com à student5@test.com, password: password123)
- 2 classes avec codes
- 3 cours par classe
- 1 examen avec 5 questions variées par cours

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 7 terminée. Dois-je continuer avec l'étape 8 (Auth API) ?"**

---

### ÉTAPE 8 : API Authentification
Crée AuthController avec :
- POST /api/auth/register
- POST /api/auth/login (configure dans security.yaml)
- GET /api/users/me

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 8 terminée. Dois-je continuer avec l'étape 9 (Voters) ?"**

---

### ÉTAPE 9 : Système de permissions (Voters)
Crée les Voters :
- ClassroomVoter
- CourseVoter

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 9 terminée. Dois-je continuer avec l'étape 10 (API Teacher) ?"**

---

### ÉTAPE 10 : API Teacher - Classrooms
Crée TeacherClassroomController avec tous les endpoints CRUD classroom.

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 10 terminée. Dois-je continuer avec l'étape 11 (API Teacher Courses) ?"**

---

### ÉTAPE 11 : API Teacher - Courses
Crée TeacherCourseController avec tous les endpoints CRUD course.

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 11 terminée. Dois-je continuer avec l'étape 12 (API Student) ?"**

---

### ÉTAPE 12 : API Student - Classrooms et Courses
Crée StudentClassroomController et StudentCourseController.

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 12 terminée. Dois-je continuer avec l'étape 13 (Service Randomisation) ?"**

---

### ÉTAPE 13 : Service ExamRandomizerService
Crée le service de randomisation des examens.

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 13 terminée. Dois-je continuer avec l'étape 14 (API Examens Teacher) ?"**

---

### ÉTAPE 14 : API Teacher - Examens
Crée TeacherExamController avec création examen, ajout questions, résultats.

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 14 terminée. Dois-je continuer avec l'étape 15 (API Examens Student) ?"**

---

### ÉTAPE 15 : API Student - Examens
Crée StudentExamController avec start, submit, result.

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 15 terminée. Backend terminé ! Dois-je continuer avec l'étape 16 (Frontend React) ?"**

---

### ÉTAPE 16 : Frontend - Setup React
1. Initialise le frontend avec Vite
2. Installe les dépendances (react-router-dom, axios, tailwindcss)
3. Configure TailwindCSS
4. Crée la structure de dossiers

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 16 terminée. Dois-je continuer avec l'étape 17 (Auth Frontend) ?"**

---

### ÉTAPE 17 : Frontend - Authentification
Crée :
- AuthContext
- useAuth hook
- Pages Login et Register
- Service authService.js
- Configuration axios avec interceptor

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 17 terminée. Dois-je continuer avec l'étape 18 (Dashboard Teacher) ?"**

---

### ÉTAPE 18 : Frontend - Dashboard Teacher
Crée tous les composants teacher :
- ClassroomList, ClassroomForm
- CourseEditor
- ExamBuilder

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 18 terminée. Dois-je continuer avec l'étape 19 (Dashboard Student) ?"**

---

### ÉTAPE 19 : Frontend - Dashboard Student
Crée tous les composants student :
- Dashboard
- ClassroomJoin
- CourseViewer
- ExamTaking
- ProgressTracker

**→ ARRÊTE-TOI ICI ET DEMANDE : "Étape 19 terminée. Projet complet ! Veux-tu que je crée des tests ?"**

---

### ÉTAPE 20 (OPTIONNELLE) : Tests
Crée des tests PHPUnit pour le backend et des tests Jest pour le frontend.

## Conventions
- Backend : PSR-12, attributs PHP 8, type hints stricts
- Frontend : Functional components, hooks, TailwindCSS
- API : JSON, snake_case pour clés, codes HTTP standards
- UUID partout comme ID
- Validation stricte côté backend
- JAMAIS exposer les réponses correctes avant soumission examen

## Priorité
1. Auth (register, login, JWT)
2. Teacher : CRUD classrooms et courses
3. Student : join classroom, view courses
4. Examens complets avec randomisation
5. Frontend complet

Génère un projet propre, fonctionnel et prêt à l'emploi avec toute la logique métier implémentée.
