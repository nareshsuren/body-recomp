# Body Recomp Tracker

Personal body recomposition tracker PWA with coaching. PHP + SQLite backend, auto-deploys from GitHub to Cloudways.

## Architecture

```
GitHub repo → GitHub Actions (on push) → rsync → Cloudways server
                                                    ├── index.html (PWA)
                                                    ├── api.php (PHP + SQLite)
                                                    └── data/recomp.db (persistent)
```

## One-time setup

### 1. Cloudways: Enable SSH access

1. Log into Cloudways → your Application → **Access Details**
2. Note: **SSH Host**, **Username**, and **SSH Port** (usually 22)
3. Go to **Settings & Packages** or **SSH Access** and ensure SSH is enabled

### 2. Create an SSH key pair for deployment

Run locally:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/cloudways_deploy -N "" -C "github-actions-deploy"
```

This creates two files:
- `~/.ssh/cloudways_deploy` (private key — goes into GitHub)
- `~/.ssh/cloudways_deploy.pub` (public key — goes onto Cloudways server)

### 3. Add the public key to Cloudways

Option A (Cloudways dashboard): Go to your server → **SSH Public Keys** → paste the contents of `cloudways_deploy.pub`

Option B (manual): SSH into server with password, then:
```bash
mkdir -p ~/.ssh && chmod 700 ~/.ssh
echo "YOUR_PUBLIC_KEY_CONTENTS" >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

### 4. Add secrets to GitHub repo

Go to your repo → **Settings → Secrets and variables → Actions → New repository secret**

Add these 5 secrets:

| Secret name | Value |
|---|---|
| `SSH_HOST` | Your Cloudways server IP (from Access Details) |
| `SSH_PORT` | SSH port, usually `22` |
| `SSH_USER` | Your Cloudways SSH username |
| `SSH_PRIVATE_KEY` | Entire contents of `~/.ssh/cloudways_deploy` (the PRIVATE key, including BEGIN/END lines) |
| `REMOTE_PATH` | Server path, e.g. `/home/master/applications/xyz123/public_html/recomp/` (trailing slash required) |

To find REMOTE_PATH: SSH into server and run `cd ~/public_html && pwd`, then append `/recomp/`.

### 5. First deploy

```bash
git add . && git commit -m "Initial deploy" && git push
```

GitHub Actions will rsync the `src/` folder to your server. Check the Actions tab for status.

### 6. Server setup

After first deploy, SSH into your server:

```bash
cd /path/to/public_html/recomp
mkdir -p data && chmod 700 data
```

### 7. Set the API token

1. Open `src/api.php` → copy the `API_TOKEN` value
2. Open `src/index.html` → paste into `CONFIG.TOKEN`
3. Commit and push — auto-deploys

### 8. Install on your phone

1. Visit `https://your-domain.com/recomp/` in Chrome/Safari
2. Complete the setup screen
3. Add to Home Screen (Chrome menu or Safari share button)

## Daily use

Open the app → Log tab → enter weight → done.
Coach tab tells you what to do.

## Making changes

Edit files in `src/`, commit, push. GitHub Actions deploys automatically.
The `data/` directory (your database) is never touched during deploys.

## Backup

Download `data/recomp.db` from your server via SFTP. That's your entire dataset.
