<?php

namespace App\Service;

use Github\Client;
use Psr\Log\LoggerInterface;

class GithubService
{
    private Client $client;
    private LoggerInterface $logger;

    public function __construct(string $githubToken, LoggerInterface $logger)
    {
        $this->client = new Client();
        $this->client->authenticate($githubToken, null, Client::AUTH_ACCESS_TOKEN);
        $this->logger = $logger;
    }

    /**
     * Extrait le propriétaire et le nom du repo depuis une URL GitHub
     */
    public function parseGithubUrl(string $url): ?array
    {
        // Supporte : https://github.com/user/repo ou github.com/user/repo
        if (preg_match('#github\.com/([^/]+)/([^/]+)#', $url, $matches)) {
            return [
                'owner' => $matches[1],
                'repo' => rtrim($matches[2], '.git')
            ];
        }
        return null;
    }

    /**
     * Récupère les informations d'un repository
     */
    public function getRepositoryInfo(string $owner, string $repo): ?array
    {
        try {
            $repoInfo = $this->client->repo()->show($owner, $repo);
            
            return [
                'name' => $repoInfo['name'],
                'description' => $repoInfo['description'] ?? 'Aucune description',
                'language' => $repoInfo['language'] ?? 'Non spécifié',
                'stars' => $repoInfo['stargazers_count'],
                'forks' => $repoInfo['forks_count'],
                'created_at' => $repoInfo['created_at'],
                'updated_at' => $repoInfo['updated_at'],
                'default_branch' => $repoInfo['default_branch'],
                'size' => $repoInfo['size'], // en KB
                'url' => $repoInfo['html_url'],
            ];
        } catch (\Exception $e) {
            $this->logger->error('GitHub API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les commits d'un repository
     */
    public function getCommits(string $owner, string $repo, int $limit = 100): ?array
    {
        try {
            $commits = $this->client->repo()->commits()->all($owner, $repo, [
                'per_page' => $limit
            ]);
            
            return array_map(function($commit) {
                return [
                    'sha' => $commit['sha'],
                    'message' => $commit['commit']['message'],
                    'author' => $commit['commit']['author']['name'],
                    'date' => $commit['commit']['author']['date'],
                    'url' => $commit['html_url'],
                ];
            }, $commits);
        } catch (\Exception $e) {
            $this->logger->error('GitHub API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les statistiques des contributeurs
     */
    public function getContributorsStats(string $owner, string $repo): ?array
    {
        try {
            $contributors = $this->client->repo()->contributors($owner, $repo);
            
            $total = array_sum(array_column($contributors, 'contributions'));
            
            return array_map(function($contributor) use ($total) {
                return [
                    'username' => $contributor['login'],
                    'contributions' => $contributor['contributions'],
                    'percentage' => round(($contributor['contributions'] / $total) * 100, 1),
                    'avatar' => $contributor['avatar_url'],
                    'profile' => $contributor['html_url'],
                ];
            }, $contributors);
        } catch (\Exception $e) {
            $this->logger->error('GitHub API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les branches
     */
    public function getBranches(string $owner, string $repo): ?array
    {
        try {
            $branches = $this->client->repo()->branches($owner, $repo);
            
            return array_map(function($branch) {
                return [
                    'name' => $branch['name'],
                ];
            }, $branches);
        } catch (\Exception $e) {
            $this->logger->error('GitHub API Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie si un repository existe et est accessible
     */
    public function validateRepository(string $url): array
    {
        $parsed = $this->parseGithubUrl($url);
        
        if (!$parsed) {
            return [
                'valid' => false,
                'message' => 'URL GitHub invalide. Format attendu : https://github.com/user/repo'
            ];
        }
        
        $info = $this->getRepositoryInfo($parsed['owner'], $parsed['repo']);
        
        if (!$info) {
            return [
                'valid' => false,
                'message' => 'Repository introuvable ou privé. Vérifiez l\'URL et les permissions.'
            ];
        }
        
        return [
            'valid' => true,
            'owner' => $parsed['owner'],
            'repo' => $parsed['repo'],
            'info' => $info
        ];
    }
}