# 📋 ARCHITECTURE SKILLORA - Routes & Rôles

## 🎯 Flux d'Authentification

### 1. Visiteur Non Connecté
- **Route** : `/` (front_home)
- **Affichage** : Page d'accueil publique
- **Navbar** : Boutons "Connexion" et "S'inscrire"

### 2. Inscription
- **Route** : `/register` (app_register)
- **Rôle par défaut** : `ROLE_ETUDIANT`
- **Redirection** : Vers `/` (front_home)

### 3. Connexion
- **Route** : `/login` (app_login)
- **Handler** : `LoginSuccessHandler`
- **Redirection** : `/home/{userId}` (front_home_user) pour TOUS les rôles
- **Résultat** : Page d'accueil avec navbar adaptée au rôle

---

## 👥 Rôles et Permissions

### ROLE_ETUDIANT (par défaut)
- ✅ Accès au forum (lecture/écriture)
- ✅ Créer des publications (soumises à validation)
- ✅ Commenter
- ✅ Voir son profil
- ❌ Pas de dashboard spécifique

**Navbar Dropdown "Forum"** :
- Parcourir le Forum
- Mes Publications
- Nouvelle Publication

**Navbar Dropdown "Mon Compte"** :
- Mon Profil
- Modifier Profil
- Déconnexion

---

### ROLE_MEMBRE
- ✅ Tout ce que ROLE_ETUDIANT peut faire
- ✅ Dashboard membre
- ✅ Publications soumises à validation par responsable

**Navbar Dropdown "Forum"** :
- Parcourir le Forum
- Mes Publications
- Nouvelle Publication

**Navbar Dropdown "Mon Compte"** :
- Dashboard
- Mon Profil
- Modifier Profil
- Déconnexion

---

### ROLE_RESPONSABLE_CLUB
- ✅ Tout ce que ROLE_MEMBRE peut faire
- ✅ Dashboard responsable
- ✅ Publications publiées directement (sans validation)
- ✅ Valider les publications des membres
- ✅ Superviser les commentaires

**Navbar Dropdown "Forum"** :
- Parcourir le Forum
- Mes Publications
- Nouvelle Publication
- Supervision Commentaires

**Navbar Dropdown "Mon Compte"** :
- Dashboard
- Mon Profil
- Modifier Profil
- Déconnexion

---

### ROLE_ADMIN
- ✅ Accès complet à la plateforme
- ✅ Dashboard admin
- ✅ Gestion des utilisateurs
- ✅ Gestion des rôles

**Navbar** :
- Tableau de Bord

**Navbar Dropdown "Forum"** :
- Parcourir le Forum

**Navbar Dropdown "Admin"** :
- Dashboard
- Gestion Utilisateurs
- Déconnexion

---

## 🗺️ Routes Principales

### Routes Publiques (PUBLIC_ACCESS)
```
/                    → front_home (Page d'accueil publique)
/login               → app_login (Connexion)
/register            → app_register (Inscription)
/forgot-password     → Mot de passe oublié
/reset-password      → Réinitialisation mot de passe
```

### Routes Utilisateur Connecté (ROLE_USER)
```
/home/{userId}       → front_home_user (Page d'accueil utilisateur)
/home                → app_home (Redirige vers front_home_user)
/forum               → app_forum_index (Liste des publications)
/publication         → Gestion des publications
/user/{id}           → Profil utilisateur
```

### Routes Membre (ROLE_MEMBRE)
```
/membre/dashboard    → app_membre_dashboard
```

### Routes Responsable (ROLE_RESPONSABLE_CLUB)
```
/responsable/dashboard                    → app_responsable_dashboard
/responsable/publication                  → Gestion publications responsable
/responsable/commentaire/{userId}         → Supervision commentaires
```

### Routes Admin (ROLE_ADMIN)
```
/admin/dashboard     → app_admin_dashboard
/admin/users         → Gestion utilisateurs
```

---

## 🔄 Hiérarchie des Rôles

```
ROLE_ADMIN
  ├── ROLE_RESPONSABLE_CLUB
  │     ├── ROLE_MEMBRE
  │     │     └── ROLE_USER
  │     └── ROLE_USER
  ├── ROLE_MEMBRE
  │     └── ROLE_USER
  ├── ROLE_ETUDIANT
  │     └── ROLE_USER
  └── ROLE_USER

ROLE_RESPONSABLE_CLUB
  ├── ROLE_MEMBRE
  │     └── ROLE_USER
  └── ROLE_USER

ROLE_MEMBRE
  └── ROLE_USER

ROLE_ETUDIANT
  └── ROLE_USER
```

---

## 🎨 Comportement de la Navbar

### Variable `_user` dans Twig
La navbar utilise `_user` qui est résolu dans `base.html.twig` :
```twig
{% set _user = null %}
{% if currentUser is defined and currentUser %}
    {% set _user = currentUser %}
{% elseif navUser is defined and navUser %}
    {% set _user = navUser %}
{% endif %}
```

### Sources de `_user`
1. **currentUser** : Passé explicitement par le contrôleur
2. **navUser** : Résolu automatiquement par `AppExtension` via :
   - `userId` dans l'URL (`/home/{userId}`)
   - Paramètre `?u=` dans la query string
   - Session utilisateur

### Affichage Dynamique
La navbar change automatiquement selon `_user.role` :
- Si `_user` est null → Boutons Login/SignUp
- Si `_user.role == 'admin'` → Dropdowns Admin
- Si `_user.role == 'responsable_club'` → Dropdowns Responsable
- Si `_user.role == 'membre'` → Dropdowns Membre
- Si `_user.role == 'etudiant'` → Dropdowns Étudiant

---

## ✅ Checklist de Vérification

- [x] Inscription crée un utilisateur avec ROLE_ETUDIANT
- [x] Connexion redirige vers `/home/{userId}` pour tous les rôles
- [x] Navbar affiche les bons dropdowns selon le rôle
- [x] Les routes sont protégées par les bons rôles
- [x] La hiérarchie des rôles est correcte
- [x] Le logout redirige vers la page d'accueil publique
- [x] Les dashboards sont accessibles via les dropdowns
- [x] Le forum est accessible à tous les utilisateurs connectés

---

## 🐛 Problèmes Résolus

### ❌ Avant
- Connexion → Redirection vers dashboard selon le rôle
- Navbar ne changeait pas dynamiquement
- Routes mal organisées

### ✅ Après
- Connexion → Redirection vers `/home/{userId}` pour TOUS
- Navbar change automatiquement selon `_user.role`
- Routes claires et organisées
- Dashboards accessibles via navbar dropdown
