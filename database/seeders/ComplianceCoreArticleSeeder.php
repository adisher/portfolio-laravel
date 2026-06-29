<?php

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds the first flagship marketing article (ComplianceCore) as a DRAFT.
 * Run ONCE, then review and publish via the admin editor. Do not re-run after
 * you start editing in the admin, or it will reset the content to this version.
 */
class ComplianceCoreArticleSeeder extends Seeder
{
    public function run(): void
    {
        $content = <<<'MD'
ComplianceCore didn't start as a product. It started as a take-home task in a job interview.

The role went to a candidate with more experience building systems like this one. I didn't get it. But the problem they handed me, checking vendor documents against a compliance policy, stayed with me long after the process ended. It was the kind of problem I couldn't put down, so I kept building, refining it well past anything the task asked for. This is what I learned, and what it turned into.

## Why this is genuinely hard, not just tedious

It's easy to look at vendor compliance review and think "that's just careful reading, throw more people at it." But the difficulty isn't volume. It's structure.

The rules live in one huge document. The proof that a vendor follows them is spread across several others. To check a single rule, you have to remember it, then search a contract, an addendum, and a certificate to see whether it's met.

Now repeat that for every rule, in every document, for every vendor. The gaps that slip through are rarely the obvious ones. They are the contradictions sitting three documents apart, where the contract says one thing and the security questionnaire quietly says another.

After dozens of these reviews, no one catches those reliably. Not because they are careless, but because holding hundreds of pages in your head and comparing them is something people are simply not built to do well.

## Why the obvious AI answer doesn't work

When people hear "use AI for this," they usually picture dropping the documents into a chatbot and asking "is this compliant?" I want to be honest about why that approach is dangerous here, because it's the same reason most people are right to distrust AI for high-stakes work.

A plain language model will give you a confident answer with no idea where it came from. In a compliance context, an unsourced "yes, this is compliant" is worse than useless, because it manufactures false confidence about something that carries real legal and financial risk. And the naive retrieval approach, just pulling the most similar-looking text, misses the cross-document reasoning that the hard cases actually require.

So the interesting problem was never "can AI read the documents." It was: can you build something that's actually trustworthy in a workflow where being wrong is expensive?

## What "trustworthy" actually requires

Working through that question is most of what I learned. A few principles held up:

**Every finding has to be traceable.** Not "this looks compliant," but "this requirement, on this page of your policy, is satisfied by this clause, on this page of their contract." If a person can't verify the finding in ten seconds by following the citation, the tool hasn't earned trust, it's just relocated the doubt.

**It has to reason across documents, not within them.** The whole value is catching the contradiction between the contract and the questionnaire. That means the system has to hold the full picture, not answer one document at a time.

**It has to know what it doesn't know.** The fastest way to lose a compliance team's trust is one confident, wrong finding. So low-confidence results get flagged for human review rather than asserted. The goal is to make a sharp reviewer faster, not to replace their judgment.

That last one matters most. This isn't "let the AI decide." It's "let the AI do the exhausting cross-referencing, and hand a clean, cited, prioritized report to the human who makes the call."

## What I built

That's what ComplianceCore is. It ingests the full policy playbook, processes the whole multi-document vendor package, and produces a report where every finding is cited to the exact page and section, contradictions between documents are flagged automatically, and low-confidence items are marked for review instead of hidden.

The review that took two to three days takes under five minutes. It runs 78 checks per vendor across five document types and groups what it finds by severity, so the reviewer starts with what matters. Under the hood it's a hybrid search and cross-document reasoning pipeline with reranking and an adaptive pool of models, but honestly, none of that is the point. The point is that a compliance lead can trust the output because they can see exactly where every finding came from.

## Where it stands

I built ComplianceCore to do exactly this, and it's ready for a hands-on demo. If your team reviews vendors at volume and you'd like to see it run against your actual policy and documents, not a sample dataset, reach out and I'll show you. And if you're solving a different problem where AI has to be genuinely trustworthy rather than just impressive, that's the kind of work I most like to take on.
MD;

        $category = Category::where('slug', 'ai-machine-learning')->first();
        $user = User::first();
        $wordCount = str_word_count(strip_tags($content));

        BlogPost::updateOrCreate(
            ['slug' => 'why-vendor-compliance-review-still-takes-3-days'],
            [
                'title'            => 'Why Vendor Compliance Review Still Takes 3 Days (And What I Did About It)',
                'excerpt'          => 'The story of building citation-backed AI for vendor compliance review, and why "trustworthy" was the real engineering problem.',
                'content'          => $content,
                'category_id'      => $category?->id,
                'user_id'          => $user?->id,
                'status'           => 'draft',
                'source_type'      => 'original',
                'meta_title'       => 'Why Vendor Compliance Review Still Takes 3 Days',
                'meta_description' => "Manual vendor compliance review takes 2-3 days and still misses gaps. Here's why it's genuinely hard, and how citation-backed AI makes it trustworthy.",
                'meta_keywords'    => ['vendor compliance review automation', 'procurement compliance software', 'AI contract compliance review'],
                'reading_time'     => max(1, (int) ceil($wordCount / 200)),
            ]
        );
    }
}
