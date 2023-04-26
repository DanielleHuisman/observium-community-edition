#!/bin/bash
set -e

. ./.github/scripts/check_update.sh

echo "Going to update Observium from $LATEST_GIT_VERSION to $LATEST_OBSERVIUM_VERSION."

mkdir -p .new

echo "Extracting Observium archive..."
wget https://www.observium.org/observium-community-latest.tar.gz -O - -q \
    | tar -xzf - -C .new

echo "Updating files..."
rsync -ra --exclude logs --exclude rrd --exclude .new --exclude .git* --delete-after .new/observium/ .

echo "Deleting archive..."
rm -rf .new

echo "Update complete."
