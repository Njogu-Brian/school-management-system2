<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateAssistantContentRequest;
use App\Models\CurriculumDesign;
use App\Services\EmbeddingService;
use App\Services\PromptTemplateService;
use App\Services\LLMService;
use App\Models\Academics\SchemeOfWork;
use App\Models\Academics\LessonPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CurriculumAssistantController extends Controller
{
    protected EmbeddingService $embeddingService;
    protected PromptTemplateService $promptService;
    protected LLMService $llmService;

    public function __construct(
        EmbeddingService $embeddingService,
        PromptTemplateService $promptService,
        LLMService $llmService
    ) {
        $this->embeddingService = $embeddingService;
        $this->promptService = $promptService;
        $this->llmService = $llmService;

        $this->middleware('permission:curriculum_assistant.use')->only(['generate', 'chat']);
    }

    /**
     * Generate content (scheme, lesson plan, assessment)
     */
    public function generate(GenerateAssistantContentRequest $request)
    {
        try {
            $curriculumDesign = CurriculumDesign::findOrFail($request->curriculum_design_id);
            
            // Generate query embedding
            $queryEmbedding = $this->embeddingService->generateEmbedding($request->query);
            
            if (!$queryEmbedding) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to generate query embedding',
                ], 500);
            }

            // Retrieve relevant chunks
            $chunks = $this->embeddingService->searchSimilar(
                $queryEmbedding,
                $curriculumDesign->id,
                config('curriculum_ai.top_k', 5)
            );

            // Build context
            $context = array_merge($request->context ?? [], [
                'class_level' => $request->context['class_level'] ?? $curriculumDesign->class_level,
            ]);

            // Generate prompt based on type
            $prompt = match ($request->type) {
                'scheme' => $this->promptService->generateSchemeOfWorkPrompt($curriculumDesign, $context, $chunks),
                'lesson_plan' => $this->promptService->generateLessonPlanPrompt($curriculumDesign, $context, $chunks),
                'assessment' => $this->promptService->generateAssessmentPrompt($curriculumDesign, $context, $chunks),
                'report_card' => $this->promptService->generateReportCardPrompt($curriculumDesign, $context, $chunks),
                default => throw new \InvalidArgumentException("Invalid generation type: {$request->type}"),
            };

            // Generate content using LLM
            $generatedContent = $this->llmService->generate($prompt);

            if (!$generatedContent) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to generate content',
                ], 500);
            }

            // Parse JSON response
            $parsedContent = $this->parseJsonResponse($generatedContent);

            // Store generated content (optional - for history)
            $this->storeGeneratedContent($request->type, $parsedContent, $curriculumDesign, $context);

            return response()->json([
                'success' => true,
                'type' => $request->type,
                'content' => $parsedContent,
                'citations' => $this->formatCitations($chunks),
                'raw_response' => $generatedContent,
            ]);
        } catch (\Exception $e) {
            Log::error('Assistant content generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate content: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Chat interface for assistant
     */
    public function chat(Request $request)
    {
        $request->validate([
            'curriculum_design_id' => 'required|exists:curriculum_designs,id',
            'message' => 'required|string|max:2000',
            'conversation_history' => 'sometimes|array',
        ]);

        try {
            $curriculumDesign = CurriculumDesign::findOrFail($request->curriculum_design_id);

            // Generate query embedding
            $queryEmbedding = $this->embeddingService->generateEmbedding($request->message);
            
            if (!$queryEmbedding) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to process query',
                ], 500);
            }

            // Retrieve relevant chunks
            $chunks = $this->embeddingService->searchSimilar(
                $queryEmbedding,
                $curriculumDesign->id,
                config('curriculum_ai.top_k', 5)
            );

            // Build conversation context
            $context = $this->buildConversationContext($request->message, $chunks, $request->conversation_history ?? []);

            // Generate response
            $response = $this->llmService->generate($context);

            if (!$response) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to generate response',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'response' => $response,
                'citations' => $this->formatCitations($chunks),
            ]);
        } catch (\Exception $e) {
            Log::error('Assistant chat failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process chat: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse JSON response from LLM
     */
    protected function parseJsonResponse(string $response): array
    {
        // Try to extract JSON from response
        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            $json = $matches[1];
        } elseif (preg_match('/\{.*\}/s', $response, $matches)) {
            $json = $matches[0];
        } else {
            $json = $response;
        }

        $parsed = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Fallback: return as text
            return ['raw_text' => $response];
        }

        return $parsed;
    }

    /**
     * Store generated content in database
     */
    protected function storeGeneratedContent(string $type, array $content, CurriculumDesign $curriculumDesign, array $context): void
    {
        try {
            match ($type) {
                'scheme' => $this->storeSchemeOfWork($content, $curriculumDesign, $context),
                'lesson_plan' => $this->storeLessonPlan($content, $curriculumDesign, $context),
                'assessment' => $this->storeAssessment($content, $curriculumDesign, $context),
                default => null,
            };
        } catch (\Exception $e) {
            Log::warning('Failed to store generated content', [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store scheme of work
     */
    protected function storeSchemeOfWork(array $content, CurriculumDesign $curriculumDesign, array $context): void
    {
        // Implementation depends on your SchemeOfWork model structure
        // This is a placeholder
        if (isset($content['weeks'])) {
            // Store scheme of work
        }
    }

    /**
     * Store lesson plan
     */
    protected function storeLessonPlan(array $content, CurriculumDesign $curriculumDesign, array $context): void
    {
        // Implementation depends on your LessonPlan model structure
    }

    /**
     * Store assessment
     */
    protected function storeAssessment(array $content, CurriculumDesign $curriculumDesign, array $context): void
    {
        // Implementation depends on your Exam/Assessment model structure
    }

    /**
     * Format citations from retrieved chunks
     */
    protected function formatCitations(array $chunks): array
    {
        $citations = [];
        foreach ($chunks as $chunk) {
            $citations[] = [
                'text' => substr($chunk['text_snippet'] ?? '', 0, 200),
                'page' => $chunk['metadata']['page'] ?? null,
                'source_type' => $chunk['source_type'] ?? null,
            ];
        }
        return $citations;
    }

    /**
     * Build conversation context for chat
     */
    protected function buildConversationContext(string $message, array $chunks, array $history): string
    {
        $chunksText = $this->formatChunksForContext($chunks);
        
        $context = "You are an AI assistant helping teachers with curriculum design and lesson planning for Kenyan CBC/CBE curriculum.\n\n";
        $context .= "Relevant Curriculum Content:\n{$chunksText}\n\n";

        if (!empty($history)) {
            $context .= "Conversation History:\n";
            foreach ($history as $entry) {
                $context .= "User: {$entry['user']}\n";
                $context .= "Assistant: {$entry['assistant']}\n\n";
            }
        }

        $context .= "Current Question: {$message}\n\n";
        $context .= "Please provide a helpful, accurate response based on the curriculum content above.";

        return $context;
    }

    /**
     * Format chunks for context
     */
    protected function formatChunksForContext(array $chunks): string
    {
        $formatted = [];
        foreach ($chunks as $chunk) {
            $formatted[] = ($chunk['text_snippet'] ?? '');
        }
        return implode("\n\n---\n\n", $formatted);
    }
}
