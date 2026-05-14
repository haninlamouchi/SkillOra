<div align="center">

<img src="https://img.shields.io/badge/Symfony-7.4-000000?style=for-the-badge&logo=symfony&logoColor=white" />
<img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" />
<img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" />
<img src="https://img.shields.io/badge/Doctrine%20ORM-orange?style=for-the-badge" />
<img src="https://img.shields.io/badge/Twig-3.x-brightgreen?style=for-the-badge" />

# 🎓 Skillora

### *Plateforme Intelligente de Gestion de Formations, Clubs & Communauté*

> A comprehensive educational platform built with Symfony 7.4, integrating AI-powered features, real-time notifications, and a rich community ecosystem.

---

</div>

## 📋 Table of Contents

- [About the Project](#-about-the-project)
- [Core Modules](#-core-modules)
  - [Gestion Utilisateurs](#-gestion-utilisateurs)
  - [Gestion Formation](#-gestion-formation)
  - [Gestion Challenges](#-gestion-challenges)
  - [Gestion Forum](#-gestion-forum)
  - [Gestion Club](#-gestion-club)
- [Tech Stack](#-tech-stack)
- [Key Integrations](#-key-integrations)
- [Project Structure](#-project-structure)
- [Role & Permission System](#-role--permission-system)
- [Getting Started](#-getting-started)
- [Environment Variables](#-environment-variables)
- [Contributors](#-contributors)

---

## 🌟 About the Project

**Skillora** is a full-featured educational web platform developed as part of the **PIDEV 3A — Esprit School of Engineering (2026)** project by team **57**. It brings together learning management, competitive challenges, community forums, and student clubs under one unified, AI-enhanced interface.

The platform supports multiple user roles — students, club members, club managers, formation managers, and administrators — each with tailored dashboards and access levels.

---

## 🧩 Core Modules

---

### 👤 Gestion Utilisateurs

Handles all aspects of user lifecycle management and authentication.

**Features:**
- 📝 **Registration & Login** — Secure sign-up with email verification via `symfonycasts/verify-email-bundle`
- 🔐 **OAuth2 / Google Login** — Social login integration via `knpuniversity/oauth2-client-bundle`
- 🔑 **Password Reset** — Token-based forgot/reset password flow
- 📸 **Profile Management** — Photo upload via Cloudinary, profile editing
- 🛡️ **Role-Based Access Control** — Hierarchical roles: `ROLE_ADMIN` > `ROLE_RESPONSABLE_CLUB` > `ROLE_MEMBRE` > `ROLE_ETUDIANT`
- 👥 **Admin User Management** — Full CRUD with role assignment, activation/suspension
- 📊 **Statistics Dashboard** — Admin analytics on users, roles, and activity
- 📱 **SMS Notifications** — Account alerts via Twilio
- 🧠 **Face Recognition** — Integrated face API for identity verification

---

### 📚 Gestion Formation

A complete Learning Management System (LMS) covering formation creation, enrollment, assessments, and AI-assisted learning.

**Features:**
- 🏫 **Formation Catalog** — Browse, filter, and search available formations
- 📂 **Video Content** — Upload and stream formation videos via Cloudinary
- 📋 **Enrollment System** — Student participation tracking with progress monitoring
- 💳 **Stripe Payments** — Secure paid formation enrollment via Stripe API
- 📄 **PDF Certificate Generation** — Automatic certificate issuance via `dompdf`
- 📅 **Calendar View** — Formation schedule via `tattali/calendar-bundle`
- 🤖 **AI Chatbot** — Context-aware assistant powered by Gemini / Groq APIs
- 🃏 **AI Flashcard Generation** — Auto-generated study cards from formation content
- 📝 **Exams & Quizzes** — Inline exam system with multi-choice questions and scoring
- 🏅 **Level Quiz System** — Adaptive difficulty quizzes with progress tracking
- 📢 **Real-time Notifications** — Mercure-based broadcast when new formations are published
- 📊 **Backoffice Analytics** — Google Charts integration for formation statistics

---

### 🏆 Gestion Challenges

A competitive challenge system that motivates students through structured tasks and deliverables.

**Features:**
- ➕ **Challenge Creation & Management** — Define challenges with deadlines, levels, and descriptions
- 📤 **Livrable Submission** — Students submit deliverables (`LivrableChallenge`) for evaluation
- 🧩 **Level-Based Challenges** — Difficulty tiers linked to the `Level` entity
- 👥 **Group Participation** — Team-based challenge participation via `Groupe` and `MembreGroupe`
- 🏅 **Scoring & Rankings** — Evaluation and leaderboard display
- 📋 **Admin Management** — Full backoffice CRUD for challenge lifecycle management
- 🔔 **Notifications** — Alerts for new challenges and submission deadlines

---

### 💬 Gestion Forum

A moderated community discussion platform with AI-assisted content tools.

**Features:**
- 📰 **Publication System** — Create, edit, and delete posts with image support
- 💬 **Comments** — Threaded commenting on publications
- ✅ **Moderation Workflow** — Publications from students/members require responsable approval before going public
- 🤖 **AI Content Suggestions** — `PublicationAIController` provides AI-driven writing assistance
- ❤️ **Favorites** — Bookmark publications via `Favori` / `Favorite` entities
- 🌐 **Translation** — Multi-language support via `TranslationController`
- 🔍 **Search & Filter** — Browse forum by topic, author, or date
- 📊 **Admin Supervision** — Full backoffice management: `AdminPublicationController`

**Role-Based Access:**
| Role | Capabilities |
|------|-------------|
| Étudiant | Read, write (pending approval), comment |
| Membre | Same as Étudiant + dashboard |
| Responsable Club | Publish directly, validate member posts, supervise comments |
| Admin | Full access, delete any content |

---

### 🏛️ Gestion Club

Manages student clubs, memberships, and internal communications.

**Features:**
- 🏢 **Club Creation & Management** — Create clubs with descriptions, logos (Cloudinary), and categories
- 📬 **Membership Requests** — Students submit `DemandeAdhesion` / `DemandeClub` to join clubs
- 🧑‍🤝‍🧑 **Member Management** — Approve/reject requests, manage member lists
- ⭐ **Member Evaluation** — `EvaluationMembre` system for peer/responsable feedback
- 🔔 **Club Notifications** — `NotificationClub` entity for in-club announcements
- 📊 **Club Dashboard** — Responsable & member dashboards with activity overview
- 🔑 **Role Management** — Promote members to `ROLE_RESPONSABLE_CLUB`

---

## 🛠️ Tech Stack

| Category | Technology |
|----------|-----------|
| **Framework** | Symfony 7.4 |
| **Language** | PHP 8.2+ |
| **ORM** | Doctrine ORM 3.x |
| **Templating** | Twig 3.x |
| **Database** | MySQL |
| **Frontend** | Bootstrap 5, Vanilla JS, Stimulus, Turbo |
| **Asset Pipeline** | Symfony AssetMapper + ImportMap |
| **Real-time** | Symfony Mercure |
| **Auth** | Symfony Security + OAuth2 (Google) |
| **File Storage** | Cloudinary |
| **Payments** | Stripe |
| **Email** | Symfony Mailer (Google SMTP) |
| **SMS** | Twilio SDK |
| **PDF** | DomPDF |
| **Charts** | Google Charts (`cmen/google-charts-bundle`) |
| **Pagination** | KnpPaginator |
| **Calendar** | TattaliCalendarBundle |
| **AI** | Google Gemini API, Groq API (via GuzzleHTTP) |
| **Testing** | PHPUnit 11, PHPStan 2 |

---

## 🔗 Key Integrations

| Integration | Purpose |
|------------|---------|
| 🤖 **Gemini / Groq AI** | Chatbot assistant, flashcard generation, publication AI suggestions |
| ☁️ **Cloudinary** | Image & video storage for formations, clubs, and profiles |
| 💳 **Stripe** | Secure payment processing for paid formations |
| 📧 **Gmail SMTP** | Email verification, password reset, and notifications |
| 📱 **Twilio** | SMS alerts for account and platform events |
| 🔄 **Mercure** | Server-Sent Events for real-time formation notifications |
| 🔐 **Google OAuth2** | Social login for fast registration/sign-in |
| 📊 **Google Charts** | Visual analytics in admin and responsable dashboards |

---

## 📁 Project Structure

```
Skillora/
├── src/
│   ├── Controller/          # All Symfony controllers (front + back)
│   ├── Entity/              # Doctrine entities (32 entities)
│   ├── Repository/          # Custom query repositories
│   ├── Form/                # Symfony form types
│   ├── Service/             # Business logic services (AI, Payment, etc.)
│   ├── Security/            # Custom authenticators & voters
│   ├── Event/               # Custom domain events
│   ├── EventSubscriber/     # Doctrine & kernel event subscribers
│   ├── Enum/                # PHP 8.1+ enumerations
│   └── Twig/                # Custom Twig extensions
├── templates/               # Twig templates (front & back)
├── migrations/              # Doctrine migration files
├── tests/                   # PHPUnit test suite
├── assets/                  # JavaScript & CSS assets
├── public/                  # Web root
├── config/                  # Symfony configuration
└── face_api/                # Face recognition API integration
```

---

## 🔐 Role & Permission System

```
ROLE_ADMIN
  └── Full platform access — user management, all dashboards, all modules

ROLE_RESPONSABLE_FORMATION
  └── Manage formations, exams, quizzes, certifications

ROLE_RESPONSABLE_CLUB
  └── Manage club, approve members, moderate forum publications

ROLE_MEMBRE
  └── Access club dashboard, submit posts (pending validation)

ROLE_ETUDIANT (default)
  └── Browse formations, enroll, take exams, participate in forum & challenges
```

---

## 🚀 Getting Started

### Prerequisites

- PHP **8.2+**
- Composer
- MySQL
- Symfony CLI *(optional but recommended)*
- Node.js *(for asset building)*

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/haninlamouchi/Esprit-PIDEV-3A57-2026-Skillora.git
cd Esprit-PIDEV-3A57-2026-Skillora

# 2. Install PHP dependencies
composer install

# 3. Configure environment variables
cp .env .env.local
# Edit .env.local with your DB credentials and API keys

# 4. Create the database and run migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Install JavaScript dependencies
php bin/console importmap:install

# 6. Start the development server
symfony server:start
# or
php -S localhost:8000 -t public/
```

---

## ⚙️ Environment Variables

Key variables to configure in your `.env.local`:

```env
# Database
DATABASE_URL="mysql://user:password@127.0.0.1:3306/skillora"

# Mailer
MAILER_DSN=gmail+smtp://your_email@gmail.com:app_password@default

# Cloudinary
CLOUDINARY_URL=cloudinary://api_key:api_secret@cloud_name

# Stripe
STRIPE_SECRET_KEY=sk_test_...
STRIPE_PUBLIC_KEY=pk_test_...

# Twilio
TWILIO_ACCOUNT_SID=...
TWILIO_AUTH_TOKEN=...
TWILIO_PHONE_NUMBER=...

# AI APIs
GEMINI_API_KEY=...
GROQ_API_KEY=...

# Mercure
MERCURE_URL=http://localhost:3000/.well-known/mercure
MERCURE_PUBLIC_URL=http://localhost:3000/.well-known/mercure
MERCURE_JWT_SECRET=...

# Google OAuth
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
```

---

## 👨‍💻 Contributors

| Name | Module | LinkedIn |
|------|--------|----------|
| **Malek Dabbek** | Gestion Utilisateurs | [LinkedIn](https://www.linkedin.com/in/malek-dabbek-8b5729408/) |
| **Eya Kouki** | Gestion Formation | [LinkedIn](https://www.linkedin.com/in/kouki-eya-746b0b337/) |
| **Maryem Khalfi** | Gestion Challenge | [LinkedIn](https://www.linkedin.com/in/MaryemKhalfi) |
| **Hanin Lamouchi** | Gestion Forum | [LinkedIn](https://www.linkedin.com/in/lamouchi-hanin-a1528027a/) |
| **Emna Boufarguine** | Gestion Club | [LinkedIn](https://www.linkedin.com/in/emna-boufarguine-b50a46336/) |

---

<div align="center">

Made with ❤️ by **404 NOT FOUND** — Esprit School of Engineering · PIDEV 3A57 · 2026

</div>
