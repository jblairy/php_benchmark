import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

/**
 * Stimulus controller for real-time dashboard updates via Mercure
 * Shows notification banner when benchmarks complete, lets user decide when to refresh
 */
export default class extends Controller {
    static values = {
        url: String,
        topic: String
    };

    static targets = ['banner', 'bannerText'];

    eventSource = null;
    pendingUpdates = 0;
    reconnectAttempts = 0;
    maxReconnectAttempts = 5;
    reconnectDelay = 1000; // Start with 1 second

    connect() {
        console.log('🔌 Dashboard Mercure Controller connected');
        console.log('📡 Mercure URL:', this.urlValue);
        console.log('📢 Topic:', this.topicValue);

        this.subscribeToMercure();
    }

    disconnect() {
        console.log('🔌 Dashboard Mercure Controller disconnected');
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    }

    subscribeToMercure() {
        const url = new URL(this.urlValue);
        url.searchParams.append('topic', this.topicValue);

        console.log('🔗 Subscribing to:', url.toString());

        // Close existing connection if any
        if (this.eventSource) {
            this.eventSource.close();
        }

        this.eventSource = new EventSource(url);

        this.eventSource.onopen = () => {
            console.log('✅ Mercure connection established');
            // Reset reconnection attempts on successful connection
            this.reconnectAttempts = 0;
            this.reconnectDelay = 1000;
        };

        this.eventSource.onmessage = (event) => {
            console.log('📬 Mercure event received');
            const data = JSON.parse(event.data);
            this.handleBenchmarkEvent(data);
        };

        this.eventSource.onerror = (error) => {
            console.error('❌ Mercure connection error:', error);

            // Check if EventSource is closed
            if (this.eventSource.readyState === EventSource.CLOSED) {
                console.log('🔄 Connection closed, attempting to reconnect...');
                this.attemptReconnection();
            }
        };
    }

    attemptReconnection() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.error('❌ Max reconnection attempts reached. Please refresh the page.');
            return;
        }

        this.reconnectAttempts++;
        const delay = this.reconnectDelay * Math.pow(2, this.reconnectAttempts - 1); // Exponential backoff

        console.log(`⏳ Reconnecting in ${delay}ms (attempt ${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);

        setTimeout(() => {
            console.log('🔄 Reconnecting to Mercure...');
            this.subscribeToMercure();
        }, delay);
    }

    handleBenchmarkEvent(data) {
        if (data.type === 'benchmark.completed') {
            console.log('✅ Benchmark completed:', data.benchmarkName || data.benchmarkId);
            this.onBenchmarkCompleted(data);
        }
    }

    async onBenchmarkCompleted(data) {
        console.log('✅ Benchmark completed, reloading card...');
        console.log('  - Benchmark ID:', data.benchmarkId);
        console.log('  - Benchmark Name:', data.benchmarkName);

        // Wait a bit for database to be updated
        await new Promise(resolve => setTimeout(resolve, 500));

        // Find the matching card component and reload it
        this.reloadMatchingCard(data.benchmarkId, data.benchmarkName);
    }

    async reloadMatchingCard(benchmarkId, benchmarkName) {
        console.log('🔍 Looking for card with benchmarkId:', benchmarkId);

        // Find all BenchmarkCard components
        const cards = document.querySelectorAll('[data-live-name-value="BenchmarkCard"]');
        console.log('  📊 Found', cards.length, 'BenchmarkCard components');

        for (const card of cards) {
            // Get benchmarkId from the props
            const propsJson = card.dataset.livePropsValue;
            if (!propsJson) continue;

            try {
                const props = JSON.parse(propsJson);
                const cardBenchmarkId = props.benchmarkId;

                console.log(`    Checking card: ${cardBenchmarkId}`);

                if (cardBenchmarkId === benchmarkId) {
                    console.log('    ✅ MATCH! Reloading this card...');

                    const component = await getComponent(card);

                    component.render().then(() => {
                        console.log('    ✨ Card refreshed successfully!');
                    }).catch(error => {
                        console.error('    ❌ Failed to refresh card:', error);
                    });

                    break; // Found the match, stop searching
                }
            } catch (error) {
                console.error('    ❌ Error parsing props:', error);
            }
        }
    }

    refreshNow() {
        console.log('🔄 User requested manual refresh');
        window.location.reload();
    }
}
