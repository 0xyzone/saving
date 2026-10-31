# Global Agent Memory & Operating Instructions

These instructions must be followed by all agents working on this project:

## 1. Completion Workflow
Every time any task is completed, the agent must:
1. **Push changes to Git**:
   ```bash
   git add .
   git commit -m "descriptive commit message"
   git push origin main
   ```
2. **Send ntfy Notification**:
   Send a notification to the ntfy.sh topic `saving-tasks`:
   ```bash
   curl.exe -s -d "Task completed: [Brief Summary of what was done]" https://ntfy.sh/saving-tasks
   ```

## 2. Versioning & Changelogs
- Maintain the versioning mechanism categorised into:
  - **Major**: Breaking architectural changes or substantial overhauls.
  - **Minor**: Significant new features and functionality additions.
  - **Macro (Patch)**: Small updates, enhancements, fixes, or tweaks.
- New releases/versions must be registered in the **Changelog** model/resource (`App\Models\Changelog`).
- Current version is dynamically displayed:
  - Inside the Filament panel (Sidebar footer linking to `/changelogs`).
  - At the bottom of the Filament login screen (`resources/views/filament/version-badge.blade.php`).

## 3. Core Architecture Conventions
- **Authentication**: Users authenticate using `primary_contact_number` (not email).
- **Two-Step Login**: Step 1 validates that the primary contact number exists in DB; Step 2 presents the password field.
- **Payment Options**: Stored in relational table `user_payment_options` associated with `User`.
- **Profile Management**: Handled via `joaopaulolndev/filament-edit-profile` with custom components for contact numbers and address.
