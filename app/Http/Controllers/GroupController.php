<?php

namespace App\Http\Controllers;

use App\Enums\GroupRole;
use App\Http\Requests\Group\ApproveJoinRequestRequest;
use App\Http\Requests\Group\InviteMemberRequest;
use App\Http\Requests\Group\JoinGroupRequest;
use App\Http\Requests\Group\StoreGroupRequest;
use App\Http\Requests\Group\UpdateGroupImagesRequest;
use App\Http\Requests\Group\UpdateGroupRequest;
use App\Http\Resources\GroupInvitationResource;
use App\Http\Resources\GroupMemberResource;
use App\Http\Resources\GroupResource;
use App\Http\Resources\PostResource;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use App\Notifications\GroupInvitationNotification;
use App\Notifications\GroupJoinRequestNotification;
use App\Services\GroupInvitationService;
use App\Services\GroupPermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class GroupController extends Controller
{
    public function __construct(
        protected GroupPermissionService $permissionService,
        protected GroupInvitationService $invitationService
    ) {
    }

    /**
     * Display a listing of groups (discovery page).
     */
    public function index(Request $request): Response
    {
        $query = Group::with('owner')
            ->withCount('members');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by user's groups
        if ($request->has('filter') && $request->filter === 'my-groups') {
            $query->whereHas('members', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        $groups = $query->latest()->paginate(12);

        return Inertia::render('Groups/Discover', [
            'groups' => GroupResource::collection($groups),
            'filters' => [
                'search' => $request->search,
                'filter' => $request->filter,
            ],
        ]);
    }

    /**
     * Show the form for creating a new group.
     */
    public function create(): Response
    {
        $this->authorize('create', Group::class);

        return Inertia::render('Groups/Create');
    }

    /**
     * Store a newly created group in storage.
     */
    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $group = Group::create([
            'name' => $data['name'],
            'about' => $data['about'] ?? null,
            'auto_approval' => $data['auto_approval'] ?? true,
            'user_id' => auth()->id(),
        ]);

        // Add creator as admin
        $group->members()->attach(auth()->id(), [
            'status' => 'approved',
            'role' => GroupRole::ADMIN->value,
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        return redirect()->route('groups.show', $group->slug)
            ->with('status', 'Group created successfully!');
    }

    /**
     * Display the specified group.
     */
    public function show(Group $group): Response
    {
        $user = auth()->user();
        $isMember = $user && $group->isMember($user);

        // Load group with relationships
        $group->load([
            'owner',
            'members' => function ($query) {
                $query->limit(10);
            }
        ]);

        // Load posts if user is a member
        $posts = null;
        if ($isMember) {
            $posts = $group->posts()
                ->with(['user', 'reactions', 'comments.user', 'comments.reactions', 'attachments'])
                ->latest()
                ->paginate(10);
            $posts = PostResource::collection($posts);
        }

        // Get pending join requests count for admins/moderators
        $pendingRequestsCount = 0;
        if ($user && $this->permissionService->canApproveRequests($user, $group)) {
            $pendingRequestsCount = $group->pendingRequests()->count();
        }

        // Check if user has a pending join request
        $hasPendingRequest = false;
        if ($user && !$isMember) {
            $hasPendingRequest = $group->pendingRequests()
                ->where('user_id', $user->id)
                ->exists();
        }

        return Inertia::render('Groups/Show', [
            'group' => new GroupResource($group),
            'posts' => $posts,
            'members' => GroupMemberResource::collection($group->members),
            'pendingRequestsCount' => $pendingRequestsCount,
            'hasPendingRequest' => $hasPendingRequest,
        ]);
    }

    /**
     * Show the form for editing the specified group.
     */
    public function edit(Group $group): Response
    {
        $this->authorize('update', $group);

        return Inertia::render('Groups/Edit', [
            'group' => new GroupResource($group),
        ]);
    }

    /**
     * Update the specified group in storage.
     */
    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $data = $request->validated();

        $group->update($data);

        return back()->with('status', 'Group updated successfully!');
    }

    /**
     * Remove the specified group from storage.
     */
    public function destroy(Group $group): RedirectResponse
    {
        $this->authorize('delete', $group);

        $group->delete();

        return redirect()->route('groups.index')
            ->with('status', 'Group deleted successfully!');
    }

    /**
     * Update group images (cover and thumbnail).
     */
    public function updateImages(UpdateGroupImagesRequest $request, Group $group): RedirectResponse
    {
        $manager = new ImageManager(new Driver());

        // Handle cover upload
        if ($request->hasFile('cover')) {
            // Delete old cover
            if ($group->cover_path) {
                $old = $group->cover_path;
                if (str_starts_with($old, '/storage/')) {
                    $old = substr($old, strlen('/storage/'));
                }
                Storage::disk('public')->delete($old);
            }

            // Process and optimize cover image
            $file = $request->file('cover');
            $timestamp = time();
            $filename = 'cover_' . $timestamp . '_' . uniqid() . '.jpg';
            $path = 'groups/' . $group->id . '/' . $filename;

            // Resize and optimize cover (1200x400)
            $image = $manager->read($file);
            $image->cover(1200, 400);

            Storage::disk('public')->put($path, $image->toJpeg(85));
            $group->cover_path = '/storage/' . $path;
        }

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail
            if ($group->thumbnail_path) {
                $old = $group->thumbnail_path;
                if (str_starts_with($old, '/storage/')) {
                    $old = substr($old, strlen('/storage/'));
                }
                Storage::disk('public')->delete($old);
            }

            // Process and optimize thumbnail
            $file = $request->file('thumbnail');
            $timestamp = time();
            $filename = 'thumbnail_' . $timestamp . '_' . uniqid() . '.jpg';
            $path = 'groups/' . $group->id . '/' . $filename;

            // Resize and optimize thumbnail (300x300)
            $image = $manager->read($file);
            $image->cover(300, 300);

            Storage::disk('public')->put($path, $image->toJpeg(85));
            $group->thumbnail_path = '/storage/' . $path;
        }

        $group->save();

        return redirect()->route('groups.show', $group->slug)
            ->with('status', 'Group images updated successfully!');
    }

    /**
     * Display group members.
     */
    public function members(Group $group): Response
    {
        $members = $group->members()
            ->withPivot(['status', 'role', 'created_at'])
            ->paginate(20);

        return Inertia::render('Groups/Members', [
            'group' => new GroupResource($group),
            'members' => GroupMemberResource::collection($members),
        ]);
    }

    /**
     * Invite a user to the group.
     */
    public function inviteMember(InviteMemberRequest $request, Group $group): RedirectResponse
    {
        $data = $request->validated();
        $user = User::findOrFail($data['user_id']);

        // Check if user is already a member
        if ($group->isMember($user)) {
            return back()->withErrors(['user_id' => 'User is already a member of this group.']);
        }

        // Create invitation
        $invitation = $this->invitationService->createInvitation(
            $group,
            $user,
            auth()->user()
        );

        // Send notification
        $user->notify(new GroupInvitationNotification($invitation));

        return back()->with('status', 'Invitation sent successfully!');
    }

    /**
     * Respond to a group invitation.
     */
    public function respondToInvitation(Request $request, string $token): RedirectResponse
    {
        $request->validate([
            'action' => ['required', 'in:accept,reject'],
        ]);

        $invitation = $this->invitationService->findByToken($token);

        if (!$invitation) {
            return redirect()->route('groups.index')
                ->withErrors(['invitation' => 'Invalid invitation token.']);
        }

        if (!$invitation->isValid()) {
            return redirect()->route('groups.index')
                ->withErrors(['invitation' => 'This invitation has expired or has already been used.']);
        }

        // Check if user is authorized
        if ($invitation->user_id !== auth()->id()) {
            abort(403);
        }

        if ($request->action === 'accept') {
            $this->invitationService->acceptInvitation($invitation);
            return redirect()->route('groups.show', $invitation->group->slug)
                ->with('status', 'You have successfully joined the group!');
        } else {
            $this->invitationService->rejectInvitation($invitation);
            return redirect()->route('groups.index')
                ->with('status', 'Invitation rejected.');
        }
    }

    /**
     * Join a group (or request to join).
     */
    public function join(JoinGroupRequest $request, Group $group): RedirectResponse
    {
        $user = auth()->user();

        // Check if already a member
        if ($group->isMember($user)) {
            return back()->withErrors(['group' => 'You are already a member of this group.']);
        }

        // Check if there's a pending request
        $pendingRequest = $group->pendingRequests()
            ->where('user_id', $user->id)
            ->exists();

        if ($pendingRequest) {
            return back()->withErrors(['group' => 'You already have a pending join request.']);
        }

        if ($group->auto_approval) {
            // Auto-approve: immediately add as member
            $group->members()->attach($user->id, [
                'status' => 'approved',
                'role' => GroupRole::MEMBER->value,
                'created_by' => $user->id,
                'created_at' => now(),
            ]);

            return back()->with('status', 'You have successfully joined the group!');
        } else {
            // Manual approval: create pending request
            $group->members()->attach($user->id, [
                'status' => 'pending',
                'role' => GroupRole::MEMBER->value,
                'created_by' => $user->id,
                'created_at' => now(),
            ]);

            // Notify group admins
            $admins = $group->members()
                ->wherePivot('role', GroupRole::ADMIN->value)
                ->get();

            foreach ($admins as $admin) {
                $admin->notify(new GroupJoinRequestNotification($group, $user));
            }

            return back()->with('status', 'Your join request has been submitted!');
        }
    }

    /**
     * Approve or reject a join request.
     */
    public function approveRequest(ApproveJoinRequestRequest $request, Group $group): RedirectResponse
    {
        $data = $request->validated();
        $userId = $data['user_id'];

        // Find the pending request
        $pendingMember = $group->members()
            ->wherePivot('user_id', $userId)
            ->wherePivot('status', 'pending')
            ->first();

        if (!$pendingMember) {
            return back()->withErrors(['request' => 'Join request not found.']);
        }

        if ($data['action'] === 'approve') {
            // Update status to approved
            $group->members()->updateExistingPivot($userId, [
                'status' => 'approved',
                'role' => $data['role'] ?? GroupRole::MEMBER->value,
            ]);

            return back()->with('status', 'Join request approved!');
        } else {
            // Reject and remove the request
            $group->members()->detach($userId);

            return back()->with('status', 'Join request rejected.');
        }
    }

    /**
     * Show pending join requests (admin panel).
     */
    public function pendingRequests(Group $group): Response
    {
        $this->authorize('approveRequests', $group);

        $pendingRequests = $group->pendingRequests()
            ->withPivot(['created_at'])
            ->get();

        return Inertia::render('Groups/PendingRequests', [
            'group' => new GroupResource($group),
            'pendingRequests' => GroupMemberResource::collection($pendingRequests),
        ]);
    }

    /**
     * Leave a group.
     */
    public function leave(Group $group): RedirectResponse
    {
        $user = auth()->user();

        // Owner cannot leave their own group
        if ($group->isOwner($user)) {
            return back()->withErrors(['group' => 'Group owners cannot leave the group. Transfer ownership or delete the group instead.']);
        }

        if (!$group->isMember($user)) {
            return back()->withErrors(['group' => 'You are not a member of this group.']);
        }

        $group->members()->detach($user->id);

        return redirect()->route('groups.index')
            ->with('status', 'You have left the group.');
    }
}
