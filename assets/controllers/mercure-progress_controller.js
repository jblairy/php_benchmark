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
        this.subscribeToMercure();
    }

    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
        }
    }

    subscribeToMercure() {
        const url = new URL(this.urlValue);
        url.searchParams.append('topic', this.topicValue);

        console.log('🔗 Connecting to Mercure:', url.toString());
        this.eventSource = new EventSource(url.toString());

        this.eventSource.onopen = () => {
            console.log('✅ Mercure connection established');
        };

        this.eventSource.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleBenchmarkUpdate(data);
        };

        this.eventSource.onerror = (error) => {
            console.error('❌ Mercure connection error:', error);
            console.error('ReadyState:', this.eventSource.readyState);
        };
    }

    handleBenchmarkUpdate(data) {

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
        const nameEl = this.element.querySelector('.benchmark-progress__name');
        if (nameEl) {
            nameEl.textContent = `${data.benchmarkName} (${data.benchmarkId})`;
        }

        const badgeEl = this.element.querySelector('.benchmark-progress__badge');
        if (badgeEl) {
            badgeEl.textContent = data.phpVersion.toUpperCase();
            badgeEl.classList.remove('benchmark-progress__badge--hidden');
        }

        // Hide all status divs
        this.hideAllStatus();

        // Show started status
        const startedEl = this.element.querySelector('.benchmark-progress__status--started');
        if (startedEl) {
            startedEl.style.display = 'block';
        }

    }

    showProgress(data) {
        // Hide all status divs
        this.hideAllStatus();

        // Show running status
        const runningEl = this.element.querySelector('.benchmark-progress__status--running');
        if (runningEl) {
            runningEl.style.display = 'block';
        }

        // Update progress bar
        const progress = data.totalIterations > 0
            ? (data.currentIteration / data.totalIterations) * 100
            : 0;

        const progressBar = this.element.querySelector('.benchmark-progress__progress-bar');
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
        }

        const progressText = this.element.querySelector('.benchmark-progress__progress-text');
        if (progressText) {
            progressText.textContent = `${data.currentIteration} / ${data.totalIterations}`;
        }

    }

    showCompleted(data) {
        // Hide all status divs
        this.hideAllStatus();

        // Show completed status
        const completedEl = this.element.querySelector('.benchmark-progress__status--completed');
        if (completedEl) {
            completedEl.style.display = 'block';
        }

    }

    hideAllStatus() {
        const statusDivs = this.element.querySelectorAll('[class*="benchmark-progress__status--"]');
        statusDivs.forEach(el => el.style.display = 'none');
    }
}
