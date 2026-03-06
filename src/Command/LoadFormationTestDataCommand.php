<?php

namespace App\Command;

use App\Entity\Club;
use App\Entity\Formation;
use App\Entity\Level;
use App\Entity\ParticipationFormation;
use App\Entity\User;
use App\Entity\Video;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-formation-test-data',
    description: 'Charge des données de test pour les formations et évaluations',
)]
class LoadFormationTestDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('📚 Chargement des données de test pour les formations');

        // Récupérer les clubs existants
        $clubs = $this->em->getRepository(Club::class)->findAll();
        if (empty($clubs)) {
            $io->error('Aucun club trouvé. Veuillez d\'abord créer des clubs avec app:load-test-data');
            return Command::FAILURE;
        }

        // Récupérer les utilisateurs existants
        $users = $this->em->getRepository(User::class)->findAll();
        if (count($users) < 3) {
            $io->error('Pas assez d\'utilisateurs. Veuillez d\'abord créer des utilisateurs avec app:load-test-data');
            return Command::FAILURE;
        }

        $io->section('🎓 Création des formations');
        $formations = [];

        // Formation 1 - Club Développement Web
        $formations[] = $this->createFormation(
            'Formation React & Redux',
            'Apprenez à créer des applications React modernes avec Redux pour la gestion d\'état. Cette formation couvre les hooks, le routing, et les bonnes pratiques.',
            $clubs[0],
            new \DateTime('-30 days'),
            new \DateTime('+60 days'),
            false,
            true,
            49.99
        );

        $formations[] = $this->createFormation(
            'Symfony 6 Avancé',
            'Maîtrisez Symfony 6 : API Platform, Messenger, événements, tests unitaires et d\'intégration. Formation complète pour devenir expert Symfony.',
            $clubs[0],
            new \DateTime('-15 days'),
            new \DateTime('+45 days'),
            false,
            true,
            79.99
        );

        $formations[] = $this->createFormation(
            'Introduction au Web',
            'Formation gratuite pour débuter : HTML5, CSS3, JavaScript. Créez votre premier site web responsive.',
            $clubs[0],
            new \DateTime('-60 days'),
            new \DateTime('-10 days'),
            true,
            false,
            0
        );

        // Formation 2 - Club Data Science
        $formations[] = $this->createFormation(
            'Python pour la Data Science',
            'Maîtrisez Python, NumPy, Pandas, Matplotlib et Seaborn. Apprenez à analyser et visualiser des données.',
            $clubs[1],
            new \DateTime('-20 days'),
            new \DateTime('+40 days'),
            false,
            true,
            89.99
        );

        $formations[] = $this->createFormation(
            'Machine Learning Fondamentaux',
            'Introduction au Machine Learning : régression, classification, clustering. Utilisez scikit-learn pour vos premiers modèles.',
            $clubs[1],
            new \DateTime('-10 days'),
            new \DateTime('+50 days'),
            false,
            true,
            129.99
        );

        // Formation 3 - Club Cybersécurité
        $formations[] = $this->createFormation(
            'Sécurité Web Offensive',
            'Apprenez le pentesting web : SQL Injection, XSS, CSRF, SSRF. Utilisez Burp Suite, sqlmap et autres outils.',
            $clubs[2],
            new \DateTime('-25 days'),
            new \DateTime('+35 days'),
            false,
            true,
            149.99
        );

        foreach ($formations as $formation) {
            $this->em->persist($formation);
        }
        $this->em->flush();
        $io->success(sprintf('✅ %d formations créées', count($formations)));

        // Créer des niveaux pour chaque formation
        $io->section('📊 Création des niveaux (levels)');
        $allLevels = [];
        
        foreach ($formations as $index => $formation) {
            $levels = [];
            $levels[] = $this->createLevel($formation, 'Débutant', 'Niveau d\'introduction', 1);
            $levels[] = $this->createLevel($formation, 'Intermédiaire', 'Approfondissement des concepts', 2);
            $levels[] = $this->createLevel($formation, 'Avancé', 'Niveau expert', 3);
            
            foreach ($levels as $level) {
                $this->em->persist($level);
            }
            $allLevels[$index] = $levels;
        }
        $this->em->flush();
        $io->success('✅ Niveaux créés pour toutes les formations');

        // Créer des vidéos pour chaque formation
        $io->section('🎥 Création des vidéos');
        $videoCount = 0;
        
        // Vidéos pour Formation React
        $this->createVideo($formations[0], 'Introduction à React', 'Les bases de React et JSX', 'https://www.youtube.com/watch?v=intro_react');
        $this->createVideo($formations[0], 'Les composants', 'Créer des composants fonctionnels', 'https://www.youtube.com/watch?v=react_components');
        $this->createVideo($formations[0], 'Les Hooks', 'useState, useEffect et hooks personnalisés', 'https://www.youtube.com/watch?v=react_hooks');
        $this->createVideo($formations[0], 'Redux', 'Gestion d\'état avec Redux Toolkit', 'https://www.youtube.com/watch?v=redux_toolkit');
        $videoCount += 4;

        // Vidéos pour Formation Symfony
        $this->createVideo($formations[1], 'Architecture Symfony', 'MVC et structure des dossiers', 'https://www.youtube.com/watch?v=symfony_arch');
        $this->createVideo($formations[1], 'Doctrine ORM', 'Gestion de base de données', 'https://www.youtube.com/watch?v=doctrine_orm');
        $this->createVideo($formations[1], 'API Platform', 'Créer des API REST', 'https://www.youtube.com/watch?v=api_platform');
        $videoCount += 3;

        // Vidéos pour Formation Python Data Science
        $this->createVideo($formations[3], 'Bases Python', 'Syntaxe et structures de données', 'https://www.youtube.com/watch?v=python_basics');
        $this->createVideo($formations[3], 'NumPy et Pandas', 'Manipulation de données', 'https://www.youtube.com/watch?v=numpy_pandas');
        $this->createVideo($formations[3], 'Visualisation', 'Matplotlib et Seaborn', 'https://www.youtube.com/watch?v=matplotlib');
        $videoCount += 3;

        $this->em->flush();
        $io->success(sprintf('✅ %d vidéos créées', $videoCount));

        // Créer des quiz avec questions (DÉSACTIVÉ TEMPORAIREMENT)
        $io->section('📝 Quiz et questions');
        $io->warning('Les quiz sont désactivés dans cette version - nécessite ajustements des entités');
        $quizzes = [];

        // Inscrire des utilisateurs aux formations
        $io->section('👥 Inscription des membres aux formations');
        $participations = [];
        
        // Récupérer des membres
        $membres = $this->em->getRepository(User::class)->findBy(['role' => 'membre']);
        
        if (count($membres) >= 3) {
            // Membre 1 participe à React et Symfony
            $participations[] = $this->createParticipation($membres[0], $formations[0], new \DateTime('-28 days'), true);
            $participations[] = $this->createParticipation($membres[0], $formations[1], new \DateTime('-12 days'), true);
            
            // Membre 2 participe à Python Data Science
            $participations[] = $this->createParticipation($membres[1], $formations[3], new \DateTime('-18 days'), true);
            
            // Membre 3 participe à Cybersécurité
            $participations[] = $this->createParticipation($membres[2], $formations[5], new \DateTime('-20 days'), true);
            
            // Ajout de plus de participations
            if (count($membres) >= 6) {
                $participations[] = $this->createParticipation($membres[3], $formations[0], new \DateTime('-25 days'), true);
                $participations[] = $this->createParticipation($membres[4], $formations[3], new \DateTime('-15 days'), true);
                $participations[] = $this->createParticipation($membres[5], $formations[5], new \DateTime('-10 days'), false);
            }
        }
        
        foreach ($participations as $participation) {
            $this->em->persist($participation);
        }
        $this->em->flush();
        $io->success(sprintf('✅ %d participations créées', count($participations)));

        // Résultats de quiz désactivés (quiz désactivés)
        $resultats = [];

        // Résumé final
        $io->success('🎉 Données de test pour les formations chargées avec succès !');
        
        $io->table(
            ['Type', 'Quantité'],
            [
                ['Formations', count($formations)],
                ['Niveaux', count($formations) * 3],
                ['Vidéos', $videoCount],
                ['Quiz', count($quizzes)],
                ['Questions', 0],
                ['Participations', count($participations)],
                ['Résultats Quiz', count($resultats)],
            ]
        );

        $io->section('📋 Formations créées');
        foreach ($formations as $formation) {
            $club = $formation->getClub() ? $formation->getClub()->getNom() : 'Aucun club';
            $prix = $formation->isIsPaid() ? $formation->getPrix() . '€' : 'Gratuit';
            $io->text(sprintf('  • <info>%s</info> (%s) - %s', $formation->getTitre(), $club, $prix));
        }

        return Command::SUCCESS;
    }

    private function createFormation(
        string $titre,
        string $description,
        Club $club,
        \DateTime $dateDebut,
        \DateTime $dateFin,
        bool $terminee,
        bool $isPaid,
        float $prix
    ): Formation {
        $formation = new Formation();
        $formation->setTitre($titre);
        $formation->setDescription($description);
        $formation->setClub($club);
        $formation->setDateDebut($dateDebut);
        $formation->setDateFin($dateFin);
        $formation->setTerminee($terminee);
        $formation->setIsPaid($isPaid);
        $formation->setPrix($prix);
        
        return $formation;
    }

    private function createLevel(Formation $formation, string $titre, string $description, int $numero): Level
    {
        $level = new Level();
        $level->setFormation($formation);
        $level->setTitre($titre);
        $level->setDescription($description);
        $level->setNumero($numero);
        
        return $level;
    }

    private function createVideo(
        Formation $formation,
        string $titre,
        string $description,
        string $videoPath
    ): Video {
        $video = new Video();
        $video->setFormation($formation);
        $video->setTitre($titre);
        $video->setDescription($description);
        $video->setVideoPath($videoPath);
        
        $this->em->persist($video);
        return $video;
    }

    private function createParticipation(
        User $user,
        Formation $formation,
        \DateTime $dateParticipation,
        bool $paymentStatus
    ): ParticipationFormation {
        $participation = new ParticipationFormation();
        $participation->setUser($user);
        $participation->setFormation($formation);
        $participation->setDateParticipation($dateParticipation);
        $participation->setPaymentStatus($paymentStatus);
        return $participation;
    }
}
