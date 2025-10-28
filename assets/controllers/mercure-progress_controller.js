import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller for real-time benchmark progress updates via Mercure
 */
export default class extends Controller {
    static values = {
        url: String,
        topic: String
    };

    eventSource = null;

    connect() {
        console.log('🔌 Mercure Progress Controller connected');
        console.log('📡 Mercure URL:', this.urlValue);
        console.log('📢 Topic:', this.topicValue);
        this.subscribeToMercure();
    }

    disconnect() {
        console.log('🔌 Mercure Progress Controller disconnected');
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
            this.handleBenchmarkUpdate(data);
        };

        this.eventSource.onerror = (error) => {
            console.error('❌ Mercure connection error:', error);
            console.error('ReadyState:', this.eventSource.readyState);
        };
    }

    handleBenchmarkUpdate(data) {
        console.log('📨 Mercure event received:', data);

        switch (data.type) {
            case 'benchmark.started':
                this.showStarted(data);
                break;

            case 'benchmark.progress':
                this.showProgress(data);
                break;

            case 'benchmark.completed':
                this.showCompleted(data);
                break;
        }
    }

    showStarted(data) {
        // Update benchmark name and PHP version
        const nameEl = this.element.querySelector('.benchmark-name');
        if (nameEl) {
            nameEl.textContent = `${data.benchmarkName} (${data.benchmarkId})`;
        }

        const badgeEl = this.element.querySelector('.php-version-badge');
        if (badgeEl) {
            badgeEl.textContent = data.phpVersion.toUpperCase();
            badgeEl.style.display = 'inline-block';
        }

        // Hide all status divs
        this.hideAllStatus();

        // Show started status
        const startedEl = this.element.querySelector('.status-started');
        if (startedEl) {
            startedEl.style.display = 'block';
        }

        console.log('✅ Started:', data.benchmarkName, data.phpVersion);
    }

    showProgress(data) {
        // Hide all status divs
        this.hideAllStatus();

        // Show running status
        const runningEl = this.element.querySelector('.status-running');
        if (runningEl) {
            runningEl.style.display = 'block';
        }

        // Update progress bar
        const progress = data.totalIterations > 0
            ? (data.currentIteration / data.totalIterations) * 100
            : 0;

        const progressBar = this.element.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
        }

        const progressText = this.element.querySelector('.progress-text');
        if (progressText) {
            progressText.textContent = `${data.currentIteration} / ${data.totalIterations}`;
        }

        console.log('⏱️ Progress:', `${data.currentIteration}/${data.totalIterations}`, `${progress.toFixed(1)}%`);
    }

    showCompleted(data) {
        // Hide all status divs
        this.hideAllStatus();

        // Show completed status
        const completedEl = this.element.querySelector('.status-completed');
        if (completedEl) {
            completedEl.style.display = 'block';
        }

        console.log('✅ Completed!');
    }

    hideAllStatus() {
        const statusDivs = this.element.querySelectorAll('[class^="status-"]');
        statusDivs.forEach(el => el.style.display = 'none');
    }
}
