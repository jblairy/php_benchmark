import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['chart']

    toggle() {
        this.chartTarget.classList.toggle('chart__container--visible');
    }
}
