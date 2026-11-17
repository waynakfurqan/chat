# ASWJ College Australia — Islamic Knowledge Course Platform

Custom WordPress plugin (`aswj-college-lms/`) that adds a course/LMS system to
the existing ASWJ College WordPress site, integrated with **Fluent Forms Pro**
for student registration and payments.

## Features

- **Courses → Lessons** (one YouTube video per lesson) with lesson notes.
- **Progress tracking** — students tick lessons off; progress bars everywhere.
- **Student portal** (`[aswj_portal]`) — my courses, progress, profile.
- **Course catalog** (`[aswj_courses]`) with access badges.
- **Access tiers**: Free (open to anyone), Paid (one-time Fluent Forms
  payment), Subscription (recurring), Diploma-students-only, plus a
  **Sisters-only** restriction that combines with any tier.
- **Gated video playback** — the YouTube player is only served to authorized
  students via an authenticated request; the video ID never appears in page
  HTML (see SETUP.md §6 for honest limits of YouTube protection).
- **Fluent Forms Pro integration** — registration feed assigns the Student
  role and auto-flags sisters from the gender field; paid/subscription forms
  enroll students automatically; cancellation revokes subscription access.
- **Admin tools** — Students screen (flags + manual enrollment) and Settings.

## Install

Zip the `aswj-college-lms` folder and upload it via
**Plugins → Add New → Upload Plugin**, then follow
[`aswj-college-lms/SETUP.md`](aswj-college-lms/SETUP.md) for full step-by-step
configuration (pages, registration form, payments, courses).
