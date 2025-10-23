#!/usr/bin/env bash

# If not in the `dev-workspace` directory, change to it
if [[ ! $(pwd) =~ .*dev-workspace$ ]]; then
  cd dev-workspace
fi

set -a
source ../.env
set +a

echo "Setting up portable user permissions for $(whoami) (UID: $(id -u), GID: $(id -g))..."

# Create a group that matches the host user's group
HOST_GID=$(id -g)
HOST_GROUP=$(getent group $HOST_GID | cut -d: -f1)

echo "Host group: $HOST_GROUP (GID: $HOST_GID)"

# Set directory permissions to allow group write
echo "Setting directory permissions..."
chmod g+w ../src/ ../dist/ 2>/dev/null || true

# Fix ownership of any files that might be owned by root
echo "Fixing file ownership..."
sudo chown -R $(id -u):$(id -g) ../src/include.php ../src/Versions.php 2>/dev/null || true
sudo chown -R $(id -u):$(id -g) ../vendor/squizlabs/php_codesniffer/CodeSniffer.conf 2>/dev/null || true

# Add group write permissions to PHPCS config
chmod g+w ../vendor/squizlabs/php_codesniffer/CodeSniffer.conf 2>/dev/null || true

# Create a simple script to run the container with the correct user mapping
cat > scripts/run-with-user.sh << EOF
#!/usr/bin/env bash

# Run the container with user mapping
docker compose -f docker/compose.yaml run -e DROPBOX_ACCESS_TOKEN=\$DROPBOX_ACCESS_TOKEN --rm terminal "\$@"
EOF

chmod +x scripts/run-with-user.sh

echo "Setup completed! You can now run './run composer build' without permission issues."
echo ""
echo "For other users on this machine, they should run:"
echo "  cd dev-workspace"
echo "  bash ./scripts/portable-setup.sh"
