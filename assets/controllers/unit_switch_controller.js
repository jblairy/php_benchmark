// assets/controllers/unit-switch_controller.js
import { Controller } from '@hotwired/stimulus';

/**
 * Global unit switch controller - controls all benchmark cards at once
 */
export default class extends Controller {
    static targets = ['cell', 'msButton', 'nsButton'];
    static values = {
        currentUnit: { type: String, default: 'ms' }
    }

    connect() {
        console.log('🔄 Global Unit Switch Controller connected');
        this.updateButtonStates();
    }

    toggle(event) {
        const clickedUnit = event.currentTarget.dataset.unit;
        
        // Don't toggle if clicking the already active unit
        if (clickedUnit === this.currentUnitValue) return;
        
        this.currentUnitValue = clickedUnit;

        // Update all cells across all cards
        this.cellTargets.forEach(cell => {
            const value = parseFloat(cell.dataset.value);
            if (!isNaN(value)) {
                const displayValue = this.formatValue(value);
                cell.textContent = displayValue;
            }
        });

        // Update all metric headers across all cards
        const metrics = [
            { key: 'p50', label: 'p50' },
            { key: 'p80', label: 'p80' },
            { key: 'p90', label: 'p90' },
            { key: 'p95', label: 'p95' },
            { key: 'p99', label: 'p99' },
            { key: 'avg', label: 'Moyenne' }
        ];
        
        metrics.forEach(metric => {
            document.querySelectorAll(`[data-metric="${metric.key}"]`).forEach(header => {
                header.textContent = `${metric.label} (${this.currentUnitValue})`;
            });
        });

        this.updateButtonStates();
    }

    updateButtonStates() {
        if (this.hasMsButtonTarget && this.hasNsButtonTarget) {
            if (this.currentUnitValue === 'ms') {
                this.msButtonTarget.classList.add('filter__unit-toggle-option--active');
                this.nsButtonTarget.classList.remove('filter__unit-toggle-option--active');
            } else {
                this.msButtonTarget.classList.remove('filter__unit-toggle-option--active');
                this.nsButtonTarget.classList.add('filter__unit-toggle-option--active');
            }
        }
    }

    formatValue(value) {
        if (this.currentUnitValue === 'ns') {
            return (value * 1_000_000).toFixed(0);
        }
        return value.toFixed(5);
    }
}
