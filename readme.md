# WorkHub

A modern, role-based project and workforce management platform built for SMEs. WorkHub lets organisations create dedicated workspaces, onboard team members, manage projects and tasks, collaborate through file attachments and comments, run video meetings, and track performance through analytics dashboards.

---

## Table of Contents

- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Features](#features)
- [Project Structure](#project-structure)
- [Database Schema](#database-schema)
- [Installation](#installation)
- [Environment & Configuration](#environment--configuration)
- [Authentication Flows](#authentication-flows)
- [API Reference](#api-reference)
- [Role & Permission Model](#role--permission-model)
- [File Uploads](#file-uploads)
- [Video Meetings](#video-meetings)
- [Analytics](#analytics)
- [Design System](#design-system)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.1+ (no framework) |
| Database | MySQL 8 |
| File Storage | Cloudinary |
| Video Meetings | JaaS (Jitsi as a Service) |
| Frontend | Vanilla JS, Chart.js 4.4.0 |
| Fonts | DM Sans, Playfair Display (Google Fonts) |
| Auth | bcrypt passwords, OTP email verification, PHP sessions |

---

## Architecture

WorkHub follows a **Model–Controller** pattern with a single front-controller router.

```
public/index.php          ← front controller, all requests routed here
src/
  Controller/             ← one controller per domain
  Models/                 ← PDO models, one class per table group
  Middleware/             ← AuthMiddleware (admin), UserMIddleware (members)
  Service/                ← CloudinaryService
  Utils/                  ← response.php, ActivityLogger.php, Email.php, helpers.php
views/                    ← PHP templates
  auth/                   ← login, register, verify, ForgotPassword
  User/                   ← member-facing views
  partials/               ← sidebar.php
config/
  Config.php              ← DB credentials
  jaas.php                ← JaaS App ID + Key ID (git-ignored)
public/
  css/                    ← dashboard.css, auth.css
  js/                     ← one JS file per page
```

Requests hit `public/index.php`, the URI is normalised, and a `switch` routes to the appropriate controller method or view include.

---

## Features

### Organisation & Workspaces
- Admin registers with email + OTP verification
- Admin creates one organisation (workspace) with name, slogan, and logo
- All data is scoped to the organisation — complete multi-tenant isolation
- Settings page: update org info, replace/remove logo, delete all members, delete organisation

### Role-Based Access Control
| Role | Capabilities |
|---|---|
| **Admin** | Full control — org settings, members, projects, tasks, analytics |
| **Manager** | Project-level manager — create/assign tasks, start meetings, view project analytics |
| **Member** | View assigned tasks, update own task status, comment, attach files |

### Member Management
- Admin invites members (name, email, role, optional avatar)
- System generates a random password and displays it once in a credentials modal
- Member is forced to change password on first login
- Admin can reset any member's password at any time — generates a new password and resets the forced-change flag
- Admin can remove individual members or wipe all members at once

### Projects
- Full CRUD with status: `active`, `completed`, `archived`
- Project detail page with progress bar, team workload panel, status donut chart, priority breakdown
- Project-level file panel showing all attachments across every task, grouped by task, with preview and download

### Task Management
- Statuses: `pending` → `in_progress` → `in_review` → `completed`
- Priorities: `low`, `medium`, `high`, `critical`
- Due dates with overdue detection
- Admin/manager assigns tasks; only the assigned member can update status
- Task detail side panel with comments and file attachments

### File Attachments
- Attached at task level, visible at both task level and project level
- Supported types: images (JPEG, PNG, GIF, WebP), PDF, DOCX, XLSX, CSV, TXT, PPTX, ZIP
- Max 10 MB per file, 5 MB for org logos
- Images stored as `resource_type: image`; all other files as `resource_type: raw` (fixes Cloudinary ZIP rejection)
- Images show inline thumbnails; clicking opens a fullscreen lightbox with download option
- All other files show a type icon with a direct download button

### Comments
- Threaded comments on every task
- Visible to all members of the project
- Any participant can delete any comment

### Video Meetings
- Powered by JaaS (Jitsi as a Service) with RS256 JWT authentication
- Stable room name per project — same link every time
- Moderator JWT for admin/manager, participant JWT for members
- No lobby — participants join directly
- Meeting history tracked in DB

### Analytics
**Admin analytics dashboard:**
- 6 KPI cards (total tasks, completed, in progress, overdue, active projects, total members)
- 30-day task completion trend chart (Chart.js)
- Status distribution and priority distribution charts
- Top performers leaderboard
- Project progress bars
- Activity log with filters

**Member analytics page** (`/member-analytics?id=X`):
- Completion rate ring
- 30-day activity bar chart
- Status and priority breakdowns
- Full task list and project membership

### Activity Logging
- Every significant action is logged (created project, assigned task, removed member, etc.)
- Logs include actor type (admin/user), entity type, entity ID, and a description
- Viewable in the analytics dashboard with filtering

### Settings
- Update organisation name and slogan
- Upload or remove organisation logo
- Change admin password (with current password verification)
- Danger zone: delete all members, delete organisation (requires typing org name)

### Forgot Password (Admin)
- 4-step flow: enter email → receive 6-digit OTP → verify code → set new password
- OTP expires in 10 minutes, single-use (cleared after successful reset)
- Password strength bar on new password input
- Matches the dark auth page aesthetic

---

## Project Structure

```
workhub/
├── config/
│   ├── Config.php
│   └── jaas.php                  ← git-ignored
├── public/
│   ├── index.php                 ← router
│   ├── css/
│   │   ├── auth.css
│   │   └── dashboard.css
│   └── js/
│       ├── app.js                ← shared helpers (esc, formatDate, modals, toast)
│       ├── dashboard.js          ← admin dashboard
│       ├── project-details.js    ← project detail page
│       ├── members.js            ← members page
│       ├── analytics.js          ← admin analytics
│       ├── member-analytics.js   ← member analytics page
│       ├── meeting.js            ← JaaS meeting logic
│       ├── settings.js           ← settings page
│       ├── user-dashboard.js     ← member/manager dashboard
│       └── user-tasks.js         ← member/manager full tasks page
├── src/
│   ├── Controller/
│   │   ├── AdminController.php
│   │   ├── AnalyticsController.php
│   │   ├── AttachmentController.php
│   │   ├── CommentController.php
│   │   ├── MeetingController.php
│   │   ├── MemberController.php
│   │   ├── ProjectController.php
│   │   ├── ProjectMemberController.php
│   │   ├── SettingController.php
│   │   ├── TaskController.php
│   │   └── UserController.php
│   ├── Middleware/
│   │   ├── AuthMiddleware.php    ← admin session guard
│   │   └── UserMIddleware.php    ← member session guard (note capital M)
│   ├── Models/
│   │   ├── ActivityLogModel.php
│   │   ├── AdminModel.php
│   │   ├── AnalyticsModel.php
│   │   ├── AttachmentModel.php
│   │   ├── CommentModel.php
│   │   ├── Database.php
│   │   ├── MeetingModel.php
│   │   ├── OrganizationModel.php
│   │   ├── ProjectMemberModel.php
│   │   ├── ProjectModel.php
│   │   ├── SettingModel.php
│   │   ├── TaskModel.php
│   │   └── UserModel.php
│   ├── Service/
│   │   └── CloudinaryService.php
│   └── Utils/
│       ├── ActivityLogger.php
│       ├── Email.php
│       ├── helpers.php
│       └── response.php
├── views/
│   ├── auth/
│   │   ├── ForgotPassword.php
│   │   ├── login.php
│   │   ├── register.php
│   │   └── verify.php
│   ├── partials/
│   │   └── sidebar.php
│   ├── User/
│   │   ├── project-description.php
│   │   ├── Tasks.php
│   │   └── userDashboard.php
│   ├── Analytics.php
│   ├── Dashboard.php
│   ├── Home.php
│   ├── MemberAnalytics.php
│   ├── Members.php
│   ├── project.php
│   ├── projectdetail.php
│   └── Setting.php
└── README.md
```

---

## Database Schema

```sql
admin           (id, email, password, isverified, otp, otp_expires_at, created_at)
organization    (organization_id, admin_id, name, slogan, organization_logo,
                 logo_public_id, created_at)
user            (user_id, organization_id, name, email, password,
                 password_changed, role, userProfile, profile_public_id, created_at)
project         (project_id, organization_id, name, description, status, created_at)
project_members (id, project_id, user_id, role)
task            (task_id, project_id, title, description, status, priority,
                 assigned_to, due_date, created_at)
task_attachment (attachment_id, task_id, file_name, file_url, public_id,
                 file_type, file_size, uploaded_by, uploaded_type, created_at)
task_comment    (comment_id, task_id, body, author_id, author_type,
                 author_name, author_avatar, created_at)
meeting         (meeting_id, project_id, room_name, started_by, started_at,
                 ended_at, status)
activity_log    (log_id, org_id, action, entity_type, entity_id,
                 actor_id, actor_type, description, created_at)
```

---

## Installation

```bash
# 1. Clone the repo
git clone https://github.com/yourname/workhub.git
cd workhub

# 2. Point your web server document root to /public
# Apache / Nginx — see .htaccess or nginx.conf

# 3. Copy and fill in config
cp config/Config.example.php config/Config.php
cp config/jaas.example.php   config/jaas.php

# 4. Import the database
mysql -u root -p workhub < database/schema.sql

# 5. Ensure uploads/tmp is writable (Cloudinary handles permanent storage)
```

**Apache `.htaccess`** (place in `/public`):
```apacheconf
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

---

## Environment & Configuration

**`config/Config.php`**
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'workhub');
define('DB_USER', 'root');
define('DB_PASS', '');

define('CLOUDINARY_CLOUD_NAME', 'your_cloud_name');
define('CLOUDINARY_API_KEY',    'your_api_key');
define('CLOUDINARY_API_SECRET', 'your_api_secret');

define('MAIL_HOST',     'smtp.gmail.com');
define('MAIL_PORT',     587);
define('MAIL_USERNAME', 'your@gmail.com');
define('MAIL_PASSWORD', 'your_app_password');
define('MAIL_FROM',     'your@gmail.com');
```

**`config/jaas.php`** ← **git-ignored, never commit**
```php
define('JAAS_APP_ID', 'vpaas-magic-cookie-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('JAAS_KEY_ID', 'xxxxxx');
define('JAAS_PRIVATE_KEY', <<<EOT
-----BEGIN RSA PRIVATE KEY-----
...
-----END RSA PRIVATE KEY-----
EOT);
```

---

## Authentication Flows

### Admin
1. Register with email + password → OTP sent via email
2. Verify OTP → account activated
3. Login → session set (`admin_id`, `admin_email`)
4. All admin routes guarded by `AuthMiddleware::checkAuth()`

### Admin Forgot Password
1. Enter email → OTP sent (10 min expiry)
2. Enter 6-digit code
3. Set new password → OTP cleared, password updated

### Member (Team)
1. Admin creates member → system generates password → admin shares credentials
2. Member logs in → forced to change password on first login
3. Session set (`user_id`, `user_name`, `role`, `password_changed`)
4. All member routes guarded by `UserAuthMiddleware::checkAuth()` + `requirePasswordChanged()`
5. Admin can reset any member's password at any time from the Members page

---

## API Reference

### Auth & Organisation
| Method | Route | Description |
|---|---|---|
| POST | `/register` | Admin registration |
| POST | `/login` | Admin login |
| POST | `/verify` | OTP verification |
| POST | `/api/admin/forgot-password` | Send reset OTP |
| POST | `/api/admin/reset-password` | Verify OTP + set new password |
| GET | `/api/organization` | Get org details |
| POST | `/organization/create` | Create organisation |
| POST | `/api/organization/update` | Update name/slogan |
| POST | `/api/organization/logo` | Upload logo |
| DELETE | `/api/organization/logo` | Remove logo |
| DELETE | `/api/organization/delete` | Delete entire org |

### Members
| Method | Route | Description |
|---|---|---|
| GET | `/api/members` | List org members |
| POST | `/api/members` | Create member |
| DELETE | `/api/members` | Remove member |
| DELETE | `/api/members/all` | Remove all members |
| POST | `/api/members/reset-password` | Admin reset member password |

### Projects
| Method | Route | Description |
|---|---|---|
| GET/POST/PUT/DELETE | `/api/projects` | Project CRUD |
| GET | `/api/projects/single` | Single project by ID |
| GET/POST/DELETE | `/api/projects/members` | Project member management |
| PATCH | `/api/projects/members/role` | Change member role |
| GET | `/api/projects/files` | All files across project tasks |

### Tasks
| Method | Route | Description |
|---|---|---|
| GET/POST/PUT/DELETE | `/api/tasks` | Admin task CRUD |
| PATCH | `/api/tasks/status` | Update task status (admin) |
| GET/POST/PUT/DELETE | `/api/user/tasks` | Member task CRUD |
| PATCH | `/api/user/tasks/status` | Update task status (member) |

### Attachments & Comments
| Method | Route | Description |
|---|---|---|
| GET/POST/DELETE | `/api/tasks/attachments` | Task file attachments |
| GET/POST/DELETE | `/api/tasks/comments` | Task comments |

### Meetings
| Method | Route | Description |
|---|---|---|
| POST | `/api/meetings/start` | Start meeting |
| GET | `/api/meetings/active` | Get active meeting |
| GET | `/api/meetings/token` | Get participant JWT |
| POST | `/api/meetings/end` | End meeting |
| GET | `/api/meetings/history` | Meeting history |

### Analytics
| Method | Route | Description |
|---|---|---|
| GET | `/api/analytics/admin` | Admin KPIs + charts data |
| GET | `/api/analytics/activity` | Activity log |
| GET | `/api/analytics/member` | Member performance data |
| GET | `/api/analytics/project` | Project-level analytics |

### Settings
| Method | Route | Description |
|---|---|---|
| POST | `/api/admin/change-password` | Change admin password |

---

## Role & Permission Model

```
Admin
 └── owns one Organisation
      ├── creates Projects
      │    └── adds Members to Projects (as manager or member)
      └── creates Members (users)

Manager (project-level)
 └── can create/edit/delete tasks within their projects
 └── can start/end meetings
 └── can see all tasks in their projects

Member
 └── can update status only on tasks assigned to them
 └── can comment and attach files on any task in their projects
 └── can view projects they are a member of
```

Middleware enforcement:
- `AuthMiddleware::checkAuth()` — rejects non-admin sessions
- `UserAuthMiddleware::checkAuth()` — rejects non-member sessions
- `UserAuthMiddleware::requirePasswordChanged()` — redirects to password change if first login

---

## File Uploads

All files go through `CloudinaryService::uploadImage()` which auto-detects MIME type:

| MIME type | Cloudinary `resource_type` |
|---|---|
| `image/*`, `application/pdf` | `image` |
| Everything else (docx, xlsx, zip, csv…) | `raw` |

This is important — passing a ZIP file to Cloudinary's `image` endpoint causes a "Unsupported ZIP file" error. The auto-detection resolves this transparently.

Size limits: **10 MB** per task attachment, **5 MB** for org logos.

Blocked types: `exe`, `bat`, `sh`, `php`, `py`, `html`, video, audio.

---

## Video Meetings

JaaS integration uses RS256 JWT tokens generated server-side in `MeetingController`.

- **Room name** is derived from `project_id` — stable and unique per project
- **Moderator token** — issued to admin and project managers (`moderator: true`)
- **Participant token** — issued to members (`moderator: false`)
- No lobby — `prejoinPageEnabled: false`
- Meeting opens in a new tab
- Active meeting state tracked in DB; only one meeting per project at a time

Config lives in `config/jaas.php` (git-ignored). You need:
- JaaS App ID
- JaaS Key ID
- RSA private key (PEM format)

---

## Analytics

### Admin Dashboard
Data served by `GET /api/analytics/admin`:
- Total tasks, completed, in progress, overdue, active projects, total members
- 30-day task completion trend (daily counts)
- Status distribution (pending/in_progress/in_review/completed)
- Priority distribution
- Top 5 performers by completed task count
- Last 5 active projects with progress %

### Member Analytics
Data served by `GET /api/analytics/member?user_id=X`:
- Overall completion rate (ring chart)
- 30-day activity trend (bar chart)
- Status and priority breakdowns
- All projects the member belongs to
- Full task list with status and due date

---

## Design System

```css
--brand:          #6A0031;   /* deep burgundy */
--brand-mid:      #8a1144;
--brand-light:    #b8245f;
--brand-pale:     #fdf2f6;
--brand-pale2:    #f5e6ed;
--accent:         #E8A045;   /* warm amber */
--text-primary:   #1a1218;
--text-secondary: #6b5b65;
--text-muted:     #a08898;
--border:         #e8dde3;
--surface:        #ffffff;
--surface-2:      #faf7f9;
--sidebar-w:      260px;
--header-h:       64px;
--radius:         12px;
--radius-sm:      8px;
```

**Fonts:** `DM Sans` (body, UI) + `Playfair Display` (headings, logo, numbers)

Auth pages use a separate dark theme (`auth.css`) — `#0a0a0a` background with a subtle teal (`#7df9ff`) accent, matching the dark card aesthetic.

---

## Known Gaps & Planned Features

| Feature | Status |
|---|---|
| Email notifications (task assigned, due date, etc.) | Planned — cron job |
| In-app notification bell | Planned |
| Multiple organisations per admin | Not supported (one org per admin) |
| Project-level file attachments (separate from tasks) | Not built |
| Admin profile page | Not built |
| CSRF tokens | Recommended before production |
| Password reset for members (self-service) | Admin-only reset currently |

---

## License

MIT