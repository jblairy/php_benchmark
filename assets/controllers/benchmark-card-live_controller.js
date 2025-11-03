import { Controller } from '@hotwired/stimulus';

/**
 * Real-time benchmark card controller
 * Updates individual cells when new data arrives via Mercure
 * NO page reload, NO DOM replacement - just smooth value updates
 */
export default class extends Controller {
    static values = {
        benchmarkId: String,
        benchmarkName: String
    };

    connect() {

        // Bind the event handler to this instance so we can remove it later
        this.boundHandleEvent = this.handleEvent.bind(this);

        // Listen for Mercure updates on document (bubbles up)
        document.addEventListener('benchmark:dataUpdated', this.boundHandleEvent);
    }

    disconnect() {

        // Remove the event listener to prevent memory leaks
        if (this.boundHandleEvent) {
            document.removeEventListener('benchmark:dataUpdated', this.boundHandleEvent);
        }
    }

    handleEvent(event) {
        this.handleDataUpdate(event.detail);
    }

    handleDataUpdate(data) {

        // Only update if this card matches the updated benchmark
        if (data.benchmarkId !== this.benchmarkIdValue) {
            return;
        }


        // Update each PHP version's data
        Object.entries(data.phpVersions || {}).forEach(([phpVersion, stats]) => {
            this.updatePhpVersionStats(phpVersion, stats);
        });

        // Show visual feedback
        this.flashUpdate();
    }

    updatePhpVersionStats(phpVersion, stats) {

        // Find the column for this PHP version
        const headers = this.element.querySelectorAll('th.table__cell--metric');

        let columnIndex = -1;

        headers.forEach((header, index) => {
            const text = header.textContent.trim();
            if (text.includes(phpVersion.replace('php', ''))) {
                columnIndex = index;
            }
        });

        if (columnIndex === -1) {
            console.warn(`     ❌ Column not found for ${phpVersion}`);
            return;
        }


        // Update each metric
        this.updateCellValue('p50', columnIndex, stats.p50);
        this.updateCellValue('p80', columnIndex, stats.p80);
        this.updateCellValue('p90', columnIndex, stats.p90);
        this.updateCellValue('p95', columnIndex, stats.p95);
        this.updateCellValue('p99', columnIndex, stats.p99);
        this.updateCellValue('avg', columnIndex, stats.avg);
        this.updateCellValue('count', columnIndex, stats.count, false); // no decimal
    }

    updateCellValue(metricName, columnIndex, newValue, hasDecimals = true) {

        // Find the row for this metric
        const rows = this.element.querySelectorAll('tbody tr');

        let targetCell = null;

        rows.forEach((row, rowIndex) => {
            const metricCell = row.querySelector('[data-metric]');
            if (metricCell) {
                const metric = metricCell.dataset.metric;

                if (metric === metricName) {
                    const cells = row.querySelectorAll('td.table__cell:not(.table__cell--metric)');
                    targetCell = cells[columnIndex];
                }
            }
        });

        if (!targetCell) {
            console.warn(`       ❌ Cell not found for ${metricName} column ${columnIndex}`);
            return;
        }

        // Get current value
        const currentText = targetCell.textContent.trim().replace(/,/g, '');
        const currentValue = parseFloat(currentText);

        // Check if value actually changed
        if (Math.abs(currentValue - newValue) < 0.00001) {
            return; // No change
        }

        // Format new value
        const formattedValue = hasDecimals
            ? newValue.toFixed(5).replace(/\.?0+$/, '')
            : newValue.toString();


        // Animate the change
        this.animateCellUpdate(targetCell, formattedValue);

    }

    animateCellUpdate(cell, newValue) {
        // Add highlight animation
        cell.classList.add('cell-updating');

        // Update the value
        cell.textContent = newValue;

        // Remove animation after it completes
        setTimeout(() => {
            cell.classList.remove('cell-updating');
        }, 1000);
    }

    flashUpdate() {
        // Flash the entire card briefly to show it was updated
        this.element.style.transition = 'box-shadow 0.3s ease';
        this.element.style.boxShadow = '0 0 20px rgba(76, 175, 80, 0.5)';

        setTimeout(() => {
            this.element.style.boxShadow = '';
        }, 500);
    }
}
