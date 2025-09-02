import { Controller } from '@hotwired/stimulus';
import { Chart } from 'chart.js';

export default class extends Controller {
    static targets = ['chart', 'container'];
    static values = {
        labels: Array,
        p50Data: Array,
        p90Data: Array,
        avgData: Array
    }

    connect() {
        this.initializeChart();
    }

    initializeChart() {
        const ctx = this.chartTarget.getContext('2d');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: this.labelsValue,
                datasets: [
                    {
                        label: 'p50 (ms)',
                        data: this.p50DataValue,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'p90 (ms)',
                        data: this.p90DataValue,
                        backgroundColor: 'rgba(255, 159, 64, 0.5)',
                        borderColor: 'rgba(255, 159, 64, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Moyenne (ms)',
                        data: this.avgDataValue,
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Temps d\'exécution (ms)'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Comparaison des performances par version PHP'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + context.parsed.y.toFixed(5) + ' ms';
                            }
                        }
                    }
                }
            }
        });
    }

    toggleChart(event) {
        event.preventDefault();
        this.containerTarget.classList.toggle('chart__container--visible');
    }
}
