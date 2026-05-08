# Deployment Guide

## Quick Start with Docker

### Local Development

```bash
# 1. Clone and setup
git clone <repo>
cd web_project_full

# 2. Copy environment files
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# 3. Update backend/.env with your Neon database URL
# NEON_DATABASE_URL=postgresql://user:pass@ep-xxx.neon.tech/dbname?sslmode=require

# 4. Run with Docker Compose
docker-compose up

# Frontend: http://localhost:3000
# Backend: http://localhost:8000
```

---

## Deploy Backend to Render.app (Recommended)

### Step 1: Prepare Repository

```bash
# Push to GitHub
git push origin main
```

### Step 2: Create Render Service

1. Go to [render.com](https://render.com)
2. Click "New" → "Web Service"
3. Connect GitHub repository
4. Select `backend` directory as root

### Step 3: Configure Render

| Setting           | Value                                             |
| ----------------- | ------------------------------------------------- |
| **Name**          | campus-cms-backend                                |
| **Region**        | Choose closest                                    |
| **Branch**        | main                                              |
| **Build Command** | `composer install --no-dev --optimize-autoloader` |
| **Start Command** | `php -S 0.0.0.0:$PORT`                            |

### Step 4: Add Environment Variables

In Render dashboard → Environment:

```
NEON_DATABASE_URL=postgresql://user:pass@ep-xxx.neon.tech/dbname?sslmode=require
APP_ENV=production
JWT_SECRET=<generate-strong-random-string>
CORS_ORIGINS=https://yourdomain.com,https://www.yourdomain.com
```

### Step 5: Deploy

- Click "Deploy"
- Your backend will be available at: `https://campus-cms-backend.onrender.com`

---

## Deploy Frontend to Vercel

### Step 1: Create Vercel Project

```bash
# Install Vercel CLI
npm install -g vercel

# Deploy
cd frontend
vercel
```

### Step 2: Configure Environment

In Vercel dashboard → Settings → Environment Variables:

```
VITE_API_URL=https://campus-cms-backend.onrender.com
VITE_APP_ENV=production
```

### Step 3: Redeploy

```bash
vercel --prod
```

---

## Deploy Frontend to Netlify

### Step 1: Create Site

1. Go to [netlify.com](https://netlify.com)
2. "Add new site" → "Import existing project"
3. Select GitHub repository
4. Set base directory: `frontend`

### Step 2: Build Settings

| Setting               | Value                                       |
| --------------------- | ------------------------------------------- |
| **Build Command**     | `npm run build` (or leave empty for static) |
| **Publish Directory** | `frontend/src` (or `frontend` if no build)  |

### Step 3: Environment Variables

```
VITE_API_URL=https://your-backend.onrender.com
```

---

## Using Docker with Deployed Backend

### For Docker Compose (Local)

```bash
docker-compose up
```

### For Kubernetes or Docker Swarm

```bash
# Build backend image
docker build -t campus-cms-backend:1.0 ./backend

# Run backend
docker run -d \
  -p 8000:8000 \
  -e NEON_DATABASE_URL="postgresql://..." \
  -e CORS_ORIGINS="https://yourdomain.com" \
  campus-cms-backend:1.0

# Run frontend
docker run -d \
  -p 3000:3000 \
  -e VITE_API_URL="http://backend-url" \
  -v $(pwd)/frontend:/app \
  node:18-alpine \
  sh -c "npm install && npm run dev"
```

---

## Frontend Configuration for Deployed Backend

### Option 1: Build-time (Recommended)

Set `VITE_API_URL` environment variable before build:

```bash
VITE_API_URL=https://backend.example.com npm run build
```

### Option 2: Runtime (No Rebuild)

Add to `frontend/index.html` before `<script type="module" src="./src/js/app.js">`:

```html
<script>
  window.ENV = {
    API_URL: "https://your-backend-domain.com",
  };
</script>
```

### Option 3: Browser Console (Development)

```javascript
localStorage.setItem("API_BASE_URL", "https://your-backend.com");
window.location.reload();
```

---

## CORS Configuration

### Backend CORS Settings (in .env)

```env
# Allow multiple origins
CORS_ORIGINS=https://yourdomain.com,https://www.yourdomain.com,https://app.yourdomain.com

# Allow all (development only!)
CORS_ORIGINS=*
```

### Production Best Practices

1. **Never use** `CORS_ORIGINS=*` in production
2. **Specify exact domains**: `https://yourdomain.com`
3. **Use HTTPS** for all domains
4. **Set CORS_ORIGINS before deploying**

---

## Troubleshooting

### "CORS error" in browser console

- Check `CORS_ORIGINS` environment variable in backend
- Ensure frontend URL matches exactly (including protocol and port)
- Example: Frontend at `https://app.com` needs `CORS_ORIGINS=https://app.com`

### "API endpoint not found"

- Verify `VITE_API_URL` or `window.ENV.API_URL` points to correct backend
- Check browser console Network tab for actual request URL
- Confirm backend is running and accessible

### Database connection error

- Verify `NEON_DATABASE_URL` format: `postgresql://user:pass@host/db?sslmode=require`
- Test locally: `psql "postgresql://user:pass@host/db?sslmode=require"`

---

## Final Checklist

- [ ] Backend `.env` has `NEON_DATABASE_URL`
- [ ] Backend `.env` has correct `CORS_ORIGINS`
- [ ] Frontend `.env` or `VITE_API_URL` points to deployed backend
- [ ] Database migrations applied (if needed)
- [ ] HTTPS enabled for all domains
- [ ] JWT_SECRET changed from default
- [ ] Test login with admin@campus.edu / password
- [ ] Test from actual frontend URL (not localhost)
