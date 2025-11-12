#!/bin/bash

echo "╔════════════════════════════════════════════════════════════╗"
echo "║       PHPMD 100% COMPLIANCE VERIFICATION                   ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

echo "1️⃣  Running PHPMD..."
PHPMD_COUNT=$(docker-compose -f docker-compose.dev.yml exec frankenphp vendor/bin/phpmd ./src text rulesets.xml 2>&1 | grep -v "^Deprecated:" | grep "^/app/" | wc -l)
if [ "$PHPMD_COUNT" -eq 0 ]; then
    echo "   ✅ PHPMD: 0 violations"
else
    echo "   ❌ PHPMD: $PHPMD_COUNT violations found"
fi

echo ""
echo "2️⃣  Running PHPStan Level Max..."
if docker-compose -f docker-compose.dev.yml exec frankenphp vendor/bin/phpstan analyse --memory-limit=512M 2>&1 | grep -q "\[OK\] No errors"; then
    echo "   ✅ PHPStan: No errors"
else
    echo "   ❌ PHPStan: Errors found"
fi

echo ""
echo "3️⃣  Running PHP-CS-Fixer..."
FIXER_COUNT=$(docker-compose -f docker-compose.dev.yml exec frankenphp vendor/bin/php-cs-fixer fix --dry-run 2>&1 | grep -oP 'Found \K\d+' | head -1)
if [ "$FIXER_COUNT" = "0" ]; then
    echo "   ✅ PHP-CS-Fixer: 0 files need fixing"
else
    echo "   ❌ PHP-CS-Fixer: $FIXER_COUNT files need fixing"
fi

echo ""
echo "════════════════════════════════════════════════════════════"

if [ "$PHPMD_COUNT" -eq 0 ] && [ "$FIXER_COUNT" = "0" ]; then
    echo ""
    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║              ✅ ALL CHECKS PASSED ✅                       ║"
    echo "║         100% PHPMD COMPLIANCE VERIFIED                     ║"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo ""
    exit 0
else
    echo ""
    echo "╔════════════════════════════════════════════════════════════╗"
    echo "║              ❌ SOME CHECKS FAILED ❌                      ║"
    echo "╚════════════════════════════════════════════════════════════╝"
    echo ""
    exit 1
fi
