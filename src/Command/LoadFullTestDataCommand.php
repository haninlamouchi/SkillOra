<?php

namespace App\Command;

use App\Entity\Club;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:load-full-test-data',
    description: 'Charge un jeu complet de données de test (users, clubs, formations)',
)]
class LoadFullTestDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🚀 Chargement complet des données de test - SkillOra');

        // 1. CRÉER LES UTILISATEURS
        $io->section('👥 Création des utilisateurs');
        
        // Responsables de club
        $responsables = [];
        $responsables[] = $this->createUser('Dubois', 'Marie', 'marie.dubois@skillora.com', 'password123', 'responsable_club', '0612345678', new \DateTime('1985-05-15'));
        $responsables[] = $this->createUser('Martin', 'Pierre', 'pierre.martin@skillora.com', 'password123', 'responsable_club', '0687654321', new \DateTime('1982-11-22'));
        $responsables[] = $this->createUser('Bernard', 'Sophie', 'sophie.bernard@skillora.com', 'password123', 'responsable_club', '0623456789', new \DateTime('1990-03-08'));
        
        foreach ($responsables as $resp) {
            $this->em->persist($resp);
        }
        $this->em->flush();
        $io->success(sprintf('✅ %d responsables créés', count($responsables)));

        // Membres
        $membres = [];
        $membres[] = $this->createUser('Laurent', 'Emma', 'emma.laurent@student.skillora.com', 'password123', 'membre', '0698765432', new \DateTime('2000-06-12'));
        $membres[] = $this->createUser('Roux', 'Lucas', 'lucas.roux@student.skillora.com', 'password123', 'membre', '0656781234', new \DateTime('1999-09-25'));
        $membres[] = $this->createUser('Morel', 'Julie', 'julie.morel@student.skillora.com', 'password123', 'membre', '0634567890', new \DateTime('2001-02-18'));
        $membres[] = $this->createUser('Simon', 'Thomas', 'thomas.simon@student.skillora.com', 'password123', 'membre', '0645678901', new \DateTime('2000-12-03'));
        $membres[] = $this->createUser('Petit', 'Camille', 'camille.petit@student.skillora.com', 'password123', 'membre', '0678901234', new \DateTime('2001-07-30'));
        $membres[] = $this->createUser('Blanc', 'Alexandre', 'alexandre.blanc@student.skillora.com', 'password123', 'membre', '0689012345', new \DateTime('1999-04-14'));
        $membres[] = $this->createUser('Garcia', 'Léa', 'lea.garcia@student.skillora.com', 'password123', 'membre', '0690123456', new \DateTime('2001-01-20'));
        $membres[] = $this->createUser('Rousseau', 'Hugo', 'hugo.rousseau@student.skillora.com', 'password123', 'membre', '0612340987', new \DateTime('2000-08-15'));
        $membres[] = $this->createUser('Leroy', 'Sarah', 'sarah.leroy@student.skillora.com', 'password123', 'membre', '0687651234', new \DateTime('2001-11-05'));
        
        foreach ($membres as $membre) {
            $this->em->persist($membre);
        }
        $this->em->flush();
        $io->success(sprintf('✅ %d membres créés', count($membres)));

        // 2. CRÉER LES CLUBS
        $io->section('🏛️ Création des clubs');
        
        $clubs = [];
        $clubs[] = $this->createClub(
            'Club Développement Web',
            'Club dédié aux passionnés de développement web et technologies front-end/back-end. On explore React, Vue, Angular, Symfony, Laravel et plus encore !',
            $responsables[0],
            new \DateTime('2024-01-15')
        );
        
        $clubs[] = $this->createClub(
            'Club Data Science',
            'Club pour les amoureux de l\'analyse de données, machine learning et IA. Python, R, TensorFlow, PyTorch sont nos outils du quotidien.',
            $responsables[1],
            new \DateTime('2024-02-20')
        );
        
        $clubs[] = $this->createClub(
            'Club Cybersécurité',
            'Club spécialisé dans la sécurité informatique et la protection des données. Pentesting, CTF, forensic, et éthique hacker.',
            $responsables[2],
            new \DateTime('2024-03-01')
        );
        
        foreach ($clubs as $club) {
            $this->em->persist($club);
        }
        $this->em->flush();
        $io->success(sprintf('✅ %d clubs créés', count($clubs)));

        // 3. ASSIGNER LES MEMBRES AUX CLUBS
        $io->section('🔗 Association membres-clubs');
        
        // Club Dev Web: Emma, Lucas, Léa
        $clubs[0]->addMembre($membres[0]);
        $clubs[0]->addMembre($membres[1]);
        $clubs[0]->addMembre($membres[6]);
        
        // Club Data Science: Julie, Thomas, Hugo
        $clubs[1]->addMembre($membres[2]);
        $clubs[1]->addMembre($membres[3]);
        $clubs[1]->addMembre($membres[7]);
        
        // Club Cybersécurité: Camille, Alexandre, Sarah
        $clubs[2]->addMembre($membres[4]);
        $clubs[2]->addMembre($membres[5]);
        $clubs[2]->addMembre($membres[8]);
        
        $this->em->flush();
        $io->success('✅ Membres assignés aux clubs');

        // RÉSUMÉ FINAL
        $io->success('🎉 Données de base chargées avec succès !');
        
        $io->table(
            ['Type', 'Quantité'],
            [
                ['Responsables', count($responsables)],
                ['Membres', count($membres)],
                ['Clubs', count($clubs)],
                ['Membres par club', 3],
            ]
        );

        $io->section('🔐 Identifiants de connexion (tous: password123)');
        $io->listing([
            'Responsables:',
            '  marie.dubois@skillora.com (Club Développement Web)',
            '  pierre.martin@skillora.com (Club Data Science)',
            '  sophie.bernard@skillora.com (Club Cybersécurité)',
            '',
            'Membres Club Dev Web:',
            '  emma.laurent@student.skillora.com',
            '  lucas.roux@student.skillora.com',
            '  lea.garcia@student.skillora.com',
            '',
            'Membres Club Data Science:',
            '  julie.morel@student.skillora.com',
            '  thomas.simon@student.skillora.com',
            '  hugo.rousseau@student.skillora.com',
            '',
            'Membres Club Cybersécurité:',
            '  camille.petit@student.skillora.com',
            '  alexandre.blanc@student.skillora.com',
            '  sarah.leroy@student.skillora.com',
        ]);

        $io->note('Exécutez maintenant: php bin/console app:load-formation-test-data');

        return Command::SUCCESS;
    }

    private function createUser(
        string $nom,
        string $prenom,
        string $email,
        string $plainPassword,
        string $role,
        string $telephone,
        \DateTime $dateNaissance
    ): User {
        $user = new User();
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setEmail($email);
        $user->setRole($role);
        $user->setTelephone($telephone);
        $user->setDateNaissance($dateNaissance);
        $user->setDateInscription(new \DateTime());
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);
        
        return $user;
    }

    private function createClub(
        string $nom,
        string $description,
        User $responsable,
        \DateTime $dateCreation
    ): Club {
        $club = new Club();
        $club->setNom($nom);
        $club->setDescription($description);
        $club->setResponsable($responsable);
        $club->setDateCreation($dateCreation);
        
        return $club;
    }
}
