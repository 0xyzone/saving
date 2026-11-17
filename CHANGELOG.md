# Changelog

All notable changes to the **Saving Portal** application will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.8.0] - 2026-11-17

### Added
- **Mid-Session Seat Allocation Catch-Up & Past-Recipient Share Distribution**:
  - Added `SavingsSchemeService::applyMidSessionCatchUp()` to automatically generate catch-up installments across completed rounds for newly allocated seats, increment each completed round's `gross_pool_amount` and `net_payout_amount`, and update `payout_status` to `partial` so past recipients' additional shares can be disbursed via **Settle Recipient Disbursement**.
  - Updated the **Allocate / Assign Seats** modal in `ManageSavingsScheme` with a live **Mid-Session Catch-Up Summary**, **Settle Catch-Up Installments Now** toggle, payment method selector, and settlement date picker.

## [1.7.0] - 2026-11-17

### Added
- **Edit Profile Enhancements & Payment Methods**:
  - Added `username` and **Payment Methods** management (with QR code upload, `is_active`, and distinct single `is_preferred` toggle per user) to the Edit Profile page (`EditContactAndAddress`).
  - Enforced a single preferred payment method per user at both the UI (`live()` toggle) and model (`UserPaymentOption::booted()`) levels.
- **Recipient Preferred Payment & QR in Round Summary**:
  - Displayed the round recipient's preferred/active payment method and QR code preview inside the Round Summary modal.
- **Clickable Dashboard Widgets**:
  - Scoped `UsersOverview` Active/Preferred Payment Methods counts to the authenticated user and linked them to the Edit Profile page.
  - Added pending round scheme title(s) to **Current Period Dues** and click actions to **Current Period Dues**, **Total Contributed**, and **Disbursed Rounds** widgets.

### Changed
- **Formal Cooperative Savings Terminology**: Replaced informal raffle/lottery/prize wording with formal cooperative group savings & disbursement terminology across the app.
- **Navigation Visibility**: Hid the `Savings Schemes` navigation item for users without `Create:SavingsScheme` permission.
- **Removed Avatar Feature**: Disabled the profile avatar form and removed avatar fields from `UserForm` and `UsersTable`.

## [1.6.0] - 2026-11-17

### Added
- **Grouped Multi-Seat Installments & Partial/Full Settlements**:
  - Grouped the period installments list by member (`1` row per user across all their seats in the round) with combined seat numbers, base amount, interest, total due, paid amount, and remaining balance.
  - Added `paid_amount` to `scheme_installments` and updated the settlement modal to support **partial** or **full** settlement amounts along with a selectable **Settlement Date**.
- **Automatic Round Completion & Winner Payout**:
  - Once all required installments for a round are settled, the round is automatically marked `completed` and `paid` out to the winner (for Period #1 Organizer, periods with an already-declared winner, or periods with an approved reservation), and the scheme advances to the next round.

## [1.5.0] - 2026-11-17

### Added
- **Username Authentication & `make:filament-user` Update**:
  - Added nullable unique `username` column to `users` (and made `primary_contact_number` nullable) via migration.
  - Updated `Login` to accept either `username` or `primary_contact_number`.
  - Updated `MakeFilamentUserCommand` (`make:filament-user`) to prompt for `username` instead of phone number.
  - Added `username` field and column in `UserForm` and `UsersTable`.
- **Multi-Seat Allocation & Searchable User Labels**:
  - Updated `Allocate / Assign Seats` action in `ManageSavingsScheme` to support selecting multiple users and specifying the number of seats per selected user.
  - Formatted user dropdown labels as `Name (username)` and enabled searching by `name`, `username`, and `primary_contact_number`.

### Changed
- **Seats Roster Privacy**: Replaced phone numbers with `@username` in the seats roster and restricted phone number visibility in installment/winner details to the scheme organizer.
- **Auto-Activate Scheme on Settlement**: Automatically transitions a scheme's status to `active` once an installment payment is marked as paid.
- **Maslanda Deposit**: Renamed all `Khaja Kharcha` UI labels to `Maslanda Deposit` and updated the default value to `2000`.
- **Input Padding & Terminology**: Increased input field padding across the app in `custom-styles.blade.php` and removed all references to `dhukuti`.

## [1.4.0] - 2026-11-17

### Added
- **Member Dashboard Widgets for Ongoing Saving Schemes**:
  - `MemberSchemesOverview`: Stats overview showing active seats, current period dues (pending vs. paid), total contributed amount, and won rounds/payouts received.
  - `MyOngoingSchemeSeats`: Responsive card-grid widget displaying each ongoing seat (`active` and `open_for_applications` schemes), current running period, due date (BS & AD), current period payment status, win status, and a read-only **View Details** slide-over modal with the seat's installment ledger.
  - `MyInstallmentHistory`: Filterable payment ledger of the member's installments across ongoing schemes.
  - `MyReservationsAndWinnings`: Dedicated dashboard view of the member's won rounds/payout balances and period reservation requests.

### Changed
- **Dashboard Widget Visibility**:
  - Scoped member widgets to users holding at least one seat in an ongoing scheme (`active` or `open_for_applications`).
  - Restricted `UsersOverview` widget visibility to `super_admin`.

## [1.3.2] - 2026-11-16

### Changed
- **Organizer-Only Savings Scheme Visibility & Scoping**:
  - Restricted Savings Schemes resource visibility to scheme organizers (and `super_admin`), removing `ViewAny:SavingsScheme` and `View:SavingsScheme` from `panel_user`.
  - Scoped `SavingsSchemeResource::getEloquentQuery()`, `SavingsSchemePolicy`, and `ManageSavingsScheme::mount()` so organizers only list and access schemes they organize.
- **Merged SP Brand Logo & Sidebar Brand Lockup**:
  - Redesigned the `SP` vector monogram (`logo-dark.svg`, `logo-light.svg`, `favicon-local.svg`, `favicon-prod.svg`) with an interlocking `S` and `P` sharing a unified central spine and portal coin core.
  - Fixed the Filament sidebar/topbar brand lockup (`brand.blade.php` and `custom-styles.blade.php`) by removing the invalid nested `<a>` tag and aligning the icon, title, and version pill onto a single clean row.

## [1.3.1] - 2026-11-16

### Added
- **Custom SP Brand Logos & Environment-Aware Favicons**:
  - Designed custom `SP` monogram vector logos for both light mode (`public/images/logo-light.svg`) and dark mode (`public/images/logo-dark.svg`), integrated across the Filament panel header, login screen, and changelog page.
  - Created distinct environment-aware favicons: Amber/Gold `SP` (`public/images/favicon-prod.svg`) for production and Cyan/Blue `SP` with a green corner indicator (`public/images/favicon-local.svg`) for local development (`app()->isLocal()`), plus a fallback `public/favicon.ico`.

## [1.3.0] - 2026-11-12

### Added
- **Bikram Sambat (Nepali BS) Calendar Integration**:
  - Integrated `anuzpandey/laravel-nepali-date` package with custom `NepaliDateService` providing bidirectional BS ↔ AD conversion, Nepali month names (Roman & Devanagari), and day-capped arithmetic across month transitions.
  - Replaced English date calendar selection with primary **Nepali Date Dropdowns (Year, Month, Day)** in both creation and edit pages with real-time automatic auto-filling and conversion of Gregorian (English AD) dates.
  - Grounded round due dates strictly in the Nepali calendar period (matching the exact Nepali day of the month for every round, e.g. 26th of Kartik, Mangsir, Poush, etc.), with Gregorian AD due dates derived automatically.
  - Formatted Nepali dates (`formatted_start_date_bs` and `formatted_due_date_bs`) rendered across scheme list tables and round timelines.
- **Round & Period Reservation System**:
  - `scheme_period_reservations` architecture allowing members to apply for specific future round payouts with reason justifications.
  - Organizer approval workflow with pre-selection in winner declaration modals and badge indicators in round cards.
- **Raffle & Lucky Draw Winner Declaration**:
  - Integrated Lucky Draw action on the scheme management page for fair, randomized selection among all eligible non-winning seats.
- **Dedicated Round Details Summary**:
  - Dedicated View Details slide-over/modal per round displaying gross pool, member dues deductions, Khaja Kharcha fees, net prize, paid sum, and remaining balance due.
  - Real-time collection metrics and complete member installment ledger with payment verification details.
- **Dedicated Round Details & Interaction**:
  - Clickable round rows to instantly initiate the dedicated Round Summary view modal.
  - In-row and in-modal **Reserve Round** buttons with instant seat allocation and reason tracking.
  - In-row and in-modal **Round Audit & Validation** modal featuring reconciliation checklists for member collections (paid vs pending, unpaid member list) and prize payout status, with official audit sign-off and validation timestamps.
  - Dedicated **Edit Scheme** header action modal to adjust scheme title, organizer, interest, and Khaja Kharcha on the fly.
  - Configured full-width panel layout (`Width::Full`) and desktop-collapsible sidebar (`sidebarCollapsibleOnDesktop()`).
- **Multi-Channel Settlement Recording**:
  - Support for `Cash`, `eSewa`, `Khalti`, `Bank Transfer`, and `Other` payment methods with reference tracking for both winner prize payouts and member installment collections.
  - Partial payout handling with dynamic remaining balance calculations and dedicated settlement completion actions.
- Comprehensive automated test suite (`NepaliDateAndPeriodReservationTest`) verifying date conversions, month-end capping, reservation approvals, multi-channel settlements, and interactive Livewire modals.

### Fixed
- **Filament Impersonation 403 Forbidden Access**:
  - Resolved 403 error during user impersonation by allowing panel access in `User::canAccessPanel()` when an active impersonation session (`Impersonation::isImpersonating()`) is in effect.
  - Implemented automatic `panel_user` role assignment for newly created and existing users via model hook and database migration, ensuring non-super-admin users can access the panel without authorization lockouts.
  - Configured `canImpersonate()` (restricted to super admin) and `canBeImpersonated()` on `User` model.
  - Added full test suite in `ImpersonationTest.php` covering impersonation flows and leave routes.

## [1.2.0] - 2026-11-12

### Added
- Complete Periodical Savings Scheme system (Committee / Dhukuti architecture) with configurable seat and period settings.
- Financial settlement calculation engine (`SavingsSchemeService`):
  - Organizer auto-assigned to Seat #1, winning Period 1 in full without Khaja Kharcha deductions.
  - Automatic deduction of winning member's seat dues from payout (winner pays zero out-of-pocket for winning round).
  - Fixed Khaja Kharcha deduction starting from Period 2.
  - Fixed interest accumulation on subsequent installments for all past winning members (excluding Organizer).
- Dedicated interactive management page (`ManageSavingsScheme`):
  - Period-by-period progression timeline and winner declaration with instant payout preview.
  - Interactive seats roster displaying allocation, organizer status, and past winning periods.
  - Real-time installment collections ledger with per-member payment recording and automatic prize-offset settlement.
- `SavingsSchemeResource` registered under **Savings Management** navigation group.
- Complete Filament Shield & Shield Enhanced permission integration for Savings Schemes:
  - Custom permissions: `DeclareWinner:SavingsScheme`, `ManageInstallments:SavingsScheme`, `ManageSeats:SavingsScheme`.
  - Policy enforcement on `SavingsSchemePolicy` and interactive UI guard conditions on action modals and collection actions.
- Complete automated test suite covering all multi-seat, interest accumulation, winner prize calculation edge cases, and permission enforcement.

## [1.1.0] - 2026-11-12

### Added
- Comprehensive Roles and Permissions implementation powered by Filament Shield (`bezhansalleh/filament-shield`) and Shield Enhanced (`agroezinger/filament-shield-enhanced`).
- Published and customized `RoleResource` with navigation-grouped permission management tabs for Resources, Pages, Widgets, and custom permissions.
- Fine-grained multi-action resource permissions on `UserResource` (`ManageRoles`, `ManagePaymentOptions`, `Export`) with custom descriptions and conditional UI visibility.
- Role assignment and management inside the User management resource (multiple role selection with search and preload, table badges, and infolist entries).
- Automated database seeding for roles and permissions via `RoleAndPermissionSeeder` integrated into `DatabaseSeeder`.
- Comprehensive feature tests covering role access control, fine-grained resource permission checks, and permission form rendering.

### Security
- Strict authorization policies (`UserPolicy` and `RolePolicy`) enforced across panels with gate-based super-admin bypass.
- Dedicated `canAccessPanel` authorization for panel access control.

## [1.0.6] - 2026-11-05

### Added
- App brand version pill badge (`vX.Y.Z`) with glowing pulse indicator positioned beside the app logo and title across the Filament panel and public pages.
- Direct file-based markdown changelog parser in `ChangelogService` using `Str::markdown()`.
- Premium glassmorphic login screen styling with radial backdrop glow, refined typography, and input prefix icons.

### Changed
- Switched changelog data architecture to solely read from `CHANGELOG.md`, completely eliminating database storage and operations.
- Upgraded version indicators across sidebar footer and login screen to match the unified badge UI.
- Enhanced login form UX with dynamic step-based headings, helpful subheadings, and icon-enhanced action buttons.

### Removed
- Dropped `changelogs` database table and removed `Changelog` Eloquent model.

## [1.0.5] - 2026-10-31

### Added
- Dedicated modern public Changelog page (`/changelog`) with dark gradient hero card, interactive timeline, and release tags.
- Responsive SemVer tracking with automatic release categorization and badge highlights.

### Changed
- Converted changelog view out of Filament admin panel to a standalone branded public route.
- Improved sidebar and login version indicators with clean badge styling.

## [1.0.4] - 2026-10-31

### Added
- Waldo-inspired mobile-first card grid layout for User management resource.
- Interactive slide-over drawer modals for Create, Edit, and View user forms instead of standalone pages.
- Touch-friendly action buttons and card hover glow effects.

### Fixed
- Disabled spin buttons and mouse wheel increment/decrement behavior on numeric input fields.
- Optimized viewport handling and responsive modal sizing up to 96vh on mobile devices.

## [1.0.3] - 2026-10-31

### Added
- Custom profile management components for primary and secondary contact numbers and full address details.
- User payment options relational table and dynamic payment methods configuration.

### Security
- Reinforced phone number validation patterns and duplicate check protections during onboarding.

## [1.0.2] - 2026-10-31

### Added
- Two-step authentication flow: verifies primary contact number in step 1 before rendering password verification in step 2.
- Session-based contact identification with streamlined error messaging.

### Changed
- Switched default authentication identifier from email to `primary_contact_number`.

## [1.0.1] - 2026-10-31

### Added
- Extended user schema migration adding full name, alternate contact number, address, and status fields.
- User payment options structure and relational models.
- Custom Tailwind color palette and dark mode slate theme tokens.

## [1.0.0] - 2026-10-31

### Added
- Initial release of Saving Portal.
- Filament Admin Panel foundation with custom branding, authentication scaffolding, and role permissions.
- Database architecture for user accounts, sessions, and system jobs.

