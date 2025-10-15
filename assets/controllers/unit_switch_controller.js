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
        const metrics = ['p50', 'p80', 'p90', 'p95', 'p99', 'Moyenne'];
        metrics.forEach(metric => {
            const header = this.element.querySelector(`[data-metric="${metric}"]`);
            if (header) {
                header.textContent = `${metric} (${this.currentUnitValue})`;
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
