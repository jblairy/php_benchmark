#!/bin/bash
# End-to-end test of Mercure real-time events

ITERATIONS=${1:-3}
TEST=${2:-"Loop"}
VERSION=${3:-"php84"}

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║              Mercure End-to-End Test                           ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "Configuration:"
echo "  Test: $TEST"
echo "  PHP Version: $VERSION"
echo "  Iterations: $ITERATIONS"
echo ""

# Create temp file for events
EVENTS_FILE="/tmp/mercure_test_$$"

echo "Starting event listener in background..."
timeout 60 curl -N "http://localhost:3000/.well-known/mercure?topic=benchmark/progress" > "$EVENTS_FILE" 2>&1 &
CURL_PID=$!

# Give curl time to connect
sleep 2

echo "Running benchmark..."
docker-compose exec -T main php bin/console benchmark:run \
    --test="$TEST" \
    --php-version="$VERSION" \
    --iterations="$ITERATIONS"

BENCHMARK_EXIT=$?

# Wait for events to be captured
echo "Waiting for events to be captured..."
sleep 3

# Kill curl
kill $CURL_PID 2>/dev/null

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "Results:"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Parse events
STARTED=$(grep -c '"benchmark.started"' "$EVENTS_FILE" 2>/dev/null || echo "0")
PROGRESS=$(grep -c '"benchmark.progress"' "$EVENTS_FILE" 2>/dev/null || echo "0")
COMPLETED=$(grep -c '"benchmark.completed"' "$EVENTS_FILE" 2>/dev/null || echo "0")
TOTAL_EVENTS=$(grep -c '^data:' "$EVENTS_FILE" 2>/dev/null || echo "0")

echo "Events received:"
echo "  📨 Total events: $TOTAL_EVENTS"
echo "  🚀 Started: $STARTED"
echo "  🔄 Progress: $PROGRESS"
echo "  ✅ Completed: $COMPLETED"
echo ""

# Expected counts
EXPECTED_STARTED=1
EXPECTED_PROGRESS=$ITERATIONS
EXPECTED_COMPLETED=1
EXPECTED_TOTAL=$((EXPECTED_STARTED + EXPECTED_PROGRESS + EXPECTED_COMPLETED))

echo "Expected:"
echo "  📨 Total events: $EXPECTED_TOTAL"
echo "  🚀 Started: $EXPECTED_STARTED"
echo "  🔄 Progress: $EXPECTED_PROGRESS"
echo "  ✅ Completed: $EXPECTED_COMPLETED"
echo ""

# Validation
ALL_PASSED=true

if [ "$BENCHMARK_EXIT" -ne 0 ]; then
    echo "❌ Benchmark execution failed (exit code: $BENCHMARK_EXIT)"
    ALL_PASSED=false
fi

if [ "$STARTED" -ne "$EXPECTED_STARTED" ]; then
    echo "❌ Started events mismatch (expected $EXPECTED_STARTED, got $STARTED)"
    ALL_PASSED=false
fi

if [ "$PROGRESS" -ne "$EXPECTED_PROGRESS" ]; then
    echo "❌ Progress events mismatch (expected $EXPECTED_PROGRESS, got $PROGRESS)"
    ALL_PASSED=false
fi

if [ "$COMPLETED" -ne "$EXPECTED_COMPLETED" ]; then
    echo "❌ Completed events mismatch (expected $EXPECTED_COMPLETED, got $COMPLETED)"
    ALL_PASSED=false
fi

echo ""
echo "════════════════════════════════════════════════════════════════"

if [ "$ALL_PASSED" = true ]; then
    echo "🎉 All tests passed! Mercure real-time events are working correctly."
    echo ""
    echo "Sample event:"
    grep -m 1 '^data:' "$EVENTS_FILE" | sed 's/^data: //' | python3 -m json.tool 2>/dev/null || \
        grep -m 1 '^data:' "$EVENTS_FILE"

    rm -f "$EVENTS_FILE"
    exit 0
else
    echo "❌ Some tests failed."
    echo ""
    echo "Debug information saved to: $EVENTS_FILE"
    echo "View with: cat $EVENTS_FILE"
    exit 1
fi
