#!/bin/bash

# CI Check Script
# Spouští stejné kontroly jako GitHub Actions workflow

set -e

echo "=================================="
echo "Running CI checks locally"
echo "=================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# PHPStan
echo -e "${YELLOW}[1/2] Running PHPStan...${NC}"
if vendor/bin/phpstan analyse --memory-limit=256M; then
    echo -e "${GREEN}✓ PHPStan passed${NC}"
else
    echo -e "${RED}✗ PHPStan failed${NC}"
    exit 1
fi
echo ""

# Tests
echo -e "${YELLOW}[2/2] Running tests...${NC}"
if vendor/bin/phpunit --colors=always; then
    echo -e "${GREEN}✓ Tests passed${NC}"
else
    echo -e "${RED}✗ Tests failed${NC}"
    exit 1
fi
echo ""

echo -e "${GREEN}=================================="
echo "All checks passed!"
echo "==================================${NC}"
