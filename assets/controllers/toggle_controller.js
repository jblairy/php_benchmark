import { Controller } from '@hotwired/stimulus';

/*
 * Controller for toggling between chart and table view
 */
export default class extends Controller {
    static targets = ['chart', 'table', 'iconChart', 'iconTable', 'button'];

    connect() {
        this.showingChart = false;
    }

    toggle() {
        this.showingChart = !this.showingChart;

        if (this.showingChart) {
            // Show chart, hide table
            this.tableTarget.classList.add('benchmark-card__table-wrapper--hidden');
            this.chartTarget.classList.add('benchmark-card__chart-container--visible');
            
            // Switch icons
            this.iconChartTarget.style.display = 'none';
            this.iconTableTarget.style.display = 'inline';
            
            // Update title
            this.buttonTarget.setAttribute('title', 'Afficher le tableau');
        } else {
            // Show table, hide chart
            this.tableTarget.classList.remove('benchmark-card__table-wrapper--hidden');
            this.chartTarget.classList.remove('benchmark-card__chart-container--visible');
            
            // Switch icons
            this.iconChartTarget.style.display = 'inline';
            this.iconTableTarget.style.display = 'none';
            
            // Update title
            this.buttonTarget.setAttribute('title', 'Afficher le graphique');
        }
    }
}
