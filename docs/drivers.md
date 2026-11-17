# Drivers

Super PDF supports two PDF engines. DomPDF is the default. Browsershot is optional and used for tagged / accessible PDFs.

| Driver | Config | Tagged PDFs | Needs | Usage guide |
|---|---|---|---|---|
| DomPDF | `driver => 'dompdf'` | No | Nothing extra | **[DomPDF guide](dompdf.md)** |
| Browsershot | `driver => 'browsershot'` | Yes (`tagged => true`) | Node + npm + Puppeteer + Chrome | **[Browsershot guide](browsershot.md)** |

```twig
{{ craft.superpdf.html(html, {
    filename: 'doc',
    driver: 'browsershot',
    tagged: true
}) }}
```

This page is the **server setup** reference for Browsershot. For templates, margins, fonts, images, page breaks, and page totals, use the per-driver guides above.

Jump to your environment under **Browsershot setup**:

- [Ubuntu (nginx + php-fpm)](#ubuntu-nginx--php-fpm)
- [macOS (Herd / Valet / nginx)](#macos-herd--valet--nginx)
- [DDEV](#ddev)
- [Docker](#docker)

---

## DomPDF

No Node/Chrome install. Leave `driver` unset or set:

```php
// config/super-pdf.php
'driver' => 'dompdf',
```

Use DomPDF unless you need tagged / PDF/UA output. Full usage (config, CSS, fonts, images, headers, page totals): **[DomPDF guide](dompdf.md)**.

---

## Browsershot setup

Composer already installs `spatie/browsershot`. You must still install **Node**, **Puppeteer** (npm), and **Chrome** on the machine that runs PHP.

### Config keys (all environments)

| Key | Purpose |
|---|---|
| `driver` | `'browsershot'` |
| `tagged` | `true` for accessible/tagged PDFs |
| `browsershotNodeBinary` | Absolute path to `node` |
| `browsershotNpmBinary` | Absolute path to `npm` |
| `browsershotChromePath` | Absolute path to Chrome / Chromium binary |
| `browsershotNoSandbox` | `true` on DDEV / Docker / some Linux hosts |
| `browsershotTimeout` | Seconds (optional) |
| `browsershotShowBackground` | Print CSS backgrounds (default `true`) |
| `browsershotAllowLocalFileAccess` | Allow local fonts/CSS/images (default `true`) |
| `browsershotWaitUntilNetworkIdle` | Wait for remote fonts/CSS (default `true`) |
| `browsershotContentUrl` | Base URL for relative `href` / `src` / `@font-face` paths |

### DomPDF template compatibility (summary)

Browsershot accepts the **same HTML/CSS templates** as DomPDF where possible: fixed `header`/`footer` + `@page` margins map to Chromium native chrome + margins (including the outer left/right gutter). Backgrounds, local fonts, and `SUPER_PDF_TOTAL_PAGES` / `.page-number` are handled automatically.

Full tables and copy-paste examples: **[Browsershot guide](browsershot.md#dompdf-template-compatibility)**. DomPDF remote images follow HTTP redirects by default — see **[DomPDF guide](dompdf.md#remote-images)**.

Not pixel-identical (Chrome ≠ DomPDF). Same markup should work without a Browsershot-only rewrite.

How to discover paths (run on the **same host** as PHP):

```bash
which node
which npm

# Puppeteer’s Chrome (after npm install puppeteer + browsers install):
node -e '(async()=>{const p=require("puppeteer"); console.log(await p.executablePath());})()'

# Or system Google Chrome on macOS:
ls "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
```

Paste those absolute paths into `config/super-pdf.php` (examples in each section below).

Smoke test after setup:

```bash
php craft super-pdf/generate \
  --html='<!doctype html><html lang="en"><body><h1>Hi</h1></body></html>' \
  --filename=browsershot-smoke \
  --type=object \
  --driver=browsershot \
  --tagged=1
```

---

### Ubuntu (nginx + php-fpm)

#### 1. Install Node + npm → set in config

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt-get install -y nodejs

node -v && npm -v
which node   # often /usr/bin/node
which npm    # often /usr/bin/npm
```

```php
// config/super-pdf.php
'driver' => 'browsershot',
'tagged' => true,
'browsershotNodeBinary' => '/usr/bin/node',
'browsershotNpmBinary' => '/usr/bin/npm',
```

#### 2. Install Puppeteer → set in config

Install as the **php-fpm user** (often `www-data`) so PHP can resolve the module — not only as root:

```bash
sudo -u www-data npm install -g puppeteer

# Verify (as that user):
sudo -u www-data node -e "require('puppeteer'); console.log('puppeteer ok')"
sudo -u www-data npm root -g
```

No separate “puppeteer path” config key — Browsershot loads Puppeteer via Node (`NODE_PATH` / global modules). If php-fpm still cannot find it, keep using the same `browsershotNodeBinary` / `browsershotNpmBinary` that own the global install.

Project-local alternative (from Craft root):

```bash
cd /var/www/your-site
sudo -u www-data npm init -y
sudo -u www-data npm install puppeteer
```

#### 3. Install Chromium / Chrome → set in config

```bash
# Downloads Puppeteer’s “Chrome for Testing”
sudo -u www-data npx puppeteer browsers install chrome

# Print the binary path:
sudo -u www-data node -e '(async()=>{const p=require("puppeteer"); console.log(await p.executablePath());})()'
```

Also install OS libraries (Ubuntu 24.04):

```bash
sudo apt update
sudo apt install -y \
  libasound2t64 libatk1.0-0 libatk-bridge2.0-0 libcairo2 libcups2 \
  libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libglib2.0-0 \
  libgtk-3-0 libnspr4 libnss3 libpango-1.0-0 libx11-6 libxcb1 \
  libxcomposite1 libxdamage1 libxext6 libxfixes3 libxrandr2 \
  fonts-liberation ca-certificates
```

On Ubuntu 22.04 use `libasound2` instead of `libasound2t64`.

If Chrome reports sandbox / AppArmor errors:

```php
'browsershotNoSandbox' => true,
```

Optional sysctl (Ubuntu 23.10+):

```bash
echo 'kernel.apparmor_restrict_unprivileged_userns=0' | sudo tee /etc/sysctl.d/99-browsershot.conf
echo 'kernel.apparmor_restrict_unprivileged_unconfined=0' | sudo tee -a /etc/sysctl.d/99-browsershot.conf
sudo sysctl --system
```

Full Ubuntu example config:

```php
<?php

return [
    'driver' => 'browsershot',
    'tagged' => true,
    'browsershotNodeBinary' => '/usr/bin/node',
    'browsershotNpmBinary' => '/usr/bin/npm',
    // Paste from: node -e '(async()=>{const p=require("puppeteer"); console.log(await p.executablePath());})()'
    'browsershotChromePath' => '/home/www-data/.cache/puppeteer/chrome/.../chrome',
    'browsershotNoSandbox' => true,
];
```

Reload PHP after changes:

```bash
sudo systemctl reload php8.3-fpm   # adjust version
```

---

### macOS (Herd / Valet / nginx)

#### 1. Install Node + npm → set in config

**Homebrew:**

```bash
brew install node
which node
which npm
# Apple Silicon often: /opt/homebrew/bin/node
# Intel often:         /usr/local/bin/node
```

**nvm** (your machine):

```bash
nvm install 24
nvm use 24
which node   # e.g. /Users/YOU/.nvm/versions/node/v24.2.0/bin/node
which npm
```

```php
// config/super-pdf.php — nvm example
'driver' => 'browsershot',
'tagged' => true,
'browsershotNodeBinary' => '/Users/YOU/.nvm/versions/node/v24.2.0/bin/node',
'browsershotNpmBinary' => '/Users/YOU/.nvm/versions/node/v24.2.0/bin/npm',
```

```php
// Homebrew Apple Silicon example
'browsershotNodeBinary' => '/opt/homebrew/bin/node',
'browsershotNpmBinary' => '/opt/homebrew/bin/npm',
```

php-fpm / nginx often **do not** load nvm. Always set absolute `browsershotNodeBinary` / `browsershotNpmBinary` when using nvm.

#### 2. Install Puppeteer → set in config

From the Craft project root (recommended with nvm):

```bash
cd /path/to/craft-project
nvm use 24          # same Node you put in config
npm init -y         # once
npm install puppeteer
node -e "require('puppeteer'); console.log('puppeteer ok')"
```

Or globally (works well with Browsershot’s `NODE_PATH=\`npm root -g\``):

```bash
npm install -g puppeteer
node -e "require('puppeteer'); console.log('puppeteer ok')"
```

Again: no dedicated puppeteer path key — Node must resolve the package. Keep Node/npm binaries in config pointed at the Node that has Puppeteer installed.

#### 3. Install Chromium / Chrome → set in config

```bash
npx puppeteer browsers install chrome

# Puppeteer Chrome path (preferred):
node -e '(async()=>{const p=require("puppeteer"); console.log(await p.executablePath());})()'
```

Example output on Apple Silicon:

```text
/Users/YOU/.cache/puppeteer/chrome/mac_arm-…/chrome-mac-arm64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing
```

System Chrome (optional fallback):

```bash
ls "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
```

```php
<?php

return [
    'driver' => 'browsershot',
    'tagged' => true,
    'browsershotNodeBinary' => '/Users/YOU/.nvm/versions/node/v24.2.0/bin/node',
    'browsershotNpmBinary' => '/Users/YOU/.nvm/versions/node/v24.2.0/bin/npm',
    'browsershotChromePath' => '/Users/YOU/.cache/puppeteer/chrome/mac_arm-157.0.8103.50/chrome-mac-arm64/Google Chrome for Testing.app/Contents/MacOS/Google Chrome for Testing',
    // or:
    // 'browsershotChromePath' => '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
];
```

Verify PHP can run that Node:

```bash
php -r 'echo shell_exec("/Users/YOU/.nvm/versions/node/v24.2.0/bin/node -v");'
```

---

### DDEV

PHP/Node run **inside** the DDEV web container. Install everything with `ddev exec`, not only on the host Mac.

#### 1. Install Node + npm → set in config

```bash
ddev exec node -v
ddev exec npm -v
ddev exec which node   # often /usr/bin/node
ddev exec which npm
```

If Node is missing, enable it via DDEV docs / an addon, then `ddev restart`.

```php
'driver' => 'browsershot',
'tagged' => true,
'browsershotNodeBinary' => '/usr/bin/node',
'browsershotNpmBinary' => '/usr/bin/npm',
'browsershotNoSandbox' => true,
```

#### 2. Install Puppeteer → set in config

```bash
ddev exec bash -c 'cd /var/www/html && npm init -y && npm install puppeteer'
ddev exec bash -c 'cd /var/www/html && node -e "require(\"puppeteer\"); console.log(\"puppeteer ok\")"'
```

(No puppeteer path key — project `node_modules` must be visible inside the container.)

#### 3. Install Chromium / Chrome → set in config

```bash
ddev exec bash -c 'cd /var/www/html && npx puppeteer browsers install chrome'
ddev exec bash -c 'cd /var/www/html && node -e "(async()=>{const p=require(\"puppeteer\"); console.log(await p.executablePath());})()"'
```

If libraries are missing, add to `.ddev/config.yaml` then `ddev restart`:

```yaml
webimage_extra_packages:
  - libnss3
  - libatk-bridge2.0-0
  - libcups2
  - libgbm1
  - libasound2t64
  - fonts-liberation
```

```php
<?php

return [
    'driver' => 'browsershot',
    'tagged' => true,
    'browsershotNodeBinary' => '/usr/bin/node',
    'browsershotNpmBinary' => '/usr/bin/npm',
    'browsershotChromePath' => '/var/www/html/.cache/puppeteer/chrome/.../chrome', // paste from executablePath()
    'browsershotNoSandbox' => true,
];
```

Smoke test:

```bash
ddev exec php craft super-pdf/generate \
  --html='<!doctype html><html lang="en"><body><h1>Hi</h1></body></html>' \
  --filename=browsershot-ddev \
  --type=object \
  --driver=browsershot \
  --tagged=1
```

---

### Docker

Bake Node + Puppeteer + Chrome into the **same image** that runs Craft PHP (and queue workers).

#### 1. Install Node + npm → set in config

In the Dockerfile (Debian/Ubuntu base):

```dockerfile
USER root
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
  && apt-get install -y nodejs
```

```php
'browsershotNodeBinary' => '/usr/bin/node',
'browsershotNpmBinary' => '/usr/bin/npm',
```

#### 2. Install Puppeteer → set in config

```dockerfile
ENV PUPPETEER_CACHE_DIR=/var/cache/puppeteer
RUN npm install -g puppeteer \
  && mkdir -p /var/cache/puppeteer \
  && chmod -R a+rx /var/cache/puppeteer
```

#### 3. Install Chromium / Chrome → set in config

```dockerfile
RUN npx puppeteer browsers install chrome \
  && apt-get update && apt-get install -y --no-install-recommends \
    libasound2t64 libatk1.0-0 libatk-bridge2.0-0 libcups2 libgbm1 \
    libnss3 libnspr4 libx11-6 libxcb1 libxcomposite1 libxdamage1 \
    libxrandr2 fonts-liberation ca-certificates \
  && rm -rf /var/lib/apt/lists/*
```

Inside a running container, print Chrome path:

```bash
docker compose exec php node -e '(async()=>{const p=require("puppeteer"); console.log(await p.executablePath());})()'
```

```php
<?php

return [
    'driver' => 'browsershot',
    'tagged' => true,
    'browsershotNodeBinary' => '/usr/bin/node',
    'browsershotNpmBinary' => '/usr/bin/npm',
    'browsershotChromePath' => '/var/cache/puppeteer/chrome/.../chrome', // paste from executablePath()
    'browsershotNoSandbox' => true,
];
```

Use the same image for queue workers so Browsershot jobs do not fail in a slim CLI container.

---

## Troubleshooting

| Error | Fix |
|---|---|
| `Cannot find module 'puppeteer'` | Install Puppeteer with the **same** Node as `browsershotNodeBinary` (project or global). |
| `Could not find Chrome` | Run `npx puppeteer browsers install chrome` and set `browsershotChromePath` from `executablePath()`. |
| Works in Terminal, fails in browser | php-fpm PATH/user differs — set absolute Node/npm/Chrome paths in config. |
| `No usable sandbox` | `'browsershotNoSandbox' => true` |
| Encryption ignored | DomPDF-only; expected with Browsershot |

Upstream: [Spatie Browsershot requirements](https://spatie.be/docs/browsershot/v4/requirements).
