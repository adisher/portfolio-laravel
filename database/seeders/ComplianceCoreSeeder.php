<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\WorkItem;
use Illuminate\Database\Seeder;

/**
 * Canonical manual for ComplianceCore. One seeder per work item so running
 * one never overwrites another. Idempotent: matched by name.
 */
class ComplianceCoreSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'type'  => 'product',
            'active' => true,
            'sort_order' => 1,

            'tagline' => 'AI-powered vendor compliance review: from 2-3 days to under 5 minutes, with citation-backed findings.',

            'target_audience' => 'Enterprise procurement teams and third-party risk managers in regulated industries, and any organization reviewing vendor packages against internal policy at volume.',

            'how_it_helps' => "Ingests the full policy playbook and processes multi-document vendor packages (contracts, insurance certificates, security questionnaires), surfacing compliance gaps with precise citations to both the source document and the policy requirement. Uses hybrid semantic and keyword search, cross-document reasoning, and an adaptive multi-model AI pool. Flags contradictions between documents automatically and generates vendor-facing gap summaries with remediation actions. It assists human judgment rather than replacing it: low-confidence findings are explicitly flagged for manual review.",

            'call_to_action' => "If vendor compliance is eating your team's time, get in touch and I'll walk you through how ComplianceCore handles your actual policy and vendor documents.",

            'tech_stack' => 'Laravel, FastAPI, Python, Claude API, PostgreSQL, Celery, RAG (hybrid search + cross-encoder reranking)',

            'url' => null,

            'pain_points' => [
                'Manual vendor compliance review takes 2-3 days per vendor',
                'Compliance gaps get buried across multiple documents and slip through',
                'Policies span 500+ pages, so humans cannot reliably cross-reference every requirement',
                'Reviewing hundreds of vendor packages a year does not scale with manual effort',
                "Contradictions between a vendor's own documents go unnoticed",
            ],

            'objections' => [
                'Can I trust AI for something as high-stakes as compliance?',
                'Is our sensitive vendor and policy data secure?',
                'We already have a review process and a team for this',
                'AI hallucinates: what if it invents a finding or misses a real one?',
                'Onboarding a new tool into our workflow is too much effort',
            ],

            'key_outcomes' => [
                'Review time cut from 2-3 days to under 5 minutes',
                '78 compliance checks per vendor across 5 document types',
                'Every finding cited to the exact page and policy section',
                'Contradictions between documents flagged automatically',
                'Structured report grouped by severity and category, with PDF export',
            ],

            'proof_links' => [
                // Demo video URL goes here once recorded.
            ],

            'differentiators' => [
                'Citation-backed findings that trace to the exact source and policy section, not opaque AI output',
                'Cross-document reasoning catches issues that span multiple files',
                'Adaptive multi-model AI pool with dynamic rate-limit handling across 6 LLMs',
                'Assists rather than replaces human judgment; low-confidence findings are flagged',
                'Production-grade async architecture: Celery, real-time progress, cross-encoder reranking',
            ],

            'target_keywords' => [
                'vendor compliance review automation',
                'procurement compliance software',
                'third-party risk management automation',
                'AI contract compliance review',
                'vendor due diligence automation',
            ],

            'article_angles' => [
                'Why your team still spends 3 days reviewing every vendor (and what that costs)',
                'The compliance gaps hiding in your vendor documents right now',
                'What I learned building citation-backed AI for high-stakes document review',
                'RAG is not enough: why vendor compliance needs cross-document reasoning',
                'How to make AI you can actually trust inside a compliance workflow',
            ],

            'notes' => 'Status: private beta. No live AI demo (token cost); a demo video for prospects is pending and will go in Proof Links. Live demo reserved for actual demo bookings.',
        ];

        // Link to the existing portfolio project by name (IDs differ per env)
        $data['project_id'] = Project::where('title', 'ComplianceCore')->value('id');

        WorkItem::updateOrCreate(['name' => 'ComplianceCore'], $data);
    }
}
