<?php

namespace App\Tests\Service;

use App\Entity\Challenge;
use App\Service\ChallengeValidator;
use PHPUnit\Framework\TestCase;

class ChallengeValidatorTest extends TestCase
{
    /**
     * TEST 1 : Challenge valide complet
     */
    public function testChallengeValide()
    {
        $challenge = new Challenge();
        $challenge->setTitre('Challenge Symfony Avancé');
        $challenge->setDescription('Description complète du challenge.');
        $challenge->setDateDebut(new \DateTime('2026-01-01'));
        $challenge->setDateFin(new \DateTime('2026-01-31'));
        
        $validator = new ChallengeValidator();
        
        $this->assertTrue($validator->validate($challenge));
    }
    
    /**
     * TEST 2 : Titre vide
     */
    public function testTitreVide()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre du challenge est obligatoire');
        
        $challenge = new Challenge();
        $challenge->setTitre('');
        
        $validator = new ChallengeValidator();
        $validator->validateTitre($challenge);
    }
    
    /**
     * TEST 3 : Date de fin avant date de début
     */
    public function testDateFinAvantDateDebut()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début');
        
        $challenge = new Challenge();
        $challenge->setDateDebut(new \DateTime('2026-01-31'));
        $challenge->setDateFin(new \DateTime('2026-01-01'));
        
        $validator = new ChallengeValidator();
        $validator->validateDates($challenge);
    }
    
    /**
     * TEST 4 : Dates manquantes
     */
    public function testDatesManquantes()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Les dates de début et de fin sont obligatoires');
        
        $challenge = new Challenge();
        
        $validator = new ChallengeValidator();
        $validator->validateDates($challenge);
    }
    
    /**
     * TEST 5 : Description vide
     */
    public function testDescriptionVide()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description du challenge est obligatoire');
        
        $challenge = new Challenge();
        $challenge->setDescription('');
        
        $validator = new ChallengeValidator();
        $validator->validateDescription($challenge);
    }
    
    /**
     * TEST 6 : Description trop courte (moins de 10 caractères)
     */
    public function testDescriptionTropCourte()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description doit contenir au moins 10 caractères');
        
        $challenge = new Challenge();
        $challenge->setDescription('Court'); // Seulement 5 caractères
        
        $validator = new ChallengeValidator();
        $validator->validateDescription($challenge);
    }
    
    /**
     * TEST 7 : Description valide (exactement 10 caractères)
     */
    public function testDescriptionValide10Caracteres()
    {
        $challenge = new Challenge();
        $challenge->setDescription('1234567890'); // Exactement 10
        
        $validator = new ChallengeValidator();
        
        $this->assertTrue($validator->validateDescription($challenge));
    }
    
    /**
     * TEST 8 : Description valide (plus de 10 caractères)
     */
    public function testDescriptionValideLongue()
    {
        $challenge = new Challenge();
        $challenge->setDescription('Description complète et détaillée');
        
        $validator = new ChallengeValidator();
        
        $this->assertTrue($validator->validateDescription($challenge));
    }
    
    /**
     * TEST 9 : Dates égales
     */
    public function testDatesEgales()
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $challenge = new Challenge();
        $challenge->setDateDebut(new \DateTime('2026-01-15'));
        $challenge->setDateFin(new \DateTime('2026-01-15'));
        
        $validator = new ChallengeValidator();
        $validator->validateDates($challenge);
    }
}