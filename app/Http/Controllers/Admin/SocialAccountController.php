<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\Social\SocialPublisher;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SocialAccountController extends Controller
{
    public function __construct(private SocialPublisher $publisher) {}

    public function index()
    {
        $accounts = SocialAccount::withCount([
            'socialPosts as posted_count' => fn ($q) => $q->where('status', 'posted'),
        ])->latest()->get();

        $recent = SocialPost::with(['blogPost', 'socialAccount'])
            ->latest()
            ->take(20)
            ->get();

        return view('admin.social.index', compact('accounts', 'recent'));
    }

    public function create()
    {
        return view('admin.social.create', [
            'drivers' => $this->publisher->drivers(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateBase($request);
        $this->assertRequiredCredentials($request, $data['platform']);

        SocialAccount::create($this->mapAccount($data, $request));

        return redirect()->route('admin.social.index')
            ->with('success', 'Social account connected.');
    }

    public function edit(SocialAccount $social)
    {
        return view('admin.social.edit', [
            'account' => $social,
            'drivers' => $this->publisher->drivers(),
        ]);
    }

    public function update(Request $request, SocialAccount $social)
    {
        $data = $this->validateBase($request);

        $social->update($this->mapAccount($data, $request, $social));

        return redirect()->route('admin.social.index')
            ->with('success', 'Social account updated.');
    }

    public function destroy(SocialAccount $social)
    {
        $social->delete();

        return redirect()->route('admin.social.index')
            ->with('success', 'Social account removed.');
    }

    /** Flip the per-account auto-posting gate. */
    public function toggleAuto(SocialAccount $social)
    {
        $social->update(['auto_post_enabled' => ! $social->auto_post_enabled]);

        return back()->with('success', sprintf(
            'Auto-posting %s for %s.',
            $social->auto_post_enabled ? 'enabled' : 'disabled',
            $social->name
        ));
    }

    /** The publish dashboard: articles + their per-account posting status. */
    public function publishBoard(Request $request)
    {
        $accounts = SocialAccount::active()->get();

        $articles = BlogPost::published()
            ->with(['category', 'tags', 'socialPosts'])
            ->latest('published_at')
            ->paginate(20);

        return view('admin.social.publish', compact('accounts', 'articles'));
    }

    /** Post one specific article to one specific account, on demand. */
    public function postNow(Request $request)
    {
        $validated = $request->validate([
            'blog_post_id'      => 'required|exists:blog_posts,id',
            'social_account_id' => 'required|exists:social_accounts,id',
        ]);

        $post    = BlogPost::findOrFail($validated['blog_post_id']);
        $account = SocialAccount::findOrFail($validated['social_account_id']);

        if (! $account->is_active) {
            return back()->with('error', "{$account->name} is inactive.");
        }

        $log = $this->publisher->publish($account, $post, 'manual');

        return $log->status === 'posted'
            ? back()->with('success', "Posted \"{$post->title}\" to {$account->name}.")
            : back()->with('error', "Failed to post to {$account->name}: {$log->error}");
    }

    /** Preview the composed caption for an article + account (AJAX/JSON). */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'blog_post_id'      => 'required|exists:blog_posts,id',
            'social_account_id' => 'required|exists:social_accounts,id',
        ]);

        $post    = BlogPost::with(['category', 'tags'])->findOrFail($validated['blog_post_id']);
        $account = SocialAccount::findOrFail($validated['social_account_id']);

        return response()->json([
            'caption' => $this->publisher->composeCaption($account, $post),
        ]);
    }

    /** Validate the platform-independent fields; credentials handled separately. */
    private function validateBase(Request $request): array
    {
        $platforms = implode(',', array_keys($this->publisher->drivers()));

        return $request->validate([
            'platform'          => "required|string|in:{$platforms}",
            'name'              => 'required|string|max:255',
            'caption_template'  => 'nullable|string|max:2000',
            'min_human_views'   => 'required|integer|min:0|max:100000',
            'is_active'         => 'boolean',
            'auto_post_enabled' => 'boolean',
        ]);
    }

    /** On create, ensure every credential the driver marks required is present. */
    private function assertRequiredCredentials(Request $request, string $platform): void
    {
        $driver = $this->publisher->drivers()[$platform] ?? null;
        if (! $driver) {
            return;
        }

        $missing = [];
        foreach ($driver->credentialFields() as $key => $meta) {
            if (! empty($meta['required'])) {
                $value = $request->input("cred.{$platform}.{$key}");
                if (! is_string($value) || trim($value) === '') {
                    $missing[] = $meta['label'];
                }
            }
        }

        if ($missing) {
            throw ValidationException::withMessages([
                'credentials' => 'Missing required credential(s): ' . implode(', ', $missing) . '.',
            ]);
        }
    }

    /**
     * Build the model attributes, folding each driver's declared credential
     * fields into the encrypted array. Values are read from cred[{platform}][*]
     * and trimmed (a stray space corrupts tokens). On edit, a blank password
     * field keeps the stored secret so it is not wiped.
     */
    private function mapAccount(array $data, Request $request, ?SocialAccount $social = null): array
    {
        $platform = $data['platform'];
        $driver   = $this->publisher->drivers()[$platform] ?? null;
        $fields   = $driver ? $driver->credentialFields() : [];

        $credentials = [];
        foreach ($fields as $key => $meta) {
            $value = $request->input("cred.{$platform}.{$key}");
            $value = is_string($value) ? trim($value) : $value;

            if (($meta['type'] ?? 'text') === 'password' && ($value === null || $value === '')) {
                $value = $social ? $social->credential($key) : null;
            }

            $credentials[$key] = $value;
        }

        return [
            'platform'          => $platform,
            'name'              => $data['name'],
            'credentials'       => $credentials,
            'caption_template'  => $data['caption_template'] ?? null,
            'min_human_views'   => $data['min_human_views'],
            'is_active'         => $request->boolean('is_active'),
            'auto_post_enabled' => $request->boolean('auto_post_enabled'),
        ];
    }
}
