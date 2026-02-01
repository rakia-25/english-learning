# E-Learning Platform

Plateforme d'apprentissage en ligne (type Moodle) : **backend Symfony** + **frontend React**.

## Structure du projet

```
e-learning/
├── backend/     # API Symfony (PHP, MySQL, JWT, API Platform)
├── frontend/    # Interface React (Vite, TailwindCSS)
├── instructions.md
└── README.md
```

## Lancer le projet

### Backend (API)

```bash
cd backend
composer install
# Configurer .env (DATABASE_URL, JWT, etc.) si besoin
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load --no-interaction   # données de test
symfony serve
# ou : php -S localhost:8000 -t public
```

API : **http://localhost:8000**

### Frontend (React)

```bash
cd frontend
npm install
npm run dev
```

Interface : **http://localhost:5173**

Le frontend est configuré pour proxyer `/api` vers `http://localhost:8000` en dev.

## Comptes de test (fixtures)

- **Enseignants** : `teacher1@test.com` / `teacher2@test.com` — mot de passe : `password123`
- **Étudiants** : `student1@test.com` … `student5@test.com` — mot de passe : `password123`
