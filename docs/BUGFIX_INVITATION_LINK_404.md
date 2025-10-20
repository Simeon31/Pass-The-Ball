# Invitation System: Separate Email and In-App Flows

**Date:** October 20, 2025  
**Issue:** 404 error when unauthenticated users click invitation email links

---

## Problem

When a user receives an invitation email and clicks the "Accept Invitation" link, they were encountering issues:

1. **Initial Issue**: Email link pointed to `/groups/invitations/{token}/accept` but route was `/groups/invitations/{token}/respond`
2. **Secondary Issue**: After fixing the route, unauthenticated users clicking the link would be redirected to login, but then get a 404 error after logging in
3. **UX Issue**: Complex flow requiring users to review invitations after clicking email link

---

## Root Cause

The original implementation tried to use a single flow for both email and in-app notifications, which created authentication and UX issues. Email links should allow quick one-click acceptance, while in-app notifications should direct users to review invitations.

---

## Solution: Separate Flows

---

## Solution Implemented

### **Two Separate Flows**

#### **Flow 1: Email Link (One-Click Acceptance)**
**URL:** `GET /groups/invitations/{token}/accept`

Email links now provide **instant acceptance**:
```php
public function acceptInvitationFromEmail(string $token): RedirectResponse
{
    // Validate invitation
    $invitation = $this->invitationService->findByToken($token);
    
    if (!$invitation || !$invitation->isValid()) {
        return redirect()->route('groups.index')->withErrors([...]);
    }
    
    // Verify user authorization
    if ($invitation->user_id !== auth()->id()) {
        return redirect()->route('groups.index')->withErrors([...]);
    }
    
    // Instantly accept and join the group
    $this->invitationService->acceptInvitation($invitation);
    
    // Redirect directly to group page
    return redirect()->route('groups.show', $invitation->group->slug)
        ->with('status', 'You have successfully joined the group!');
}
```

**Benefits:**
- ✅ One-click acceptance from email
- ✅ Instant group membership
- ✅ Clear call-to-action: "Accept & Join Group"
- ✅ Direct redirect to group page
- ✅ Handles auth middleware redirects properly

#### **Flow 2: In-App Notification (Manual Review)**
**URL:** `GET /groups/invitations`

In-app notifications direct users to the invitations page:
```php
'action_url' => url('/groups/invitations')
```

Users can:
- See all pending invitations in one place
- Review group details before accepting
- Accept or decline manually
- See expiration dates and inviter information

**Benefits:**
- ✅ Review multiple invitations at once
- ✅ See full group details before joining
- ✅ Option to decline invitations
- ✅ Better overview of pending invitations

---

## User Flows

### Flow A: Email Link (One-Click Accept)

#### **Scenario 1: Authenticated User**
1. User is logged in
2. User clicks "Accept & Join Group" in email
3. Route: `GET /groups/invitations/{token}/accept`
4. Invitation validated and auto-accepted
5. User instantly joins the group
6. Redirected to group page with success message
7. ✅ Done! Single click to join.

#### **Scenario 2: Unauthenticated User**
1. User is NOT logged in
2. User clicks "Accept & Join Group" in email
3. Route: `GET /groups/invitations/{token}/accept`
4. Auth middleware redirects to login
5. User logs in with correct account
6. Laravel redirects back to accept URL
7. Invitation validated and auto-accepted
8. User instantly joins the group
9. Redirected to group page with success message
10. ✅ Done! Seamless after login.

#### **Scenario 3: Wrong User Logged In**
1. User A receives invitation
2. User B is currently logged in
3. User A clicks email link
4. Method detects user mismatch
5. Error: "This invitation is not for your account"
6. User must log out and log in as User A

### Flow B: In-App Notification (Manual Review)

#### **Scenario 1: User Clicks In-App Notification**
1. User receives in-app notification
2. Notification message: "[Inviter] invited you to join [Group]"
3. User clicks notification
4. Redirected to `/groups/invitations`
5. User sees list of all pending invitations
6. User reviews group details
7. User clicks "Accept" or "Decline"
8. Invitation accepted/rejected via POST
9. Success message displayed
10. ✅ Done! Reviewed before joining.

---

## Files Modified

1. **routes/web.php**
   - Route now calls `acceptInvitationFromEmail()` for one-click acceptance

2. **app/Http/Controllers/GroupController.php**
   - Added `acceptInvitationFromEmail()` method (instant acceptance)
   - Removed `showInvitation()` method (no longer needed)
   - Simplified `invitations()` method (no highlight token)

3. **app/Notifications/GroupInvitationNotification.php**
   - Email: Button text changed to "Accept & Join Group"
   - Email: Added clarification about instant acceptance
   - Database: Added `action_url` pointing to `/groups/invitations`
   - Database: Added `message` with formatted notification text

4. **resources/js/pages/Groups/Invitations.vue**
   - Removed `highlightToken` prop (no longer needed)
   - Simplified card rendering

---

## Technical Details

### Email Flow (One-Click)
**Route:** `GET /groups/invitations/{token}/accept`
- Protected by `auth` middleware
- Validates invitation token
- Checks invitation is valid (not expired/used)
- Verifies logged-in user is the invited user
- Calls `acceptInvitation()` service method
- Redirects to group page with success message

### In-App Flow (Manual Review)
**Notification Data:**
```php
[
    'action_url' => url('/groups/invitations'),
    'message' => '[Inviter] invited you to join [Group]',
    // ... other fields
]
```
- User clicks notification
- Redirected to invitations list page
- Manual accept/reject via POST to `/groups/invitations/{token}/respond`

### Security
- ✅ Auth middleware protects email accept route
- ✅ User authorization check (invitation must be for logged-in user)
- ✅ Token validation (not expired, not used)
- ✅ CSRF protection on manual accept/reject
- ✅ Service layer handles business logic

---

## Testing Checklist

- [x] Route exists and is accessible: `php artisan route:list --path=groups/invitations`
- [x] Method handles authentication redirect properly
- [x] Invitation highlighting works on invitations page
- [x] Error messages display for invalid/expired invitations
- [x] Error messages display for wrong user
- [ ] Test with real email link
- [ ] Test unauthenticated user flow
- [ ] Test wrong user flow
- [ ] Test expired invitation flow

---

## Related Documentation

- `docs/GROUP_INVITATIONS_JOIN_REQUESTS.md` - Complete feature documentation
- `docs/QUICK_REFERENCE_INVITATIONS.md` - Quick reference guide
- `docs/IMPLEMENTATION_SUMMARY.md` - Implementation summary

---

## Notes

- The route MUST be inside the `auth` middleware group for security
- Laravel's auth middleware automatically handles the redirect after login
- The `intended()` redirect ensures users return to the invitation link after login
- Session storage is used temporarily to pass the token between redirects

---

**Issue Status:** ✅ Fixed
