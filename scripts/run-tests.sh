#!/usr/bin/env bash

# =============================================================================
# RACINE BY GANDA - Test Runner Script
# =============================================================================
# Cross-platform test execution script for local and CI environments
# Supports: Windows (Git Bash/WSL), Linux, macOS
#
# Usage:
#   ./scripts/run-tests.sh [options]
#
# Options:
#   --parallel          Run tests in parallel (faster, requires parallel extension)
#   --stop-on-failure   Stop on first test failure (fast-fail mode)
#   --profile           Show test execution timing breakdown
#   --coverage          Generate code coverage report (requires Xdebug)
#   --filter=PATTERN    Run only tests matching pattern
#   --help              Show this help message
# =============================================================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default options
PARALLEL=false
STOP_ON_FAILURE=false
PROFILE=false
COVERAGE=false
FILTER=""

# Parse command line arguments
for arg in "$@"; do
    case $arg in
        --parallel)
            PARALLEL=true
            shift
            ;;
        --stop-on-failure)
            STOP_ON_FAILURE=true
            shift
            ;;
        --profile)
            PROFILE=true
            shift
            ;;
        --coverage)
            COVERAGE=true
            shift
            ;;
        --filter=*)
            FILTER="${arg#*=}"
            shift
            ;;
        --help)
            grep "^#" "$0" | grep -v "#!/" | sed 's/^# //'
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $arg${NC}"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# =============================================================================
# Environment Validation
# =============================================================================

echo -e "${BLUE}=== RACINE Test Runner ===${NC}"
echo ""

# Check PHP
if ! command -v php &> /dev/null; then
    echo -e "${RED}✗ PHP not found${NC}"
    echo "Please install PHP 8.1 or higher"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo -e "${GREEN}✓ PHP $PHP_VERSION${NC}"

# Check Composer
if ! command -v composer &> /dev/null; then
    echo -e "${RED}✗ Composer not found${NC}"
    echo "Please install Composer"
    exit 1
fi

echo -e "${GREEN}✓ Composer installed${NC}"

# Check SQLite extension
if ! php -m | grep -q sqlite3; then
    echo -e "${YELLOW}⚠ SQLite extension not found${NC}"
    echo "Some tests may fail. Install php-sqlite3 for full compatibility."
fi

# =============================================================================
# Database Setup
# =============================================================================

echo ""
echo -e "${BLUE}=== Database Setup ===${NC}"

# Ensure .env.testing exists
if [ ! -f .env.testing ]; then
    echo -e "${YELLOW}⚠ .env.testing not found, creating from .env.example${NC}"
    cp .env.example .env.testing
    # Set testing database to SQLite
    sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env.testing 2>/dev/null || \
    sed -i '' 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env.testing
fi

echo -e "${GREEN}✓ Test environment configured${NC}"

# =============================================================================
# Build Test Command
# =============================================================================

TEST_CMD="php artisan test"

if [ "$PARALLEL" = true ]; then
    TEST_CMD="$TEST_CMD --parallel"
    echo -e "${BLUE}→ Parallel execution enabled${NC}"
fi

if [ "$STOP_ON_FAILURE" = true ]; then
    TEST_CMD="$TEST_CMD --stop-on-failure"
    echo -e "${BLUE}→ Stop-on-failure enabled${NC}"
fi

if [ "$PROFILE" = true ]; then
    TEST_CMD="$TEST_CMD --profile"
    echo -e "${BLUE}→ Profiling enabled${NC}"
fi

if [ "$COVERAGE" = true ]; then
    if ! php -m | grep -q xdebug; then
        echo -e "${RED}✗ Xdebug not found${NC}"
        echo "Code coverage requires Xdebug extension"
        exit 1
    fi
    TEST_CMD="$TEST_CMD --coverage"
    echo -e "${BLUE}→ Coverage enabled${NC}"
fi

if [ -n "$FILTER" ]; then
    TEST_CMD="$TEST_CMD --filter=$FILTER"
    echo -e "${BLUE}→ Filter: $FILTER${NC}"
fi

# =============================================================================
# Run Tests
# =============================================================================

echo ""
echo -e "${BLUE}=== Running Tests ===${NC}"
echo ""

START_TIME=$(date +%s)

# Run the tests
if eval "$TEST_CMD"; then
    EXIT_CODE=0
    echo ""
    echo -e "${GREEN}✓ All tests passed${NC}"
else
    EXIT_CODE=$?
    echo ""
    echo -e "${RED}✗ Tests failed${NC}"
fi

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

echo ""
echo -e "${BLUE}=== Summary ===${NC}"
echo "Duration: ${DURATION}s"
echo "Exit code: $EXIT_CODE"

exit $EXIT_CODE
