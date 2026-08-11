# 🚀 CI/CD Deployment Guide

This project uses **GitHub Actions** to automatically **deploy** whenever you
push to the `main` branch of
[`icfo-bookme/ship-ticket-tracker`](https://github.com/icfo-bookme/ship-ticket-tracker).

- On every push the workflow builds the app and **deploys it directly** into
  your server folder (e.g. `/var/www/ship-ticket-tracker`) via SSH.
- `.env` and `storage/` are **preserved** across deploys (never overwritten).
- Migrations + caches run automatically.

---

## 1. Add GitHub Secrets (one time)

Repo → **Settings → Secrets and variables → Actions → New repository secret**:

| Secret name       | Value / example                | What it is                             |
|-------------------|--------------------------------|----------------------------------------|
| `SSH_HOST`        | `187.77.128.105`               | Your server IP                         |
| `SSH_PORT`        | `22`                           | SSH port                               |
| `SSH_USERNAME`    | `ubuntu` / `root` / `deploy`   | The user used to log in via SSH        |
| `SSH_PASSWORD`    | the user's SSH password        | The SSH password of that user          |
| `DEPLOY_PATH`     | `/var/www/ship-ticket-tracker` | The folder on the server to deploy to  |

> Deploy uses **SSH password auth** (no SSH key needed). Make sure the server has
> `PasswordAuthentication yes` in `/etc/ssh/sshd_config`.

---

## 2. Server: one-time setup

### Enable SSH password login (if disabled)
```bash
sudo nano /etc/ssh/sshd_config
# set: PasswordAuthentication yes
sudo systemctl restart sshd   # or: sudo service ssh restart
```

### Create the deploy folder & give your user permission
```bash
sudo mkdir -p /var/www/ship-ticket-tracker
sudo chown -R ubuntu:ubuntu /var/www/ship-ticket-tracker   # use your SSH_USERNAME
```

That's it — the pipeline will deploy straight into this folder on every push.

---

## 3. What happens on the server during a deploy

The pipeline uploads the code and places it directly into `DEPLOY_PATH`
(`/var/www/ship-ticket-tracker`). This is a **direct deploy**:

```
/var/www/ship-ticket-tracker          <- your DEPLOY_PATH
├── .env                              <- your credentials (kept, never overwritten)
├── storage/                          <- logs/cache/uploads (kept, never overwritten)
├── app/  config/  routes/  public/ ...   <- fresh code from the repo
└── vendor/  public/build/            <- production deps + compiled assets
```

- Stale files from the previous deploy are removed.
- `.env` and `storage/` are copied forward into the new code, so nothing is lost.
- Then it runs:
  ```bash
  php artisan migrate --force
  php artisan config:cache
  php artisan view:cache
  php artisan storage:link
  php artisan queue:restart
  ```

### Point your web server at `DEPLOY_PATH/public`
```nginx
server {
    listen 80;
    server_name your.domain.com;
    root /var/www/ship-ticket-tracker/public;   # <- public folder, not the root

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

---

## 4. The `.env` file

On the **first** deploy, the pipeline copies `.env.example` to
`$DEPLOY_PATH/.env`. **After that you must edit it** with your real values:

```bash
sudo nano /var/www/ship-ticket-tracker/.env
```

At minimum set:

```ini
APP_KEY=base64:...            # php artisan key:generate
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

Then:
```bash
cd /var/www/ship-ticket-tracker
php artisan config:cache
```

> ⚠️ Without a valid `APP_KEY`, `migrate` / `config:cache` can fail. Make sure
> `.env` has one before the first deploy finishes its artisan steps.

---

## 5. How it behaves

- **Push to `main`** → deploy runs automatically.
- **Manual run** → **Actions → CI/CD Deploy → Run workflow**.
- Deploys are **concurrency-safe**: a new push cancels a still-running older one.
- `.env` and `storage/` survive every deploy.
- Migrations run automatically (`php artisan migrate --force`).

---

## 6. Troubleshooting

| Problem | Fix |
|---------|-----|
| `Permission denied` / `Authentication failed` | Wrong `SSH_PASSWORD` or `SSH_USERNAME`; or `PasswordAuthentication` is `no` in `/etc/ssh/sshd_config`. |
| `DEPLOY_PATH` not writable | `sudo chown -R <user>:<user> /var/www/ship-ticket-tracker` (your `SSH_USERNAME`). |
| Deploy succeeded but site is blank | Edit `.env` (APP_KEY / DB), then `php artisan config:cache` in `/var/www/ship-ticket-tracker`. |
| `tar: file changed as we read it` | Already fixed in the workflow (artifact folder is excluded from the tarball). |
| Migrations fail (`APP_KEY` missing) | Set `APP_KEY=base64:...` in `/var/www/ship-ticket-tracker/.env`. |
