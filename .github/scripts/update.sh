#!/bin/bash
set -e

. ./.github/scripts/check_update.sh

echo "Going to update Observium from $LATEST_GIT_VERSION to $LATEST_OBSERVIUM_VERSION."

mkdir -p .new

echo "Extracting Observium archive..."
wget https://www.observium.org/observium-community-latest.tar.gz -O - -q \
    | tar -xzf - -C .new

echo "Copying files..."
cp -r .new/observium/* .

echo "Deleting archive..."
rm -rf .new

echo "Update complete."
