<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EmbeddingService
{
    protected string $provider;
    protected string $model;
    protected int $dimensions;

    public function __construct()
    {
        $this->provider = config('curriculum_ai.embedding_provider');
        $this->model = config('curriculum_ai.embedding_model');
        $this->dimensions = config('curriculum_ai.embedding_dimensions');
    }

    /**
     * Generate embeddings for a text string
     *
     * @param string $text
     * @return array|null
     */
    public function generateEmbedding(string $text): ?array
    {
        if (empty(trim($text))) {
            return null;
        }

        return match ($this->provider) {
            'openai' => $this->generateOpenAIEmbedding($text),
            'hf' => $this->generateHuggingFaceEmbedding($text),
            'local' => $this->generateLocalEmbedding($text),
            default => throw new \InvalidArgumentException("Unsupported embedding provider: {$this->provider}"),
        };
    }

    /**
     * Generate embeddings for multiple texts
     *
     * @param array $texts
     * @return array
     */
    public function generateEmbeddings(array $texts): array
    {
        $embeddings = [];
        foreach ($texts as $text) {
            $embedding = $this->generateEmbedding($text);
            if ($embedding) {
                $embeddings[] = $embedding;
            }
        }
        return $embeddings;
    }

    /**
     * Generate embedding using OpenAI API
     */
    protected function generateOpenAIEmbedding(string $text): ?array
    {
        $apiKey = config('curriculum_ai.openai.api_key');
        if (!$apiKey) {
            Log::error('OpenAI API key not configured');
            return null;
        }

        try {
            $response = Http::timeout(config('curriculum_ai.openai.timeout'))
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/embeddings', [
                    'model' => $this->model,
                    'input' => $text,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'][0]['embedding'] ?? null;
            }

            Log::error('OpenAI embedding API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('OpenAI embedding exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate embedding using HuggingFace API
     */
    protected function generateHuggingFaceEmbedding(string $text): ?array
    {
        $apiKey = config('curriculum_ai.huggingface.api_key');
        $apiUrl = config('curriculum_ai.huggingface.api_url');

        try {
            $response = Http::timeout(config('curriculum_ai.huggingface.timeout'))
                ->withHeaders([
                    'Authorization' => $apiKey ? "Bearer {$apiKey}" : null,
                    'Content-Type' => 'application/json',
                ])
                ->post("{$apiUrl}/pipeline/feature-extraction/{$this->model}", [
                    'inputs' => $text,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                // HuggingFace returns array directly
                return is_array($data) ? $data : null;
            }

            Log::error('HuggingFace embedding API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('HuggingFace embedding exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Generate embedding using local model (sentence-transformers via Python API)
     * 
     * Assumes a local Python service running sentence-transformers
     * Example: FastAPI service that accepts POST /embed with {"text": "..."}
     */
    protected function generateLocalEmbedding(string $text): ?array
    {
        $endpoint = env('LOCAL_EMBEDDING_ENDPOINT', 'http://localhost:8001/embed');

        try {
            $response = Http::timeout(30)
                ->post($endpoint, [
                    'text' => $text,
                    'model' => $this->model,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['embedding'] ?? $data ?? null;
            }

            Log::error('Local embedding API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Local embedding exception', ['error' => $e->getMessage()]);
            // Fallback: return a zero vector (not ideal, but prevents errors)
            return array_fill(0, $this->dimensions, 0.0);
        }
    }

    /**
     * Store embedding in vector store
     *
     * @param int $curriculumDesignId
     * @param string $sourceType
     * @param int|null $sourceId
     * @param string $textSnippet
     * @param array $embedding
     * @param array $metadata
     * @return \App\Models\CurriculumEmbedding
     */
    public function storeEmbedding(
        int $curriculumDesignId,
        string $sourceType,
        ?int $sourceId,
        string $textSnippet,
        array $embedding,
        array $metadata = []
    ): \App\Models\CurriculumEmbedding {
        $vectorStore = config('curriculum_ai.vector_store');
        $vectorStoreId = null;

        // Store in external vector DB if configured
        if ($vectorStore !== 'pgvector') {
            $vectorStoreId = $this->storeInExternalVectorDB($vectorStore, $textSnippet, $embedding, $metadata);
        } else {
            // For pgvector, we'll store the embedding directly in the database
            // This requires the pgvector extension and a vector column
            // For now, we'll store as JSON and handle vector operations separately
        }

        return \App\Models\CurriculumEmbedding::create([
            'curriculum_design_id' => $curriculumDesignId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'text_snippet' => $textSnippet,
            'vector_store_id' => $vectorStoreId,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Store in external vector database (Weaviate, Milvus, Qdrant)
     */
    protected function storeInExternalVectorDB(string $store, string $text, array $embedding, array $metadata): ?string
    {
        // This is a placeholder - implement based on your vector DB choice
        // Example for Weaviate:
        // $url = config('curriculum_ai.vector_store_urls.weaviate');
        // $response = Http::post("{$url}/v1/objects", [...]);
        // return $response->json()['id'];

        Log::warning("External vector store storage not implemented for: {$store}");
        return null;
    }

    /**
     * Search for similar embeddings
     *
     * @param array $queryEmbedding
     * @param int $curriculumDesignId
     * @param int $topK
     * @return array
     */
    public function searchSimilar(array $queryEmbedding, int $curriculumDesignId, int $topK = null): array
    {
        $topK = $topK ?? config('curriculum_ai.top_k');
        $vectorStore = config('curriculum_ai.vector_store');

        if ($vectorStore === 'pgvector') {
            return $this->searchPgVector($queryEmbedding, $curriculumDesignId, $topK);
        }

        // For external stores, implement search logic
        return $this->searchExternalVectorDB($vectorStore, $queryEmbedding, $curriculumDesignId, $topK);
    }

    /**
     * Search using pgvector (cosine similarity)
     */
    protected function searchPgVector(array $queryEmbedding, int $curriculumDesignId, int $topK): array
    {
        // Check if pgvector extension is available
        $hasPgVector = DB::selectOne("SELECT EXISTS(SELECT 1 FROM pg_extension WHERE extname = 'vector')");

        if (!$hasPgVector || !$hasPgVector->exists) {
            // Fallback: simple text search or return empty
            Log::warning('pgvector extension not available, using fallback search');
            return \App\Models\CurriculumEmbedding::where('curriculum_design_id', $curriculumDesignId)
                ->limit($topK)
                ->get()
                ->toArray();
        }

        // Convert embedding array to PostgreSQL vector format
        $vectorString = '[' . implode(',', $queryEmbedding) . ']';

        // Use cosine similarity (1 - cosine_distance)
        $results = DB::select("
            SELECT 
                id,
                source_type,
                source_id,
                text_snippet,
                metadata,
                1 - (embedding <=> ?::vector) as similarity
            FROM curriculum_embeddings
            WHERE curriculum_design_id = ?
            ORDER BY embedding <=> ?::vector
            LIMIT ?
        ", [$vectorString, $curriculumDesignId, $vectorString, $topK]);

        return $results;
    }

    /**
     * Search in external vector database
     */
    protected function searchExternalVectorDB(string $store, array $queryEmbedding, int $curriculumDesignId, int $topK): array
    {
        // Placeholder - implement based on vector DB
        Log::warning("External vector store search not implemented for: {$store}");
        return [];
    }
}

