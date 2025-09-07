<?php

namespace App\Services;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class ElasticsearchService
{
    protected Client $client;
    protected array $config;
    protected bool $isConnected = false;

    public function __construct(array $config = null)
    {
        $this->config = $config ?? config('elasticsearch', []);

        try {
            $this->client = $this->createClient();
            $this->testConnection();
        } catch (Exception $e) {
            Log::warning('Elasticsearch connection failed, falling back to file logging', [
                'error' => $e->getMessage(),
                'hosts' => $this->config['hosts']
            ]);

            if (!$this->config['fallback']['enabled']) {
                throw $e;
            }
        }
    }

    /**
     * Create Elasticsearch client with configuration
     */
    protected function createClient(): Client
    {
        $builder = ClientBuilder::create()
            ->setHosts($this->config['hosts'])
            ->setRetries($this->config['retries'])
            ->setElasticMetaHeader(false); // Disable automatic version detection

        // Authentication
        if (!empty($this->config['authentication']['username'])) {
            $builder->setBasicAuthentication(
                $this->config['authentication']['username'],
                $this->config['authentication']['password']
            );
        } elseif (!empty($this->config['authentication']['api_key'])) {
            $builder->setApiKey($this->config['authentication']['api_key']);
        }

        // SSL configuration
        if ($this->config['ssl']['enabled']) {
            $sslConfig = [
                'verify' => $this->config['ssl']['verification'],
            ];

            if ($this->config['ssl']['ca_bundle']) {
                $sslConfig['caBundle'] = $this->config['ssl']['ca_bundle'];
            }

            $builder->setSSLVerification($sslConfig['verify']);

            if (isset($sslConfig['caBundle'])) {
                $builder->setCABundle($sslConfig['caBundle']);
            }
        }

        return $builder->build();
    }

    /**
     * Test Elasticsearch connection
     */
    protected function testConnection(): bool
    {
        try {
            $info = $this->client->info();
            $this->isConnected = true;
            Log::info('Successfully connected to Elasticsearch', [
                'cluster_name' => $info['cluster_name'] ?? 'unknown',
                'version' => $info['version']['number'] ?? 'unknown'
            ]);
            return true;
        } catch (Exception $e) {
            $this->isConnected = false;
            throw $e;
        }
    }

    /**
     * Check if Elasticsearch is available
     */
    public function isAvailable(): bool
    {
        return $this->isConnected;
    }

    /**
     * Log survey submission to Elasticsearch
     */
    public function logSurveySubmission(array $data): bool
    {
        $indexName = $this->getIndexName('survey_submissions');

        // Prepare document for Elasticsearch
        $document = $this->prepareSurveyDocument($data);

        if ($this->isConnected) {
            return $this->indexDocument($indexName, $document);
        } else {
            return $this->fallbackToFile($document);
        }
    }

    /**
     * Index document in Elasticsearch
     */
    protected function indexDocument(string $index, array $document): bool
    {
        try {
            // Ensure index exists
            $this->ensureIndexExists($index);

            $params = [
                'index' => $index,
                'body' => $document,
            ];

            $response = $this->client->index($params);

            if (isset($response['_id'])) {
                Log::info('Survey submission logged to Elasticsearch', [
                    'index' => $index,
                    'id' => $response['_id'],
                    'survey_id' => $document['survey']['id'] ?? null
                ]);
                return true;
            }

            return false;
        } catch (Exception $e) {
            Log::error('Failed to index survey submission in Elasticsearch', [
                'error' => $e->getMessage(),
                'index' => $index,
                'survey_id' => $document['survey']['id'] ?? null
            ]);

            // Fallback to file logging
            return $this->fallbackToFile($document);
        }
    }

    /**
     * Ensure index exists with proper mapping
     */
    protected function ensureIndexExists(string $index): void
    {
        try {
            $exists = $this->client->indices()->exists(['index' => $index]);

            if (!$exists) {
                $this->createIndex($index);
            }
        } catch (Exception $e) {
            Log::warning('Could not verify/create Elasticsearch index', [
                'index' => $index,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create index with mapping
     */
    protected function createIndex(string $index): void
    {
        $mapping = [
            'mappings' => [
                'properties' => [
                    'timestamp' => ['type' => 'date'],
                    'survey' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'title' => ['type' => 'text'],
                            'description' => ['type' => 'text'],
                        ]
                    ],
                    'responder' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'email' => ['type' => 'keyword'],
                        ]
                    ],
                    'answers' => [
                        'type' => 'nested',
                        'properties' => [
                            'question_id' => ['type' => 'integer'],
                            'response' => ['type' => 'text'],
                            'submitted_at' => ['type' => 'date'],
                        ]
                    ],
                    'metadata' => [
                        'type' => 'object',
                        'properties' => [
                            'total_questions' => ['type' => 'integer'],
                            'user_agent' => ['type' => 'text'],
                            'ip_address' => ['type' => 'ip'],
                        ]
                    ]
                ]
            ]
        ];

        $this->client->indices()->create([
            'index' => $index,
            'body' => $mapping
        ]);

        Log::info('Created Elasticsearch index', ['index' => $index]);
    }

    /**
     * Prepare survey document for Elasticsearch
     */
    protected function prepareSurveyDocument(array $data): array
    {
        return [
            'timestamp' => $data['timestamp'] ?? now()->toISOString(),
            'survey' => [
                'id' => $data['survey']['id'] ?? null,
                'title' => $data['survey']['title'] ?? '',
                'description' => $data['survey']['description'] ?? '',
            ],
            'responder' => [
                'id' => $data['responder']['id'] ?? null,
                'email' => $data['responder']['email'] ?? '',
            ],
            'answers' => array_map(function ($answer) {
                return [
                    'question_id' => $answer['question_id'] ?? null,
                    'response' => $answer['response'] ?? '',
                    'submitted_at' => $answer['submitted_at'] ?? now()->toISOString(),
                ];
            }, $data['answers'] ?? []),
            'metadata' => [
                'total_questions' => $data['metadata']['total_questions'] ?? 0,
                'user_agent' => $data['metadata']['user_agent'] ?? '',
                'ip_address' => $data['metadata']['ip_address'] ?? '',
            ]
        ];
    }

    /**
     * Fallback to file logging when Elasticsearch is unavailable
     */
    protected function fallbackToFile(array $document): bool
    {
        if (!$this->config['fallback']['enabled']) {
            return false;
        }

        try {
            $fallbackPath = $this->config['fallback']['path'];
            $date = now()->format('Y-m-d');
            $filename = "elasticsearch-fallback-{$date}.json";

            // Create directory if it doesn't exist (using PHP directly for storage/logs path)
            if (!file_exists($fallbackPath)) {
                mkdir($fallbackPath, 0755, true);
            }

            $filePath = $fallbackPath . DIRECTORY_SEPARATOR . $filename;

            // Read existing data or create empty array
            $existingData = [];
            if (file_exists($filePath)) {
                $existingContent = file_get_contents($filePath);
                $existingData = json_decode($existingContent, true) ?? [];
            }

            // Add new document
            $existingData[] = $document;

            // Save back to file
            file_put_contents($filePath, json_encode($existingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            Log::info('Survey submission logged to fallback file', [
                'file' => $filePath,
                'survey_id' => $document['survey']['id'] ?? null
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to log survey submission to fallback file', [
                'error' => $e->getMessage(),
                'survey_id' => $document['survey']['id'] ?? null
            ]);
            return false;
        }
    }

    /**
     * Get full index name with prefix
     */
    protected function getIndexName(string $baseName): string
    {
        $prefix = $this->config['index']['prefix'] ?? '';
        return $prefix ? "{$prefix}_{$baseName}" : $baseName;
    }

    /**
     * Search survey submissions
     */
    public function searchSurveySubmissions(array $query = [], int $size = 10): array
    {
        if (!$this->isConnected) {
            return ['error' => 'Elasticsearch not available'];
        }

        try {
            $params = [
                'index' => $this->getIndexName('survey_submissions'),
                'body' => [
                    'query' => $query ?: ['match_all' => (object)[]],
                    'size' => $size,
                    'sort' => [
                        ['timestamp' => ['order' => 'desc']]
                    ]
                ]
            ];

            $response = $this->client->search($params);
            return $response->asArray();
        } catch (Exception $e) {
            Log::error('Failed to search survey submissions', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Get survey submission statistics
     */
    public function getSurveyStatistics(int $surveyId): array
    {
        if (!$this->isConnected) {
            return ['error' => 'Elasticsearch not available'];
        }

        try {
            $params = [
                'index' => $this->getIndexName('survey_submissions'),
                'body' => [
                    'query' => [
                        'term' => ['survey.id' => $surveyId]
                    ],
                    'aggs' => [
                        'total_submissions' => ['value_count' => ['field' => 'survey.id']],
                        'avg_questions_per_submission' => ['avg' => ['field' => 'metadata.total_questions']],
                        'submissions_over_time' => [
                            'date_histogram' => [
                                'field' => 'timestamp',
                                'calendar_interval' => 'day'
                            ]
                        ]
                    ]
                ]
            ];

            $response = $this->client->search($params);
            return $response->asArray();
        } catch (Exception $e) {
            Log::error('Failed to get survey statistics', [
                'error' => $e->getMessage(),
                'survey_id' => $surveyId
            ]);
            return ['error' => $e->getMessage()];
        }
    }
}
