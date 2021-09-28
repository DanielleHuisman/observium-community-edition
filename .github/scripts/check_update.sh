#!/bin/bash

LATEST_OBSERVIUM_VERSION=$(
    wget https://www.observium.org/observium-community-latest.tar.gz -O - -q \
    | tar -xzf - --occurrence=1 --to-stdout observium/VERSION \
    | sed 's|Observium CE ||'
)

LATEST_GIT_VERSION=$(
    git describe --tags $(git rev-list --tags --max-count=1) \
    | cut -c 2-
)

echo "Latest version: $LATEST_OBSERVIUM_VERSION"
echo "Mirror latest version: $LATEST_GIT_VERSION"

if [ "$LATEST_OBSERVIUM_VERSION" != "$LATEST_GIT_VERSION" ]; then
    echo "Update required"
    exit 1
else
    echo "No update required"
    exit 0
fi
