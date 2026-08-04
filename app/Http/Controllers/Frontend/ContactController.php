<?php
namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Mail\ContactFormAutoReply;
use App\Mail\ContactFormSubmitted;
use App\Models\Contact;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function index()
    {
        // Get contact-related settings
        $contactSettings = [
            'email' => Setting::get('contact_email', 'hello@portfolio.com'),
            'phone' => Setting::get('contact_phone', '+1 (555) 123-4567'),
            'location' => Setting::get('contact_location', 'Available for remote work worldwide'),
            'response_time' => Setting::get('contact_response_time', 'Usually within 24 hours'),
            'social_twitter' => Setting::get('social_twitter'),
            'social_linkedin' => Setting::get('social_linkedin'),
            'social_github' => Setting::get('social_github'),
            'social_behance' => Setting::get('social_behance'),
            'social_dribbble' => Setting::get('social_dribbble'),
            'social_instagram' => Setting::get('social_instagram'),
        ];

        return view('frontend.contact', compact('contactSettings'));
    }

    public function store(Request $request)
    {
        // ── Bot traps ────────────────────────────────────────────────
        // Return the normal success response so bots believe it worked and
        // don't adapt — but save nothing and send no email.
        $successMessage = 'Thank you for your message! I\'ll get back to you soon.';

        // 1. Honeypot: the off-screen "website" field is invisible to humans.
        if (filled($request->input('website'))) {
            return back()->with('success', $successMessage);
        }

        // 2. Timing: a submission faster than a human could fill the form, or a
        //    missing/tampered token, is automated. 2s is safely below real use.
        try {
            $loadedAt = (int) decrypt($request->input('form_loaded_at'));
        } catch (\Throwable $e) {
            $loadedAt = 0;
        }
        if ($loadedAt === 0 || (now()->timestamp - $loadedAt) < 2) {
            return back()->with('success', $successMessage);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        $contact = Contact::create($validated);

        // Track contact analytics
        $this->trackContactAnalytics($contact, $request);

        try {
            // Send emails...
            Mail::to(config('mail.admin_email', 'admin@portfolio.com'))
                ->send(new ContactFormSubmitted($contact));

            Mail::to($contact->email)
                ->send(new ContactFormAutoReply($contact));

        } catch (\Exception $e) {
            \Log::error('Failed to send contact form emails: ' . $e->getMessage());
        }

        return back()->with('success', $successMessage);
    }

    private function trackContactAnalytics($contact, $request)
    {
        try {
            // Get current visitor
            $visitor = \App\Models\Visitor::where('session_id', session()->getId())->first();
            
            if (!$visitor) {
                return; // No tracking data available
            }

            // Get visitor's journey data
            $pageViews = $visitor->pageViews()->get();
            $viewedProjects = $pageViews->where('content_type', 'project')
                ->pluck('content_id')
                ->filter()
                ->values()
                ->toArray();
            $viewedBlogPosts = $pageViews->where('content_type', 'blog_post')
                ->pluck('content_id')
                ->filter()
                ->values()
                ->toArray();

            \App\Models\ContactAnalytic::create([
                'contact_id' => $contact->id,
                'visitor_id' => $visitor->id,
                'source_page' => $request->header('referer'),
                'referrer' => $visitor->referrer,
                'utm_source' => $visitor->utm_source,
                'utm_medium' => $visitor->utm_medium,
                'utm_campaign' => $visitor->utm_campaign,
                'pages_viewed_before_contact' => $pageViews->count(),
                'time_on_site_before_contact' => $visitor->session_duration,
                'viewed_projects' => $viewedProjects,
                'viewed_blog_posts' => $viewedBlogPosts,
                'is_returning_visitor' => $pageViews->count() > 1,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to track contact analytics: ' . $e->getMessage());
        }
    }
}
