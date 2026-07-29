#!/bin/bash
# Run this on the cPanel server to setup Git auto-deploy

echo "Setting up Git auto-deploy on cPanel..."

cd /home/scriptbd/public_html

# Initialize git
git init
git config user.email "deploy@scriptbd.com"
git config user.name "ScriptBD Deploy"

# Add all files
git add -A
git commit -m "Initial v4 deploy with auto-deploy system"

# Add remote (user needs to replace with their GitHub repo)
# git remote add origin https://github.com/YOUR_USERNAME/scriptbd.git
# git push -u origin main

echo "✅ Git setup complete!"
echo ""
echo "Next steps:"
echo "1. Create a GitHub repo: github.com/new"
echo "2. Run: git remote add origin https://github.com/YOUR_USERNAME/scriptbd.git"
echo "3. Run: git push -u origin main"
echo "4. Go to GitHub repo → Settings → Webhooks → Add webhook"
echo "5. Payload URL: https://scriptbd.com/git-deploy.php"
echo "6. Content type: application/json"
echo "7. Secret: scriptbd_webhook_secret_2026"
echo ""
echo "After setup, any git push will auto-deploy!"
