-- ============================================
-- DONNÉES DE TEST - SkillOra
-- Gestion des Forums, Formations et Évaluations
-- ============================================

-- 1. INSERTION DES UTILISATEURS
-- ============================================

-- Responsables de club (mot de passe: password123 hashé)
-- Hash généré avec: password_hash('password123', PASSWORD_DEFAULT)
INSERT INTO `User` (`nom`, `prenom`, `email`, `password`, `role`, `date_inscription`, `telephone`, `date_naissance`) VALUES
('Dubois', 'Marie', 'marie.dubois@skillora.com', '$2y$13$YourHashedPasswordHere1', 'responsable_club', NOW(), '0612345678', '1985-05-15'),
('Martin', 'Pierre', 'pierre.martin@skillora.com', '$2y$13$YourHashedPasswordHere2', 'responsable_club', NOW(), '0687654321', '1982-11-22'),
('Bernard', 'Sophie', 'sophie.bernard@skillora.com', '$2y$13$YourHashedPasswordHere3', 'responsable_club', NOW(), '0623456789', '1990-03-08');

-- Membres / Étudiants
INSERT INTO `User` (`nom`, `prenom`, `email`, `password`, `role`, `date_inscription`, `telephone`, `date_naissance`) VALUES
('Laurent', 'Emma', 'emma.laurent@student.skillora.com', '$2y$13$YourHashedPasswordHere4', 'membre', NOW(), '0698765432', '2000-06-12'),
('Roux', 'Lucas', 'lucas.roux@student.skillora.com', '$2y$13$YourHashedPasswordHere5', 'membre', NOW(), '0656781234', '1999-09-25'),
('Morel', 'Julie', 'julie.morel@student.skillora.com', '$2y$13$YourHashedPasswordHere6', 'membre', NOW(), '0634567890', '2001-02-18'),
('Simon', 'Thomas', 'thomas.simon@student.skillora.com', '$2y$13$YourHashedPasswordHere7', 'membre', NOW(), '0645678901', '2000-12-03'),
('Petit', 'Camille', 'camille.petit@student.skillora.com', '$2y$13$YourHashedPasswordHere8', 'membre', NOW(), '0678901234', '2001-07-30'),
('Blanc', 'Alexandre', 'alexandre.blanc@student.skillora.com', '$2y$13$YourHashedPasswordHere9', 'membre', NOW(), '0689012345', '1999-04-14'),
('Garcia', 'Léa', 'lea.garcia@student.skillora.com', '$2y$13$YourHashedPasswordHere10', 'membre', NOW(), '0690123456', '2001-01-20'),
('Rousseau', 'Hugo', 'hugo.rousseau@student.skillora.com', '$2y$13$YourHashedPasswordHere11', 'membre', NOW(), '0612340987', '2000-08-15'),
('Leroy', 'Sarah', 'sarah.leroy@student.skillora.com', '$2y$13$YourHashedPasswordHere12', 'membre', NOW(), '0687651234', '2001-11-05');

-- 2. MISE À JOUR DES CLUBS (en supposant que les clubs existeront)
-- ============================================
-- Note: Vous devez d'abord obtenir les IDs des clubs depuis votre base

-- Exemple si les clubs ont les IDs suivants après insertion:
-- UPDATE Club SET responsable_id = (SELECT id_User FROM User WHERE email = 'marie.dubois@skillora.com') WHERE nom = 'Club Développement Web';
-- UPDATE Club SET responsable_id = (SELECT id_User FROM User WHERE email = 'pierre.martin@skillora.com') WHERE nom = 'Club Data Science';
-- UPDATE Club SET responsable_id = (SELECT id_User FROM User WHERE email = 'sophie.bernard@skillora.com') WHERE nom = 'Club Cybersécurité';

-- 3. ASSOCIATIONS MEMBRES-CLUBS (club_membre)
-- ============================================
-- Note: Remplacez [CLUB_ID] par les vrais IDs de vos clubs

-- Membres du Club Développement Web
-- INSERT INTO club_membre (id_Club, id_User) VALUES
-- ([ID_CLUB_DEV_WEB], (SELECT id_User FROM User WHERE email = 'emma.laurent@student.skillora.com')),
-- ([ID_CLUB_DEV_WEB], (SELECT id_User FROM User WHERE email = 'lucas.roux@student.skillora.com')),
-- ([ID_CLUB_DEV_WEB], (SELECT id_User FROM User WHERE email = 'lea.garcia@student.skillora.com'));

-- Membres du Club Data Science
-- INSERT INTO club_membre (id_Club, id_User) VALUES
-- ([ID_CLUB_DATA_SCIENCE], (SELECT id_User FROM User WHERE email = 'julie.morel@student.skillora.com')),
-- ([ID_CLUB_DATA_SCIENCE], (SELECT id_User FROM User WHERE email = 'thomas.simon@student.skillora.com')),
-- ([ID_CLUB_DATA_SCIENCE], (SELECT id_User FROM User WHERE email = 'hugo.rousseau@student.skillora.com'));

-- Membres du Club Cybersécurité
-- INSERT INTO club_membre (id_Club, id_User) VALUES
-- ([ID_CLUB_CYBERSEC], (SELECT id_User FROM User WHERE email = 'camille.petit@student.skillora.com')),
-- ([ID_CLUB_CYBERSEC], (SELECT id_User FROM User WHERE email = 'alexandre.blanc@student.skillora.com')),
-- ([ID_CLUB_CYBERSEC], (SELECT id_User FROM User WHERE email = 'sarah.leroy@student.skillora.com'));

-- 4. FORMATIONS
-- ============================================

-- Formations du Club Développement Web
INSERT INTO formation (titre, description, date_debut, date_fin, image, lien_ressources, club_id, terminee, is_paid, prix) VALUES
('React.js: De Débutant à Expert', 
'Formation complète sur React.js: composants, hooks, state management, routing, et optimisation des performances. Projet final inclus.', 
'2026-01-15', '2026-03-20', 
'react-formation.jpg', 
'https://react.dev/learn', 
NULL, -- Remplacer par l'ID du Club Dev Web
0, 1, 199.99),

('Symfony 6: Développement Web Moderne', 
'Maîtrisez Symfony 6 de A à Z: architecture MVC, Doctrine ORM, Twig, sécurité, API Platform, tests unitaires et déploiement.', 
'2026-02-01', '2026-04-30', 
'symfony-formation.jpg', 
'https://symfony.com/doc/current/index.html', 
NULL, -- Remplacer par l'ID du Club Dev Web
0, 1, 249.99),

('JavaScript ES6+: Les Fondamentaux', 
'Apprenez le JavaScript moderne: arrow functions, promises, async/await, modules, destructuring et bien plus encore.', 
'2026-03-10', '2026-05-15', 
'javascript-formation.jpg', 
'https://developer.mozilla.org/fr/docs/Web/JavaScript', 
NULL, -- Remplacer par l'ID du Club Dev Web
0, 0, 0.00);

-- Formations du Club Data Science
INSERT INTO formation (titre, description, date_debut, date_fin, image, lien_ressources, club_id, terminee, is_paid, prix) VALUES
('Python pour la Data Science', 
'Introduction à l\'analyse de données avec Python: NumPy, Pandas, Matplotlib, Seaborn. Projets d\'analyse de données réels inclus.', 
'2026-01-20', '2026-03-25', 
'python-datascience.jpg', 
'https://pandas.pydata.org/docs/', 
NULL, -- Remplacer par l'ID du Club Data Science
0, 1, 179.99),

('Machine Learning avec scikit-learn', 
'Découvrez le Machine Learning: régression, classification, clustering, validation de modèles. Mini-projets pratiques chaque semaine.', 
'2026-02-10', '2026-05-10', 
'ml-formation.jpg', 
'https://scikit-learn.org/stable/', 
NULL, -- Remplacer par l'ID du Club Data Science
0, 1, 299.99),

('Deep Learning avec TensorFlow', 
'Plongez dans les réseaux de neurones profonds: CNN, RNN, LSTM, Transfer Learning. Construisez des modèles d\'IA avancés.', 
'2026-03-01', '2026-06-30', 
'tensorflow-formation.jpg', 
'https://www.tensorflow.org/learn', 
NULL, -- Remplacer par l'ID du Club Data Science
0, 1, 399.99);

-- Formations du Club Cybersécurité
INSERT INTO formation (titre, description, date_debut, date_fin, image, lien_ressources, club_id, terminee, is_paid, prix) VALUES
('Introduction à la Cybersécurité', 
'Les fondamentaux de la sécurité informatique: menaces, vulnérabilités, cryptographie de base, et bonnes pratiques.', 
'2026-01-25', '2026-03-30', 
'cybersec-intro.jpg', 
'https://www.cybrary.it/', 
NULL, -- Remplacer par l'ID du Club Cybersécurité
0, 0, 0.00),

('Pentesting Web: Techniques Avancées', 
'Apprenez le pentesting web: OWASP Top 10, SQL Injection, XSS, CSRF. Pratique sur environnements sécurisés.', 
'2026-02-15', '2026-05-20', 
'pentesting-formation.jpg', 
'https://owasp.org/', 
NULL, -- Remplacer par l'ID du Club Cybersécurité
0, 1, 349.99),

('Sécurité des Réseaux et Systèmes', 
'Sécurisation des infrastructures: firewalls, VPN, IDS/IPS, hardening système, détection d\'intrusions.', 
'2026-03-05', '2026-06-05', 
'network-security.jpg', 
'https://www.sans.org/', 
NULL, -- Remplacer par l'ID du Club Cybersécurité
0, 1, 279.99);

-- 5. PARTICIPATIONS AUX FORMATIONS
-- ============================================

-- Inscriptions Club Dev Web
INSERT INTO participation_formation (user_id, formation_id, date_participation, payment_status) VALUES
((SELECT id_User FROM User WHERE email = 'emma.laurent@student.skillora.com'), 1, '2026-01-15 10:00:00', 1),
((SELECT id_User FROM User WHERE email = 'lucas.roux@student.skillora.com'), 1, '2026-01-15 10:30:00', 1),
((SELECT id_User FROM User WHERE email = 'lea.garcia@student.skillora.com'), 1, '2026-01-15 11:00:00', 1),
((SELECT id_User FROM User WHERE email = 'emma.laurent@student.skillora.com'), 2, '2026-02-01 09:00:00', 1),
((SELECT id_User FROM User WHERE email = 'lucas.roux@student.skillora.com'), 3, '2026-03-10 14:00:00', 0);

-- Inscriptions Club Data Science
INSERT INTO participation_formation (user_id, formation_id, date_participation, payment_status) VALUES
((SELECT id_User FROM User WHERE email = 'julie.morel@student.skillora.com'), 4, '2026-01-20 10:00:00', 1),
((SELECT id_User FROM User WHERE email = 'thomas.simon@student.skillora.com'), 4, '2026-01-20 10:15:00', 1),
((SELECT id_User FROM User WHERE email = 'hugo.rousseau@student.skillora.com'), 4, '2026-01-20 10:30:00', 1),
((SELECT id_User FROM User WHERE email = 'julie.morel@student.skillora.com'), 5, '2026-02-10 09:00:00', 1),
((SELECT id_User FROM User WHERE email = 'thomas.simon@student.skillora.com'), 5, '2026-02-10 09:15:00', 1),
((SELECT id_User FROM User WHERE email = 'thomas.simon@student.skillora.com'), 6, '2026-03-01 10:00:00', 1);

-- Inscriptions Club Cybersécurité
INSERT INTO participation_formation (user_id, formation_id, date_participation, payment_status) VALUES
((SELECT id_User FROM User WHERE email = 'camille.petit@student.skillora.com'), 7, '2026-01-25 10:00:00', 0),
((SELECT id_User FROM User WHERE email = 'alexandre.blanc@student.skillora.com'), 7, '2026-01-25 10:20:00', 0),
((SELECT id_User FROM User WHERE email = 'sarah.leroy@student.skillora.com'), 7, '2026-01-25 10:40:00', 0),
((SELECT id_User FROM User WHERE email = 'camille.petit@student.skillora.com'), 8, '2026-02-15 09:00:00', 1),
((SELECT id_User FROM User WHERE email = 'alexandre.blanc@student.skillora.com'), 8, '2026-02-15 09:20:00', 1),
((SELECT id_User FROM User WHERE email = 'alexandre.blanc@student.skillora.com'), 9, '2026-03-05 10:00:00', 1);

-- 6. PUBLICATIONS FORUM (exemples supplémentaires)
-- ============================================

INSERT INTO Publication (id_User, titre, contenu, type_contenu, status, date_publication) VALUES
((SELECT id_User FROM User WHERE email = 'emma.laurent@student.skillora.com'), 
'Retour sur la formation React', 
'Je viens de terminer la première semaine de la formation React et c\'est génial ! Les exercices sont très pratiques. Quelqu\'un a des conseils sur les custom hooks ?', 
'TEXTE', 'PUBLIE', DATE_SUB(NOW(), INTERVAL 2 DAY)),

((SELECT id_User FROM User WHERE email = 'julie.morel@student.skillora.com'), 
'Projet ML: Prédiction des prix immobiliers', 
'Mon projet de Machine Learning avance bien ! J\'ai atteint 89% de précision sur le dataset de Boston Housing. Prochaine étape: feature engineering avancé.', 
'TEXTE', 'PUBLIE', DATE_SUB(NOW(), INTERVAL 1 DAY)),

((SELECT id_User FROM User WHERE email = 'camille.petit@student.skillora.com'), 
'Atelier CTF ce weekend', 
'N\'oubliez pas le Capture The Flag de samedi ! Formez vos équipes. Au programme: web exploitation, crypto challenges et reverse engineering.', 
'TEXTE', 'PUBLIE', DATE_SUB(NOW(), INTERVAL 12 HOUR)),

((SELECT id_User FROM User WHERE email = 'lucas.roux@student.skillora.com'), 
'Aide: Problème avec Doctrine relations', 
'Je galère avec une relation ManyToMany dans Symfony. Quelqu\'un peut m\'expliquer la différence entre owning side et inverse side ?', 
'TEXTE', 'EN_ATTENTE', NOW());

-- 7. COMMENTAIRES
-- ============================================

INSERT INTO commentaire (id_User, id_Publication, contenu, date_commentaire) VALUES
((SELECT id_User FROM User WHERE email = 'lucas.roux@student.skillora.com'), 
(SELECT id_Publication FROM Publication WHERE titre = 'Retour sur la formation React' LIMIT 1), 
'Les custom hooks sont super utiles ! Regarde la doc officielle sur useMemo et useCallback, ça va te servir pour optimiser tes composants.', 
DATE_SUB(NOW(), INTERVAL 1 DAY)),

((SELECT id_User FROM User WHERE email = 'marie.dubois@skillora.com'), 
(SELECT id_Publication FROM Publication WHERE titre = 'Retour sur la formation React' LIMIT 1), 
'Content que la formation vous plaise Emma ! N\'hésitez pas si vous avez des questions. On peut organiser une session Q&A en direct cette semaine.', 
DATE_SUB(NOW(), INTERVAL 20 HOUR)),

((SELECT id_User FROM User WHERE email = 'thomas.simon@student.skillora.com'), 
(SELECT id_Publication FROM Publication WHERE titre = 'Projet ML: Prédiction des prix immobiliers' LIMIT 1), 
'Bravo Julie ! 89% c\'est excellent. Pour améliorer, as-tu essayé le polynomial features ou le stacking de plusieurs modèles ?', 
DATE_SUB(NOW(), INTERVAL 18 HOUR)),

((SELECT id_User FROM User WHERE email = 'alexandre.blanc@student.skillora.com'), 
(SELECT id_Publication FROM Publication WHERE titre = 'Atelier CTF ce weekend' LIMIT 1), 
'Je suis chaud pour le CTF ! Qui veut faire équipe avec moi ? Je me focus sur le web exploitation.', 
DATE_SUB(NOW(), INTERVAL 6 HOUR));

-- ============================================
-- NOTES D'UTILISATION
-- ============================================

/*
IMPORTANT: 
1. Les mots de passe ci-dessus sont des placeholders. 
   Utilisez la commande Symfony suivante pour générer de vrais hash:
   
   php bin/console security:hash-password password123

2. Remplacez les NULL dans les formations par les vrais ID des clubs:
   
   UPDATE formation SET club_id = [ID_CLUB] WHERE titre LIKE '%React%';

3. Pour les associations club_membre, décommentez et remplacez les [ID_CLUB] 
   par les vrais IDs de vos clubs.

4. Pour tester, connectez-vous avec:
   - marie.dubois@skillora.com / password123
   - emma.laurent@student.skillora.com / password123
   (etc.)

5. Vérification des données insérées:
   SELECT COUNT(*) FROM User;
   SELECT COUNT(*) FROM formation;
   SELECT COUNT(*) FROM participation_formation;
   SELECT COUNT(*) FROM Publication;
   SELECT COUNT(*) FROM commentaire;
*/
