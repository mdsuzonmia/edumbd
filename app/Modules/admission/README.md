# Admission module

This module keeps applicants separate from enrolled students. A student and their academic enrollment are created only by the **Complete Admission** action.

## Install

1. Install/activate `admission` from the SaaS module screen.
2. Run `php spark migrate -n Modules\\admission` from the project root.
3. Open `/school/admission` as a school owner.

The public portal is `/admission/{school-slug}`. Status checks require the school slug, application number, and the applicant date of birth or guardian mobile. Direct application and QR verification URLs use a 192-bit random token.

## Workflow

Create an admission session, publish a circular, and receive public applications. Review applications into `eligible`, `ineligible`, `rejected`, or `correction_required`. Circulars that require a lottery can freeze the eligible list, run a cryptographically secure Fisher-Yates draw once, and publish it by finalizing. Cancelling retains the original snapshot/results and requires a reason; a new lottery is a new revision.

Candidate and result hashes are recalculated on the lottery detail page. Any mismatch is displayed as an integrity failure.

Applicants in `eligible`, `selected`, or `admission_pending` can be admitted. Conversion is transactional and creates the student, enrollment, available guardian records, confirmation, and status history together.

## Operational workflows

- **Custom form builder:** configure text, textarea, number, date, dropdown, radio, checkbox, and file fields per circular. Required fields and CodeIgniter validation rules are enforced on the public form. Fields that already contain submitted data cannot be deleted; disable them instead.
- **Application and admission fees:** set both amounts on the circular. Applicants can pay through Stripe Checkout, PayPal Checkout, or submit a manual transaction reference and optional receipt. Stripe and PayPal payments are verified against the provider response and completed automatically; only manual submissions require school-owner review. Paid application fees unlock eligibility, and paid admission fees are enforced before applicant-to-student conversion.
- **Document verification:** file-type custom fields become admission documents. Reviewers can verify or reject each file with a note. Every required file must be verified before the application can be marked eligible. Receipts and documents are stored under `writable/private/admission` and are served only through tenant-authorized routes.
- **Admission tests:** define the circular, schedule, marks, instructions, and candidate statuses that enter the test.
- **Seat plans:** configure admission-test rooms, select rooms for a test, and generate sequential or seeded-random allocations. Capacity and row/column layouts are validated. Lock the final plan before creating admit cards.
- **Admit cards:** generate one tokenized card for every allocated applicant. Cards include room, seat, schedule, instructions, QR verification, browser view, and A4 PDF download. Applicants also see available cards from their application status page.

## Payment configuration

Each school owner configures their own methods at `/school/admission/payment-settings`. Settings include currency, manual instructions, Stripe keys, PayPal credentials, and PayPal sandbox/live mode. Only enabled methods with complete credentials appear to that school's applicants.

Provider secrets are encrypted with AES-256-GCM. By default, a private master key is generated at `writable/keys/admission-payment.key` on the first settings save. Preserve this file in backups. For multi-server deployments, configure the same base64-encoded 32-byte key on every node instead:

```ini
admission.credentialsKey = BASE64_ENCODED_32_BYTE_KEY
```

Use a currency supported by every enabled provider. Keep PayPal in sandbox mode until live credentials have been tested.
