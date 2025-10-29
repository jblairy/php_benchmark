import { Controller } from '@hotwired/stimulus';

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
        }
    }

    subscribeToMercure() {
        const url = new URL(this.urlValue);
        url.searchParams.append('topic', this.topicValue);

        console.log('🔗 Subscribing to:', url.toString());

        this.eventSource = new EventSource(url);

        this.eventSource.onopen = () => {
            console.log('✅ Mercure connection established');
        };

        this.eventSource.onmessage = (event) => {
            console.log('📬 Mercure event received');
            const data = JSON.parse(event.data);
            this.handleBenchmarkEvent(data);
        };

        this.eventSource.onerror = (error) => {
            console.error('❌ Mercure connection error:', error);
        };
    }

    handleBenchmarkEvent(data) {
        if (data.type === 'benchmark.completed') {
            console.log('✅ Benchmark completed:', data.benchmarkName || data.benchmarkId);
            this.onBenchmarkCompleted(data);
        }
    }

    onBenchmarkCompleted(data) {
        // Increment pending updates counter
        this.pendingUpdates++;

        // Update banner text
        if (this.hasBannerTextTarget) {
            if (this.pendingUpdates === 1) {
                this.bannerTextTarget.textContent = `1 nouveau résultat disponible`;
            } else {
                this.bannerTextTarget.textContent = `${this.pendingUpdates} nouveaux résultats disponibles`;
            }
        }

        // Show banner
        if (this.hasBannerTarget) {
            this.bannerTarget.style.display = 'block';
        }

        console.log(`📊 ${this.pendingUpdates} pending update(s)`);
    }

    refreshNow() {
        console.log('🔄 User requested manual refresh');

        // Save scroll position
        const scrollY = window.scrollY;
        sessionStorage.setItem('dashboardScrollPosition', scrollY.toString());

        // Reload page
        window.location.reload();
    }
}

// Restore scroll position on page load
window.addEventListener('DOMContentLoaded', () => {
    const savedPosition = sessionStorage.getItem('dashboardScrollPosition');
    if (savedPosition) {
        window.scrollTo(0, parseInt(savedPosition, 10));
        sessionStorage.removeItem('dashboardScrollPosition');
    }
});
