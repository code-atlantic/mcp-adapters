#!/bin/bash
# Reset Git Updater fields for testing EDD Release Manager webhooks
# Usage: ./bin/reset-git-fields.sh <download_id>

if [ -z "$1" ]; then
    echo "Usage: $0 <download_id>"
    echo "Example: $0 483326"
    exit 1
fi

DOWNLOAD_ID=$1

echo "🔄 Resetting Git Updater fields for download ID: $DOWNLOAD_ID"
echo ""

# Reset Git Updater toggle
echo "Disabling Git Updater..."
~/wpcli-wppopupmaker.sh "wp post meta update $DOWNLOAD_ID _edd_download_use_git 0"

# Reset Git repository fields
echo "Clearing Git repository fields..."
~/wpcli-wppopupmaker.sh "wp post meta delete $DOWNLOAD_ID _edd_git_repo_owner"
~/wpcli-wppopupmaker.sh "wp post meta delete $DOWNLOAD_ID _edd_git_repo_name"
~/wpcli-wppopupmaker.sh "wp post meta delete $DOWNLOAD_ID _edd_git_repo_tag"
~/wpcli-wppopupmaker.sh "wp post meta delete $DOWNLOAD_ID _edd_git_selected_release_file"

# Clear download files array
echo "Clearing download files..."
~/wpcli-wppopupmaker.sh "wp post meta delete $DOWNLOAD_ID edd_download_files"

# Reset version to trigger update
echo "Resetting version to 1.0.0..."
~/wpcli-wppopupmaker.sh "wp post meta update $DOWNLOAD_ID _edd_sl_version 1.0.0"
~/wpcli-wppopupmaker.sh "wp post meta update $DOWNLOAD_ID _edd_readme_plugin_version 1.0.0"

echo ""
echo "✅ Reset complete! Ready for fresh webhook test."
echo ""
echo "Current status:"
~/wpcli-wppopupmaker.sh "wp post meta get $DOWNLOAD_ID _edd_download_use_git" 2>&1 | grep -E "^[01]" | sed 's/^/  _edd_download_use_git: /'
~/wpcli-wppopupmaker.sh "wp post meta get $DOWNLOAD_ID _edd_sl_version" 2>&1 | grep -E "^[0-9]" | sed 's/^/  _edd_sl_version: /'