# 30 — School Owner

This guide is for the **School Owner** — the person who owns one or more schools on the platform.
Routes are under `/school-owner` and require the `school-owner` role.

## Dashboard

**Dashboard** (`/school-owner/dashboard`) gives an overview of your school(s): active academic
period, student counts, and quick links to common actions.

## Profile & school profile

- **Profile** (`/school-owner/profile`) — edit your own account details.
- **Schools** (`/school-owner/schools`) — manage the school profiles you own:
  - View your school's profile and contact information.
  - Edit school name, logo, contact details, address, and other settings.
  - Create additional schools (multi-school management) or change a school's status.

## Billing & subscription

- **Plans → History** (`/school-owner/plans/history`) — review the plans your school has used.
- **Plans → Upgrade** (`/school-owner/plans/upgrade`) — pick a new plan. Payment continues
  through the checkout flow:
  - **Stripe** — `/plans/payment/stripe/{token}` → success/cancel.
  - **PayPal** — `/plans/payment/paypal/{token}` → success/cancel.
  - **Manual** — `/plans/payment/manual-success/{token}` for offline completion.
- **Subscriptions** (`/school-owner/subscriptions`) — view your active subscription, its status,
  billing cycle and next billing date.
  - **View** a subscription for details.
  - **Renew** before it expires using Stripe, PayPal or the manual flow
    (`/subscriptions/renew/...`).
- **Payments** (`/school-owner/payments`) — payment history; open a payment to see its details.
  - **Retry a failed payment** via PayPal or Stripe from the payment screen.

> Tip: a subscription that stays expired will limit what staff can do, so renew in good time.

## School settings & modules

- **Settings** (`/school-owner/settings`) — update your school's configuration.
- **Modules** — enable or disable the feature modules (examinations, bulk import, SMS, etc.)
  your school wants to use.

## Academics setup

- **Academics** (`/school-owner/academics`) — overview page linking to all academic menus.
- **Academic Years** — create the current and upcoming academic years.
- **Academic Classes** — define classes (e.g., Class 1, Class 2…).
- **Academic Sections** — optional sections inside a class (e.g., A, B, C).
- **Academic Departments** — optional grouping (e.g., Science, Arts).
- **Academic Categories** — optional classification for classes (e.g., Primary, Secondary).
- **Academic Shifts** — morning/day shifts if your school runs multiple sessions.


All of these use the standard list → create → edit → trash/restore workflow.

## Students

- **Students** (`/school-owner/students`) — list all students.
- **Add a student** — create a student record with enrollment details, guardian/address info and
  documents. Auto-generated codes can be fetched for new students
  (`students/getAutoGenCodes`).
- **Edit / View** — update a student or view the full profile.
- **Delete / Trash / Restore** — soft-delete workflow.
- **Print & PDF** — generate printable/PDF versions of:
  - student profile (`students/profile-print`, `students/profile-pdf`)
  - office copy (`students/office-copy-print`, `students/office-copy-pdf`)

### Bulk student import (CSV / JSON)

1. **Bulk import** (`/school-owner/students/bulk/import`).
2. Download a **sample CSV** or **sample JSON** to see the expected format.
3. **Upload** your file and **preview** the data.
4. **Validate** the file; fix any errors reported.
5. Run **Import process** to insert the students.
6. Review **import history** and open an import's **details** for a full report.

### Student promotion

- **Promotion page** (`/school-owner/students/promotion`) — choose a class/section/year.
- **Preview** the eligible students.
- **Process** the promotion (moves students to the next class/year).
- Review **promotion history** and a promotion's **details**.
- **Rollback** a promotion batch if it was created in error.


## Custom fields

- **Custom Fields** (`/school-owner/custom-fields`) — define extra fields for your school's
  records (entity-based).
- Manage field **groups**, **options** and per-record **values** from the custom-fields screens.

## Examinations

The examination module (grade systems, exam setup, marks, results, reports, promotion) is led
by the school owner and is described in detail in
[80 — Examinations & Results](80-examinations.md).

## Common tasks (quick reference)

| I want to… | Go to |
|------------|-------|
| Renew my subscription | `/school-owner/subscriptions` → Renew |
| Upgrade my plan | `/school-owner/plans/upgrade` |
| Add 100 students at once | `/school-owner/students/bulk/import` |
| Move a class up a year | `/school-owner/students/promotion` |
| Set up the new academic year | `/school-owner/academics/years` |
| Print a student profile | `/school-owner/students` → Print/PDF |