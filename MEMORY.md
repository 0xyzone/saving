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
- Changelogs are maintained solely in `CHANGELOG.md` at the project root using standard Keep a Changelog / SemVer formatting (no database storage).
- `App\Services\ChangelogService` parses `CHANGELOG.md` directly to retrieve releases, descriptions, highlights, and the active version.
- Releases are displayed on the dedicated public page [`/changelog`](file:///Applications/MAMP/htdocs/saving/resources/views/pages/changelog.blade.php) featuring a dark gradient hero card, interactive release timeline, category badges, and parsed markdown notes.
- Current version is dynamically displayed in a badge UI (`vX.Y.Z` with glowing indicator):
  - In the panel brand header directly beside the app name/logo (`resources/views/filament/brand.blade.php`).
  - In the sidebar footer linking to `/changelog` (`resources/views/filament/sidebar-version.blade.php`).
  - At the bottom of the Filament login screen (`resources/views/filament/version-badge.blade.php`).
  - In the top navigation bar of the changelog page.

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
