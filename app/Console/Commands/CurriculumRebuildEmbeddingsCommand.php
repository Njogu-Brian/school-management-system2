<?php

namespace App\Console\Commands;

use App\Models\CurriculumDesign;
use App\Models\CurriculumEmbedding;
use App\Services\EmbeddingService;
use Illuminate\Console\Command;

class CurriculumRebuildEmbeddingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'curriculum:rebuild-embeddings 
                            {--all : Rebuild embeddings for all curriculum designs}
                            {--id= : Rebuild embeddings for a specific curriculum design ID}';

    /**
     * The console command description.
     */
    protected $description = 'Rebuild embeddings for curriculum designs';

    protected EmbeddingService $embeddingService;

    public function __construct(EmbeddingService $embeddingService)
    {
        parent::__construct();
        $this->embeddingService = $embeddingService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $curriculumDesigns = CurriculumDesign::where('status', 'processed')->get();
            $this->info("Rebuilding embeddings for all {$curriculumDesigns->count()} curriculum designs...");
        } elseif ($id = $this->option('id')) {
            $curriculumDesign = CurriculumDesign::find($id);
            if (!$curriculumDesign) {
                $this->error("Curriculum design with ID {$id} not found.");
                return 1;
            }
            $curriculumDesigns = collect([$curriculumDesign]);
        } else {
            $this->error("Please specify --all or --id=<curriculum_design_id>");
            return 1;
        }

        $bar = $this->output->createProgressBar($curriculumDesigns->count());
        $bar->start();

        foreach ($curriculumDesigns as $curriculumDesign) {
            $this->rebuildEmbeddingsForDesign($curriculumDesign);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Embeddings rebuild completed!");

        return 0;
    }

    /**
     * Rebuild embeddings for a specific curriculum design
     */
    protected function rebuildEmbeddingsForDesign(CurriculumDesign $curriculumDesign): void
    {
        // Delete existing embeddings
        CurriculumEmbedding::where('curriculum_design_id', $curriculumDesign->id)->delete();

        // Rebuild embeddings from learning areas, strands, substrands, competencies
        $this->info("\nRebuilding embeddings for: {$curriculumDesign->title}");

        // Embed competencies
        foreach ($curriculumDesign->learningAreas as $learningArea) {
            foreach ($learningArea->strands as $strand) {
                foreach ($strand->substrands as $substrand) {
                    // Embed competencies
                    foreach ($substrand->competencies as $competency) {
                        $text = $competency->description ?? $competency->name;
                        $embedding = $this->embeddingService->generateEmbedding($text);
                        
                        if ($embedding) {
                            $this->embeddingService->storeEmbedding(
                                $curriculumDesign->id,
                                'competency',
                                $competency->id,
                                $text,
                                $embedding,
                                ['page' => null, 'competency_code' => $competency->code]
                            );
                        }
                    }

                    // Embed suggested experiences
                    foreach ($substrand->suggestedExperiences as $experience) {
                        $text = $experience->content;
                        $embedding = $this->embeddingService->generateEmbedding($text);
                        
                        if ($embedding) {
                            $this->embeddingService->storeEmbedding(
                                $curriculumDesign->id,
                                'experience',
                                $experience->id,
                                $text,
                                $embedding,
                                ['page' => null]
                            );
                        }
                    }
                }
            }
        }

        $this->info("Completed: {$curriculumDesign->title}");
    }
}
