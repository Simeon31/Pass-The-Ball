<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use App\Models\PostAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request)
    {
        $data = $request->validated();

        // Create the post
        $post = Post::create([
            'user_id' => $data['user_id'],
            'body' => $data['body'] ?? null,
        ]);

        // Handle attachments if present
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('post_attachments', $filename, 'public');

                PostAttachment::create([
                    'post_id' => $post->id,
                    'name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'created_by' => auth()->id(),
                ]);
            }
        }

        return back()->with('status', 'Post created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePostRequest $request, Post $post)
    {
        $data = $request->validated();

        // Update post body
        $post->update([
            'body' => $data['body'] ?? null,
        ]);

        // Handle deleted attachments
        if (!empty($data['deleted_attachments'])) {
            $deletedAttachments = PostAttachment::whereIn('id', $data['deleted_attachments'])
                ->where('post_id', $post->id)
                ->get();

            foreach ($deletedAttachments as $attachment) {
                // Delete file from storage
                $filePath = storage_path('app/public/' . $attachment->file_path);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                // Delete database record
                $attachment->delete();
            }
        }

        // Handle new attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('post_attachments', $filename, 'public');

                PostAttachment::create([
                    'post_id' => $post->id,
                    'name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'created_by' => auth()->id(),
                ]);
            }
        }

        return back()->with('status', 'Post updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        // Authorization check
        if (auth()->id() !== $post->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $post->delete();

        return back()->with('status', 'Post deleted successfully.');
    }

    /**
     * Download a post attachment.
     */
    public function downloadAttachment(PostAttachment $attachment)
    {
        $filePath = storage_path('app/public/' . $attachment->file_path);

        // Check if file exists
        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->download($filePath, $attachment->name);
    }
}
