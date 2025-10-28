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

        this.eventSource = new EventSource(url);

        this.eventSource.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleBenchmarkUpdate(data);
        };

        this.eventSource.onerror = (error) => {
            console.error('Mercure connection error:', error);
        };
    }

    handleBenchmarkUpdate(data) {
        const component = this.element;

        switch (data.type) {
            case 'benchmark.started':
                this.updateComponentData({
                    benchmarkId: data.benchmarkId,
                    benchmarkName: data.benchmarkName,
                    phpVersion: data.phpVersion,
                    totalIterations: data.totalIterations,
                    currentIteration: 0,
                    status: 'started'
                });
                break;

            case 'benchmark.progress':
                this.updateComponentData({
                    currentIteration: data.currentIteration,
                    totalIterations: data.totalIterations,
                    status: 'running'
                });
                break;

            case 'benchmark.completed':
                this.updateComponentData({
                    status: 'completed'
                });
                break;
        }
    }

    updateComponentData(updates) {
        // Update Live Component props
        const component = this.element.closest('[data-controller*="live"]');

        if (component) {
            // Trigger Live Component update via custom event
            const event = new CustomEvent('benchmark:update', {
                detail: updates,
                bubbles: true
            });

            this.element.dispatchEvent(event);

            // Also update DOM directly for immediate feedback
            this.updateDOM(updates);
        }
    }

    updateDOM(updates) {
        // Update progress bar
        if (updates.currentIteration !== undefined && updates.totalIterations !== undefined) {
            const progress = updates.totalIterations > 0
                ? (updates.currentIteration / updates.totalIterations) * 100
                : 0;

            const progressBar = this.element.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.width = `${progress}%`;
            }

            const progressText = this.element.querySelector('.progress-text');
            if (progressText) {
                progressText.textContent = `${updates.currentIteration} / ${updates.totalIterations}`;
            }
        }

        // Update status
        if (updates.status) {
            const statusElements = this.element.querySelectorAll('[class*="status-"]');
            statusElements.forEach(el => el.style.display = 'none');

            const statusElement = this.element.querySelector(`.status-${updates.status}`);
            if (statusElement) {
                statusElement.style.display = 'block';
            }
        }
    }
}
