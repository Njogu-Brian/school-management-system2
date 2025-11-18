<?php

namespace App\Services;

use App\Models\CurriculumDesign;
use App\Models\CurriculumPage;
use App\Models\Academics\LearningArea;
use App\Models\Academics\CBCStrand;
use App\Models\Academics\CBCSubstrand;
use App\Models\Academics\Competency;
use App\Models\Academics\Subject;
use App\Models\SuggestedExperience;
use App\Models\AssessmentRubric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Str;

class CurriculumParsingService
{
    protected EmbeddingService $embeddingService;

    public function __construct(EmbeddingService $embeddingService)
    {
        $this->embeddingService = $embeddingService;
    }

    /**
     * Parse a curriculum design PDF and extract structured data
     *
     * @param CurriculumDesign $curriculumDesign
     * @return bool
     */
    public function parse(CurriculumDesign $curriculumDesign): bool
    {
        try {
            @ini_set('memory_limit', '1024M');
            @set_time_limit(0);

            $curriculumDesign->update(['status' => 'processing']);
            $totalPages = $curriculumDesign->pages ?? 0;
            $this->updateProgress($curriculumDesign->id, 0, 0, 'Starting...', false, $totalPages);

            // Step 1: Extract text from PDF
            $this->updateProgress($curriculumDesign->id, 0, 0, 'Extracting text from PDF...', false, $totalPages);
            $pages = $this->extractTextFromPdf($curriculumDesign);
            if (empty($pages)) {
                throw new \Exception('Failed to extract text from PDF');
            }
            
            $totalPages = count($pages); // Update with actual count

            if ($subjectName = $this->detectSubjectName($pages)) {
                $this->syncDetectedSubject($curriculumDesign, $subjectName);
            }

            // Remove previously extracted content before inserting fresh data
            $this->clearExistingData($curriculumDesign);

            // Step 2: Store page-level text
            $this->updateProgress($curriculumDesign->id, 20, count($pages), 'Storing pages...', false, $totalPages);
            $this->storePages($curriculumDesign, $pages);

            // Step 3: Preprocess and segment text
            $this->updateProgress($curriculumDesign->id, 40, count($pages), 'Segmenting text...', false, $totalPages);
            $segments = $this->segmentText($pages);

            // Step 4: Extract structured data
            $this->updateProgress($curriculumDesign->id, 60, count($pages), 'Extracting structured data...', false, $totalPages);
            $extracted = $this->extractStructuredData($curriculumDesign, $segments);

            if (!empty($extracted['grades'])) {
                $this->syncDetectedGrades($curriculumDesign, $extracted['grades']);
            }

            // Step 5: Store extracted data
            $this->updateProgress($curriculumDesign->id, 80, count($pages), 'Storing extracted data...', false, $totalPages);
            $this->storeExtractedData($curriculumDesign, $extracted);

            // Step 6: Generate embeddings
            $this->updateProgress($curriculumDesign->id, 90, count($pages), 'Generating embeddings...', false, $totalPages);
            $this->generateEmbeddings($curriculumDesign, $extracted);

            // Step 7: Mark as processed
            $curriculumDesign->refresh();
            $metadata = $curriculumDesign->metadata ?? [];
            $metadata['extracted_at'] = now()->toIso8601String();
            $metadata['learning_areas_count'] = count($extracted['learning_areas'] ?? []);
            $metadata['strands_count'] = count($extracted['strands'] ?? []);
            $metadata['substrands_count'] = count($extracted['substrands'] ?? []);
            $metadata['competencies_count'] = count($extracted['competencies'] ?? []);

            $curriculumDesign->update([
                'status' => 'processed',
                'pages' => count($pages),
                'metadata' => $metadata,
            ]);

            $this->updateProgress($curriculumDesign->id, 100, count($pages), 'Complete!', false, $totalPages);
            
            // Clear progress after 5 minutes
            Cache::forget("curriculum_parse_progress_{$curriculumDesign->id}");

            return true;
        } catch (\Exception $e) {
            Log::error('Curriculum parsing failed', [
                'curriculum_design_id' => $curriculumDesign->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $curriculumDesign->update([
                'status' => 'failed',
                'error_notes' => $e->getMessage(),
            ]);

            $this->updateProgress($curriculumDesign->id, 0, 0, 'Failed: ' . $e->getMessage(), true);

            return false;
        }
    }

    /**
     * Update parsing progress
     */
    protected function updateProgress(int $curriculumDesignId, int $percentage, int $pagesProcessed, string $message, bool $failed = false, ?int $totalPages = null): void
    {
        $existing = Cache::get("curriculum_parse_progress_{$curriculumDesignId}", []);
        
        $progress = [
            'percentage' => $percentage,
            'pages_processed' => $pagesProcessed,
            'total_pages' => $totalPages ?? ($existing['total_pages'] ?? null),
            'message' => $message,
            'failed' => $failed,
            'updated_at' => now()->toIso8601String(),
        ];

        Cache::put("curriculum_parse_progress_{$curriculumDesignId}", $progress, now()->addMinutes(10));
    }

    /**
     * Get parsing progress
     */
    public static function getProgress(int $curriculumDesignId): ?array
    {
        return Cache::get("curriculum_parse_progress_{$curriculumDesignId}");
    }

    /**
     * Extract text from PDF file
     *
     * @param CurriculumDesign $curriculumDesign
     * @return array Array of page text [page_number => text]
     */
    protected function extractTextFromPdf(CurriculumDesign $curriculumDesign): array
    {
        $filePath = Storage::path($curriculumDesign->file_path);
        
        if (!file_exists($filePath)) {
            throw new \Exception("PDF file not found: {$filePath}");
        }

        $pages = [];
        $parser = new Parser();

        try {
            $pdf = $parser->parseFile($filePath);
            $pdfPages = $pdf->getPages();
            $totalPages = count($pdfPages);

            Log::info('Starting PDF text extraction', [
                'curriculum_design_id' => $curriculumDesign->id,
                'total_pages' => $totalPages,
            ]);

            foreach ($pdfPages as $pageNumber => $page) {
                $actualPageNum = $pageNumber + 1;
                
                // Update progress every page (percentage based on extraction phase: 0-20%)
                $extractionPercentage = min(20, (int)(($actualPageNum / $totalPages) * 20));
                $this->updateProgress(
                    $curriculumDesign->id,
                    $extractionPercentage,
                    $actualPageNum,
                    "Extracting page {$actualPageNum} of {$totalPages}...",
                    false,
                    $totalPages
                );
                
                // Log progress every 25 pages
                if ($actualPageNum % 25 === 0 || $actualPageNum === 1) {
                    Log::info('Extracting page', [
                        'curriculum_design_id' => $curriculumDesign->id,
                        'page' => $actualPageNum,
                        'total' => $totalPages,
                    ]);
                }

                $text = $page->getText();
                
                // Check if text extraction was successful (has meaningful content)
                if (strlen(trim($text)) < 50) {
                    // Likely an image-based page, try OCR
                    $text = $this->performOCR($filePath, $actualPageNum);
                }

                $pages[$actualPageNum] = $this->normalizeText($text);
            }

            Log::info('Completed PDF text extraction', [
                'curriculum_design_id' => $curriculumDesign->id,
                'pages_extracted' => count($pages),
            ]);
        } catch (\Exception $e) {
            Log::warning('PDF parsing failed, attempting OCR fallback', [
                'error' => $e->getMessage(),
            ]);

            // Fallback: try OCR for all pages
            $totalPages = $this->getPdfPageCount($filePath);
            for ($i = 1; $i <= $totalPages; $i++) {
                $pages[$i] = $this->normalizeText($this->performOCR($filePath, $i));
            }
        }

        return $pages;
    }

    /**
     * Normalize extracted text
     */
    protected function normalizeText(string $text): string
    {
        // Preserve intentional line breaks but collapse repeated whitespace
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{2,}/', "\n", $text);

        $lines = array_map('trim', explode("\n", $text));
        $lines = array_filter($lines, static fn ($line) => $line !== '');

        return trim(implode("\n", $lines));
    }

    /**
     * Perform OCR on a PDF page
     *
     * @param string $filePath
     * @param int $pageNumber
     * @return string
     */
    protected function performOCR(string $filePath, int $pageNumber): string
    {
        if (!config('curriculum_ai.ocr.enabled')) {
            return '';
        }

        $engine = config('curriculum_ai.ocr.engine', 'tesseract');
        
        if ($engine === 'tesseract') {
            return $this->performTesseractOCR($filePath, $pageNumber);
        }

        // Add other OCR engines here (Google Vision, etc.)
        return '';
    }

    /**
     * Perform OCR using Tesseract
     */
    protected function performTesseractOCR(string $filePath, int $pageNumber): string
    {
        $tesseractPath = config('curriculum_ai.ocr.tesseract_path', 'tesseract');
        $language = config('curriculum_ai.ocr.language', 'eng');

        // Convert PDF page to image first (requires imagemagick or poppler)
        $imagePath = $this->convertPdfPageToImage($filePath, $pageNumber);
        
        if (!$imagePath || !file_exists($imagePath)) {
            Log::warning('Failed to convert PDF page to image for OCR', [
                'file' => $filePath,
                'page' => $pageNumber,
            ]);

            return $this->performPythonOCR($filePath, $pageNumber, $language);
        }

        try {
            // Run tesseract
            $outputPath = $imagePath . '_ocr';
            $command = escapeshellcmd($tesseractPath) . ' ' . 
                      escapeshellarg($imagePath) . ' ' . 
                      escapeshellarg($outputPath) . ' -l ' . 
                      escapeshellarg($language) . ' 2>&1';
            
            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($outputPath . '.txt')) {
                $text = file_get_contents($outputPath . '.txt');
                unlink($outputPath . '.txt');
                @unlink($imagePath); // Clean up
                return $this->normalizeText($text);
            }

            if (file_exists($outputPath . '.txt')) {
                @unlink($outputPath . '.txt');
            }
        } catch (\Exception $e) {
            Log::error('Tesseract OCR failed', [
                'error' => $e->getMessage(),
                'file' => $filePath,
                'page' => $pageNumber,
            ]);
        }

        if (file_exists($imagePath)) {
            @unlink($imagePath);
        }

        return $this->performPythonOCR($filePath, $pageNumber, $language);
    }

    /**
     * Convert PDF page to image for OCR
     */
    protected function convertPdfPageToImage(string $filePath, int $pageNumber): ?string
    {
        // On Windows, skip pdftoppm/ImageMagick and use Python OCR directly
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return null; // Will trigger Python OCR fallback
        }

        // Try using pdftoppm (poppler-utils) first (Linux/Mac)
        $pdftoppm = 'pdftoppm';
        
        $outputDir = storage_path('app/temp/ocr');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputPath = $outputDir . '/page_' . $pageNumber . '_' . time() . '.png';

        try {
            $command = escapeshellcmd($pdftoppm) . ' -png -f ' . $pageNumber . ' -l ' . $pageNumber . ' ' . 
                      escapeshellarg($filePath) . ' ' . escapeshellarg($outputDir . '/page_' . $pageNumber) . ' 2>&1';
            
            exec($command, $output, $returnCode);

            if ($returnCode === 0) {
                $generatedFile = $outputDir . '/page_' . $pageNumber . '-1.png';
                if (file_exists($generatedFile)) {
                    rename($generatedFile, $outputPath);
                    return $outputPath;
                }
            }
        } catch (\Exception $e) {
            // Silently fail, will try ImageMagick or Python fallback
        }

        // Fallback: try imagemagick
        try {
            $convert = 'convert';
            $command = escapeshellcmd($convert) . ' -density 300 ' . 
                      escapeshellarg($filePath . '[' . ($pageNumber - 1) . ']') . ' ' . 
                      escapeshellarg($outputPath) . ' 2>&1';
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($outputPath)) {
                return $outputPath;
            }
        } catch (\Exception $e) {
            // Silently fail, will use Python OCR fallback
        }

        return null;
    }

    /**
     * Get total page count of PDF
     */
    protected function getPdfPageCount(string $filePath): int
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            return count($pdf->getPages());
        } catch (\Exception $e) {
            // Fallback: try pdfinfo
            $pdfinfo = 'pdfinfo';
            $command = escapeshellcmd($pdfinfo) . ' ' . escapeshellarg($filePath) . ' 2>&1';
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                foreach ($output as $line) {
                    if (preg_match('/Pages:\s*(\d+)/i', $line, $matches)) {
                        return (int) $matches[1];
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Store extracted page text
     */
    protected function storePages(CurriculumDesign $curriculumDesign, array $pages): void
    {
        foreach ($pages as $pageNumber => $text) {
            CurriculumPage::create([
                'curriculum_design_id' => $curriculumDesign->id,
                'page_number' => $pageNumber,
                'text' => $text,
                'ocr_confidence' => null, // Could be calculated if OCR was used
            ]);
        }
    }

    /**
     * Segment text into logical sections
     */
    protected function segmentText(array $pages): array
    {
        $segments = [];
        $currentGrade = null;
        $currentPages = [];
        $currentBuffer = [];

        foreach ($pages as $pageNumber => $text) {
            $detectedGrade = $this->detectGradeFromPage($text, $currentGrade);

            if ($detectedGrade !== null && $detectedGrade !== $currentGrade) {
                if ($currentGrade !== null && !empty($currentBuffer)) {
                    $segments[] = [
                        'type' => 'grade',
                        'grade' => $currentGrade,
                        'pages' => $currentPages,
                        'text' => implode("\n", $currentBuffer),
                    ];
                }

                $currentGrade = $detectedGrade;
                $currentPages = [];
                $currentBuffer = [];
            }

            if ($currentGrade === null) {
                continue;
            }

            $currentPages[] = $pageNumber;
            $currentBuffer[] = $text;
        }

        if ($currentGrade !== null && !empty($currentBuffer)) {
            $segments[] = [
                'type' => 'grade',
                'grade' => $currentGrade,
                'pages' => $currentPages,
                'text' => implode("\n", $currentBuffer),
            ];
        }

        return $segments;
    }

    /**
     * Extract structured data from text segments
     */
    protected function extractStructuredData(CurriculumDesign $curriculumDesign, array $segments): array
    {
        $extracted = [
            'learning_areas' => [],
            'strands' => [],
            'substrands' => [],
            'competencies' => [],
            'suggested_experiences' => [],
            'rubrics' => [],
            'grades' => [],
        ];

        foreach ($segments as $segment) {
            if (($segment['type'] ?? null) !== 'grade') {
                continue;
            }

            $grade = (int) ($segment['grade'] ?? 0);
            if ($grade === 0) {
                continue;
            }

            $gradeLabel = $this->formatGradeLabel($grade);
            $extracted['grades'][] = $grade;

            $learningAreaCode = sprintf('CD%s-G%s', $curriculumDesign->id, $grade);
            $learningAreaName = sprintf('%s - %s', $curriculumDesign->title ?? 'Curriculum Design', $gradeLabel);

            $extracted['learning_areas'][] = [
                'code' => $learningAreaCode,
                'name' => $learningAreaName,
                'description' => $this->summarizeText($segment['text']),
                'grade' => $grade,
                'grade_label' => $gradeLabel,
                'level_category' => $this->determineLevelCategory($grade),
                'levels' => [$gradeLabel],
            ];

            $strandBlocks = $this->extractStrandsFromGrade($segment['text'], $grade);

            foreach ($strandBlocks as $strandBlock) {
                $strandCode = sprintf('%s-S%s', $learningAreaCode, $strandBlock['number']);

                $extracted['strands'][] = [
                    'code' => $strandCode,
                    'name' => $strandBlock['title'],
                    'description' => $strandBlock['summary'],
                    'learning_area' => ['code' => $learningAreaCode],
                    'grade' => $grade,
                    'grade_label' => $gradeLabel,
                    'number' => $strandBlock['number'],
                ];

                foreach ($strandBlock['substrands'] as $substrandBlock) {
                    $substrandCode = sprintf('%s-SS%s', $strandCode, str_replace('.', '', $substrandBlock['code']));

                    $extracted['substrands'][] = [
                        'code' => $substrandCode,
                        'name' => $substrandBlock['title'],
                        'description' => $substrandBlock['summary'],
                        'strand' => ['code' => $strandCode],
                        'learning_outcomes' => $substrandBlock['outcomes'],
                        'key_inquiry' => $substrandBlock['key_inquiry'],
                        'core_competencies' => $substrandBlock['core_competencies'],
                        'values' => $substrandBlock['values'],
                        'pcis' => $substrandBlock['pcis'],
                        'links' => $substrandBlock['links'],
                        'lessons' => $substrandBlock['lessons'],
                        'sequence' => $substrandBlock['sequence'],
                    ];

                    foreach ($substrandBlock['outcomes'] as $outcome) {
                        $extracted['competencies'][] = [
                            'description' => $outcome,
                            'indicators' => $substrandBlock['key_inquiry'],
                            'substrand' => ['code' => $substrandCode],
                        ];
                    }

                    if (!empty($substrandBlock['experiences'])) {
                        $extracted['suggested_experiences'][] = [
                            'content' => implode("\n", $substrandBlock['experiences']),
                            'examples' => implode("\n", (array) $substrandBlock['key_inquiry']),
                            'metadata' => [
                                'core_competencies' => $substrandBlock['core_competencies'],
                                'values' => $substrandBlock['values'],
                                'pcis' => $substrandBlock['pcis'],
                                'links' => $substrandBlock['links'],
                            ],
                            'substrand' => ['code' => $substrandCode],
                        ];
                    }
                }
            }
        }

        $extracted['grades'] = array_values(array_unique(array_filter($extracted['grades'])));

        return $extracted;
    }

    /**
     * Detect section type from text
     */
    protected function detectSectionType(string $text): ?string
    {
        $text = strtolower($text);
        
        if (preg_match('/^(learning\s+area|subject)/i', $text)) {
            return 'learning_area';
        }
        if (preg_match('/^strand/i', $text)) {
            return 'strand';
        }
        if (preg_match('/^substrand|sub-strand/i', $text)) {
            return 'substrand';
        }
        if (preg_match('/^(learning\s+outcome|competency)/i', $text)) {
            return 'competency';
        }
        if (preg_match('/suggested\s+(learning\s+)?experience/i', $text)) {
            return 'suggested_experience';
        }
        if (preg_match('/assessment\s+rubric/i', $text)) {
            return 'rubric';
        }

        return null;
    }

    /**
     * Extract learning area from text
     */
    protected function extractLearningArea(string $text, int $page): ?array
    {
        // Extract code and name
        if (preg_match('/(?:Learning\s+Area|Subject)[\s:]+([A-Z]+)[\s:]+(.+?)(?:\n|$)/i', $text, $matches)) {
            return [
                'code' => trim($matches[1]),
                'name' => trim($matches[2]),
                'description' => $this->extractDescription($text),
                'page' => $page,
            ];
        }

        return null;
    }

    /**
     * Extract strand from text
     */
    protected function extractStrand(string $text, int $page, ?array $learningArea): ?array
    {
        if (preg_match('/Strand[\s:]+(\d+\.?\s*)?(.+?)(?:\n|$)/i', $text, $matches)) {
            return [
                'code' => trim($matches[1] ?? ''),
                'name' => trim($matches[2]),
                'description' => $this->extractDescription($text),
                'learning_area' => $learningArea,
                'page' => $page,
            ];
        }

        return null;
    }

    /**
     * Extract substrand from text
     */
    protected function extractSubstrand(string $text, int $page, ?array $strand): ?array
    {
        if (preg_match('/Substrand[\s:]+(\d+\.?\s*)?(.+?)(?:\n|$)/i', $text, $matches)) {
            return [
                'code' => trim($matches[1] ?? ''),
                'name' => trim($matches[2]),
                'description' => $this->extractDescription($text),
                'strand' => $strand,
                'page' => $page,
            ];
        }

        return null;
    }

    /**
     * Extract competency from text
     */
    protected function extractCompetency(string $text, int $page, ?array $substrand): ?array
    {
        if (preg_match('/(?:Learning\s+Outcome|Competency)[\s:]+(.+?)(?:\n|$)/i', $text, $matches)) {
            return [
                'description' => trim($matches[1]),
                'indicators' => $this->extractListItems($text),
                'substrand' => $substrand,
                'page' => $page,
            ];
        }

        return null;
    }

    /**
     * Extract suggested experience from text
     */
    protected function extractSuggestedExperience(string $text, int $page, ?array $substrand): ?array
    {
        $content = preg_replace('/Suggested\s+(Learning\s+)?Experience[\s:]+/i', '', $text);
        
        return [
            'content' => trim($content),
            'examples' => $this->extractListItems($text),
            'substrand' => $substrand,
            'page' => $page,
        ];
    }

    /**
     * Extract rubric from text
     */
    protected function extractRubric(string $text, int $page, ?array $substrand): ?array
    {
        // Try to extract structured rubric data
        $rubricData = [
            'levels' => [],
            'criteria' => [],
        ];

        // Look for rubric table or structured format
        if (preg_match_all('/(\d+|[A-Z]+)[\s:]+(.+?)(?:\n|$)/', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $rubricData['levels'][] = [
                    'level' => trim($match[1]),
                    'description' => trim($match[2]),
                ];
            }
        }

        return [
            'rubric_json' => $rubricData,
            'substrand' => $substrand,
            'page' => $page,
        ];
    }

    /**
     * Extract description from text (everything after the title)
     */
    protected function extractDescription(string $text): string
    {
        $lines = explode("\n", $text);
        array_shift($lines); // Remove first line (title)
        return trim(implode("\n", $lines));
    }

    /**
     * Extract list items from text
     */
    protected function extractListItems(string $text): array
    {
        $items = [];
        $lines = explode("\n", $text);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^[-•*]\s*(.+)$/', $line, $matches)) {
                $items[] = trim($matches[1]);
            } elseif (preg_match('/^\d+[\.)]\s*(.+)$/', $line, $matches)) {
                $items[] = trim($matches[1]);
            }
        }

        return $items;
    }

    /**
     * Store extracted data in database
     */
    protected function storeExtractedData(CurriculumDesign $curriculumDesign, array $extracted): void
    {
        // Store learning areas
        foreach ($extracted['learning_areas'] as $laData) {
            $learningArea = LearningArea::create([
                'code' => $laData['code'],
                'name' => $laData['name'],
                'description' => $laData['description'] ?? null,
                'curriculum_design_id' => $curriculumDesign->id,
                'level_category' => $laData['level_category'] ?? null,
                'levels' => $laData['levels'] ?? null,
                'display_order' => $laData['grade'] ?? null,
                'is_active' => true,
                'is_core' => true,
            ]);

            // Store strands for this learning area
            foreach ($extracted['strands'] as $strandData) {
                if (($strandData['learning_area']['code'] ?? null) === $laData['code']) {
                    $strand = CBCStrand::create([
                        'code' => $strandData['code'] ?? '',
                        'name' => $strandData['name'],
                        'description' => $strandData['description'] ?? null,
                        'learning_area_id' => $learningArea->id,
                        'curriculum_design_id' => $curriculumDesign->id,
                        'level' => $strandData['grade_label'] ?? null,
                        'display_order' => $strandData['number'] ?? null,
                        'is_active' => true,
                    ]);

                    // Store substrands for this strand
                    foreach ($extracted['substrands'] as $substrandData) {
                        if (($substrandData['strand']['code'] ?? null) === ($strandData['code'] ?? '')) {
                            $substrand = CBCSubstrand::create([
                                'code' => $substrandData['code'] ?? '',
                                'name' => $substrandData['name'],
                                'description' => $substrandData['description'] ?? null,
                                'strand_id' => $strand->id,
                                'learning_outcomes' => $substrandData['learning_outcomes'] ?? [],
                                'key_inquiry_questions' => $substrandData['key_inquiry'] ?? [],
                                'core_competencies' => $substrandData['core_competencies'] ?? [],
                                'values' => $substrandData['values'] ?? [],
                                'pclc' => $substrandData['pcis'] ?? [],
                                'suggested_lessons' => $substrandData['lessons'] ?? null,
                                'display_order' => $substrandData['sequence'] ?? null,
                                'is_active' => true,
                            ]);

                            // Store competencies
                            foreach ($extracted['competencies'] as $compData) {
                                if (($compData['substrand']['code'] ?? null) === ($substrandData['code'] ?? '')) {
                                    Competency::create([
                                        'substrand_id' => $substrand->id,
                                        'code' => $this->generateCompetencyCode($learningArea, $strand, $substrand),
                                        'name' => $compData['description'],
                                        'description' => $compData['description'],
                                        'indicators' => $compData['indicators'] ?? [],
                                        'is_active' => true,
                                    ]);
                                }
                            }

                            // Store suggested experiences
                            foreach ($extracted['suggested_experiences'] as $expData) {
                                if (($expData['substrand']['code'] ?? null) === ($substrandData['code'] ?? '')) {
                                    SuggestedExperience::create([
                                        'substrand_id' => $substrand->id,
                                        'content' => $expData['content'],
                                        'examples' => $expData['examples'] ?? null,
                                        'metadata' => $expData['metadata'] ?? null,
                                    ]);
                                }
                            }

                            // Store rubrics
                            foreach ($extracted['rubrics'] as $rubricData) {
                                if (($rubricData['substrand']['code'] ?? null) === ($substrandData['code'] ?? '')) {
                                    AssessmentRubric::create([
                                        'substrand_id' => $substrand->id,
                                        'rubric_json' => $rubricData['rubric_json'],
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Generate competency code
     */
    protected function generateCompetencyCode($learningArea, $strand, $substrand): string
    {
        $laCode = $learningArea->code ?? 'X';
        $strandCode = $strand->code ?? 'X';
        $substrandCode = $substrand->code ?? 'X';
        $compNum = Competency::where('substrand_id', $substrand->id)->count() + 1;
        
        return "{$laCode}.{$strandCode}.{$substrandCode}.{$compNum}";
    }

    /**
     * Generate embeddings for extracted data
     */
    protected function generateEmbeddings(CurriculumDesign $curriculumDesign, array $extracted): void
    {
        // Generate embeddings for competencies
        foreach ($extracted['competencies'] as $compData) {
            $text = $compData['description'];
            $embedding = $this->embeddingService->generateEmbedding($text);
            
            if ($embedding) {
                // Find the competency ID (would need to be stored with reference)
                // For now, we'll store with source_type and source_id after creation
            }
        }

        // Generate embeddings for suggested experiences
        foreach ($extracted['suggested_experiences'] as $expData) {
            $text = $expData['content'];
            $embedding = $this->embeddingService->generateEmbedding($text);
            
            if ($embedding) {
                // Store embedding
            }
        }
    }

    /**
     * Detect grade transitions within OCR text
     */
    protected function detectGradeFromPage(string $text, ?int $currentGrade): ?int
    {
        $patterns = [
            '/CREATIVE\s+ACTIVITIES[^\n]*GRADE\s*([1-9])/i',
            '/GRADE\s*([1-9])/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $candidate) {
                    $grade = (int) $candidate;
                    if ($currentGrade === null || $grade > $currentGrade) {
                        return $grade;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Extract strand level blocks for a given grade
     */
    protected function extractStrandsFromGrade(string $gradeText, int $grade): array
    {
        $pattern = '/STRAND\s*(\d+)\s*[:\-]\s*([^\n]+)(.*?)(?=STRAND\s*\d+\s*[:\-]|$)/is';
        $matches = [];
        $strands = [];

        if (preg_match_all($pattern, $gradeText, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $number = (int) $match[1];
                $strands[] = [
                    'number' => $number,
                    'title' => $this->cleanHeading($match[2]),
                    'summary' => $this->summarizeText($match[3], 320),
                    'substrands' => $this->extractSubstrandsFromBlock($match[3], $match[1]),
                    'grade' => $grade,
                ];
            }
        } else {
            $strands[] = [
                'number' => 1,
                'title' => sprintf('Grade %s Strand', $grade),
                'summary' => $this->summarizeText($gradeText, 320),
                'substrands' => $this->extractSubstrandsFromBlock($gradeText, 1),
                'grade' => $grade,
            ];
        }

        return $strands;
    }

    protected function cleanHeading(string $text): string
    {
        $text = str_replace('|', ' ', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Split a strand body into substrands
     */
    protected function extractSubstrandsFromBlock(string $blockText, string $strandNumber): array
    {
        $lines = preg_split('/\n+/', $blockText);
        $substrands = [];
        $current = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^(?:\d+\.\d+\s+)?(\d+\.\d+)\s+(.+)/', $line, $matches)) {
                if ($current) {
                    $substrands[] = $this->finalizeSubstrandBlock($current);
                }

                $title = $matches[2];
                $lessons = null;
                if (preg_match('/\((\d+)\s+lessons?/i', $title, $lessonMatch)) {
                    $lessons = (int) $lessonMatch[1];
                    $title = str_replace($lessonMatch[0], '', $title);
                }

                $current = [
                    'code' => $matches[1],
                    'title' => $this->cleanHeading($title),
                    'raw' => '',
                    'lessons' => $lessons,
                ];
                continue;
            }

            if ($current) {
                $current['raw'] .= ($current['raw'] === '' ? '' : "\n") . $line;
            }
        }

        if ($current) {
            $substrands[] = $this->finalizeSubstrandBlock($current);
        }

        return $substrands;
    }

    /**
     * Build metadata for an individual substrand
     */
    protected function finalizeSubstrandBlock(array $substrand): array
    {
        $rawText = $substrand['raw'];

        $outcomesText = $this->extractBetweenMarkers(
            $rawText,
            ['should be able to', 'Specific Learning Outcomes'],
            ['Suggested Learning Experiences', 'The learner is guided to', 'Suggested learning experiences']
        );

        $experiencesText = $this->extractBetweenMarkers(
            $rawText,
            ['Suggested Learning Experiences', 'The learner is guided to'],
            ['Suggested Key Inquiry', 'Key Inquiry', 'Core Competencies', 'Values', 'Pertinent', 'Assessment']
        );

        $keyInquiryText = $this->extractBetweenMarkers(
            $rawText,
            ['Key Inquiry', 'Key Inquiry Question'],
            ['Core Competencies', 'Values', 'Pertinent', 'Assessment']
        );

        $coreCompetenciesText = $this->extractBetweenMarkers(
            $rawText,
            ['Core Competencies'],
            ['Values', 'Pertinent', 'Links']
        );

        $valuesText = $this->extractBetweenMarkers(
            $rawText,
            ['Values'],
            ['Pertinent', 'Links', 'Resources']
        );

        $pcisText = $this->extractBetweenMarkers(
            $rawText,
            ['Pertinent and Contemporary Issues', 'Pertinent & Contemporary Issues', 'PCIs'],
            ['Link to other learning areas', 'Links to other learning areas', 'Resources']
        );

        $linksText = $this->extractBetweenMarkers(
            $rawText,
            ['Link to other learning areas', 'Links to other learning areas'],
            ['Resources', 'Assessment']
        );

        $outcomes = $this->splitIntoList($outcomesText);
        if (empty($outcomes) && !empty($outcomesText)) {
            $outcomes = [$outcomesText];
        }

        $experiences = $this->splitIntoList($experiencesText);
        if (empty($experiences) && !empty($experiencesText)) {
            $experiences = [$experiencesText];
        }

        return [
            'code' => $substrand['code'],
            'title' => $substrand['title'],
            'summary' => $this->summarizeText($rawText, 320),
            'lessons' => $substrand['lessons'],
            'outcomes' => $outcomes,
            'experiences' => $experiences,
            'key_inquiry' => $this->splitIntoList($keyInquiryText),
            'core_competencies' => $this->splitIntoList($coreCompetenciesText),
            'values' => $this->splitIntoList($valuesText),
            'pcis' => $this->splitIntoList($pcisText),
            'links' => $this->splitIntoList($linksText),
            'sequence' => $this->sequenceFromCode($substrand['code']),
        ];
    }

    /**
     * Extract text between semantic markers
     */
    protected function extractBetweenMarkers(string $text, array $startMarkers, array $endMarkers = []): string
    {
        $start = $this->findMarkerPosition($text, $startMarkers);
        if ($start === null) {
            return '';
        }

        $contentStart = $start['position'] + $start['length'];
        $end = $this->findMarkerPosition($text, $endMarkers, $contentStart);
        $contentEnd = $end['position'] ?? strlen($text);

        return trim(substr($text, $contentStart, $contentEnd - $contentStart));
    }

    protected function findMarkerPosition(string $text, array $markers, int $offset = 0): ?array
    {
        $best = null;

        foreach ($markers as $marker) {
            if ($marker === '') {
                continue;
            }

            $pos = stripos($text, $marker, $offset);
            if ($pos !== false && ($best === null || $pos < $best['position'])) {
                $best = [
                    'position' => $pos,
                    'length' => strlen($marker),
                ];
            }
        }

        return $best;
    }

    /**
     * Convert free text into a list by looking for bullet-like prefixes
     */
    protected function splitIntoList(?string $text): array
    {
        if (empty($text)) {
            return [];
        }

        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = preg_split('/\n+/', $text);
        $items = [];
        $current = '';

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^((?:\(?[0-9a-z]{1,3}\)?[.\-:])|[-•*@])\s*/i', $line)) {
                if ($current !== '') {
                    $items[] = trim($current);
                }
                $line = preg_replace('/^((?:\(?[0-9a-z]{1,3}\)?[.\-:])|[-•*@])\s*/i', '', $line);
                $current = $line;
            } else {
                $current = $current === '' ? $line : $current . ' ' . $line;
            }
        }

        if ($current !== '') {
            $items[] = trim($current);
        }

        $items = array_values(array_filter($items));

        if (empty($items)) {
            $text = preg_replace('/\s+/', ' ', trim($text));
            return $text === '' ? [] : [$text];
        }

        return $items;
    }

    protected function formatGradeLabel(int $grade): string
    {
        return 'Grade ' . $grade;
    }

    protected function determineLevelCategory(int $grade): string
    {
        if ($grade <= 3) {
            return 'Lower Primary';
        }

        if ($grade <= 6) {
            return 'Upper Primary';
        }

        if ($grade <= 9) {
            return 'Junior Secondary';
        }

        return 'Senior Secondary';
    }

    protected function sequenceFromCode(?string $code): ?int
    {
        if (empty($code)) {
            return null;
        }

        $numeric = preg_replace('/\D/', '', (string) $code);

        return $numeric === '' ? null : (int) $numeric;
    }

    protected function detectSubjectName(array $pages): ?string
    {
        if (empty($pages)) {
            return null;
        }

        $samples = array_slice($pages, 0, 5, true);

        foreach ($samples as $text) {
            $flat = preg_replace('/\s+/', ' ', strtoupper($text));

            if (preg_match('/CURRICULUM DESIGN\s+([A-Z&\s]+?)\s+GRADE/i', $flat, $matches)) {
                return $this->normalizeSubjectName($matches[1]);
            }

            if (preg_match('/PRIMARY SCHOOL EDUCATION\s+([A-Z&\s]+?)\s+GRADE/i', $flat, $matches)) {
                return $this->normalizeSubjectName($matches[1]);
            }
        }

        return null;
    }

    protected function normalizeSubjectName(string $subject): string
    {
        $subject = trim(preg_replace('/\s+/', ' ', $subject));
        return Str::title(Str::lower($subject));
    }

    protected function syncDetectedSubject(CurriculumDesign $curriculumDesign, string $subjectName): void
    {
        $normalized = $this->normalizeSubjectName($subjectName);

        $subject = Subject::whereRaw('LOWER(name) = ?', [Str::lower($normalized)])->first();

        if (!$subject) {
            $codeBase = strtoupper(Str::slug($normalized, '_'));
            $code = $codeBase ?: Str::random(6);
            $suffix = 1;

            while (Subject::where('code', $code)->exists()) {
                $code = ($codeBase ?: 'SUBJECT') . '_' . $suffix++;
            }

            $subject = Subject::create([
                'name' => $normalized,
                'code' => $code,
                'is_active' => true,
                'is_optional' => false,
            ]);
        }

        $metadata = $curriculumDesign->metadata ?? [];
        $metadata['detected_subject'] = $normalized;

        $curriculumDesign->update([
            'subject_id' => $subject->id,
            'metadata' => $metadata,
        ]);
    }

    protected function syncDetectedGrades(CurriculumDesign $curriculumDesign, array $grades): void
    {
        $grades = array_values(array_unique(array_filter($grades)));
        if (empty($grades)) {
            return;
        }

        sort($grades);
        $labels = array_map(fn ($grade) => $this->formatGradeLabel((int) $grade), $grades);
        $lastLabel = $labels[count($labels) - 1];
        $range = count($labels) > 1 ? sprintf('%s - %s', $labels[0], $lastLabel) : $labels[0];

        $metadata = $curriculumDesign->metadata ?? [];
        $metadata['detected_grades'] = $labels;

        $curriculumDesign->update([
            'class_level' => $range,
            'metadata' => $metadata,
        ]);
    }

    protected function clearExistingData(CurriculumDesign $curriculumDesign): void
    {
        DB::transaction(function () use ($curriculumDesign) {
            $curriculumDesign->pages()->delete();
            $curriculumDesign->embeddings()->delete();

            $substrandIds = CBCSubstrand::whereHas('strand', function ($query) use ($curriculumDesign) {
                $query->where('curriculum_design_id', $curriculumDesign->id);
            })->pluck('id');

            if ($substrandIds->isNotEmpty()) {
                Competency::whereIn('substrand_id', $substrandIds)->delete();
                SuggestedExperience::whereIn('substrand_id', $substrandIds)->delete();
                AssessmentRubric::whereIn('substrand_id', $substrandIds)->delete();
                CBCSubstrand::whereIn('id', $substrandIds)->delete();
            }

            $curriculumDesign->strands()->delete();
            $curriculumDesign->learningAreas()->delete();
        });
    }

    protected function summarizeText(string $text, int $limit = 480): string
    {
        $clean = preg_replace('/\s+/', ' ', $text);
        return Str::limit(trim($clean), $limit);
    }

    /**
     * Python-based OCR fallback using pdfplumber + pytesseract
     */
    protected function performPythonOCR(string $filePath, int $pageNumber, string $language): string
    {
        $scriptPath = config('curriculum_ai.ocr.python_script');
        if (!$scriptPath || !file_exists($scriptPath)) {
            Log::warning('Python OCR script not found', [
                'script_path' => $scriptPath,
                'page' => $pageNumber,
            ]);
            return '';
        }

        $pythonBinary = config('curriculum_ai.ocr.python_binary', 'python');
        $tesseractPath = config('curriculum_ai.ocr.tesseract_path', 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe');
        $resolution = (int) config('curriculum_ai.ocr.page_resolution', 220);

        $command = escapeshellcmd($pythonBinary) . ' ' .
            escapeshellarg($scriptPath) . ' ' .
            escapeshellarg($filePath) . ' ' .
            escapeshellarg((string) (int) $pageNumber) . ' ' .
            escapeshellarg($language) . ' ' .
            escapeshellarg($tesseractPath) . ' ' .
            escapeshellarg((string) $resolution);

        $startTime = microtime(true);
        exec($command . ' 2>&1', $output, $returnCode);
        $duration = microtime(true) - $startTime;

        if ($returnCode === 0 && !empty($output)) {
            $text = $this->normalizeText(implode("\n", $output));
            if (strlen($text) > 10) {
                return $text;
            }
        }

        Log::warning('Python OCR failed', [
            'page' => $pageNumber,
            'return_code' => $returnCode,
            'duration' => round($duration, 2) . 's',
            'output_preview' => implode("\n", array_slice($output, 0, 5)),
        ]);

        return '';
    }
}

