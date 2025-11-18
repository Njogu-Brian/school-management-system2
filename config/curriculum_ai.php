<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Vector Store Configuration
    |--------------------------------------------------------------------------
    |
    | Supported options: 'pgvector', 'weaviate', 'milvus', 'qdrant'
    | Default: 'pgvector' (requires pgvector PostgreSQL extension)
    |
    */
    'vector_store' => env('CURRICULUM_VECTOR_STORE', 'pgvector'),

    /*
    |--------------------------------------------------------------------------
    | Embedding Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Supported options: 'openai', 'hf' (HuggingFace), 'local'
    | Default: 'local' (requires sentence-transformers or local model)
    |
    */
    'embedding_provider' => env('EMBEDDING_PROVIDER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Embedding Model Configuration
    |--------------------------------------------------------------------------
    |
    | Model name/identifier based on provider:
    | - OpenAI: 'text-embedding-ada-002', 'text-embedding-3-small', etc.
    | - HuggingFace: 'sentence-transformers/all-MiniLM-L6-v2', etc.
    | - Local: Path to local model or model identifier
    |
    */
    'embedding_model' => env('EMBEDDING_MODEL', 'sentence-transformers/all-MiniLM-L6-v2'),

    /*
    |--------------------------------------------------------------------------
    | Embedding Dimensions
    |--------------------------------------------------------------------------
    |
    | Number of dimensions for embeddings. Default is 384 for all-MiniLM-L6-v2.
    | Adjust based on your embedding model.
    |
    */
    'embedding_dimensions' => (int) env('EMBEDDING_DIMENSIONS', 384),

    /*
    |--------------------------------------------------------------------------
    | Chunk Size for Text Processing
    |--------------------------------------------------------------------------
    |
    | Maximum number of characters per chunk when splitting text for embedding.
    |
    */
    'chunk_size' => (int) env('CURRICULUM_CHUNK_SIZE', 1000),

    /*
    |--------------------------------------------------------------------------
    | Chunk Overlap
    |--------------------------------------------------------------------------
    |
    | Number of characters to overlap between chunks for better context.
    |
    */
    'chunk_overlap' => (int) env('CURRICULUM_CHUNK_OVERLAP', 200),

    /*
    |--------------------------------------------------------------------------
    | Top-K Retrieval
    |--------------------------------------------------------------------------
    |
    | Number of top similar chunks to retrieve for RAG queries.
    |
    */
    'top_k' => (int) env('CURRICULUM_TOP_K', 5),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Configuration
    |--------------------------------------------------------------------------
    */
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | HuggingFace Configuration
    |--------------------------------------------------------------------------
    */
    'huggingface' => [
        'api_key' => env('HF_API_KEY'),
        'api_url' => env('HF_API_URL', 'https://api-inference.huggingface.co'),
        'timeout' => (int) env('HF_TIMEOUT', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vector Store URLs
    |--------------------------------------------------------------------------
    */
    'vector_store_urls' => [
        'weaviate' => env('WEAVIATE_URL', 'http://localhost:8080'),
        'milvus' => env('MILVUS_URL', 'http://localhost:19530'),
        'qdrant' => env('QDRANT_URL', 'http://localhost:6333'),
    ],

    /*
    |--------------------------------------------------------------------------
    | LLM Configuration for AI Assistant
    |--------------------------------------------------------------------------
    |
    | Provider options: 'openai', 'hf', 'local'
    | Default: 'openai'
    |
    */
    'llm_provider' => env('CURRICULUM_LLM_PROVIDER', 'openai'),

    'llm' => [
        'openai' => [
            'model' => env('OPENAI_MODEL', 'gpt-4-turbo-preview'),
            'temperature' => (float) env('OPENAI_TEMPERATURE', 0.7),
            'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 2000),
        ],
        'huggingface' => [
            'model' => env('HF_LLM_MODEL', 'meta-llama/Llama-2-7b-chat-hf'),
            'temperature' => (float) env('HF_TEMPERATURE', 0.7),
            'max_tokens' => (int) env('HF_MAX_TOKENS', 2000),
        ],
        'local' => [
            'endpoint' => env('LOCAL_LLM_ENDPOINT', 'http://localhost:8000/v1/chat/completions'),
            'model' => env('LOCAL_LLM_MODEL', 'local-llm'),
            'temperature' => (float) env('LOCAL_LLM_TEMPERATURE', 0.7),
            'max_tokens' => (int) env('LOCAL_LLM_MAX_TOKENS', 2000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OCR Configuration
    |--------------------------------------------------------------------------
    */
    'ocr' => [
        'enabled' => env('CURRICULUM_OCR_ENABLED', true),
        'engine' => env('CURRICULUM_OCR_ENGINE', 'tesseract'), // 'tesseract' or 'google_vision'
        'tesseract_path' => env('TESSERACT_PATH', '/usr/bin/tesseract'),
        'language' => env('TESSERACT_LANGUAGE', 'eng'),
        'python_binary' => env('CURRICULUM_OCR_PYTHON', 'python'),
        'python_script' => base_path('scripts/ocr_page.py'),
        'page_resolution' => (int) env('OCR_PAGE_RESOLUTION', 220),
        'confidence_threshold' => (float) env('OCR_CONFIDENCE_THRESHOLD', 60.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF Processing Configuration
    |--------------------------------------------------------------------------
    */
    'pdf' => [
        'parser' => env('PDF_PARSER', 'smalot'), // 'smalot' or 'spatie'
        'max_file_size' => (int) env('CURRICULUM_MAX_FILE_SIZE', 50 * 1024 * 1024), // 50MB
        'max_pages' => (int) env('CURRICULUM_MAX_PAGES', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'ai_generation_per_hour' => (int) env('CURRICULUM_AI_RATE_LIMIT', 100),
        'upload_per_day' => (int) env('CURRICULUM_UPLOAD_RATE_LIMIT', 10),
    ],
];

