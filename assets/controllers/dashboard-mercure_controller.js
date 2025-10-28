import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller for real-time dashboard updates via Mercure
 * Listens for benchmark completion events and reloads the corresponding BenchmarkCard
 */
export default class extends Controller {
    static values = {
        url: String,
        topic: String
    };

    eventSource = null;

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
            console.log('📬 Raw event data:', event.data);
            const data = JSON.parse(event.data);
            this.handleBenchmarkEvent(data);
        };

        this.eventSource.onerror = (error) => {
            console.error('❌ Mercure connection error:', error);
            console.error('ReadyState:', this.eventSource.readyState);
        };
    }

    handleBenchmarkEvent(data) {
        console.log('📨 Mercure event received:', data);

        if (data.type === 'benchmark.completed') {
            console.log('✅ Benchmark completed:', data.benchmarkId, data.phpVersion);
            this.reloadBenchmarkCard(data.benchmarkId);
        }
    }

    reloadBenchmarkCard(benchmarkId) {
        console.log('🔄 Reloading dashboard for benchmark:', benchmarkId);

        // Show a brief notification
        this.showNotification(`✅ Benchmark terminé: ${benchmarkId.split('\\').pop()}`);

        // Wait 1 second to let the user see the notification, then reload
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }

    showNotification(message) {
        // Create a simple notification
        const notification = document.createElement('div');
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #4caf50;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 10000;
            font-weight: bold;
            animation: slideIn 0.3s ease-out;
        `;

        document.body.appendChild(notification);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }, 2500);
    }
}
