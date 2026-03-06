<?php

namespace App\Tests\Controller;

use App\Entity\Publication;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PublicationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $publicationRepository;
    private string $path = '/publication/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->publicationRepository = $this->manager->getRepository(Publication::class);

        foreach ($this->publicationRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Publication index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'publication[titre]' => 'Testing',
            'publication[contenu]' => 'Testing',
            'publication[typecontenu]' => 'Testing',
            'publication[datePublication]' => 'Testing',
            'publication[status]' => 'Testing',
            'publication[user]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->publicationRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Publication();
        $fixture->setTitre('My Title');
        $fixture->setContenu('My Title');
        $fixture->setTypecontenu('My Title');
        $fixture->setDatePublication('My Title');
        $fixture->setStatus('My Title');
        $fixture->setUser('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Publication');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Publication();
        $fixture->setTitre('Value');
        $fixture->setContenu('Value');
        $fixture->setTypecontenu('Value');
        $fixture->setDatePublication('Value');
        $fixture->setStatus('Value');
        $fixture->setUser('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'publication[titre]' => 'Something New',
            'publication[contenu]' => 'Something New',
            'publication[typecontenu]' => 'Something New',
            'publication[datePublication]' => 'Something New',
            'publication[status]' => 'Something New',
            'publication[user]' => 'Something New',
        ]);

        self::assertResponseRedirects('/publication/');

        $fixture = $this->publicationRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getTitre());
        self::assertSame('Something New', $fixture[0]->getContenu());
        self::assertSame('Something New', $fixture[0]->getTypecontenu());
        self::assertSame('Something New', $fixture[0]->getDatePublication());
        self::assertSame('Something New', $fixture[0]->getStatus());
        self::assertSame('Something New', $fixture[0]->getUser());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Publication();
        $fixture->setTitre('Value');
        $fixture->setContenu('Value');
        $fixture->setTypecontenu('Value');
        $fixture->setDatePublication('Value');
        $fixture->setStatus('Value');
        $fixture->setUser('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/publication/');
        self::assertSame(0, $this->publicationRepository->count([]));
    }
}
