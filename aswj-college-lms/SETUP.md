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
| **Subscribe**     | Your monthly subscription form shortcode   |
| One page per paid course (e.g. **Enroll — Aqeedah 101**) | That course's Fluent Forms payment form |

Then go to **ASWJ Courses → Settings** and select the Portal, Catalog and
Registration pages, and fill in the registration form ID.

Shortcode options for the catalog:

- `[aswj_courses]` — all courses
- `[aswj_courses type="free"]` — only free courses (`free|paid|subscription|diploma`)
- `[aswj_courses sisters="1"]` — only sisters-only courses

---

## 3. Account registration with Fluent Forms Pro

1. **Fluent Forms → New Form.** Add fields: Name, Email, Username (or use
   email as username), Password, **Phone**, **Age**, and a **Gender** field
   (radio/select with values like `Male` / `Female`).
2. Note each field's **Name attribute** (Input Customization → Name
   Attribute), e.g. `gender`, `phone`, `age`.
3. In the form's **Settings → Marketing & CRM Integrations → User Registration**
   (Fluent Forms Pro module — enable it under Fluent Forms → Integrations if
   needed), add a **User Registration feed**: map Email/Username/Password, and
   set the default role to **Student**.
4. In **ASWJ Courses → Settings**:
   - *Registration form ID* = this form's ID.
   - *Gender / Phone / Age field names* = the name attributes from step 2
     (defaults `gender`, `phone`, `age`).
   - *Sister field value* = `female` (case-insensitive, default).

When someone registers, the plugin automatically:

- ensures they have the **Student** role,
- flags the account as a **Sister** when the gender field matches — this is
  what unlocks sisters-only courses, and
- saves phone / age / gender to their student profile.

You can always correct the flags manually under **ASWJ Courses → Students**.

> Recommended: also create a Fluent Forms **login form** or use the standard
> WordPress login. The portal links to `wp-login.php` by default.

### Autofill for returning students

Any Fluent Forms form on the site is **pre-filled automatically for logged-in
students**: name, email, phone, age and gender fields are populated from
their account, so a returning student registering for a new course only
completes what's new (e.g. the payment choice). Fields the plugin recognises:

- Name and Email field types (always autofilled),
- the Phone field type, plus any field whose name attribute matches your
  configured *phone*, *age* or *gender* field names,
- simple text fields named `name`, `full_name` or `your_name`.

Admin-set default values are never overwritten.

### Student profile page

The portal (`[aswj_portal]`) includes a **My Profile** section where students
edit their own first/last name, email, phone number and password. Changes to
gender/sister or diploma status remain admin-only (Students screen).

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

### Lessons — automatic import from a YouTube playlist (recommended)

Put the course's videos in an **Unlisted YouTube playlist**, then on the
course edit screen use the **Import Lessons from YouTube Playlist** box:
paste the playlist link and click **Import Lessons**. Every video becomes a
lesson automatically, titled after the video and ordered as in the playlist.

- **Re-import any time**: videos already imported are skipped, so when you
  add new videos to the playlist, re-importing creates only the new lessons.
- For reliable imports (and playlists over ~100 videos), add a free
  **YouTube Data API key** under **ASWJ Courses → Settings**:
  1. Go to [console.cloud.google.com](https://console.cloud.google.com), create
     a project, enable **YouTube Data API v3** (APIs & Services → Library).
  2. APIs & Services → Credentials → Create credentials → **API key**.
  3. Paste the key into the setting. (Reading playlists uses trivial quota.)
  Without a key the plugin falls back to reading the playlist page, which
  works but is less robust.

### Lessons — manual

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

### One-time paid course — with "Pay now" or "Bank transfer / cash"

1. Create a course registration form with Name, Email, Phone, Gender, Age, a
   **Payment Item** (the course fee) and a **Payment Method** field. To offer
   bank transfer / cash in person, enable the **Offline** payment method in
   Fluent Forms (Global Settings → Payment Settings → Offline) and customise
   its label/instructions (e.g. your bank details).
2. Put the form on that course's purchase page.
3. In the course's **Course Access** box set *Access type = Paid*, enter the
   **payment form ID**, and select the **purchase page**.

What happens on submission:

- The student immediately appears in **ASWJ Courses → Students** with the
  course marked **awaiting payment** (no video access yet). They also see an
  "awaiting payment verification" notice on the course and in their portal.
- **Pay now** (Stripe/PayPal): the moment Fluent Forms marks the payment
  *paid*, access unlocks automatically — nothing for you to do.
- **Bank transfer / cash**: once you've verified the money arrived, open
  **ASWJ Courses → Students**, find the student, choose the course in
  **“— Approve payment for —”** and click Save. Access unlocks instantly.

Refunds do not auto-revoke — remove access manually on the Students screen.

> Note: if a guest (not logged in) submits the form, the plugin matches them
> by email. If no account with that email exists yet, ask them to create an
> account first (or create it for them) — the pending record is created when
> the email matches an account. Best practice: put a login/register prompt on
> the purchase page for new students.

### Sponsored / excused students

To give someone access without payment, use **ASWJ Courses → Students**,
pick the course under "— Enroll in course —", tick **as sponsored/excused**,
and Save. Sponsored access behaves like a normal enrollment but is recorded
separately so you can tell it apart.

### Monthly subscription (all-access)

1. Create a form with a **Subscription Field** (e.g. $15/month) and payment
   method, and put it on a "Subscribe" page.
2. Add its form ID to **ASWJ Courses → Settings → Subscription form IDs**,
   and select the page under **Subscribe page**.

While the subscription is active the student can access **every paid and
subscription-type course on the site** — including courses you publish later.
Locked paid courses show a "Subscribe Monthly" option next to "Enroll Now".
Cancelling the subscription removes that access automatically (their
individually-purchased courses are kept). Sisters-only and diploma
restrictions still apply to subscribers.

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
