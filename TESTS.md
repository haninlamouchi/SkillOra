# 🧪 GUIDE DE TEST - SkillOra

## Test 1 : Visiteur Non Connecté ✅

### Actions
1. Aller sur `http://localhost:8000/`
2. Vérifier la navbar

### Résultat Attendu
- ✅ Page d'accueil publique affichée
- ✅ Navbar contient : Accueil, Formations, Projets, Forum, Contact
- ✅ Boutons "Connexion" et "S'inscrire" visibles
- ✅ Pas de dropdown "Mon Compte"
- ✅ Pas de cloche de notifications

---

## Test 2 : Inscription ✅

### Actions
1. Cliquer sur "S'inscrire"
2. Remplir le formulaire
3. Soumettre

### Résultat Attendu
- ✅ Redirection vers `/` (page d'accueil publique)
- ✅ Utilisateur créé avec `role = 'etudiant'` en base de données
- ✅ Message de succès (optionnel)

---

## Test 3 : Connexion ETUDIANT ✅

### Actions
1. Cliquer sur "Connexion"
2. Se connecter avec un compte étudiant
3. Observer la navbar

### Résultat Attendu
- ✅ Redirection vers `/home/{userId}` (ex: `/home/5`)
- ✅ Page d'accueil utilisateur affichée
- ✅ Navbar contient :
  - Accueil
  - Formations
  - Projets
  - **Dropdown "Forum"** avec :
    - Parcourir le Forum
    - Mes Publications
    - Nouvelle Publication
  - **Dropdown "Mon Compte"** avec :
    - Mon Profil
    - Modifier Profil
    - Déconnexion
  - **Pill utilisateur** : "Prénom (Etudiant)"
  - **Cloche de notifications** 🔔

---

## Test 4 : Connexion MEMBRE ✅

### Actions
1. Se connecter avec un compte membre
2. Observer la navbar

### Résultat Attendu
- ✅ Redirection vers `/home/{userId}`
- ✅ Navbar contient :
  - **Dropdown "Forum"** avec :
    - Parcourir le Forum
    - Mes Publications
    - Nouvelle Publication
  - **Dropdown "Mon Compte"** avec :
    - **Dashboard** ← Nouveau
    - Mon Profil
    - Modifier Profil
    - Déconnexion
  - **Pill utilisateur** : "Prénom (Membre)"

---

## Test 5 : Connexion RESPONSABLE_CLUB ✅

### Actions
1. Se connecter avec un compte responsable
2. Observer la navbar

### Résultat Attendu
- ✅ Redirection vers `/home/{userId}`
- ✅ Navbar contient :
  - **Dropdown "Forum"** avec :
    - Parcourir le Forum
    - Mes Publications
    - Nouvelle Publication
    - **Supervision Commentaires** ← Nouveau
  - **Dropdown "Mon Compte"** avec :
    - Dashboard
    - Mon Profil
    - Modifier Profil
    - Déconnexion
  - **Pill utilisateur** : "Prénom (Responsable)"

---

## Test 6 : Connexion ADMIN ✅

### Actions
1. Se connecter avec un compte admin
2. Observer la navbar

### Résultat Attendu
- ✅ Redirection vers `/home/{userId}`
- ✅ Navbar contient :
  - Accueil
  - **Tableau de Bord** ← Lien direct
  - **Dropdown "Forum"** avec :
    - Parcourir le Forum
  - **Dropdown "Admin"** avec :
    - Dashboard
    - Gestion Utilisateurs
    - Déconnexion
  - **Pill utilisateur** : "Prénom (Admin)"

---

## Test 7 : Navigation Forum ✅

### Actions (en tant qu'utilisateur connecté)
1. Cliquer sur "Forum" dans le dropdown
2. Vérifier l'accès

### Résultat Attendu
- ✅ Page forum affichée avec liste des publications
- ✅ Paramètre `?u={userId}` dans l'URL
- ✅ Navbar reste cohérente

---

## Test 8 : Accès Dashboard ✅

### Actions
1. **Membre** : Cliquer sur "Dashboard" dans dropdown "Mon Compte"
2. **Responsable** : Cliquer sur "Dashboard" dans dropdown "Mon Compte"
3. **Admin** : Cliquer sur "Tableau de Bord" ou "Dashboard" dans dropdown "Admin"

### Résultat Attendu
- ✅ Membre → `/membre/dashboard`
- ✅ Responsable → `/responsable/dashboard`
- ✅ Admin → `/admin/dashboard`
- ✅ Chaque dashboard affiche les bonnes informations

---

## Test 9 : Déconnexion ✅

### Actions
1. Cliquer sur "Déconnexion" dans le dropdown
2. Observer la redirection

### Résultat Attendu
- ✅ Redirection vers `/` (page d'accueil publique)
- ✅ Navbar redevient publique (Login/SignUp)
- ✅ Session utilisateur détruite

---

## Test 10 : Protection des Routes ✅

### Actions
1. Se déconnecter
2. Essayer d'accéder à `/admin/dashboard`
3. Essayer d'accéder à `/responsable/dashboard`
4. Essayer d'accéder à `/membre/dashboard`
5. Essayer d'accéder à `/forum`

### Résultat Attendu
- ✅ Redirection vers `/login` pour toutes ces routes
- ✅ Message d'erreur ou redirection automatique

---

## Test 11 : Hiérarchie des Rôles ✅

### Actions
1. Se connecter en tant qu'ADMIN
2. Essayer d'accéder à `/responsable/dashboard`
3. Essayer d'accéder à `/membre/dashboard`

### Résultat Attendu
- ✅ Admin peut accéder aux routes responsable
- ✅ Admin peut accéder aux routes membre
- ✅ Responsable peut accéder aux routes membre
- ❌ Membre ne peut PAS accéder aux routes responsable
- ❌ Etudiant ne peut PAS accéder aux routes membre

---

## 🐛 Problèmes Potentiels

### Problème : Navbar ne change pas après connexion
**Solution** : Vérifier que `_user` est bien résolu dans `base.html.twig`

### Problème : Redirection vers dashboard au lieu de home
**Solution** : Vérifier `LoginSuccessHandler` → doit rediriger vers `front_home_user`

### Problème : Erreur 403 Forbidden
**Solution** : Vérifier `security.yaml` → access_control

### Problème : Variable `_user` est null
**Solution** : Vérifier `AppExtension.php` → résolution de navUser

---

## 📝 Commandes Utiles

### Lister toutes les routes
```bash
php bin/console debug:router
```

### Vérifier la configuration de sécurité
```bash
php bin/console debug:security
```

### Vider le cache
```bash
php bin/console cache:clear
```

### Créer un utilisateur admin en base
```sql
UPDATE User SET role = 'admin' WHERE email = 'admin@example.com';
```

### Créer un utilisateur responsable
```sql
UPDATE User SET role = 'responsable_club' WHERE email = 'responsable@example.com';
```

### Créer un utilisateur membre
```sql
UPDATE User SET role = 'membre' WHERE email = 'membre@example.com';
```
