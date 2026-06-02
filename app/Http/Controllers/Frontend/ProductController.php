<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Project;

class ProductController extends Controller
{
    public function show($slug)
    {
        $product = Project::published()
            ->ownProducts()
            ->with(['category', 'tags', 'images'])
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedProducts = Project::published()
            ->ownProducts()
            ->where('id', '!=', $product->id)
            ->orderBy('sort_order')
            ->take(3)
            ->get();

        return view('frontend.product-detail', compact('product', 'relatedProducts'));
    }

    public function page($productSlug, $pageSlug)
    {
        $product = Project::published()
            ->ownProducts()
            ->where('slug', $productSlug)
            ->firstOrFail();

        $page = $product->productPages()
            ->published()
            ->where('slug', $pageSlug)
            ->firstOrFail();

        // Access control for protected page types (setup, deploy)
        if (in_array($page->type, ['setup', 'deploy'])) {
            $orderToken = request('token');
            $hasAccess = false;

            // Check by order token in URL
            if ($orderToken) {
                $hasAccess = Order::where('order_token', $orderToken)
                    ->where('project_id', $product->id)
                    ->where('status', 'paid')
                    ->exists();
            }

            // Admin (site owner) always has access
            if (!$hasAccess && auth()->check()) {
                $hasAccess = true;
            }

            if (!$hasAccess) {
                return redirect()->route('products.show', $product->slug)
                    ->with('error', 'Please purchase this product to access this page.');
            }
        }

        $template = match ($page->type) {
            'setup'  => 'frontend.product-page-setup',
            'deploy' => 'frontend.product-page-deploy',
            default  => 'frontend.product-page-custom',
        };

        return view($template, compact('product', 'page'));
    }
}
