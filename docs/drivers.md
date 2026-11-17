# Drivers

Super PDF can render PDFs with more than one engine. DomPDF is always available. Browsershot needs Node.js + Puppeteer + Chrome on the machine that runs PHP.

| Driver | Setting | Tagged / accessible PDFs | What Composer installs | What you install manually |
|---|---|---|---|---|
| **DomPDF** (default) | `driver: 'dompdf'` | No | `dompdf/dompdf` | Nothing extra |
| **Browsershot** | `driver: 'browsershot'` | Yes (`tagged: true`) | `spatie/browsershot` | Node.js, Puppeteer (npm), Chrome/Chromium |

Front-end Twig stays the same. Only the renderer changes:

```twig
{{ craft.superpdf.html(html, {
    filename: 'accessible-doc',
    driver: 'browsershot',
    tagged: true
}) }}
```

Or set defaults in [config](config.md) / the CP (**PDF Driver** + **Tagged (accessible) PDF**).

---

## DomPDF

No server setup beyond the plugin. Use this unless you need tagged/PDF/UA output.

Limitations relevant to accessibility:

- No structure tree → Acrobat shows **Tagged PDF: No**
- No catalog `/Lang` from `<html lang="…">`
- Semantic HTML is used for layout only

Encryption, DomPDF font dirs, and `{TP}` / `SUPER_PDF_TOTAL_PAGES` work as before.

---

## Browsershot (tagged PDFs)

Composer already pulls in `spatie/browsershot`. That PHP package shells out to Node, which must be able to `require('puppeteer')` and launch Chrome.

If you see:

```text
Error: Cannot find module 'puppeteer'
```

Puppeteer is not installed for the Node process Craft uses. Follow the environment section below.

### Quick check (any environment)

Run these **on the same host that runs `php` / nginx / `ddev` PHP** (not only your laptop terminal if PHP is inside a container):

```bash
node -v          # ideally Node 20+ (Spatie recommends 22+ for current Browsershot)
npm -v
which node
which npm
npm root -g      # where global modules live
node -e "require('puppeteer'); console.log('puppeteer ok')"
```

If the last command fails, install Puppeteer using one of the guides below, then re-check.

### Wire paths in `config/super-pdf.php`

After Node/Chrome work from the CLI, point Super PDF at them if the PHP process cannot find binaries on `$PATH`:

```php
<?php

use craft\helpers\App;

return [
    'driver' => 'browsershot',
    'tagged' => true,

    // Only set these when `which node` / Chrome differ for the PHP user
    // 'browsershotNodeBinary' => '/usr/local/bin/node',       // Intel Homebrew / Linux
    // 'browsershotNodeBinary' => '/opt/homebrew/bin/node',    // Apple Silicon Homebrew
    // 'browsershotNpmBinary' => '/opt/homebrew/bin/npm',
    // 'browsershotChromePath' => '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
    // 'browsershotChromePath' => '/usr/bin/google-chrome-stable',

    // Docker / DDEV / many Linux AppArmor setups need this:
    // 'browsershotNoSandbox' => true,

    // 'browsershotTimeout' => 60,
];
```

Full key list: [Config reference](config.md).

### Smoke test

```bash
php craft super-pdf/generate \
  --html='<!doctype html><html lang="en"><body><h1>Hi</h1><p>Tagged test</p></body></html>' \
  --filename=browsershot-smoke \
  --type=object \
  --driver=browsershot \
  --tagged=1
```

Open the written PDF in Acrobat → **File → Properties**: **Tagged PDF** should be **Yes** when `tagged` is enabled.

---

## macOS — direct (nginx / Herd / Valet / php-fpm on the Mac)

Use this when Craft’s PHP runs on the Mac itself (not inside Docker/DDEV).

### 1. Install Node.js

**Homebrew (recommended):**

```bash
brew install node
node -v && npm -v
```

**Or** install the LTS pkg from [nodejs.org](https://nodejs.org/).

Apple Silicon Homebrew binaries usually live under `/opt/homebrew/bin/`. Intel often uses `/usr/local/bin/`.

### 2. Install Puppeteer (project-local, recommended)

From your **Craft project root** (the folder that contains `craft` and `composer.json`):

```bash
cd /path/to/your-craft-project

# If you do not already have a package.json:
npm init -y

npm install puppeteer
npx puppeteer browsers install chrome
```

Verify:

```bash
node -e "require('puppeteer'); console.log('puppeteer ok')"
```

**Alternative — global install** (matches Spatie’s docs; PHP must see the same Node):

```bash
npm install -g puppeteer
npx puppeteer browsers install chrome
node -e "require('puppeteer'); console.log('puppeteer ok')"
```

Browsershot sets `NODE_PATH=\`npm root -g\`` when calling Node. Global installs are picked up that way; project-local installs resolve from the process cwd / Node module paths. If PHP’s working directory is `web/`, prefer a **global** Puppeteer install **or** keep a project-level `node_modules` and ensure Node can resolve it (see troubleshooting).

Practical recommendation for Craft:

1. Prefer **global** `npm install -g puppeteer` on macOS/nginx so `NODE_PATH` from Browsershot finds it regardless of cwd.
2. Or install in the project root **and** also run `npm install -g puppeteer` once for the PHP/nginx user.

### 3. Config (only if needed)

```php
return [
    'driver' => 'browsershot',
    'tagged' => true,
    // Apple Silicon example:
    // 'browsershotNodeBinary' => '/opt/homebrew/bin/node',
    // 'browsershotNpmBinary' => '/opt/homebrew/bin/npm',
];
```

Herd/Valet usually inherit your shell PATH for CLI `php craft …`. For **nginx + php-fpm**, the FPM pool may have a minimal PATH — set `browsershotNodeBinary` / `browsershotNpmBinary` explicitly.

### 4. Confirm nginx/php-fpm can see Node

```bash
# As the PHP user if you use one, e.g. _www or the Herd PHP binary:
php -r 'echo shell_exec("node -v");'
php -r 'echo shell_exec("npm root -g");'
```

If those are empty under PHP but work in Terminal, set the binary paths in `config/super-pdf.php`.

---

## Ubuntu — direct nginx + php-fpm

Tested pattern follows [Spatie Browsershot requirements](https://spatie.be/docs/browsershot/v4/requirements) (Forge-style Ubuntu).

### 1. Node.js

```bash
# Example: Node 22 via NodeSource (adjust major version as needed)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt-get install -y nodejs
node -v && npm -v
```

Or use your distro’s `nodejs` / `npm` packages if they meet Node 20+.

### 2. Puppeteer + Chrome

```bash
sudo npm install -g puppeteer
sudo npx puppeteer browsers install chrome
```

### 3. Chrome shared libraries

**Ubuntu 24.04:**

```bash
sudo apt update
sudo apt install -y \
  libasound2t64 libatk1.0-0 libatk-bridge2.0-0 libcairo2 libcups2 \
  libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libgcc-s1 libglib2.0-0 \
  libgtk-3-0 libnspr4 libnss3 libpango-1.0-0 libpangocairo-1.0-0 \
  libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 \
  libxdamage1 libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 \
  libxss1 libxtst6 ca-certificates fonts-liberation
```

**Ubuntu 22.04:** use `libasound2` instead of `libasound2t64`.

### 4. AppArmor / sandbox (Ubuntu 23.10+)

If Chrome errors with *No usable sandbox* / AppArmor user-namespace restrictions:

```bash
# /etc/sysctl.d/99-browsershot.conf
echo 'kernel.apparmor_restrict_unprivileged_userns=0' | sudo tee /etc/sysctl.d/99-browsershot.conf
echo 'kernel.apparmor_restrict_unprivileged_unconfined=0' | sudo tee -a /etc/sysctl.d/99-browsershot.conf
sudo sysctl --system
```

And/or in Craft:

```php
'browsershotNoSandbox' => true,
```

### 5. Config for php-fpm

```php
return [
    'driver' => 'browsershot',
    'tagged' => true,
    'browsershotNodeBinary' => '/usr/bin/node',
    'browsershotNpmBinary' => '/usr/bin/npm',
    // 'browsershotNoSandbox' => true,
];
```

Ensure the `www-data` (or php-fpm) user can execute Node and read the Puppeteer Chrome cache (often under `/root/.cache/puppeteer` when installed with `sudo` — that can break FPM). Prefer installing Puppeteer as the deploy user, or set a shared cache:

```bash
# Install as the deploy / php user instead of root when possible
sudo -u www-data npm install -g puppeteer
sudo -u www-data npx puppeteer browsers install chrome
```

Or set `PUPPETEER_CACHE_DIR` to a directory readable by php-fpm and reinstall Chrome there.

### 6. Reload PHP

```bash
sudo systemctl reload php8.3-fpm   # adjust version
# or
sudo systemctl reload nginx
```

---

## DDEV

PHP and Node usually run **inside** the DDEV web container. Install Puppeteer there, not only on the host Mac/Linux.

### 1. Confirm Node inside DDEV

```bash
ddev exec node -v
ddev exec npm -v
```

DDEV’s web image includes Node. If missing, add a [Node.js build](https://ddev.readthedocs.io/en/stable/users/extend/customization-extendibility/) or use `ddev nvm` / an addon.

### 2. Install Puppeteer inside the project (persists on the mount)

```bash
ddev exec bash -c 'cd /var/www/html && npm init -y && npm install puppeteer'
ddev exec bash -c 'cd /var/www/html && npx puppeteer browsers install chrome'
ddev exec bash -c 'cd /var/www/html && node -e "require(\"puppeteer\"); console.log(\"puppeteer ok\")"'
```

Add `node_modules/` and Puppeteer’s browser cache to `.gitignore` if they grow large (or use a global install inside the container via a custom Dockerfile — see Docker section).

### 3. Linux deps + no-sandbox

Chromium inside DDEV often needs sandbox disabled:

`config/super-pdf.php`:

```php
return [
    'driver' => 'browsershot',
    'tagged' => true,
    'browsershotNoSandbox' => true,
];
```

If Chrome fails on missing `.so` libraries, add packages via `.ddev/config.yaml`:

```yaml
webimage_extra_packages:
  - libnss3
  - libatk-bridge2.0-0
  - libcups2
  - libdrm2
  - libxkbcommon0
  - libxcomposite1
  - libxdamage1
  - libxfixes3
  - libxrandr2
  - libgbm1
  - libasound2t64
  - fonts-liberation
```

Then:

```bash
ddev restart
```

(On older Ubuntu-based images use `libasound2` instead of `libasound2t64`.)

### 4. Smoke test inside DDEV

```bash
ddev exec php craft super-pdf/generate \
  --html='<!doctype html><html lang="en"><body><h1>Hi</h1></body></html>' \
  --filename=browsershot-ddev \
  --type=object \
  --driver=browsershot \
  --tagged=1
```

---

## Docker (generic Craft image / production container)

Goal: bake Node + Puppeteer + Chrome into the **same image** that runs `php-fpm` / the Craft app, so workers and web share one environment.

### Dockerfile sketch

```dockerfile
# After your PHP base image…
USER root
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl ca-certificates gnupg \
    libasound2t64 libatk1.0-0 libatk-bridge2.0-0 libcairo2 libcups2 \
    libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libglib2.0-0 \
    libgtk-3-0 libnspr4 libnss3 libpango-1.0-0 libx11-6 libxcb1 \
    libxcomposite1 libxdamage1 libxext6 libxfixes3 libxrandr2 \
    fonts-liberation \
  && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
  && apt-get install -y nodejs \
  && npm install -g puppeteer \
  && npx puppeteer browsers install chrome \
  && rm -rf /var/lib/apt/lists/*

ENV PUPPETEER_CACHE_DIR=/var/cache/puppeteer
# Ensure the PHP user can read the cache; chown as needed

USER www-data
```

Persist `PUPPETEER_CACHE_DIR` on the image (not a disposable tmpfs home directory).

### Craft config in containers

```php
return [
    'driver' => 'browsershot',
    'tagged' => true,
    'browsershotNodeBinary' => '/usr/bin/node',
    'browsershotNpmBinary' => '/usr/bin/npm',
    'browsershotNoSandbox' => true,
];
```

### Compose tip

Run queue workers from the **same** image/service definition as PHP so they also have Puppeteer. A slim CLI image without Node will fail Browsershot jobs.

---

## Choosing an install style

| Environment | Recommended Puppeteer install | Typical config flags |
|---|---|---|
| macOS + Herd/Valet/nginx | `npm install -g puppeteer` + `npx puppeteer browsers install chrome` | Node paths if FPM PATH is minimal |
| Ubuntu + nginx | Global puppeteer **as the php-fpm user**, Chrome libs via apt | `browsershotNoSandbox` / sysctl if AppArmor blocks |
| DDEV | `ddev exec` project `npm install puppeteer` | `browsershotNoSandbox: true` + extra packages |
| Docker | Global puppeteer in Dockerfile + `PUPPETEER_CACHE_DIR` on the image | `browsershotNoSandbox: true` |

---

## Troubleshooting (Browsershot)

| Symptom | Likely fix |
|---|---|
| `Cannot find module 'puppeteer'` | Install Puppeteer globally or in the project; ensure the **PHP host** has it (`ddev exec` / container, not only the host Mac). |
| `Could not find Chrome` | Run `npx puppeteer browsers install chrome`; fix cache dir / permissions; set `browsershotChromePath` if using system Chrome. |
| `No usable sandbox` / AppArmor | `browsershotNoSandbox: true` and/or sysctl lines in the Ubuntu section. |
| Works in CLI, fails in browser | php-fpm PATH or user differs — set `browsershotNodeBinary` / `browsershotNpmBinary`; install Puppeteer for that user. |
| Queue job fails, web works | Worker container/image missing Node/Puppeteer — align images. |
| Encryption ignored | Expected — encryption is DomPDF-only. |

More general PDF issues: [Troubleshooting](troubleshooting.md).

Upstream reference: [Spatie Browsershot requirements](https://spatie.be/docs/browsershot/v4/requirements).
