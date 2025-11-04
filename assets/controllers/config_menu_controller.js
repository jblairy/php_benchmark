import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu', 'phpVersion', 'metric', 'unitRadio', 'backdrop'];
    static values = {
        phpVersions: Array,
        metrics: Array
    };

    connect() {
        // Load preferences from localStorage
        this.loadPreferences();
        this.applyFilters();
    }

    toggleMenu() {
        const isVisible = this.menuTarget.classList.toggle('config-menu--visible');
        if (this.hasBackdropTarget) {
            this.backdropTarget.classList.toggle('config-menu-backdrop--visible', isVisible);
        }
    }

    closeMenu() {
        this.menuTarget.classList.remove('config-menu--visible');
        if (this.hasBackdropTarget) {
            this.backdropTarget.classList.remove('config-menu-backdrop--visible');
        }
    }

    // PHP Versions management
    togglePhpVersion(event) {
        const version = event.target.value;
        const isChecked = event.target.checked;
        
        this.savePreference('phpVersions', version, isChecked);
        this.applyPhpVersionFilter();
    }

    selectAllPhp() {
        this.phpVersionTargets.forEach(checkbox => {
            checkbox.checked = true;
            this.savePreference('phpVersions', checkbox.value, true);
        });
        this.applyPhpVersionFilter();
    }

    deselectAllPhp() {
        this.phpVersionTargets.forEach(checkbox => {
            checkbox.checked = false;
            this.savePreference('phpVersions', checkbox.value, false);
        });
        this.applyPhpVersionFilter();
    }

    applyPhpVersionFilter() {
        const selectedVersions = this.getSelectedPhpVersions();
        
        // Get table
        const table = this.element.querySelector('.benchmark-card__table');
        if (!table) return;

        // Get all PHP version columns (header + data)
        const headers = table.querySelectorAll('thead th');
        const rows = table.querySelectorAll('tbody tr');

        // Find indices of PHP versions in header
        const versionIndices = [];
        headers.forEach((header, index) => {
            const text = header.textContent.trim();
            const match = text.match(/PHP\s*(\d+\.\d+|\d+)/);
            if (match) {
                const versionKey = `php${match[1].replace('.', '')}`;
                const isVisible = selectedVersions.includes(versionKey);
                header.style.display = isVisible ? '' : 'none';
                versionIndices.push({ index, visible: isVisible });
            }
        });

        // Hide/show corresponding cells in each row
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            versionIndices.forEach(({ index, visible }) => {
                if (cells[index]) {
                    cells[index].style.display = visible ? '' : 'none';
                }
            });
        });
    }

    // Metrics management
    toggleMetric(event) {
        const metric = event.target.value;
        const isChecked = event.target.checked;
        
        this.savePreference('metrics', metric, isChecked);
        this.applyMetricFilter();
    }

    selectAllMetrics() {
        this.metricTargets.forEach(checkbox => {
            checkbox.checked = true;
            this.savePreference('metrics', checkbox.value, true);
        });
        this.applyMetricFilter();
    }

    deselectAllMetrics() {
        this.metricTargets.forEach(checkbox => {
            checkbox.checked = false;
            this.savePreference('metrics', checkbox.value, false);
        });
        this.applyMetricFilter();
    }

    applyMetricFilter() {
        const selectedMetrics = this.getSelectedMetrics();
        const table = this.element.querySelector('.benchmark-card__table');
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr:not(.benchmark-card__table-row--category)');
        rows.forEach(row => {
            const metricCell = row.querySelector('[data-metric]');
            if (metricCell) {
                const metric = metricCell.dataset.metric;
                const isVisible = selectedMetrics.includes(metric);
                row.style.display = isVisible ? '' : 'none';
            }
        });
    }

    // Unit conversion
    changeUnit(event) {
        const unit = event.target.value;
        this.saveUnitPreference(unit);
        this.applyUnitConversion(unit);
    }

    applyUnitConversion(unit) {
        const cells = this.element.querySelectorAll('[data-unit-switch-target="cell"]');
        
        cells.forEach(cell => {
            const msValue = parseFloat(cell.dataset.value);
            if (isNaN(msValue)) return;

            let convertedValue;
            let suffix;

            switch(unit) {
                case 'us': // microseconds
                    convertedValue = msValue * 1000;
                    suffix = '';
                    break;
                case 'ns': // nanoseconds
                    convertedValue = msValue * 1000000;
                    suffix = '';
                    break;
                case 'ms': // milliseconds (default)
                default:
                    convertedValue = msValue;
                    suffix = '';
                    break;
            }

            // Format based on magnitude
            let formatted;
            if (convertedValue >= 1000000) {
                formatted = convertedValue.toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            } else if (convertedValue >= 1000) {
                formatted = convertedValue.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } else if (convertedValue >= 1) {
                formatted = convertedValue.toLocaleString('fr-FR', { minimumFractionDigits: 5, maximumFractionDigits: 5 });
            } else {
                formatted = convertedValue.toLocaleString('fr-FR', { minimumFractionDigits: 8, maximumFractionDigits: 8 });
            }

            cell.textContent = formatted + suffix;
        });
    }

    // Helper methods
    getSelectedPhpVersions() {
        return this.phpVersionTargets
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);
    }

    getSelectedMetrics() {
        return this.metricTargets
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);
    }

    savePreference(type, key, value) {
        const storageKey = `benchmark_${type}`;
        const preferences = JSON.parse(localStorage.getItem(storageKey) || '{}');
        preferences[key] = value;
        localStorage.setItem(storageKey, JSON.stringify(preferences));
    }

    saveUnitPreference(unit) {
        localStorage.setItem('benchmark_unit', unit);
    }

    loadPreferences() {
        // Load PHP versions preferences
        const phpPrefs = JSON.parse(localStorage.getItem('benchmark_phpVersions') || '{}');
        this.phpVersionTargets.forEach(checkbox => {
            if (phpPrefs[checkbox.value] !== undefined) {
                checkbox.checked = phpPrefs[checkbox.value];
            }
        });

        // Load metrics preferences
        const metricPrefs = JSON.parse(localStorage.getItem('benchmark_metrics') || '{}');
        this.metricTargets.forEach(checkbox => {
            if (metricPrefs[checkbox.value] !== undefined) {
                checkbox.checked = metricPrefs[checkbox.value];
            }
        });

        // Load unit preference
        const unit = localStorage.getItem('benchmark_unit') || 'ms';
        this.unitRadioTargets.forEach(radio => {
            if (radio.value === unit) {
                radio.checked = true;
            }
        });
    }

    applyFilters() {
        this.applyPhpVersionFilter();
        this.applyMetricFilter();
        
        const unit = localStorage.getItem('benchmark_unit') || 'ms';
        this.applyUnitConversion(unit);
    }
}
