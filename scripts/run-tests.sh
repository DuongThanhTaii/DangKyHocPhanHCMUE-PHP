#!/bin/bash
# Test Runner Script for DKHP PHP Backend
# This script manages the test environment and runs tests

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND_PHP="$PROJECT_ROOT/backend_php"

echo -e "${YELLOW}🧪 DKHP PHP Backend Test Runner${NC}"
echo "=================================="

# Parse arguments
MODE=${1:-"local"}  # "local" or "docker"
TEST_SUITE=${2:-""}  # Optional: specific test file or suite

case $MODE in
    "local")
        echo -e "${GREEN}Running tests locally (SQLite in-memory)...${NC}"
        cd "$BACKEND_PHP"
        
        # Run PHPUnit with SQLite in-memory (fast, no external deps)
        php vendor/bin/phpunit $TEST_SUITE --colors=always
        ;;
        
    "docker")
        echo -e "${GREEN}Running tests in Docker (PostgreSQL + Redis)...${NC}"
        cd "$PROJECT_ROOT"
        
        # Start test containers
        echo "Starting test containers..."
        docker compose -f docker-compose.test.yml up -d postgres-test redis-test
        
        # Wait for services to be ready
        echo "Waiting for PostgreSQL and Redis..."
        sleep 5
        
        # Copy testing env
        cp "$BACKEND_PHP/.env.testing.docker" "$BACKEND_PHP/.env.testing"
        
        # Run migrations in test DB
        echo "Running migrations..."
        docker compose -f docker-compose.test.yml run --rm backend-php \
            php artisan migrate:fresh --seed --force
        
        # Run tests
        echo "Running tests..."
        docker compose -f docker-compose.test.yml run --rm backend-php \
            php vendor/bin/phpunit $TEST_SUITE --colors=always
        
        # Cleanup
        echo "Cleaning up..."
        docker compose -f docker-compose.test.yml down
        ;;
        
    "unit")
        echo -e "${GREEN}Running Unit tests only (no DB required)...${NC}"
        cd "$BACKEND_PHP"
        php vendor/bin/phpunit tests/Unit --colors=always
        ;;
        
    "feature")
        echo -e "${GREEN}Running Feature tests only (requires DB)...${NC}"
        cd "$BACKEND_PHP"
        php vendor/bin/phpunit tests/Feature --colors=always
        ;;
        
    *)
        echo -e "${RED}Usage: $0 [local|docker|unit|feature] [test-suite]${NC}"
        echo ""
        echo "Modes:"
        echo "  local   - Run all tests with SQLite in-memory (default)"
        echo "  docker  - Run all tests with PostgreSQL + Redis in Docker"
        echo "  unit    - Run only Unit tests (no DB required)"
        echo "  feature - Run only Feature tests"
        echo ""
        echo "Examples:"
        echo "  $0 unit                           # Run unit tests"
        echo "  $0 local tests/Feature/AuthTest   # Run specific test file"
        exit 1
        ;;
esac

echo -e "${GREEN}✅ Tests completed!${NC}"
