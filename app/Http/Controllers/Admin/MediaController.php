<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $folder = $request->get('folder', '/');
        $type = $request->get('type');
        $search = $request->get('search');

        $query = Media::with('user')
            ->inFolder($folder)
            ->latest();

        if ($type) {
            $query->byType($type);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('alt_text', 'like', '%' . $search . '%');
            });
        }

        $media = $query->paginate(24);

        // Get folder structure
        $folders = $this->getFolders();

        // Get storage stats
        $stats = $this->getStorageStats();

        if ($request->ajax()) {
            return response()->json([
                'media' => $media,
                'folders' => $folders,
                'stats' => $stats
            ]);
        }

        return view('admin.media.index', compact('media', 'folders', 'stats', 'folder', 'type', 'search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'files.*' => 'required|file|mimes:jpeg,png,jpg,gif,svg,pdf,doc,docx|max:10240',
            'folder' => 'nullable|string',
        ]);

        $folder = $request->get('folder', '/');
        $uploadedFiles = [];

        foreach ($request->file('files') as $file) {
            try {
                $media = Media::uploadFile($file, $folder);
                $uploadedFiles[] = $media;
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Failed to upload ' . $file->getClientOriginalName() . ': ' . $e->getMessage()
                ], 422);
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($uploadedFiles) . ' file(s) uploaded successfully',
            'files' => $uploadedFiles
        ]);
    }

    public function show(Media $media)
    {
        return response()->json($media->load('user'));
    }

    public function update(Request $request, Media $media)
    {
        $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'folder' => 'nullable|string|max:255',
        ]);

        $media->update($request->only(['alt_text', 'description', 'folder']));

        return response()->json([
            'success' => true,
            'message' => 'Media updated successfully',
            'media' => $media
        ]);
    }

    public function destroy(Media $media)
    {
        try {
            $media->deleteFile();
            
            return response()->json([
                'success' => true,
                'message' => 'Media deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete media: ' . $e->getMessage()
            ], 422);
        }
    }

    public function createFolder(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent' => 'nullable|string',
        ]);

        $parent = $request->get('parent', '/');
        $name = $request->get('name');
        
        // Clean folder name
        $folderName = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $name);
        $folderPath = trim($parent, '/') . '/' . $folderName;

        // Create directory
        Storage::disk('public')->makeDirectory('media' . $folderPath);

        return response()->json([
            'success' => true,
            'message' => 'Folder created successfully',
            'folder' => $folderPath
        ]);
    }

    public function picker(Request $request)
    {
        $type = $request->get('type', 'image');
        $multiple = $request->boolean('multiple', false);

        $media = Media::when($type === 'image', function ($query) {
                return $query->images();
            })
            ->latest()
            ->paginate(20);

        return view('admin.media.picker', compact('media', 'type', 'multiple'));
    }

    private function getFolders()
    {
        return Media::select('folder')
            ->distinct()
            ->orderBy('folder')
            ->pluck('folder')
            ->filter()
            ->values();
    }

    private function getStorageStats()
    {
        $totalFiles = Media::count();
        $totalSize = Media::sum('size');
        $imageCount = Media::images()->count();
        $recentUploads = Media::where('created_at', '>=', now()->subDays(7))->count();

        return [
            'total_files' => $totalFiles,
            'total_size' => $totalSize,
            'formatted_size' => $this->formatBytes($totalSize),
            'image_count' => $imageCount,
            'recent_uploads' => $recentUploads,
        ];
    }

    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}