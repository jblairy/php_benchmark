// assets/controllers/unit-switch_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['cell'];
    static values = {
        currentUnit: { type: String, default: 'ms' }
    }

    toggle() {
        const newUnit = this.currentUnitValue === 'ms' ? 'ns' : 'ms';
        this.currentUnitValue = newUnit;

        this.cellTargets.forEach(cell => {
            const value = parseFloat(cell.dataset.value);
            if (!isNaN(value)) {
                const displayValue = this.formatValue(value);
                cell.textContent = displayValue;
            }
        });

        // Mise à jour du libellé de l'unité dans l'en-tête
        const metrics = [
            { key: 'p50', label: 'p50' },
            { key: 'p80', label: 'p80' },
            { key: 'p90', label: 'p90' },
            { key: 'p95', label: 'p95' },
            { key: 'p99', label: 'p99' },
            { key: 'avg', label: 'Moyenne' }
        ];
        metrics.forEach(metric => {
            const header = this.element.querySelector(`[data-metric="${metric.key}"]`);
            if (header) {
                header.textContent = `${metric.label} (${this.currentUnitValue})`;
            }
        });
    }

    formatValue(value) {
        if (this.currentUnitValue === 'ns') {
            return (value * 1_000_000).toFixed(0);
        }
        return value.toFixed(5);
    }
}
