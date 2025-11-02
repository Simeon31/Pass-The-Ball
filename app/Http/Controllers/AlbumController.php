<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAlbumRequest;
use App\Http\Requests\UpdateAlbumRequest;
use App\Http\Resources\AlbumResource;
use App\Http\Resources\PhotoResource;
use App\Models\Album;
use App\Models\User;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlbumController extends Controller
{
    protected ImageOptimizationService $imageService;

    public function __construct(ImageOptimizationService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Display a listing of albums for a user.
     */
    public function index(Request $request, string $username): Response
    {
        $user = User::where('username', $username)->firstOrFail();
        $search = $request->input('search');

        // Build query based on viewing user permissions with eager loading
        $query = Album::where('user_id', $user->id)
            ->with('user')
            ->withCount('photos');

        // Apply search filter
        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        // Apply visibility filters
        if (auth()->id() === $user->id) {
            // Owner sees all their albums
            $query->latest();
        } else {
            // Others see only public albums (can extend for followers_only)
            $query->where('visibility', 'public')->latest();
        }

        $albums = $query->paginate(12);

        return Inertia::render('Gallery/Index', [
            'profileUser' => $user,
            'albums' => AlbumResource::collection($albums),
            'isOwner' => auth()->id() === $user->id,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Store a newly created album.
     */
    public function store(StoreAlbumRequest $request)
    {
        $this->authorize('create', Album::class);

        $data = $request->validated();

        $album = Album::create([
            'user_id' => auth()->id(),
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'visibility' => $data['visibility'],
        ]);

        // Handle cover image if provided
        if ($request->hasFile('cover')) {
            $coverPath = $this->imageService->processCoverImage(
                $request->file('cover'),
                "albums/{$album->user_id}/{$album->slug}",
                $album->title
            );

            $album->cover_path = '/storage/' . $coverPath;
            $album->save();
        }

        return back()->with('status', 'Album created successfully.');
    }

    /**
     * Display the specified album with its photos.
     */
    public function show(Request $request, string $username, Album $album): Response
    {
        $user = User::where('username', $username)->firstOrFail();

        if ($album->user_id !== $user->id) {
            abort(404);
        }

        // Check viewing permissions using policy
        $this->authorize('view', $album);

        // Load photos with relationships
        $photos = $album->photos()
            ->with(['user', 'tags'])
            ->latest()
            ->paginate(24);

        return Inertia::render('Gallery/Show', [
            'profileUser' => $user,
            'album' => new AlbumResource($album->load('user')),
            'photos' => PhotoResource::collection($photos),
            'isOwner' => auth()->id() === $album->user_id,
        ]);
    }

    /**
     * Update the specified album.
     */
    public function update(UpdateAlbumRequest $request, string $username, Album $album)
    {
        $this->authorize('update', $album);

        $data = $request->validated();

        // Update album fields
        $album->update(array_filter([
            'title' => $data['title'] ?? $album->title,
            'description' => $data['description'] ?? $album->description,
            'visibility' => $data['visibility'] ?? $album->visibility,
        ]));

        // Handle cover image update if provided
        if ($request->hasFile('cover')) {
            // Delete old cover if exists
            if ($album->cover_path) {
                $this->imageService->deleteImage($album->cover_path);
            }

            // Process new cover
            $coverPath = $this->imageService->processCoverImage(
                $request->file('cover'),
                "albums/{$album->user_id}/{$album->slug}",
                $album->title
            );

            $album->cover_path = '/storage/' . $coverPath;
            $album->save();
        }

        return redirect()->route('gallery.albums.show', [
            'username' => $username,
            'album' => $album->slug,
        ])->with('status', 'Album updated successfully.');
    }

    /**
     * Remove the specified album (soft delete).
     */
    public function destroy(string $username, Album $album)
    {
        $this->authorize('delete', $album);

        // Soft delete the album (cascade deletes photos via model events)
        $album->deleted_by = auth()->id();
        $album->save();
        $album->delete();

        return redirect()->route('gallery.index', ['username' => auth()->user()->username])
            ->with('status', 'Album deleted successfully.');
    }
}
