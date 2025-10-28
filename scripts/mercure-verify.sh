#!/bin/bash
# Mercure Verification Script
# Checks that Mercure is properly configured and working

set -e

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║            Mercure Configuration Verification                  ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

SUCCESS=0
FAILED=0

check_passed() {
    echo "   ✅ $1"
    SUCCESS=$((SUCCESS + 1))
}

check_failed() {
    echo "   ❌ $1"
    FAILED=$((FAILED + 1))
}

# 1. Check Mercure container
echo "1. Checking Mercure container status..."
if docker-compose ps mercure | grep -q "Up"; then
    check_passed "Mercure container is running"
else
    check_failed "Mercure container is not running"
    echo "      Fix: docker-compose up -d mercure"
fi
echo ""

# 2. Check Mercure accessibility
echo "2. Checking Mercure HTTP accessibility..."
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:3000/.well-known/mercure)
if [ "$RESPONSE" = "400" ]; then
    check_passed "Mercure is accessible (HTTP 400 expected)"
else
    check_failed "Mercure returned HTTP $RESPONSE (expected 400)"
fi
echo ""

# 3. Check port binding
echo "3. Checking port binding..."
if docker-compose ps mercure | grep -q "3000->80"; then
    check_passed "Port 3000 is properly mapped"
else
    check_failed "Port 3000 is not mapped correctly"
fi
echo ""

# 4. Check environment variables
echo "4. Checking Symfony environment variables..."

MERCURE_URL=$(docker-compose exec -T main env 2>/dev/null | grep MERCURE_URL= | cut -d= -f2-)
if [ -n "$MERCURE_URL" ]; then
    check_passed "MERCURE_URL is set: $MERCURE_URL"
else
    check_failed "MERCURE_URL is not set"
fi

MERCURE_PUBLIC_URL=$(docker-compose exec -T main env 2>/dev/null | grep MERCURE_PUBLIC_URL= | cut -d= -f2-)
if [ -n "$MERCURE_PUBLIC_URL" ]; then
    check_passed "MERCURE_PUBLIC_URL is set: $MERCURE_PUBLIC_URL"
else
    check_failed "MERCURE_PUBLIC_URL is not set"
fi

MERCURE_JWT_SECRET=$(docker-compose exec -T main env 2>/dev/null | grep MERCURE_JWT_SECRET= | cut -d= -f2-)
if [ -n "$MERCURE_JWT_SECRET" ]; then
    check_passed "MERCURE_JWT_SECRET is set"
else
    check_failed "MERCURE_JWT_SECRET is not set"
fi
echo ""

# 5. Check Mercure bundle configuration
echo "5. Checking Mercure bundle configuration..."
if [ -f "config/packages/mercure.yaml" ]; then
    check_passed "Mercure bundle configuration file exists"
else
    check_failed "Mercure bundle configuration file not found"
fi
echo ""

# 6. Check event subscriber registration
echo "6. Checking event subscriber registration..."
if docker-compose exec -T main php bin/console debug:event-dispatcher 2>/dev/null | grep -q "BenchmarkProgressSubscriber"; then
    check_passed "BenchmarkProgressSubscriber is registered"

    # Count how many events it handles
    EVENT_COUNT=$(docker-compose exec -T main php bin/console debug:event-dispatcher 2>/dev/null | grep -c "BenchmarkProgressSubscriber" || echo "0")
    if [ "$EVENT_COUNT" -ge 3 ]; then
        check_passed "Handling $EVENT_COUNT events (expected 3)"
    else
        check_failed "Only handling $EVENT_COUNT events (expected 3)"
    fi
else
    check_failed "BenchmarkProgressSubscriber is not registered"
fi
echo ""

# 7. Check Mercure logs for errors
echo "7. Checking Mercure logs for recent errors..."
ERROR_COUNT=$(docker-compose logs --tail=50 mercure 2>/dev/null | grep -ic "error" 2>/dev/null || echo "0")
# Remove any whitespace and newlines
ERROR_COUNT=$(echo "$ERROR_COUNT" | tr -d '\n\r ' | head -c 10)
if [ -z "$ERROR_COUNT" ]; then
    ERROR_COUNT=0
fi
if [ "$ERROR_COUNT" -eq 0 ] 2>/dev/null; then
    check_passed "No errors in recent Mercure logs"
else
    check_failed "Found $ERROR_COUNT error(s) in recent Mercure logs"
    echo "      Run: docker-compose logs mercure"
fi
echo ""

# 8. Check CORS configuration
echo "8. Checking CORS configuration..."
if docker-compose config | grep -q "cors_origins"; then
    check_passed "CORS is configured in docker-compose.yml"
else
    check_failed "CORS configuration not found"
fi
echo ""

# Summary
echo "════════════════════════════════════════════════════════════════"
echo "Summary: $SUCCESS passed, $FAILED failed"
echo "════════════════════════════════════════════════════════════════"
echo ""

if [ "$FAILED" -eq 0 ]; then
    echo "🎉 All checks passed! Mercure is working correctly."
    echo ""
    echo "To test real-time events:"
    echo "  Terminal 1: ./scripts/mercure-listen.sh"
    echo "  Terminal 2: make run test=Loop iterations=5 version=php84"
    exit 0
else
    echo "⚠️  Some checks failed. Please fix the issues above."
    exit 1
fi
