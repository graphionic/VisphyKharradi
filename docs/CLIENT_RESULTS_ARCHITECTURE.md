# FTPRENEUR — Client Results / Proof System Architecture (Phase 07A)

> **Engine:** InnoDB · **Charset:** utf8mb4 (`utf8mb4_unicode_ci`) · **Migrations:** CodeIgniter 4 `app/Database/Migrations`  
> **Permanent Mandatory Security Rule:** **ZERO DATABASE FOREIGN KEYS.** All relationships are application-level logical references (`BIGINT UNSIGNED`).

---

## 1. Executive Purpose: Proof Engine vs. Simple Testimonials

The Ftpreneur Client Results / Proof System is designed as a **comprehensive transformation proof engine** rather than a basic text testimonial slider. 

While traditional SaaS websites display generic quotes, Ftpreneur client outcomes reflect measurable, scientific health and fitness transformations across nutrition, strength, and lifestyle disease management. Each record combines:

1. **Client Identity & Story**: Public display name, subtitle context, short card snippet, and full journey narrative.
2. **Program Reference**: Logical link to the package/program taken, preserved via historical name snapshots.
3. **Dynamic Before/After Metrics**: Unlimited, extensible metrics (e.g. Weight, HbA1c, Cholesterol, Blood Pressure, Waist).
4. **Visual Transformation Media**: Categorized before/after photos, progress shots, and gallery imagery.
5. **Supporting Evidence / Reports**: Verified lab reports, assessment summaries, and medical/fitness documentation with strict health privacy controls.

---

## 2. Logical ER Overview & Zero-FK Constraint

```
packages (1) ─────── (0..*) client_results [Logical reference via package_id + program_name_snapshot]
                         │
                         ├── (0..*) client_result_metrics [client_result_id, logical]
                         ├── (0..*) client_result_media   [client_result_id, logical]
                         └── (0..*) client_result_reports [client_result_id, logical]
```

### Mandatory Foreign Key Rule
- **DATABASE FOREIGN KEY COUNT = 0**.
- Relationships use indexed `BIGINT UNSIGNED` primary and reference keys.
- Application layer services (`ClientResultService`) manage relational integrity, existence validation, and child cleanup.

---

## 3. Database Schema Specification

### 3.1 Table: `client_results`
Primary transformation record for public cards and future bottom drawer narratives.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | `BIGINT UNSIGNED` | NO | Auto-Inc | Primary Key |
| `package_id` | `BIGINT UNSIGNED` | YES | NULL | Logical reference to `packages.id` (NO FK) |
| `program_name_snapshot` | `VARCHAR(150)` | YES | NULL | Immutable display name snapshot at coaching time |
| `client_display_name` | `VARCHAR(100)` | NO | — | Public display identity (e.g. "Rahul S.") |
| `client_subtitle` | `VARCHAR(150)` | YES | NULL | Role/Age context (e.g. "Managing Director, 42") |
| `short_testimonial` | `VARCHAR(500)` | NO | — | Concise card snippet for landing page |
| `full_story` | `TEXT` | NO | — | Complete transformation story for bottom drawer |
| `journey_duration` | `VARCHAR(50)` | YES | NULL | Timeframe (e.g. "16 Weeks", "6 Months") |
| `cover_image` | `VARCHAR(255)` | YES | NULL | Primary feature image path |
| `display_order` | `INT UNSIGNED` | NO | `100` | Sorting order on public landing page |
| `is_featured` | `TINYINT(1)` | NO | `0` | Landing page highlight flag |
| `is_active` | `TINYINT(1)` | NO | `1` | Publication status flag |
| `created_at` | `DATETIME` | YES | NULL | CI4 timestamp |
| `updated_at` | `DATETIME` | YES | NULL | CI4 timestamp |
| `deleted_at` | `DATETIME` | YES | NULL | Soft delete timestamp (`ClientResultModel`) |

**Indexes**:
- `PRIMARY KEY (id)`
- `KEY idx_client_results_package_id (package_id)`
- `KEY idx_client_results_active_order (is_active, display_order)`
- `KEY idx_client_results_is_featured (is_featured)`

---

### 3.2 Table: `client_result_metrics`
Repeatable, extensible before/after outcome measurements.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | `BIGINT UNSIGNED` | NO | Auto-Inc | Primary Key |
| `client_result_id` | `BIGINT UNSIGNED` | NO | — | Logical reference to `client_results.id` (NO FK) |
| `metric_name` | `VARCHAR(100)` | NO | — | e.g. "Weight", "HbA1c", "Blood Pressure" |
| `before_value` | `VARCHAR(100)` | NO | — | Baseline value string (numeric or formatted ratio) |
| `after_value` | `VARCHAR(100)` | NO | — | Post-program value string |
| `unit` | `VARCHAR(50)` | YES | NULL | e.g. "kg", "%", "mg/dL", "mmHg", "in" |
| `context` | `VARCHAR(255)` | YES | NULL | Contextual note (e.g. "Fasting baseline") |
| `measurement_start_date` | `DATE` | YES | NULL | Baseline test date |
| `measurement_end_date` | `DATE` | YES | NULL | Post-program test date |
| `display_order` | `INT UNSIGNED` | NO | `100` | Sorting order inside drawer |
| `is_public` | `TINYINT(1)` | NO | `0` | Privacy toggle (**Default: 0 / Private**) |
| `created_at` | `DATETIME` | YES | NULL | CI4 timestamp |
| `updated_at` | `DATETIME` | YES | NULL | CI4 timestamp |

**Indexes**:
- `PRIMARY KEY (id)`
- `KEY idx_crm_client_result_id (client_result_id)`
- `KEY idx_crm_result_public_order (client_result_id, is_public, display_order)`

---

### 3.3 Table: `client_result_media`
Visual proof images and gallery items.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | `BIGINT UNSIGNED` | NO | Auto-Inc | Primary Key |
| `client_result_id` | `BIGINT UNSIGNED` | NO | — | Logical reference to `client_results.id` (NO FK) |
| `media_type` | `VARCHAR(50)` | NO | — | Whitelisted: `before`, `after`, `progress`, `gallery` |
| `file_path` | `VARCHAR(255)` | NO | — | Path to media file |
| `thumbnail_path` | `VARCHAR(255)` | YES | NULL | Path to generated thumbnail |
| `caption` | `VARCHAR(300)` | YES | NULL | Optional media caption |
| `media_date` | `DATE` | YES | NULL | Date of photograph |
| `display_order` | `INT UNSIGNED` | NO | `100` | Sorting order inside drawer |
| `is_public` | `TINYINT(1)` | NO | `0` | Privacy toggle (**Default: 0 / Private**) |
| `created_at` | `DATETIME` | YES | NULL | CI4 timestamp |
| `updated_at` | `DATETIME` | YES | NULL | CI4 timestamp |

**Indexes**:
- `PRIMARY KEY (id)`
- `KEY idx_crmedia_client_result_id (client_result_id)`
- `KEY idx_crmedia_type (client_result_id, media_type)`
- `KEY idx_crmedia_public_order (client_result_id, is_public, display_order)`

---

### 3.4 Table: `client_result_reports`
Supporting lab panels and assessment report metadata.

| Column | Type | Null | Default | Description |
|--------|------|------|---------|-------------|
| `id` | `BIGINT UNSIGNED` | NO | Auto-Inc | Primary Key |
| `client_result_id` | `BIGINT UNSIGNED` | NO | — | Logical reference to `client_results.id` (NO FK) |
| `report_title` | `VARCHAR(150)` | NO | — | Title (e.g. "Baseline Metabolic Blood Panel") |
| `report_type` | `VARCHAR(50)` | YES | NULL | e.g. `lab_report`, `assessment_report` |
| `report_date` | `DATE` | YES | NULL | Issue date of report |
| `file_path` | `VARCHAR(255)` | NO | — | Path to document file |
| `file_mime` | `VARCHAR(100)` | NO | — | e.g. `application/pdf`, `image/png` |
| `file_size` | `INT UNSIGNED` | NO | — | File size in bytes |
| `description` | `VARCHAR(500)` | YES | NULL | Optional report summary |
| `display_order` | `INT UNSIGNED` | NO | `100` | Sorting order inside drawer |
| `is_public` | `TINYINT(1)` | NO | `0` | **PRIVACY MANDATORY DEFAULT: 0 / Private** |
| `created_at` | `DATETIME` | YES | NULL | CI4 timestamp |
| `updated_at` | `DATETIME` | YES | NULL | CI4 timestamp |

**Indexes**:
- `PRIMARY KEY (id)`
- `KEY idx_crreports_client_result_id (client_result_id)`
- `KEY idx_crreports_public_order (client_result_id, is_public, display_order)`

---

## 4. Architectural Rationale & Design Decisions

### 4.1 Program Snapshot Strategy (`program_name_snapshot`)
If a package is modified, renamed, or soft-deleted in the future, proof records must not lose historical context. `client_results` maintains an optional `package_id` logical link alongside a `program_name_snapshot` string (e.g., "Executive Performance Edition"). When rendering the drawer, the application falls back to `program_name_snapshot` if the referenced package is absent.

### 4.2 Flexible Metric Storage Strategy (`VARCHAR(100)`)
Metrics are stored as `VARCHAR(100)` rather than fixed `DECIMAL` columns to accommodate diverse physiological measurements:
- Standard decimal/numeric values: `92` → `78` (unit: `kg`)
- Percentage values: `8.2` → `6.4` (unit: `%`)
- Ratio / multi-part values: `160/100` → `125/82` (unit: `mmHg`)
This avoids forcing every metric into fixed numeric columns while ensuring exact entry preservation.

### 4.3 Health Privacy & Public Visibility Default (`is_public = 0`)
Medical and lab evidence can contain sensitive personal data. Every metric, media item, and report defaults to `is_public = 0`. An administrator must explicitly toggle `is_public = 1` before any child proof element is exposed to the visitor-facing drawer.

### 4.4 Explicit Application Deletion Strategy
Because database foreign keys are prohibited, deletion of a `client_results` record is managed explicitly by `ClientResultService`:
1. Service starts a database transaction.
2. Service fetches associated `client_result_media` and `client_result_reports` to schedule physical file unlinks.
3. Service deletes entries from `client_result_metrics`, `client_result_media`, and `client_result_reports` matching `client_result_id`.
4. Service soft-deletes or hard-deletes the `client_results` master record.
5. Service unlinks physical files safely post-transaction commit.

---

## 5. Future Landing Page Bottom Drawer Aggregate Structure

When a visitor clicks a Client Result card on the landing page in future phases, `ClientResultService` will assemble the complete aggregate object without page navigation:

```json
{
  "id": 1,
  "client_display_name": "Rahul S.",
  "client_subtitle": "Founder & Director, 42",
  "program_name": "Executive Performance Edition",
  "journey_duration": "16 Weeks",
  "short_testimonial": "...",
  "full_story": "...",
  "cover_image": "/uploads/client_results/1/cover.webp",
  "metrics": [
    { "name": "Weight", "before": "92", "after": "78", "unit": "kg" },
    { "name": "HbA1c", "before": "8.2", "after": "6.4", "unit": "%" }
  ],
  "media": {
    "before": [{ "file_path": "...", "caption": "Baseline" }],
    "after": [{ "file_path": "...", "caption": "Week 16" }],
    "progress": [],
    "gallery": []
  },
  "reports": [
    { "title": "Baseline vs Week 16 Lab Summary", "file_path": "...", "file_mime": "application/pdf" }
  ]
}
```

---

## 6. Medical / Outcome Wording Guardrail

1. **Factual Evidence Only**: The system stores documented baseline vs. post-program values and client narratives.
2. **Zero Automated Medical Conclusions**: The application will **never** generate automated claims such as *"Diabetes cured"*, *"Condition eliminated"*, or *"Guaranteed weight loss"*.
3. **Responsible Framing**: All public copy around metabolic or lifestyle health management emphasizes individual routine optimization and evidence-based coaching support.
