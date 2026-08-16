# ASWJ College LMS — Setup Guide

This plugin turns your existing WordPress site into an Islamic knowledge course
platform: courses and lessons, gated YouTube videos, lesson progress ticking, a
student portal, and Fluent Forms Pro integration for registration and payments.

---

## 1. Install the plugin

1. Zip the `aswj-college-lms` folder (or use the release zip from this repo).
2. In WordPress admin go to **Plugins → Add New → Upload Plugin**, upload the
   zip, then **Activate**.
3. On activation the plugin creates:
   - Two database tables (`wp_aswj_enrollments`, `wp_aswj_progress`).
   - A **Student** user role.
   - The **ASWJ Courses** admin menu.

Requirements: WordPress 5.8+, PHP 7.4+, Fluent Forms + Fluent Forms Pro active.

---

## 2. Create the site pages

Create these pages (Pages → Add New):

| Page              | Content                                    |
|-------------------|--------------------------------------------|
| **Courses**       | `[aswj_courses]`                           |
| **Student Portal**| `[aswj_portal]`                            |
| **Register**      | Your Fluent Forms registration form shortcode |
| One page per paid course (e.g. **Enroll — Aqeedah 101**) | That course's Fluent Forms payment form |

Then go to **ASWJ Courses → Settings** and select the Portal, Catalog and
Registration pages, and fill in the registration form ID.

Shortcode options for the catalog:

- `[aswj_courses]` — all courses
- `[aswj_courses type="free"]` — only free courses (`free|paid|subscription|diploma`)
- `[aswj_courses sisters="1"]` — only sisters-only courses

---

## 3. Registration with Fluent Forms Pro

1. **Fluent Forms → New Form.** Add fields: Name, Email, Username (or use
   email as username), Password, and a **Gender** field (radio/select with
   values like `Male` / `Female`).
2. Note the Gender field's **Name attribute** (Input Customization → Name
   Attribute), e.g. `gender`.
3. In the form's **Settings → Marketing & CRM Integrations → User Registration**
   (Fluent Forms Pro module — enable it under Fluent Forms → Integrations if
   needed), add a **User Registration feed**: map Email/Username/Password, and
   set the default role to **Student**.
4. In **ASWJ Courses → Settings**:
   - *Registration form ID* = this form's ID.
   - *Gender field name* = the name attribute from step 2 (default `gender`).
   - *Sister field value* = `female` (case-insensitive, default).

When someone registers, the plugin automatically:

- ensures they have the **Student** role, and
- flags the account as a **Sister** when the gender field matches — this is
  what unlocks sisters-only courses.

You can always correct the flags manually under **ASWJ Courses → Students**.

> Recommended: also create a Fluent Forms **login form** or use the standard
> WordPress login. The portal links to `wp-login.php` by default.

---

## 4. Creating courses and lessons

### Courses

**ASWJ Courses → Add New Course.** Title, description (main editor), excerpt
(short tagline), and featured image. In the **Course Access** box choose:

- **Access type**
  - *Free* — open to everyone; logged-out visitors can watch, logged-in
    students are auto-enrolled when they start watching.
  - *Paid* — unlocked by a one-time Fluent Forms payment (see §5).
  - *Subscription* — unlocked while a Fluent Forms subscription is active (§5).
  - *Diploma Program students only* — unlocked for accounts flagged Diploma.
- **Sisters only** — combines with any type above (e.g. a free sisters-only
  course, or a paid sisters-only course).
- **Price label** — display-only text like `$49 AUD`.
- **Fluent Forms payment form ID** and **Purchase page** — for paid courses.

### Lessons

**ASWJ Courses → Lessons → Add New.** One video per lesson:

1. Pick the **Course** in the Lesson Settings box.
2. Paste the **YouTube URL or ID** (use *Unlisted* visibility on YouTube).
3. Optional lesson notes in the main editor.
4. Set the lesson sequence with the **Order** field (Page Attributes box):
   1, 2, 3…

Students tick lessons off with the **Mark lesson as complete** button; progress
bars update everywhere automatically.

---

## 5. Payments (Fluent Forms Pro)

First configure a payment gateway under **Fluent Forms → Global Settings →
Payment Settings** (Stripe and/or PayPal).

### One-time paid course

1. Create a form with Name, Email, a **Payment Item** (the course fee) and a
   **Payment Method** field. Important: the buyer's email must match their
   student account email (if they submit while logged in, the match is
   automatic).
2. Put the form on that course's purchase page.
3. In the course's **Course Access** box set *Access type = Paid*, enter the
   **payment form ID**, and select the **purchase page**.

When Fluent Forms marks the payment **paid**, the student is enrolled
automatically. Refunds do not auto-revoke — remove access manually under
**ASWJ Courses → Students** if needed.

### Subscription (unlocks all subscription courses)

1. Create a form with a **Subscription Field** (e.g. $10/month) and payment
   method.
2. Add its form ID to **ASWJ Courses → Settings → Subscription form IDs**.
3. Mark the relevant courses as *Access type = Subscription*.

While the subscription is active the student can access every subscription
course (including ones you publish later — access is re-granted on each
renewal payment). Cancelling the subscription revokes access automatically.

### Diploma students / manual enrollment

There is no payment flow for the diploma program — flag the accounts under
**ASWJ Courses → Students** (tick *Diploma*), which unlocks all
diploma-type courses. You can also manually enroll/remove any student
from any specific course on the same screen.

---

## 6. Video protection — what it does and honest limits

Your videos stay on YouTube (set them to **Unlisted**, never Public). The
plugin protects them by:

- Never printing the video ID in the page HTML — the player is fetched by an
  authenticated background request that re-checks course access on the server.
- Refusing to serve the player to anyone without access (not logged in, not
  enrolled, not a sister/diploma student, expired subscription).
- Using the privacy-enhanced `youtube-nocookie.com` player with related
  videos and branding minimized, plus a shield over the player's title bar and
  right-click disabled on the player area.

**Honest limit:** YouTube offers no domain-locking for unlisted videos, so a
technically determined user can still extract the video ID from their browser
and share the raw YouTube link. If that risk ever becomes unacceptable,
migrate the sensitive courses to **Vimeo** (domain-restricted embeds) or
**Bunny Stream** (signed expiring URLs) — only
`includes/class-aswj-lms-video.php` needs changing; the rest of the plugin is
provider-agnostic.

Also disable "Allow embedding" for nothing — embedding must stay **enabled**
on YouTube for the on-site player to work.

---

## 7. Admin screens reference

- **ASWJ Courses** — manage courses.
- **ASWJ Courses → Lessons** — manage lessons.
- **ASWJ Courses → Students** — search students, toggle *Diploma* / *Sister*
  flags, manually enroll or remove from courses, see enrollments.
- **ASWJ Courses → Settings** — pages, registration form mapping, subscription
  forms, contact email.

## 8. Theming

Templates live in `aswj-college-lms/templates/`. To customize without editing
the plugin, copy `single-course.php` or `single-lesson.php` into an
`aswj-lms/` folder inside your active theme and edit the copy. Colors are CSS
variables at the top of `assets/css/aswj-lms.css` (blue/green on white, per
ASWJ College branding).
