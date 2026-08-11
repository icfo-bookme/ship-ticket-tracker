# 🚀 CI/CD Deployment Guide

This project uses **GitHub Actions** to automatically **deploy** whenever you
push to the `main` branch of
[`icfo-bookme/ship-ticket-tracker`](https://github.com/icfo-bookme/ship-ticket-tracker).

- Deploy job: installs deps, builds assets, uploads a release to your server
  (`187.77.128.105`) via SSH and does a **zero-downtime release-based deploy**
  (runs migrations + caches automatically).

---

## 1. Add GitHub Secrets (one time)

Go to your repo → **Settings → Secrets and variables → Actions → New repository secret**
and add each of these exactly as shown:

| Secret name       | Value / example                       | What it is                                                    |
|-------------------|---------------------------------------|---------------------------------------------------------------|
| `SSH_HOST`        | `187.77.128.105`                      | Your server IP                                                |
| `SSH_PORT`        | `22`                                  | SSH port (change if custom)                                   |
| `SSH_USERNAME`    | `ubuntu` (or `root`)                  | The user GitHub uses to log in                                |
| `SSH_PASSWORD`    | the user's SSH password               | **SSH (server) password** of the user above                   |
| `DEPLOY_PATH`     | `/var/www/ship-ticket-tracker`        | Where the app lives on the server (see section 3)             |

> ⚠️ The deploy uses **SSH password authentication** (no SSH keys needed).
> Make sure password login is enabled on the server (see section 2).

---

## 2. Server: enable SSH password login (one time)

The pipeline logs in with a **password** (no key pair needed). Check that password
login is enabled on the server (`187.77.128.105`) — on most hosting setups it
already is:

```bash
# On your server, as root/sudo, check this line in /etc/ssh/sshd_config:
PasswordAuthentication yes
# If it says "no", change it to "yes", then restart:
sudo systemctl restart sshd   # or: sudo service ssh restart
```

> 🔒 Security note: with password login enabled, use a **strong** password for the
> `SSH_USERNAME` user, and consider restricting which IPs can reach SSH in your
> firewall. For better security you can later switch to SSH keys
> (`SSH_PRIVATE_KEY` + `SSH_PUBLIC_KEY` secrets) without changing the pipeline — the
> workflow supports both; just swap the secret names.

---

## 3. Directory layout & web server

The pipeline builds this structure on the server inside `DEPLOY_PATH`:

```
/var/www/ship-ticket-tracker
├── releases/
│   └── 20260131120000/    <- each deploy is a new timestamped folder
├── shared/
│   ├── .env               <- your real credentials (survive every deploy)
│   └── storage/           <- logs, cache, uploaded files (survive every deploy)
└── current -> releases/20260131120000   <- symlink, always the live release
```

### Create the directory & grant permissions
```bash
sudo mkdir -p /var/www/ship-ticket-tracker
sudo chown -R deploy:deploy /var/www/ship-ticket-tracker
```

### Point your web server at `current/public`
After the **first** deploy has run, configure Nginx/Apache to serve:

```
server_root = /var/www/ship-ticket-tracker/current/public
```

Nginx example:

```nginx
server {
    listen 80;
    server_name your.domain.com;
    root /var/www/ship-ticket-tracker/current/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

> It is **intentional** that the document root is `current/public` — that is why the
> `current` symlink is flipped during each deploy, giving you zero downtime.

---

## 4. The `.env` file on the server

On the **first** deploy, the pipeline copies `.env.example` to
`DEPLOY_PATH/shared/.env`. After that **you must edit it** with your real values:

```bash
sudo nano /var/www/ship-ticket-tracker/shared/.env
```

At minimum set:

```ini
APP_KEY=base64:...            # generate one: php artisan key:generate
APP_ENV=production
APP_DEBUG=false
APP_URL=http://your-domain-or-ip

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ticket_tracker
DB_USERNAME=laravel_user
DB_PASSWORD=your-db-password
```

Then rebuild the config cache from the live release:

```bash
cd /var/www/ship-ticket-tracker/current
php artisan config:cache
```

---

## 5. How it behaves

- **Push to `main`** → workflow runs automatically.
- **Manual run** → open **Actions → CI/CD Deploy → Run workflow**.
- Deploys are **concurrency-safe**: a new push cancels a still-running older deploy.
- Only the latest **5** releases are kept; old ones are auto-cleaned.
- Migrations run automatically (`php artisan migrate --force`).

---

## 6. Troubleshooting

| Problem | Fix |
|---------|-----|
| `Permission denied (publickey)` / `Authentication failed` | Wrong `SSH_PASSWORD` or the wrong `SSH_USERNAME`. Also ensure `PasswordAuthentication yes` in `/etc/ssh/sshd_config` on the server. |
| `DEPLOY_PATH` not writable | `sudo chown -R deploy:deploy /var/www/ship-ticket-tracker` (use your `SSH_USERNAME`). |
| First deploy succeeded but site is blank | Edit `shared/.env` (APP_KEY / DB credentials), then `php artisan config:cache` in `current`. |
| Route/optimize errors with closures | `route:cache`/`optimize` are intentionally not run (the app uses route closures). `config:cache` + `view:cache` are enough. |
| Path not found `ship-deploy.tar.gz` | Check the SCP `source`/`target`; ensure `/tmp/ship-deploy` is writable by your SSH user. |
