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

        this.subscribeToMercure();
    }

    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    }

    subscribeToMercure() {
        const url = new URL(this.urlValue);
        url.searchParams.append('topic', this.topicValue);


        // Close existing connection if any
        if (this.eventSource) {
            this.eventSource.close();
        }

        this.eventSource = new EventSource(url);

        this.eventSource.onopen = () => {
            // Reset reconnection attempts on successful connection
            this.reconnectAttempts = 0;
            this.reconnectDelay = 1000;
        };

        this.eventSource.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleBenchmarkEvent(data);
        };

        this.eventSource.onerror = (error) => {
            console.error('❌ Mercure connection error:', error);

            // Check if EventSource is closed
            if (this.eventSource.readyState === EventSource.CLOSED) {
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


        setTimeout(() => {
            this.subscribeToMercure();
        }, delay);
    }

    handleBenchmarkEvent(data) {
        if (data.type === 'benchmark.completed') {
            this.onBenchmarkCompleted(data);
        }
    }

    async onBenchmarkCompleted(data) {

        // Wait a bit for database to be updated
        await new Promise(resolve => setTimeout(resolve, 500));

        // Find the matching card component and reload it
        this.reloadMatchingCard(data.benchmarkId, data.benchmarkName);
    }

    async reloadMatchingCard(benchmarkId, benchmarkName) {

        // Find all BenchmarkCard components
        const cards = document.querySelectorAll('[data-live-name-value="BenchmarkCard"]');

        for (const card of cards) {
            // Get benchmarkId from the props
            const propsJson = card.dataset.livePropsValue;
            if (!propsJson) continue;

            try {
                const props = JSON.parse(propsJson);
                const cardBenchmarkId = props.benchmarkId;


                if (cardBenchmarkId === benchmarkId) {

                    const component = await getComponent(card);

                    component.render().then(() => {
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
        window.location.reload();
    }
}
