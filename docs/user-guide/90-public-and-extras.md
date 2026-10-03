# 90 — Public Features & Utilities

This guide covers features that don't belong to a single academic role: public/shared access,
verification, backups, bulk uploads, and school-to-parent communication.

---

## 1. Public pages

- **Homepage** (`/`) — the public landing page.
- **Pricing** (`/pricing`) — subscription plans and costs.
- **Terms** (`/terms`) and **Privacy Policy** (`/privacy-policy`) — legal documentation.
- **Payment** (`/payment`) — used during registration to complete a plan purchase
  (see [10 — Signing Up & Onboarding](10-registration.md)).

## 2. Registration & verification

- **Registration** (`/registration`) — a new school signs up (owner account + school).
- **Email verification** — verify your address from the link sent to your inbox
  (`/verify-email/{token}`); resend at `/email/verification/send`.
- **Phone verification** — verify your phone number when required (`/verify-phone`).
- **Forgot / reset password** — request a reset link (`/forgot-password`) and complete the
  reset (`/reset-password/{token}`).

## 3. Student QR verification

- **Verify a student** (`/students/verify/{identifier}`) and
  **verify by QR code** (`/students/verify/qr-code/{token}`) let authorized users confirm a
  student's identity (e.g., at the gate, in a library, or during examinations).
- **Public student profile** (`/students/profile/{token}`) — a shareable, token-based view of a
  student's public profile.

> These use unique tokens/identifiers, so never share a student's verification token publicly.

## 4. Backups (owner/super-admin)

**Backup** (`/backup`) helps you safeguard school data:

- **Download JSON** — export the school's data to a JSON file.
- **Download photo** — export uploaded photos/images.
- **Save JSON** / **Upload photo** — restore from a previously downloaded file when needed.

>> Make backups **before** large changes or imports so you can restore if something goes wrong.

## 5. Bulk student upload (module)

- **Bulk Student** (`/bulkstudent`) — an alternative bulk import tool:
  - **Download CSV** template.
  - **Upload & save students** from the prepared file.
- This complements the owner-area CSV/JSON import described in
  [30 — School Owner](30-school-owner.md).

## 6. SMS / WhatsApp (module)

- **SMS & WhatsApp** (`/sms_whatapp`) — send text messages or WhatsApp messages to parents,
  staff or groups (communication such as reminders, fee notices, event alerts).
- Use **Send SMS** with the recipient list and message body. Confirm your SMS provider
  (Twilio/Vonage) is configured in system/school settings first.

## 7. Certificates (module)

- The **certificates** module supports issuing/generating certificates (e.g., completion,
  character, transfer certificates) for students, which can be printed or shared.

## 8. Payments overview

Payments are used across registration, plan upgrades and subscription renewals:

- **Stripe** and **PayPal** automatic checkout flows.
- **Manual / offline** payment, confirmed by the platform team.
- Payment and invoice history is visible to school owners
  ([30 — School Owner](30-school-owner.md)).

## 9. Utility / access pages

- **No access** (`/no-access`) and **Unauthorized** (`/unauthorized`) — shown when your current
  role cannot open a page. Sign in with the correct account if you expected access.
- **Uploads** — uploaded files are served through a protected route (`/uploads/{path}`); you
  generally do not need to visit it directly.

---

## Quick index by task

| Task | Where |
|------|-------|
| Sign up a new school | `/registration` |
| Verify my email/phone | `/verify-email`, `/verify-phone` |
| Reset my password | `/forgot-password` |
| Verify a student's identity | `/students/verify/{id}`, `/students/verify/qr-code/{token}` |
| Back up school data | `/backup` |
| Send bulk SMS/WhatsApp | `/sms_whatapp` |
| Bulk upload students | `/bulkstudent` or `/school-owner/students/bulk/import` |