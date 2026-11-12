# Changelog

All notable changes to the **Saving Portal** application will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

