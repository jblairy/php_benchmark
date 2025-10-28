#!/bin/bash
# Listen to Mercure events with formatted output

TOPIC=${1:-"benchmark/progress"}
FORMAT=${2:-"pretty"}

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║              Listening to Mercure Events                       ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "Topic: $TOPIC"
echo "Format: $FORMAT"
echo "Press Ctrl+C to stop"
echo ""
echo "Waiting for events..."
echo "────────────────────────────────────────────────────────────────"
echo ""

if [ "$FORMAT" = "raw" ]; then
    # Raw SSE format
    curl -N "http://localhost:3000/.well-known/mercure?topic=${TOPIC}"
elif [ "$FORMAT" = "json" ]; then
    # JSON only (requires jq)
    if ! command -v jq &> /dev/null; then
        echo "Error: jq is required for JSON format"
        echo "Install: apt-get install jq"
        exit 1
    fi
    curl -N "http://localhost:3000/.well-known/mercure?topic=${TOPIC}" | \
        grep '^data:' | sed 's/^data: //' | jq .
elif [ "$FORMAT" = "pretty" ]; then
    # Pretty formatted output with timestamps and colors
    if command -v jq &> /dev/null; then
        curl -N "http://localhost:3000/.well-known/mercure?topic=${TOPIC}" | \
            while IFS= read -r line; do
                if [[ $line == id:* ]]; then
                    # Extract UUID
                    UUID=$(echo "$line" | sed 's/id: urn:uuid://')
                    echo -e "\n📨 Event ID: \033[0;36m$UUID\033[0m"
                elif [[ $line == data:* ]]; then
                    # Parse and pretty-print JSON
                    DATA=$(echo "$line" | sed 's/^data: //')
                    TYPE=$(echo "$DATA" | jq -r '.type')
                    TIMESTAMP=$(echo "$DATA" | jq -r '.timestamp')
                    TIME=$(date -d "@$TIMESTAMP" '+%H:%M:%S' 2>/dev/null || echo "$TIMESTAMP")

                    case "$TYPE" in
                        "benchmark.started")
                            BENCH=$(echo "$DATA" | jq -r '.benchmarkName')
                            PHP=$(echo "$DATA" | jq -r '.phpVersion')
                            ITER=$(echo "$DATA" | jq -r '.totalIterations')
                            echo -e "⏱️  \033[1;32m$TYPE\033[0m at $TIME"
                            echo -e "   Benchmark: $BENCH on $PHP ($ITER iterations)"
                            ;;
                        "benchmark.progress")
                            BENCH=$(echo "$DATA" | jq -r '.benchmarkName')
                            CURRENT=$(echo "$DATA" | jq -r '.currentIteration')
                            TOTAL=$(echo "$DATA" | jq -r '.totalIterations')
                            PROGRESS=$(echo "$DATA" | jq -r '.progress')
                            echo -e "🔄 \033[1;33m$TYPE\033[0m at $TIME"
                            echo -e "   $BENCH: $CURRENT/$TOTAL (\033[1m${PROGRESS}%\033[0m)"
                            ;;
                        "benchmark.completed")
                            BENCH=$(echo "$DATA" | jq -r '.benchmarkName')
                            PHP=$(echo "$DATA" | jq -r '.phpVersion')
                            echo -e "✅ \033[1;32m$TYPE\033[0m at $TIME"
                            echo -e "   $BENCH on $PHP finished!"
                            ;;
                        *)
                            echo -e "📬 $TYPE at $TIME"
                            echo "$DATA" | jq .
                            ;;
                    esac
                fi
            done
    else
        # Fallback without jq
        curl -N "http://localhost:3000/.well-known/mercure?topic=${TOPIC}" | \
            while IFS= read -r line; do
                if [[ $line == id:* ]]; then
                    echo ""
                    echo "[$(date '+%H:%M:%S')] $line"
                elif [[ $line == data:* ]]; then
                    echo "$line"
                fi
            done
    fi
elif [ "$FORMAT" = "stats" ]; then
    # Statistics mode: count events
    echo "Event Statistics (Ctrl+C to stop and show results)"
    echo ""

    STARTED=0
    PROGRESS=0
    COMPLETED=0

    curl -N "http://localhost:3000/.well-known/mercure?topic=${TOPIC}" | \
        while IFS= read -r line; do
            if [[ $line == data:* ]]; then
                TYPE=$(echo "$line" | sed 's/^data: //' | grep -o '"type":"[^"]*"' | cut -d'"' -f4)
                case "$TYPE" in
                    "benchmark.started")
                        STARTED=$((STARTED + 1))
                        ;;
                    "benchmark.progress")
                        PROGRESS=$((PROGRESS + 1))
                        ;;
                    "benchmark.completed")
                        COMPLETED=$((COMPLETED + 1))
                        ;;
                esac

                # Update display
                echo -ne "\rStarted: $STARTED | Progress: $PROGRESS | Completed: $COMPLETED"
            fi
        done
else
    echo "Unknown format: $FORMAT"
    echo "Available formats: raw, json, pretty, stats"
    exit 1
fi
