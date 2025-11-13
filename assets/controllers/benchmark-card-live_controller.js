import { Controller } from '@hotwired/stimulus';

/**
 * Real-time benchmark card controller
 * Updates individual cells when new data arrives
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

        // Listen for updates on document (bubbles up)
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

        // Check if table exists (card might still be in loading state)
        const table = this.element.querySelector('table.benchmark-card__table');
        if (!table) {
            console.warn(`⚠️  Table not found for ${this.benchmarkIdValue} - card might still be loading`);
            return;
        }

        console.log(`🔄 Updating ${this.benchmarkIdValue} with stats for ${Object.keys(data.phpVersions || {}).length} PHP versions`);

        // Update each PHP version's data
        Object.entries(data.phpVersions || {}).forEach(([phpVersion, stats]) => {
            this.updatePhpVersionStats(phpVersion, stats);
        });

        // Show visual feedback
        this.flashUpdate();
    }

    updatePhpVersionStats(phpVersion, stats) {

        // Find the column for this PHP version
        // Look for headers that are NOT the metric column (first column)
        const headers = this.element.querySelectorAll('thead th.benchmark-card__table-cell:not(.benchmark-card__table-cell--metric)');

        let columnIndex = -1;
        
        // Extract just the version number (e.g., "php82" -> "82")
        const versionNumber = phpVersion.replace('php', '');

        headers.forEach((header, index) => {
            const text = header.textContent.trim();
            // Match "PHP 82" with "82" or "PHP 8.2" with "82"
            // Support both "PHP 82" and "PHP 8.2" formats
            const normalizedText = text.replace(/\s+/g, '').toLowerCase();
            const phpPrefix = 'php' + versionNumber;
            
            if (normalizedText === phpPrefix || text.includes(versionNumber)) {
                columnIndex = index;
            }
        });

        if (columnIndex === -1) {
            // This is normal - the card may not have data for all PHP versions
            // Just silently skip this version
            return;
        }


        // Update each metric that exists in the template
        this.updateCellValue('p50', columnIndex, stats.p50);
        this.updateCellValue('min', columnIndex, stats.min);
        this.updateCellValue('max', columnIndex, stats.max);
        this.updateCellValue('p90', columnIndex, stats.p90);
        this.updateCellValue('p95', columnIndex, stats.p95);
        this.updateCellValue('p99', columnIndex, stats.p99);
        this.updateCellValue('throughput', columnIndex, stats.throughput, false); // no decimal
        this.updateCellValue('stdDev', columnIndex, stats.stdDev);
        this.updateCellValue('cv', columnIndex, stats.cv);
        this.updateCellValue('memoryUsed', columnIndex, stats.memoryUsed);
        this.updateCellValue('memoryPeak', columnIndex, stats.memoryPeak);
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
                    // Select cells that are NOT the metric column (first column)
                    const cells = row.querySelectorAll('td.benchmark-card__table-cell:not(.benchmark-card__table-cell--metric)');
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
