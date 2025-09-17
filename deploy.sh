#!/bin/bash

echo "🚀 Starting deployment to Fly.io..."

# Check if fly CLI is installed
if ! command -v fly &> /dev/null; then
    echo "❌ Fly CLI is not installed. Please install it first:"
    echo "curl -L https://fly.io/install.sh | sh"
    exit 1
fi

# Check if app exists or needs to be created
if ! fly status &> /dev/null; then
    echo "📱 Creating new Fly app..."
    fly launch --no-deploy
else
    echo "✅ Fly app already exists"
fi

# Set secrets from .env file
echo "🔐 Setting environment secrets..."

# Extract values from .env
APP_KEY=$(grep "^APP_KEY=" .env | cut -d '=' -f2- | sed 's/^"//;s/"$//')

echo "Setting secrets..."
# Set the APP_KEY from .env
fly secrets set APP_KEY="$APP_KEY"

# For production database, you'll need to update these with actual production values
echo ""
echo "⚠️  For production database, please run these commands with your actual database credentials:"
echo "fly secrets set DB_CONNECTION=pgsql"
echo "fly secrets set DB_DATABASE=chenesa"
echo "fly secrets set DB_USERNAME=<your_production_db_user>"
echo "fly secrets set DB_PASSWORD=<your_production_db_password>"
echo "fly secrets set DB_HOST=<your_production_db_host>"
echo "fly secrets set DB_PORT=5432"

# Set other Laravel production settings
fly secrets set APP_ENV=production
fly secrets set APP_DEBUG=false
fly secrets set LOG_LEVEL=error

echo ""
echo "✅ Basic secrets have been set!"
echo ""
echo "📦 Now deploying the application..."
fly deploy