<?php

namespace App\Services\AI;

use App\Models\Client;
use App\Models\Quote;
use Illuminate\Support\Collection;

class ContextAggregatorService
{
    /**
     * Build a rich context object for the AI quote generator.
     *
     * @param  int|null $clientId
     * @param  string   $jobType        Raw job description
     * @param  string   $serviceType    Extracted by IntentAnalyzerService
     * @param  array    $answers        Answers to clarifying questions [{id, answer}]
     * @param  string   $urgency
     * @param  array    $scopeHints
     * @return array
     */
    public function aggregate(
        ?int   $clientId,
        string $jobType,
        string $serviceType,
        array  $answers,
        string $urgency,
        array  $scopeHints
    ): array {
        // ── 1. Client data ────────────────────────────────────────────────
        $client     = $clientId ? Client::find($clientId) : null;
        $clientData = $this->buildClientData($client);

        // ── 2. Same client's past accepted/approved quotes ─────────────────
        $clientQuotes = collect();
        if ($clientId) {
            $clientQuotes = Quote::with('items')
                ->where('client_id', $clientId)
                ->whereIn('status', ['accepted', 'approved'])
                ->latest()
                ->take(5)
                ->get();
        }

        // ── 3. Same service-type past quotes (broader search, any client) ──
        $serviceQuotes = Quote::with('items')
            ->where(function ($q) use ($serviceType, $jobType) {
                $q->where('title', 'LIKE', "%{$serviceType}%")
                  ->orWhere('title', 'LIKE', "%{$jobType}%");
            })
            ->whereIn('status', ['accepted', 'approved'])
            ->latest()
            ->take(10)
            ->get();

        // ── 4. Merge, deduplicate, compute average pricing ─────────────────
        $allQuotes   = $clientQuotes->merge($serviceQuotes)->unique('id');
        $pastPricing = $this->computeAveragePricing($allQuotes);

        // ── 5. Confidence scoring (additive) ──────────────────────────────
        $confidence = 0.3; // base: market rates only
        if ($clientQuotes->isNotEmpty()) {
            $confidence += 0.2;
        }
        if ($serviceQuotes->count() >= 5) {
            $confidence += 0.3;
        } elseif ($serviceQuotes->isNotEmpty()) {
            $confidence += 0.15; // partial credit
        }
        if (!empty($answers)) {
            $confidence += 0.2;
        }
        $confidence = min(1.0, round($confidence, 2));

        // ── 6. Data source label ───────────────────────────────────────────
        $dataSource = 'market_rates';
        if ($clientQuotes->isNotEmpty() && $serviceQuotes->count() >= 5) {
            $dataSource = 'past_data';
        } elseif ($clientQuotes->isNotEmpty() || $serviceQuotes->isNotEmpty()) {
            $dataSource = 'mixed';
        }

        // ── 7. Build context string for AI prompt ─────────────────────────
        $contextString = $this->buildContextString(
            $clientData,
            $allQuotes,
            $pastPricing,
            $answers,
            $serviceType,
            $jobType,
            $urgency,
            $scopeHints
        );

        return [
            'client'           => $clientData,
            'past_pricing'     => $pastPricing,
            'context_string'   => $contextString,
            'confidence_score' => $confidence,
            'data_source'      => $dataSource,
            'urgency'          => $urgency,
            'service_type'     => $serviceType,
            'scope_hints'      => $scopeHints,
            'answers'          => $answers,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function buildClientData(?Client $client): array
    {
        if (!$client) {
            return [
                'name'           => 'Unknown Client',
                'type'           => 'residential',
                'industry'       => null,
                'payment_term'   => 'Net 30',
                'is_tax_applicable' => false,
                'tax_percentage' => 0,
                'currency'       => 'USD',
            ];
        }

        return [
            'name'              => $client->full_name,
            'type'              => $client->client_type,
            'industry'          => $client->industry,
            'payment_term'      => $client->payment_term ?? 'Net 30',
            'is_tax_applicable' => (bool) $client->is_tax_applicable,
            'tax_percentage'    => (int) ($client->tax_percentage ?? 0),
            'currency'          => $client->preferred_currency ?? 'USD',
        ];
    }

    /**
     * Compute average unit_price per item_name across all past quotes.
     *
     * @param  Collection $quotes
     * @return array  [{item_name, avg_price, sample_count}]
     */
    private function computeAveragePricing(Collection $quotes): array
    {
        $grouped = [];

        foreach ($quotes as $quote) {
            foreach ($quote->items as $item) {
                $key = strtolower(trim($item->item_name));
                if (!isset($grouped[$key])) {
                    $grouped[$key] = ['total' => 0, 'count' => 0, 'original_name' => $item->item_name];
                }
                $grouped[$key]['total'] += (float) $item->unit_price;
                $grouped[$key]['count']++;
            }
        }

        $result = [];
        foreach ($grouped as $data) {
            $result[] = [
                'item_name'    => $data['original_name'],
                'avg_price'    => round($data['total'] / $data['count'], 2),
                'sample_count' => $data['count'],
            ];
        }

        // Sort by sample_count desc (most reliable first)
        usort($result, fn ($a, $b) => $b['sample_count'] <=> $a['sample_count']);

        return $result;
    }

    /**
     * Build the formatted context string that goes into the Gemini prompt.
     */
    private function buildContextString(
        array      $clientData,
        Collection $allQuotes,
        array      $pastPricing,
        array      $answers,
        string     $serviceType,
        string     $jobType,
        string     $urgency,
        array      $scopeHints
    ): string {
        $lines = [];

        // Client section
        $taxStr = $clientData['is_tax_applicable']
            ? "Yes ({$clientData['tax_percentage']}%)"
            : 'No';

        $lines[] = "CLIENT CONTEXT:";
        $lines[] = "- Name: {$clientData['name']}";
        $lines[] = "- Type: {$clientData['type']} (commercial/residential)";
        $lines[] = "- Industry: " . ($clientData['industry'] ?? 'Not specified');
        $lines[] = "- Payment Terms: {$clientData['payment_term']}";
        $lines[] = "- Tax Applicable: {$taxStr}";
        $lines[] = "- Currency: {$clientData['currency']}";

        // Past pricing table
        $lines[] = "";
        if (!empty($pastPricing)) {
            $lines[] = "PAST PRICING DATA (from historical quotes):";
            $lines[] = str_pad('Item Name', 35) . str_pad('Avg Price', 12) . 'Samples';
            $lines[] = str_repeat('-', 60);
            foreach ($pastPricing as $p) {
                $lines[] = str_pad($p['item_name'], 35)
                    . str_pad('$' . number_format($p['avg_price'], 2), 12)
                    . $p['sample_count'];
            }
        } else {
            $lines[] = "PAST PRICING DATA: None available — use market rates.";
        }

        // Past quote titles for reference
        if ($allQuotes->isNotEmpty()) {
            $lines[] = "";
            $lines[] = "PAST QUOTE REFERENCES:";
            foreach ($allQuotes->take(5) as $q) {
                $lines[] = "- \"{$q->title}\" | Total: {$q->total_amount} {$clientData['currency']}";
            }
        }

        // Job details
        $lines[] = "";
        $lines[] = "JOB DETAILS:";
        $lines[] = "- Service Type: {$serviceType}";
        $lines[] = "- Description: {$jobType}";
        $lines[] = "- Urgency: {$urgency}";
        $lines[] = "- Scope Hints: " . (empty($scopeHints) ? 'None' : implode(', ', $scopeHints));

        // User-provided answers
        if (!empty($answers)) {
            $lines[] = "";
            $lines[] = "USER-PROVIDED DETAILS:";
            foreach ($answers as $answer) {
                $lines[] = "- {$answer['id']}: {$answer['answer']}";
            }
        }

        return implode("\n", $lines);
    }
}
