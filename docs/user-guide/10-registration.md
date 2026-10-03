# 10 — Signing Up & Onboarding

This guide covers how a school joins the platform and what happens after registration.

## 1. Explore before you register

- The public **homepage** describes the platform.
- The **Pricing** page lists the available subscription plans.
- The **Terms** and **Privacy Policy** pages provide the legal documentation.

## 2. Registering a school

1. From the homepage click **Register** (or go to `/registration`).
2. Complete the registration form with the school details and the account owner details
   (the person who will become the **School Owner**).
3. Submit the form (`/registration`, POST).

After submitting you will be taken through the onboarding flow:

- If an email verification is required, you will receive a verification email. Open the link in
  the email to verify your address (`/verify-email/{token}`). You can resend the verification
  email from `/email/verification/send` if needed.
- If phone verification is enabled, you will be asked to verify your phone number
  (`/verify-phone`).
- You may see a **registration success** page (`/registration-success/{token}`) confirming your
  account was created; the system may also show a **manual success** page when payment is handled
  offline (`/register/manual-success/{token}`).

## 3. Choosing a plan & paying

Once your account exists you select a subscription plan:

- **Stripe checkout** — `/payment/stripe/{token}` → pay by card → success/cancel pages.
- **PayPal checkout** — `/payment/paypal/{token}` → pay via PayPal → success/cancel pages.
- **Manual / offline payment** — the School Owner completes payment outside the platform and the
  super admin confirms it.

The same payment options are also used later for **plan upgrades** and **subscription renewals**
(see [30 — School Owner](30-school-owner.md)).

## 4. First-time school owner checklist

After your subscription is active, set up your school so staff and teachers can work:

1. **School profile** — confirm your school's name, logo and contact details
   (`/school-owner/profile`, `/school-owner/schools`).
2. **Academics** — create the academic year, classes, sections, departments, categories, shifts
   and subjects (`/school-owner/academics*`).
3. **Students** — add students individually or via bulk import, then enroll them.
4. **Examination setup** — create grade systems, exam setups and mark distributions before the
   first exam (see [80 — Examinations & Results](80-examinations.md)).
5. **Enable modules** — in School Settings, enable the feature modules your school needs.

## 5. Common questions

**I didn't receive the verification email.** Use the resend link at `/email/verification/send`,
or check spam. Your verification status is shown on your profile page.

**My payment was not confirmed.** Payments that go through Stripe/PayPal are confirmed by the
gateway; offline/manual payments are confirmed by the platform team. Check your subscription
status on `/school-owner/subscriptions`.

**Can I change plan later?** Yes — see plan upgrade in [30 — School Owner](30-school-owner.md).