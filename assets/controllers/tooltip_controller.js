import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        text: String
    };

    connect() {
        this.tooltip = null;
        this.showTimeout = null;
    }

    disconnect() {
        this.hide();
        if (this.tooltip) {
            this.tooltip.remove();
        }
    }

    show() {
        // Clear any existing timeout
        if (this.showTimeout) {
            clearTimeout(this.showTimeout);
        }

        // Show immediately
        this.showTimeout = setTimeout(() => {
            this.createTooltip();
        }, 0);
    }

    hide() {
        if (this.showTimeout) {
            clearTimeout(this.showTimeout);
            this.showTimeout = null;
        }

        if (this.tooltip) {
            this.tooltip.remove();
            this.tooltip = null;
        }
    }

    createTooltip() {
        // Remove existing tooltip if any
        if (this.tooltip) {
            this.tooltip.remove();
        }

        // Create tooltip element
        this.tooltip = document.createElement('div');
        this.tooltip.className = 'tooltip-floating';
        this.tooltip.textContent = this.textValue;

        // Add to body for proper positioning
        document.body.appendChild(this.tooltip);

        // Position tooltip
        this.positionTooltip();

        // Show with animation
        requestAnimationFrame(() => {
            this.tooltip.classList.add('tooltip-floating--visible');
        });
    }

    positionTooltip() {
        if (!this.tooltip) return;

        const trigger = this.element;
        const triggerRect = trigger.getBoundingClientRect();
        const tooltipRect = this.tooltip.getBoundingClientRect();

        // Calculate position above the trigger
        let left = triggerRect.left + (triggerRect.width / 2) - (tooltipRect.width / 2);
        let top = triggerRect.top - tooltipRect.height - 8;

        // Keep tooltip within viewport horizontally
        const padding = 10;
        if (left < padding) {
            left = padding;
        } else if (left + tooltipRect.width > window.innerWidth - padding) {
            left = window.innerWidth - tooltipRect.width - padding;
        }

        // If tooltip would go above viewport, show below instead
        if (top < padding) {
            top = triggerRect.bottom + 8;
            this.tooltip.classList.add('tooltip-floating--below');
        }

        this.tooltip.style.left = `${left}px`;
        this.tooltip.style.top = `${top}px`;
    }
}
