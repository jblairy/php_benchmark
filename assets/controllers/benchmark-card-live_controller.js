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
        console.log('📊 Benchmark card connected:', this.benchmarkNameValue);
        console.log('   - Benchmark ID:', this.benchmarkIdValue);

        // Bind the event handler to this instance so we can remove it later
        this.boundHandleEvent = this.handleEvent.bind(this);

        // Listen for Mercure updates on document (bubbles up)
        document.addEventListener('benchmark:dataUpdated', this.boundHandleEvent);
    }

    disconnect() {
        console.log('🔌 Benchmark card disconnected:', this.benchmarkNameValue);

        // Remove the event listener to prevent memory leaks
        if (this.boundHandleEvent) {
            document.removeEventListener('benchmark:dataUpdated', this.boundHandleEvent);
        }
    }

    handleEvent(event) {
        console.log('📬 Event received by card:', this.benchmarkNameValue);
        console.log('   - Event detail:', event.detail);
        this.handleDataUpdate(event.detail);
    }

    handleDataUpdate(data) {
        console.log('🔄 handleDataUpdate called for:', this.benchmarkNameValue);
        console.log('   - This card ID:', this.benchmarkIdValue);
        console.log('   - Event data ID:', data.benchmarkId);

        // Only update if this card matches the updated benchmark
        if (data.benchmarkId !== this.benchmarkIdValue) {
            console.log('   ⏭️  Skipping, not for this card');
            return;
        }

        console.log('   ✅ MATCH! Updating this card');
        console.log('   - PHP Versions data:', data.phpVersions);

        // Update each PHP version's data
        Object.entries(data.phpVersions || {}).forEach(([phpVersion, stats]) => {
            console.log(`   - Updating ${phpVersion}:`, stats);
            this.updatePhpVersionStats(phpVersion, stats);
        });

        // Show visual feedback
        this.flashUpdate();
    }

    updatePhpVersionStats(phpVersion, stats) {
        console.log(`     🔍 Finding column for ${phpVersion}`);

        // Find the column for this PHP version
        const headers = this.element.querySelectorAll('th.table__cell--metric');
        console.log(`     - Found ${headers.length} header cells`);

        let columnIndex = -1;

        headers.forEach((header, index) => {
            const text = header.textContent.trim();
            console.log(`       Header ${index}: "${text}"`);
            if (text.includes(phpVersion.replace('php', ''))) {
                columnIndex = index;
                console.log(`       ✅ MATCH at index ${index}`);
            }
        });

        if (columnIndex === -1) {
            console.warn(`     ❌ Column not found for ${phpVersion}`);
            return;
        }

        console.log(`     ✅ Column found at index ${columnIndex}, updating cells...`);

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
        console.log(`       🔍 updateCellValue: ${metricName} col ${columnIndex} = ${newValue}`);

        // Find the row for this metric
        const rows = this.element.querySelectorAll('tbody tr');
        console.log(`       - Found ${rows.length} table rows`);

        let targetCell = null;

        rows.forEach((row, rowIndex) => {
            const metricCell = row.querySelector('[data-metric]');
            if (metricCell) {
                const metric = metricCell.dataset.metric;
                console.log(`         Row ${rowIndex}: metric="${metric}"`);

                if (metric === metricName) {
                    const cells = row.querySelectorAll('td.table__cell:not(.table__cell--metric)');
                    console.log(`         ✅ MATCH! Found ${cells.length} data cells`);
                    targetCell = cells[columnIndex];
                    console.log(`         Target cell:`, targetCell);
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
        console.log(`       Current value: ${currentValue}`);

        // Check if value actually changed
        if (Math.abs(currentValue - newValue) < 0.00001) {
            console.log(`       ⏭️  No change (difference too small)`);
            return; // No change
        }

        // Format new value
        const formattedValue = hasDecimals
            ? newValue.toFixed(5).replace(/\.?0+$/, '')
            : newValue.toString();

        console.log(`       ✨ ANIMATING: ${currentValue} → ${newValue} (formatted: ${formattedValue})`);

        // Animate the change
        this.animateCellUpdate(targetCell, formattedValue);

        console.log(`       ✅ Updated ${metricName} col ${columnIndex}: ${currentValue} → ${newValue}`);
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
