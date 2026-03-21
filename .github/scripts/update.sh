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

# Taken from https://www.observium.org/observium_installscript.sh
echo "Patching includes/community.inc.php with stub functions"
cat > "includes/community.inc.php" << 'CMNT'
<?php

// stub functions for not exist features
function cache_groups() { return []; }
function get_type_groups($type = NULL, $check_permission = TRUE) { return []; }
function get_groups_by_type($type = NULL) { return []; }
function get_group_entities($group_ids, $entity_type = '') { return []; }
function get_group_entities_array($group_ids, $entity_type = '') { return []; }
function get_entity_group_names($entity_type, $entity_id) { return []; }

// EOF
CMNT

echo "Update complete."
