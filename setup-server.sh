#!/bin/bash
# Run this once on your Cloudways server after first deploy
# Usage: ssh user@server 'bash -s' < setup-server.sh

DEPLOY_PATH="${1:-/home/master/applications/YOUR_APP_FOLDER/public_html/recomp}"

echo "Setting up Body Recomp Tracker at: $DEPLOY_PATH"

# Create data directory with restricted permissions
mkdir -p "$DEPLOY_PATH/data"
chmod 700 "$DEPLOY_PATH/data"

# Ensure PHP can write to data directory
# (Cloudways runs PHP as the application user, so this should work)
echo "data/ directory created with restricted permissions"

# Test the API
if command -v curl &> /dev/null; then
    echo ""
    echo "Testing API..."
    RESPONSE=$(curl -s "$DEPLOY_PATH/../recomp/api.php?action=load" 2>/dev/null || echo "Could not test — run manually via browser")
    echo "Response: $RESPONSE"
fi

echo ""
echo "Next steps:"
echo "1. Open api.php and note the API_TOKEN value"
echo "2. Open index.html and set CONFIG.TOKEN to the same value"
echo "3. Commit and push — GitHub Actions will deploy the change"
echo "4. Visit your site and complete setup"
