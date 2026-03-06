# 🗺️ CARTE DES ROUTES - SkillOra

```
┌─────────────────────────────────────────────────────────────────┐
│                        ROUTES PUBLIQUES                          │
└─────────────────────────────────────────────────────────────────┘

/                           → front_home (Page d'accueil publique)
/login                      → app_login (Connexion)
/register                   → app_register (Inscription)
/forgot-password            → Mot de passe oublié
/reset-password             → Réinitialisation


┌─────────────────────────────────────────────────────────────────┐
│                   ROUTES UTILISATEUR CONNECTÉ                    │
│                         (ROLE_USER)                              │
└─────────────────────────────────────────────────────────────────┘

/home                       → app_home (Redirige vers front_home_user)
/home/{userId}              → front_home_user (Page d'accueil utilisateur)
/logout                     → app_logout (Déconnexion)

┌─── FORUM ───┐
│ /forum                    → app_forum_index (Liste publications)
│ /forum/{id}               → app_forum_show (Détail publication)
│ /forum/{id}/edit          → Éditer commentaire
└─────────────┘

┌─── PUBLICATIONS ───┐
│ /publication                      → app_publication_index (Mes publications)
│ /publication/new/{id}             → app_publication_new (Nouvelle)
│ /publication/{id}                 → app_publication_show (Détail)
│ /publication/{id}/edit            → app_publication_edit (Éditer)
│ /publication/{id}/delete          → Supprimer
└────────────────────┘

┌─── PROFIL ───┐
│ /user/{id}                → app_user_show (Voir profil)
│ /user/{id}/edit           → app_user_edit (Modifier profil)
└──────────────┘


┌─────────────────────────────────────────────────────────────────┐
│                      ROUTES MEMBRE                               │
│                      (ROLE_MEMBRE)                               │
└─────────────────────────────────────────────────────────────────┘

/membre/dashboard           → app_membre_dashboard

+ Toutes les routes ROLE_USER


┌─────────────────────────────────────────────────────────────────┐
│                  ROUTES RESPONSABLE CLUB                         │
│                  (ROLE_RESPONSABLE_CLUB)                         │
└─────────────────────────────────────────────────────────────────┘

/responsable/dashboard      → app_responsable_dashboard

┌─── PUBLICATIONS RESPONSABLE ───┐
│ /responsable/publication                    → app_responsable_club_publication_index
│ /responsable/publication/new/{id}           → app_responsable_club_publication_new
│ /responsable/publication/{id}               → app_responsable_club_publication_show
│ /responsable/publication/{id}/edit          → Éditer
└────────────────────────────────┘

┌─── SUPERVISION COMMENTAIRES ───┐
│ /responsable/commentaire/{userId}           → app_responsable_club_commentaire_index
│ /responsable/commentaire/{id}/reply         → Répondre
└─────────────────────────────────┘

+ Toutes les routes ROLE_MEMBRE
+ Toutes les routes ROLE_USER


┌─────────────────────────────────────────────────────────────────┐
│                        ROUTES ADMIN                              │
│                       (ROLE_ADMIN)                               │
└─────────────────────────────────────────────────────────────────┘

/admin/dashboard            → app_admin_dashboard

┌─── GESTION UTILISATEURS ───┐
│ /admin/users                      → app_user_index (Liste)
│ /admin/users/new                  → app_user_new (Créer)
│ /admin/users/{id}                 → Détail
│ /admin/users/{id}/edit            → Éditer
│ /admin/users/{id}/delete          → Supprimer
│ /admin/users/role/{role}          → app_admin_users_by_role (Filtrer par rôle)
└────────────────────────────┘

+ Toutes les routes ROLE_RESPONSABLE_CLUB
+ Toutes les routes ROLE_MEMBRE
+ Toutes les routes ROLE_USER


┌─────────────────────────────────────────────────────────────────┐
│                    HIÉRARCHIE DES ACCÈS                          │
└─────────────────────────────────────────────────────────────────┘

PUBLIC_ACCESS
    ↓
ROLE_USER (ROLE_ETUDIANT)
    ↓
ROLE_MEMBRE
    ↓
ROLE_RESPONSABLE_CLUB
    ↓
ROLE_ADMIN (Accès complet)


┌─────────────────────────────────────────────────────────────────┐
│                  FLUX DE NAVIGATION TYPIQUE                      │
└─────────────────────────────────────────────────────────────────┘

┌─── VISITEUR ───┐
│ 1. Arrive sur /
│ 2. Voit la page d'accueil publique
│ 3. Clique sur "S'inscrire"
│ 4. Remplit /register
│ 5. Redirigé vers /
│ 6. Clique sur "Connexion"
│ 7. Remplit /login
│ 8. Redirigé vers /home/{userId}
└────────────────┘

┌─── ETUDIANT CONNECTÉ ───┐
│ 1. Sur /home/{userId}
│ 2. Navbar affiche dropdowns Etudiant
│ 3. Clique sur "Forum" → /forum?u={userId}
│ 4. Clique sur "Nouvelle Publication" → /publication/new/{userId}?u={userId}
│ 5. Clique sur "Mon Profil" → /user/{userId}
│ 6. Clique sur "Déconnexion" → /logout → /
└─────────────────────────┘

┌─── RESPONSABLE CONNECTÉ ───┐
│ 1. Sur /home/{userId}
│ 2. Navbar affiche dropdowns Responsable
│ 3. Clique sur "Dashboard" → /responsable/dashboard
│ 4. Clique sur "Supervision" → /responsable/commentaire/{userId}
│ 5. Clique sur "Forum" → /forum?u={userId}
│ 6. Clique sur "Déconnexion" → /logout → /
└────────────────────────────┘

┌─── ADMIN CONNECTÉ ───┐
│ 1. Sur /home/{userId}
│ 2. Navbar affiche dropdowns Admin
│ 3. Clique sur "Tableau de Bord" → /admin/dashboard
│ 4. Clique sur "Gestion Utilisateurs" → /admin/users
│ 5. Clique sur "Forum" → /forum?u={userId}
│ 6. Clique sur "Déconnexion" → /logout → /
└──────────────────────┘


┌─────────────────────────────────────────────────────────────────┐
│                    PARAMÈTRES D'URL                              │
└─────────────────────────────────────────────────────────────────┘

{userId}        → ID de l'utilisateur dans la route
?u={userId}     → ID de l'utilisateur en query string (pour le forum)

Exemples :
- /home/5                           → Page d'accueil de l'utilisateur 5
- /forum?u=5                        → Forum avec contexte utilisateur 5
- /publication/new/5?u=5            → Nouvelle publication pour user 5
- /user/5                           → Profil de l'utilisateur 5


┌─────────────────────────────────────────────────────────────────┐
│                  REDIRECTIONS AUTOMATIQUES                       │
└─────────────────────────────────────────────────────────────────┘

/home (connecté)        → /home/{userId}
/home (non connecté)    → /
/login (déjà connecté)  → /home/{userId}
/logout                 → /
Route protégée          → /login (si non connecté)


┌─────────────────────────────────────────────────────────────────┐
│                    COMMANDES UTILES                              │
└─────────────────────────────────────────────────────────────────┘

# Lister toutes les routes
php bin/console debug:router

# Filtrer les routes par nom
php bin/console debug:router | grep "forum"

# Voir les détails d'une route
php bin/console debug:router app_forum_index

# Vérifier la sécurité
php bin/console debug:security

# Vider le cache
php bin/console cache:clear
```
