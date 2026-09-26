# FTPRENEUR — PRODUCTION ENVIRONMENT ARCHITECTURE & DEPLOYMENT GUIDE

> [!IMPORTANT]
> **Git contains CODE. The server's `.env` contains ENVIRONMENT CONFIGURATION.**
> Never commit actual production database credentials, encryption keys, API keys, or passwords to version control.

---

## 1. Overview & Isolation Strategy

To ensure zero risk of accidental credential leaks or configuration overwrites when deploying code via `git pull origin main` on Hostinger:

1. **Environment File Exclusion**: `.env` is listed in `.gitignore` and is **never** tracked in Git.
2. **Server-Specific Configuration**: Hostinger production server maintains its own `.env` file directly at the project root (`HOSTINGER_PROJECT_ROOT/.env`).
3. **Deployment Safety**: Executing `git pull origin main` updates application source files in `app/`, `public/`, `system/`, etc., while leaving `.env`, `writable/` runtime storage, and dynamic user uploads in `public/uploads/` completely untouched.

---

## 2. Production `.env` Variable Reference

Below is the authoritative list of required environment variables for the Hostinger production environment. 

> [!CAUTION]
> Replace all placeholder values (`YOUR_...`) in the actual server `.env` with strong production values generated on the server.

```ini
# ==============================================================================
# ENVIRONMENT & APP CONFIGURATION
# ==============================================================================
CI_ENVIRONMENT = production

# Must be exact HTTPS live production domain with trailing slash
app.baseURL = 'https://yourdomain.com/'

# Force HTTPS redirect in production
app.forceGlobalSecureRequests = true

# ==============================================================================
# DATABASE CONFIGURATION (HOSTINGER MYSQL)
# ==============================================================================
database.default.hostname = 'localhost' # Or Hostinger MySQL internal IP / hostname
database.default.database = 'YOUR_HOSTINGER_DB_NAME'
database.default.username = 'YOUR_HOSTINGER_DB_USER'
database.default.password = 'YOUR_HOSTINGER_DB_PASSWORD'
database.default.DBDriver = 'MySQLi'
database.default.DBPrefix = ''
database.default.port     = 3306

# ==============================================================================
# ENCRYPTION & SECURITY KEYS
# ==============================================================================
# 32-byte hex key generated via: php spark secret:generate (or hex2bin string)
encryption.key = 'hex2bin:YOUR_32_BYTE_HEX_KEY_HERE'

# ==============================================================================
# SESSION CONFIGURATION
# ==============================================================================
# Session cookie security settings
session.driver = 'CodeIgniter\Session\Handlers\FileHandler'
session.cookieName = 'ftpreneur_session'
session.expiration = 7200
session.savePath = ''

# ==============================================================================
# SECURITY / CSRF CONFIGURATION
# ==============================================================================
security.tokenName = 'csrf_ftpreneur_token'
security.headerName = 'X-CSRF-TOKEN'
security.cookieName = 'ftpreneur_csrf'
security.expires    = 7200

# ==============================================================================
# FUTURE PAYMENT GATEWAY (RAZORPAY) — PLACEHOLDERS ONLY
# ==============================================================================
# Note: Do NOT fill until payment integration phase (Phase 12)
# RAZORPAY_KEY_ID     = 'rzp_live_xxxxxxxx'
# RAZORPAY_KEY_SECRET = 'YOUR_RAZORPAY_LIVE_SECRET'
# RAZORPAY_WEBHOOK_SECRET = 'YOUR_RAZORPAY_WEBHOOK_SECRET'
```

---

## 3. Production Error & Debug Behavior

When `CI_ENVIRONMENT = production`:
- Detailed exception tracebacks and Kint debug bars are **disabled** automatically by CodeIgniter 4.
- End users see a clean, friendly standard error page (`app/Views/errors/html/error_500.php`).
- Technical errors, DB exceptions, and stack traces are logged silently to `/writable/logs/log-YYYY-MM-DD.php`.
- Debugging in production is conducted exclusively by reading server log files.

---

## 4. Directory Permissions & Deployment Survival

### A. Writable Directory (`/writable`)
- **Permissions**: `755` (or `775` depending on Hostinger PHP execution mode / FCGI handler).
- **Ownership**: Webserver user / cPanel user account.
- **Git Status**: All contents of `/writable/cache/`, `/writable/logs/`, `/writable/session/`, `/writable/uploads/`, `/writable/debugbar/`, `/writable/htmlpurifier/` are ignored by `.gitignore` (except `index.html` placeholders).
- **Survival**: Deployments (`git pull`) never clean or overwrite runtime logs, session locks, or cached data.

### B. Public Uploads (`/public/uploads`)
- **Git Status**:
  - `public/uploads/packages/*.jpg` default package cover images are tracked in Git as baseline seed assets.
  - `public/uploads/client_results/` dynamic image media uploaded via Admin is untracked / ignored.
- **Survival Strategy**: Running `git pull origin main` in production will NOT delete or overwrite uploaded client images because git pull does not remove untracked files. (`git clean -fd` or `git reset --hard` MUST NEVER be run on production).

### C. Client Results Media Architecture
- **Public Media (`public/uploads/client_results/`)**: Public visual proof (before/after photos, progress photos, client story gallery images). Accessible directly by public browser HTTP requests.
- **Private Reports (`writable/uploads/client_results/reports/`)**: Sensitive documentary evidence (medical lab reports, health assessment PDFs). Kept strictly outside `public/` web root under `writable/uploads/`. Access is protected and served strictly through authenticated PHP download controller streaming.

---

## 5. Hostinger Production Setup Procedure

> [!NOTE]
> **Confirmed Hostinger Deployment Root**:
> The Hostinger domain document root is fixed as: `~/domains/visphykharradiftpreneur.com/public_html/`.
> The Git repository (`main` branch) is cloned directly into `public_html/`.
> Root deployment compatibility is provided via repository-root `index.php` front controller and root `.htaccess` security protection.

1. **Repository Clone**:
   Clone the `main` branch directly into Hostinger `public_html`:
   ```bash
   cd ~/domains/visphykharradiftpreneur.com/public_html
   git clone -b main <REPOSITORY_URL> .
   ```
2. **Environment File Creation**:
   Create `.env` directly at `public_html/.env` (protected from HTTP access via root `.htaccess`):
   ```bash
   cp env .env
   # Edit .env to set CI_ENVIRONMENT=production, production baseURL, DB credentials, encryption key
   ```
3. **Directory Permission Verification**:
   Verify `writable/` and `public/uploads/` are writable:
   ```bash
   chmod -R 755 writable public/uploads
   ```
4. **Database Migration Execution**:
   Run database migrations to build database schema:
   ```bash
   php spark migrate
   ```
5. **Controlled Initial Data Setup**:
   - **Do NOT run general seeders automatically**.
   - Seeders like `PackageSeeder`, `ClientResultSeeder`, `FaqSeeder` contain local/test data.
   - Run seeders only if explicitly required and approved for production baseline setup.

6. **Admin Account Bootstrap**:
   - Production Admin account MUST NOT reuse local development default passwords.
   - Admin account creation for production will occur via a controlled CLI spark command in a dedicated upcoming deployment step (Phase Prep G).

---

## 6. Deployment Checklist (`development` → `main`)

- [ ] All code changes tested and verified on local `development` branch.
- [ ] No `.env` or secret files staged/committed in Git.
- [ ] Code merged into `main` branch.
- [ ] On Hostinger server: `git pull origin main`.
- [ ] If database schema changed: `php spark migrate`.
- [ ] Perform smoke test on live production domain.

---

## 7. Known Production Hardening Blockers (Pre-Launch)

> [!WARNING]
> **Private Client Result Visual Media Privacy Blocker**:
> Currently, Client Result visual media records have an `is_public` boolean flag (e.g. `is_public = 0` for private images). However, image media files uploaded via Admin are stored under `public/uploads/client_results/`. Files stored under `public/` can be accessed directly via web URL if the filename is known, even if the application UI does not display or link to them.
> 
> **Required Action Before Public Launch**:
> Private visual proof media (`is_public = 0`) must be moved to protected storage under `writable/uploads/client_results/private_media/` and served exclusively through an authenticated PHP streaming proxy controller (matching the existing architecture used for private PDF evidence reports under `writable/uploads/client_results/reports/`).

