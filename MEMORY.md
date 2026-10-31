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
- New releases/versions must be registered in the **Changelog** model (`App\Models\Changelog`) using `App\Services\ChangelogService`.
- Releases are displayed on a custom dedicated page [`/changelog`](file:///d:/wamp64/www/saving/app/Filament/Pages/Changelog.php) featuring a dark gradient hero card, interactive release timeline, category badges, bullet highlights, and release creation in a slideover modal (matching `waldo` design language).
- Current version is dynamically displayed:
  - Inside the Filament panel (Sidebar footer linking to `/changelog`).
  - At the bottom of the Filament login screen (`resources/views/filament/version-badge.blade.php`).

## 3. UI/UX, Modal & Mobile Rules (Mandatory - Waldo Design Reference)
- **Card-Based Grid Layouts for Resources**:
  - Resources should leverage responsive card grid layouts (`->contentGrid(['default' => 1, 'md' => 2, 'xl' => 3])`) with `Stack`, `Split`, and collapsible `Panel` columns so that on mobile viewports (and tablets), records render as clean, modern interactive cards instead of cramped wide data tables.
- **Single-Page Resource Management (Slide-overs Everywhere)**:
  - Create, Edit, and View forms must stay on the index page via slide-over modals (`slideOver()`) instead of separate full-page routes (e.g. `UserResource` routes only `ListUsers::route('/')`, with create, edit, view happening via slide-over modals).
  - Configured globally via `AppServiceProvider` and individual action calls.
- **Mobile Screen Optimization**:
  - Touch-friendly action buttons, responsive modals (`max-height: 96vh`), smooth horizontal touch scrolling, and clean card hover glow effects (`custom-styles.blade.php`).
- **Number Input Fields**:
  - Numeric input fields must not display up/down spin buttons/arrows across all browsers (`appearance: textfield`).
  - Mouse wheel scrolling over number fields must not change the number or input value (`onwheel: this.blur()`).

## 4. Core Architecture Conventions
- **Authentication**: Users authenticate using `primary_contact_number` (not email).
- **Two-Step Login**: Step 1 validates that the primary contact number exists in DB; Step 2 presents the password field.
- **Payment Options**: Stored in relational table `user_payment_options` associated with `User`.
- **Profile Management**: Handled via `joaopaulolndev/filament-edit-profile` with custom components for contact numbers and address.
