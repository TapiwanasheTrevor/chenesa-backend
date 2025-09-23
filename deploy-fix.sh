#!/bin/bash

echo "🔧 Fixing CORS and deployment issues..."

# Set the correct environment variables for production
echo "Setting production environment variables..."
fly secrets set APP_URL="https://chenesa-shy-grass-3201.fly.dev"
fly secrets set APP_NAME="Chenesa"
fly secrets set VITE_APP_NAME="Chenesa"

# Deploy the application
echo "📦 Deploying the application..."
fly deploy

echo "✅ Deployment complete!"
echo "🌐 Your app should be available at: https://chenesa-shy-grass-3201.fly.dev"
