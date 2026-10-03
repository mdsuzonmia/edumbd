# 00 — Overview & Getting Around

## What is Edum?

Edum is a **School Management System** delivered as a multi-tenant SaaS platform. Schools sign
up, subscribe to a plan, and then use the platform to run their academic operations:

- **Academics** — years, classes, sections, departments, categories, shifts, subjects, syllabus, routines
- **Students** — admission, profiles, documents, bulk import, promotion between classes
- **Examinations & results** — exam setup, grade systems, marks entry, result generation &
  publishing, reports and transcripts
- **Attendance** — daily entry and analytics
- **Billing** — subscription plans, Stripe/PayPal payments, invoices, renewal

## Roles and what each role can do

| Role | Purpose | Key areas |
|------|---------|-----------|
| **School Owner** | Owns/manages one or more schools | School profile, billing & subscription, plan upgrades, academics, students (incl. bulk import & promotion), teachers, examinations, custom fields, school settings & modules |
| **Student** | Accesses the student portal | View own results, routines, syllabus, attendance; edit profile; view transcripts |


Every role is authenticated by login, and access is enforced per-role through route filters.
The areas you see in the menu depend on your role.

## Signing in

1. Open the platform homepage and click **Login** (or go to `/login`).
2. Enter your registered **email address** and **password**.
3. Click **Login**.

If you have not verified your email or phone number, the system may prompt you to verify before
you can use some features — see [10 — Signing Up & Onboarding](10-registration.md).

## Signing out

- Click **Logout** in the top-right menu. Always log out when using a shared computer.

## Your profile & password

All roles have a **Profile** area (`/auth/my-profile` etc. depending on role):

- **View profile** — your details, including:
  - Email verification status
  - Subscription details (where applicable)
  - Payment details & billing history
- **Edit profile** — update name, contact details and other profile fields.
- **Change password** — set a new password. Use a strong, unique password.

## General tips

- Use **hard refresh** (Ctrl+F5) if a page looks out of date after an update.
- If a page says you have "no access" or are "unauthorized", your role does not permit that area.
- Deleted records are soft-deleted ("trash"); look for **Trash**, **Restore**, and **Empty trash**
  actions to manage them.