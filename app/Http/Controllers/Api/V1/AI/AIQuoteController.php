<?php

namespace App\Http\Controllers\Api\V1\AI;

use App\Http\Controllers\Api\V1\BaseController;
use App\Services\AI\GeminiService;
use App\Services\AI\IntentAnalyzerService;
use App\Services\AI\QuestionEngineService;
use App\Services\AI\ContextAggregatorService;
use Illuminate\Http\Request;
use Exception;

class AIQuoteController extends BaseController
{
    protected GeminiService $geminiService;
    protected IntentAnalyzerService $intentAnalyzer;
    protected QuestionEngineService $questionEngine;
    protected ContextAggregatorService $contextAggregator;

    public function __construct(
        GeminiService $geminiService,
        IntentAnalyzerService $intentAnalyzer,
        QuestionEngineService $questionEngine,
        ContextAggregatorService $contextAggregator
    ) {
        $this->geminiService     = $geminiService;
        $this->intentAnalyzer    = $intentAnalyzer;
        $this->questionEngine    = $questionEngine;
        $this->contextAggregator = $contextAggregator;
    }

    public function generateQuote(Request $request)
    {
        try {
            $request->validate([
                'client_id'   => 'nullable|integer',
                'client_name' => 'required|string',
                'client_type' => 'required|in:residential,commercial',
                'job_type'    => 'required|string',
            ]);
            $result = $this->geminiService->generateQuote(
                $request->client_name,
                $request->client_type,
                $request->job_type,
                $request->client_id
            );
            return $this->successResponse($result, 'Quote generated successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function analyzeIntent(Request $request)
    {
        try {
            $request->validate([
                'job_description' => 'required|string|min:5',
                'client_id'       => 'nullable|integer',
            ]);
            $jobDescription = $request->job_description;
            $intent = $this->intentAnalyzer->analyze($jobDescription);
            $questions = $this->questionEngine->getQuestions(
                $intent['service_type'],
                $intent['scope_hints'],
                $intent['urgency']
            );
            $sessionPayload = [
                'job_description' => $jobDescription,
                'client_id'       => $request->client_id,
                'service_type'    => $intent['service_type'],
                'scope_hints'     => $intent['scope_hints'],
                'urgency'         => $intent['urgency'],
                'ts'              => time(),
            ];
            $sessionId = base64_encode(json_encode($sessionPayload));
            return $this->successResponse([
                'service_type' => $intent['service_type'],
                'scope_hints'  => $intent['scope_hints'],
                'urgency'      => $intent['urgency'],
                'confidence'   => $intent['confidence'],
                'questions'    => $questions,
                'session_id'   => $sessionId,
            ], 'Intent analyzed successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function generateConversational(Request $request)
    {
        try {
            $request->validate([
                'session_id'       => 'nullable|string',
                'client_id'        => 'nullable|integer',
                'job_description'  => 'required|string',
                'answers'          => 'nullable|array',
                'answers.*.id'     => 'required|string',
                'answers.*.answer' => 'required|string',
                'service_type'     => 'required|string',
                'urgency'          => 'required|in:urgent,normal,scheduled',
                'scope_hints'      => 'nullable|array',
            ]);
            $clientId    = $request->client_id;
            $jobDesc     = $request->job_description;
            $serviceType = $request->service_type;
            $urgency     = $request->urgency;
            $scopeHints  = $request->scope_hints ?? [];
            $answers     = $request->answers     ?? [];
            $richContext = $this->contextAggregator->aggregate(
                $clientId, $jobDesc, $serviceType, $answers, $urgency, $scopeHints
            );
            $result = $this->geminiService->generateQuoteWithContext($richContext);
            return $this->successResponse($result, 'Conversational quote generated successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function generateLineItems(Request $request)
    {
        try {
            $request->validate([
                'job_description' => 'required|string|min:5',
                'client_id'       => 'nullable|integer',
            ]);
            $result = $this->geminiService->generateLineItemsOnly(
                $request->job_description,
                $request->client_id
            );
            return $this->successResponse($result, 'Line items generated successfully');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
