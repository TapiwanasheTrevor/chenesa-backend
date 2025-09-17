# Deployment to Fly.io

This guide walks you through deploying the Chenesa Laravel application to Fly.io.

## Prerequisites

1. Install Fly CLI:
```bash
curl -L https://fly.io/install.sh | sh
```

2. Sign up/login to Fly.io:
```bash
fly auth login
```

## Database Setup

### Option 1: Fly Postgres (Recommended)
```bash
# Create a Postgres cluster
fly postgres create --name chenesa-db

# Attach to your app (after app creation)
fly postgres attach chenesa-db
```

### Option 2: External Database
Configure your database connection details in the secrets (see Environment Variables section).

## Deployment Steps

### 1. Initial Setup

```bash
# Initialize the app (first time only)
fly launch --no-deploy

# This will:
# - Create your Fly app
# - Set up the fly.toml configuration
# - Register your app name
```

### 2. Set Environment Variables

```bash
# Set your Laravel app key (get from .env)
fly secrets set APP_KEY="base64:your-app-key-here"

# Set JWT secret
fly secrets set JWT_SECRET="your-jwt-secret"

# If using external database, set these:
fly secrets set DB_HOST="your-db-host"
fly secrets set DB_DATABASE="chenesa"
fly secrets set DB_USERNAME="your-db-user"
fly secrets set DB_PASSWORD="your-db-password"
```

### 3. Deploy

```bash
# Deploy the application
fly deploy

# Check deployment status
fly status

# View logs
fly logs
```

### 4. Post-Deployment

```bash
# Run migrations (if not auto-run)
fly ssh console -C "php artisan migrate --force"

# Create admin user
fly ssh console -C "php artisan db:seed --class=AdminUserSeeder"
```

## Useful Commands

```bash
# SSH into the container
fly ssh console

# View app information
fly info

# Scale the app
fly scale vm shared-cpu-1x --memory 512

# Add more instances
fly scale count 2

# Monitor app
fly monitor
```

## Health Check

The application includes a health check endpoint at `/health` that verifies:
- Application is running
- Database connection is working

Fly.io will automatically use this endpoint to monitor app health.

## Troubleshooting

### Database Connection Issues
- Ensure DATABASE_URL is properly set if using Fly Postgres
- Check that DB_* environment variables are correct for external databases
- Verify network connectivity between app and database

### Storage Issues
- The application uses local storage by default
- For persistent storage across deployments, consider using Fly Volumes or S3

### Performance
- Monitor memory usage with `fly monitor`
- Scale up if needed with `fly scale vm`
- Enable opcache for better PHP performance (already configured in Dockerfile)

## Environment Variables Reference

| Variable | Description | Required |
|----------|-------------|----------|
| APP_KEY | Laravel encryption key | Yes |
| JWT_SECRET | JWT signing secret | Yes |
| DB_HOST | Database host | Yes (if external DB) |
| DB_DATABASE | Database name | Yes (if external DB) |
| DB_USERNAME | Database user | Yes (if external DB) |
| DB_PASSWORD | Database password | Yes (if external DB) |

## Quick Deploy Script

Use the included deploy script for quick deployment:

```bash
./deploy.sh
```

This script will guide you through the deployment process and show you the necessary commands to run.