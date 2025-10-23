#!/bin/bash

# PublishPress Hub Plugin Remote Sync Script
# Watches for changes in the plugin directory and syncs to remote WordPress site via SSH

# Default base path (current directory if not specified)
BASE_PATH="."

# Parse command line arguments first to get base path
while [[ $# -gt 0 ]]; do
    case $1 in
        -b|--base-path)
            BASE_PATH="$2"
            shift 2
            ;;
        -h|--help)
            # We'll show help later after loading env vars
            HELP_REQUESTED=true
            shift
            ;;
        -o|--once)
            ONCE_MODE=true
            shift
            ;;
        -w|--watch)
            WATCH_MODE=true
            shift
            ;;
        -t|--test)
            TEST_SSH=true
            shift
            ;;
        *)
            # If it's not a recognized option and we haven't set BASE_PATH yet, treat it as base path
            if [ "$BASE_PATH" = "." ] && [ "${1:0:1}" != "-" ]; then
                BASE_PATH="$1"
            else
                echo "Unknown option: $1"
                HELP_REQUESTED=true
            fi
            shift
            ;;
    esac
done

# Load environment variables from the specified base path
echo -e "${BLUE}Looking for .env file in: $BASE_PATH${NC}"

if [ -f "$BASE_PATH/.env" ]; then
    set -a
    source "$BASE_PATH/.env"
    set +a
    echo -e "${BLUE}Loaded environment variables from $BASE_PATH/.env file${NC}"
elif [ -f "$BASE_PATH/../.env" ]; then
    set -a
    source "$BASE_PATH/../.env"
    set +a
    echo -e "${BLUE}Loaded environment variables from $BASE_PATH/../.env file${NC}"
else
    echo -e "${RED}Error: No .env file found in $BASE_PATH or $BASE_PATH/..${NC}"
    echo ""
    echo "Please create a .env file in the specified base path with the following variables:"
    echo "  REMOTE_SYNC_SOURCE_DIR"
    echo "  REMOTE_SYNC_REMOTE_HOST"
    echo "  REMOTE_SYNC_REMOTE_PORT"
    echo "  REMOTE_SYNC_REMOTE_TARGET_DIR"
    echo "  REMOTE_SYNC_SSH_KEY_PATH"
    echo ""
    echo "You can copy from the example file:"
    echo "  cp dev-workspace/sync-config.example $BASE_PATH/.env"
    echo "  # Then edit $BASE_PATH/.env with your actual values"
    echo ""
    echo "Usage: $0 [BASE_PATH] [OPTIONS]"
    echo "  BASE_PATH: Path to the directory containing the .env file (default: current directory)"
    echo "  Options: -h (help), -o (once), -w (watch), -t (test SSH)"
    exit 1
fi

# Colors for output (define after env loading to avoid conflicts)
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SOURCE_DIR=$REMOTE_SYNC_SOURCE_DIR
REMOTE_HOST=$REMOTE_SYNC_REMOTE_HOST
REMOTE_PORT=$REMOTE_SYNC_REMOTE_PORT
REMOTE_TARGET_DIR=$REMOTE_SYNC_REMOTE_TARGET_DIR
SSH_KEY_PATH=$REMOTE_SYNC_SSH_KEY_PATH
EXCLUDE_FILE=".rsync-filters-sync"

# Validate that all required environment variables are set
if [ -z "$SOURCE_DIR" ] || [ -z "$REMOTE_HOST" ] || [ -z "$REMOTE_PORT" ] || [ -z "$REMOTE_TARGET_DIR" ] || [ -z "$SSH_KEY_PATH" ]; then
    echo -e "${RED}Error: Missing required environment variables${NC}"
    echo "Please ensure all required variables are set in your .env file:"
    echo "  SOURCE_DIR: $SOURCE_DIR"
    echo "  REMOTE_HOST: $REMOTE_HOST"
    echo "  REMOTE_PORT: $REMOTE_PORT"
    echo "  REMOTE_TARGET_DIR: $REMOTE_TARGET_DIR"
    echo "  SSH_KEY_PATH: $SSH_KEY_PATH"
    exit 1
fi

# Detect operating system
if [[ "$OSTYPE" == "darwin"* ]]; then
    OS="macos"
elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
    OS="linux"
else
    OS="unknown"
fi

# SSH Configuration
SSH_OPTIONS="-o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o GlobalKnownHostsFile=/dev/null -o ServerAliveInterval=600 -o ServerAliveCountMax=5 -o LogLevel=ERROR"

# Function to sync files
sync_files() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')] Syncing files to remote...${NC}"

    # Create target directory on remote if it doesn't exist
    if ! ssh -i "$SSH_KEY_PATH" -p "$REMOTE_PORT" $SSH_OPTIONS "$REMOTE_HOST" "mkdir -p '$REMOTE_TARGET_DIR'"; then
        echo -e "${RED}Failed to create remote directory${NC}"
        return 1
    fi

    # Check if exclude file exists
    local exclude_option=""
    if [ -f "$SOURCE_DIR/$EXCLUDE_FILE" ]; then
        exclude_option="--exclude-from=$SOURCE_DIR/$EXCLUDE_FILE"
        echo -e "${BLUE}Using exclude file: $EXCLUDE_FILE${NC}"
    else
        echo -e "${YELLOW}Exclude file not found: $EXCLUDE_FILE${NC}"
    fi

    # Sync files using rsync over SSH
    if rsync -av --delete \
        -e "ssh -i $SSH_KEY_PATH -p $REMOTE_PORT $SSH_OPTIONS" \
        $exclude_option \
        "$SOURCE_DIR/" "$REMOTE_HOST:$REMOTE_TARGET_DIR/"; then

        echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')] Remote sync completed successfully${NC}"
        return 0
    else
        echo -e "${RED}[$(date '+%Y-%m-%d %H:%M:%S')] Remote sync failed${NC}"
        return 1
    fi
}

# Function to check if required tools are available
check_requirements() {
    echo -e "${BLUE}Checking system requirements...${NC}"

    # Check if rsync is available
    if ! command -v rsync &> /dev/null; then
        echo -e "${RED}Error: rsync is not installed or not in PATH${NC}"
        if [ "$OS" = "macos" ]; then
            echo "On macOS, rsync should be available by default"
            echo "If missing, install with: brew install rsync"
        else
            echo "Please install rsync for your system"
        fi
        exit 1
    fi

    # Check if ssh is available
    if ! command -v ssh &> /dev/null; then
        echo -e "${RED}Error: SSH is not available${NC}"
        echo "Please ensure OpenSSH is installed"
        exit 1
    fi

    echo -e "${GREEN}All required tools are available${NC}"
}

# Function to check if file watching tool is available
check_file_watcher() {
    echo -e "${BLUE}Detected OS: $OS${NC}"

    if [ "$OS" = "macos" ]; then
        # macOS: prefer fswatch
        if command -v fswatch &> /dev/null; then
            FILE_WATCHER="fswatch"
            echo -e "${GREEN}Using fswatch for macOS file watching${NC}"
            return 0
        else
            echo -e "${RED}Error: fswatch not found on macOS${NC}"
            echo "Please install it with: brew install fswatch"
            exit 1
        fi
    elif [ "$OS" = "linux" ]; then
        # Linux: prefer inotifywait
        if command -v inotifywait &> /dev/null; then
            FILE_WATCHER="inotifywait"
            echo -e "${GREEN}Using inotifywait for Linux file watching${NC}"
            return 0
        else
            echo -e "${RED}Error: inotifywait not found on Linux${NC}"
            echo "Please install it with: sudo apt-get install inotify-tools"
            exit 1
        fi
    else
        # Unknown OS: try both tools
        if command -v fswatch &> /dev/null; then
            FILE_WATCHER="fswatch"
            echo -e "${GREEN}Using fswatch for file watching${NC}"
            return 0
        elif command -v inotifywait &> /dev/null; then
            FILE_WATCHER="inotifywait"
            echo -e "${GREEN}Using inotifywait for file watching${NC}"
            return 0
        else
            echo -e "${RED}Error: No file watching tool available${NC}"
            echo "Please install one of the following:"
            echo "  - On macOS: brew install fswatch"
            echo "  - On Linux: sudo apt-get install inotify-tools"
            exit 1
        fi
    fi
}

# Function to test SSH connection
test_ssh_connection() {
    echo -e "${BLUE}Testing SSH connection to $REMOTE_HOST...${NC}"
    if ssh -i "$SSH_KEY_PATH" -p "$REMOTE_PORT" $SSH_OPTIONS "$REMOTE_HOST" "echo 'SSH connection successful'" 2>/dev/null; then
        echo -e "${GREEN}SSH connection test successful${NC}"
        return 0
    else
        echo -e "${RED}SSH connection test failed${NC}"
        echo "Please check your SSH configuration:"
        echo "  - SSH key path: $SSH_KEY_PATH"
        echo "  - Remote host: $REMOTE_HOST"
        echo "  - Remote port: $REMOTE_PORT"
        echo "  - Make sure your SSH key is added to the remote server"
        return 1
    fi
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [BASE_PATH] [OPTIONS]"
    echo ""
    echo "Arguments:"
    echo "  BASE_PATH              Path to the directory containing the .env file"
    echo "                         (default: current directory)"
    echo ""
    echo "Options:"
    echo "  -h, --help             Show this help message"
    echo "  -o, --once             Sync once and exit"
    echo "  -w, --watch            Watch for changes and sync continuously (default)"
    echo "  -t, --test             Test SSH connection and exit"
    echo "  -b, --base-path PATH   Alternative way to specify base path"
    echo ""
    echo "Examples:"
    echo "  $0                     # Use current directory, watch for changes"
    echo "  $0 /path/to/plugin     # Use /path/to/plugin as base path"
    echo "  $0 --base-path /path   # Alternative syntax for base path"
    echo "  $0 /path/to/plugin --once    # Sync once from specified path"
    echo "  $0 /path/to/plugin --test    # Test SSH from specified path"
    echo ""
    echo "Configuration:"
    echo "  Create a .env file in the specified base path with:"
    echo "  - REMOTE_SYNC_SOURCE_DIR: Local source directory"
    echo "  - REMOTE_SYNC_REMOTE_HOST: Remote SSH host (user@hostname)"
    echo "  - REMOTE_SYNC_REMOTE_PORT: SSH port (default: 22)"
    echo "  - REMOTE_SYNC_REMOTE_TARGET_DIR: Remote target directory"
    echo "  - REMOTE_SYNC_SSH_KEY_PATH: Path to SSH private key"
    echo ""
    echo "System Requirements:"
    echo "  - macOS: fswatch (brew install fswatch)"
    echo "  - Linux: inotify-tools (sudo apt-get install inotify-tools)"
    echo "  - Both: rsync, ssh, bash"
}

# Show help if requested
if [ "$HELP_REQUESTED" = true ]; then
    show_usage
    exit 0
fi

# Check if source directory exists
if [ ! -d "$SOURCE_DIR" ]; then
    echo -e "${RED}Error: Source directory does not exist: $SOURCE_DIR${NC}"
    exit 1
fi

# Check if SSH key exists
if [ ! -f "$SSH_KEY_PATH" ]; then
    echo -e "${RED}Error: SSH key not found: $SSH_KEY_PATH${NC}"
    echo "Please check the SSH_KEY_PATH variable in your .env file"
    echo "Current value: $SSH_KEY_PATH"
    exit 1
fi

# Test SSH connection if requested
if [ "$TEST_SSH" = true ]; then
    test_ssh_connection
    exit $?
fi

# Check if file watching tool is available
check_file_watcher

echo -e "${BLUE}PublishPress Hub Plugin Remote Sync Script${NC}"
echo -e "${BLUE}Base Path: $BASE_PATH${NC}"
echo -e "${BLUE}Source: $SOURCE_DIR${NC}"
echo -e "${BLUE}Remote: $REMOTE_HOST:$REMOTE_TARGET_DIR${NC}"
echo -e "${BLUE}SSH Key: $SSH_KEY_PATH${NC}"
echo ""

# Check system requirements
check_requirements

# Test SSH connection before starting
if ! test_ssh_connection; then
    exit 1
fi

# Initial sync
echo -e "${YELLOW}Performing initial sync...${NC}"
sync_files

if [ "$ONCE_MODE" = true ]; then
    echo -e "${GREEN}One-time sync completed. Exiting.${NC}"
    exit 0
fi

echo -e "${YELLOW}Watching for changes... (Press Ctrl+C to stop)${NC}"
echo -e "${BLUE}File watcher: $FILE_WATCHER${NC}"
echo ""

# Set up signal handling for graceful shutdown
trap 'echo -e "\n${YELLOW}Shutting down file watcher...${NC}"; exit 0' INT TERM

# Watch for changes
if [ "$FILE_WATCHER" = "fswatch" ]; then
    # macOS: fswatch approach with better event handling
    echo -e "${BLUE}Using fswatch for file watching (macOS)${NC}"
    echo -e "${BLUE}Watching directory: $SOURCE_DIR${NC}"

    # Use fswatch with more specific event types for better performance
    fswatch -r -e ".*" -i ".*" "$SOURCE_DIR" | while read file; do
        # Skip certain file types and directories
        if [[ "$file" =~ \.(git|node_modules|vendor|dev-workspace|tests|cursor|github|dist|log|env|babelrc|webpack|package|yarn|composer|makefile|codeception|phpcs|phplint|phpstan|phpmd|gitignore|gitattributes|gitmodules|distignore|rsync-filters|jsconfig|code-workspace|changelog|readme|license|mockups|docs|languages|lib) ]]; then
            continue
        fi

        # Skip if it's a directory we want to exclude
        if [[ "$file" =~ /(\.git|node_modules|vendor|dev-workspace|tests|\.cursor|\.github|dist|mockups|docs|languages|lib)/ ]]; then
            continue
        fi

        echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] Change detected: $file${NC}"
        if sync_files; then
            echo -e "${GREEN}Sync completed for change in: $file${NC}"
        else
            echo -e "${RED}Sync failed for change in: $file${NC}"
        fi
    done
else
    # Linux: inotifywait approach
    echo -e "${BLUE}Using inotifywait for file watching (Linux)${NC}"
    inotifywait -m -r -e modify,create,delete,move "$SOURCE_DIR" --format '%w%f %e' | while read file event; do
        # Skip certain file types and directories
        if [[ "$file" =~ \.(git|node_modules|vendor|dev-workspace|tests|cursor|github|dist|log|env|babelrc|webpack|package|yarn|composer|makefile|codeception|phpcs|phplint|phpstan|phpmd|gitignore|gitattributes|gitmodules|distignore|rsync-filters|jsconfig|code-workspace|changelog|readme|license|mockups|docs|languages|lib) ]]; then
            continue
        fi

        # Skip if it's a directory we want to exclude
        if [[ "$file" =~ /(\.git|node_modules|vendor|dev-workspace|tests|\.cursor|\.github|dist|mockups|docs|languages|lib)/ ]]; then
            continue
        fi

        echo -e "${YELLOW}[$(date '+%Y-%m-%d %H:%M:%S')] Change detected: $file ($event)${NC}"
        if sync_files; then
            echo -e "${GREEN}Sync completed for change in: $file${NC}"
        else
            echo -e "${RED}Sync failed for change in: $file${NC}"
        fi
    done
fi
