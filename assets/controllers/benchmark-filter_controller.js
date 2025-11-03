import { Controller } from '@hotwired/stimulus';

/**
 * Stimulus controller for filtering and sorting benchmarks without page reload
 */
export default class extends Controller {
    static targets = ['card', 'searchInput', 'sortButton', 'categoryFilter', 'noResults'];
    static values = {
        currentSort: { type: String, default: 'name' },
        currentOrder: { type: String, default: 'asc' },
        searchQuery: { type: String, default: '' },
        selectedCategory: { type: String, default: 'all' }
    };

    connect() {
        console.log('🔍 Benchmark Filter Controller connected');
        this.updateView();
    }

    search(event) {
        this.searchQueryValue = event.target.value.toLowerCase();
        this.updateView();
    }

    filterByCategory(event) {
        this.selectedCategoryValue = event.target.value;
        this.updateCategoryButtonStates();
        this.updateView();
    }

    sort(event) {
        const sortBy = event.currentTarget.dataset.sortBy;

        // Toggle order if clicking on the same sort button
        if (this.currentSortValue === sortBy) {
            this.currentOrderValue = this.currentOrderValue === 'asc' ? 'desc' : 'asc';
        } else {
            this.currentSortValue = sortBy;
            this.currentOrderValue = 'asc';
        }

        this.updateSortButtonStates();
        this.updateView();
    }

    resetFilters() {
        this.searchQueryValue = '';
        this.selectedCategoryValue = 'all';
        this.currentSortValue = 'name';
        this.currentOrderValue = 'asc';

        if (this.hasSearchInputTarget) {
            this.searchInputTarget.value = '';
        }

        this.updateCategoryButtonStates();
        this.updateSortButtonStates();
        this.updateView();
    }

    updateView() {
        const cards = this.getCardsWithData();

        // Filter cards
        const filteredCards = this.filterCards(cards);

        // Sort cards
        const sortedCards = this.sortCards(filteredCards);

        // Hide all cards first
        cards.forEach(({ element }) => {
            element.style.display = 'none';
        });

        // Show filtered and sorted cards
        sortedCards.forEach(({ element }, index) => {
            element.style.display = 'block';
            element.style.order = index;
        });

        // Show/hide no results message
        this.toggleNoResults(sortedCards.length === 0);

        // Update stats
        this.updateStats(sortedCards.length, cards.length);
    }

    getCardsWithData() {
        return this.cardTargets.map(element => {
            const categoryEl = element.querySelector('.benchmark-card__category');
            const titleEl = element.querySelector('.benchmark-card__title');
            const codeEl = element.querySelector('.benchmark-card__code');

            return {
                element,
                category: categoryEl ? categoryEl.textContent.trim().toLowerCase() : '',
                name: titleEl ? titleEl.textContent.trim().toLowerCase() : '',
                code: codeEl ? codeEl.textContent.trim().toLowerCase() : '',
                fullText: (categoryEl?.textContent || '') + ' ' + (titleEl?.textContent || '') + ' ' + (codeEl?.textContent || '')
            };
        });
    }

    filterCards(cards) {
        return cards.filter(card => {
            // Search filter
            const matchesSearch = !this.searchQueryValue ||
                card.fullText.toLowerCase().includes(this.searchQueryValue);

            // Category filter
            const matchesCategory = this.selectedCategoryValue === 'all' ||
                card.category === this.selectedCategoryValue.toLowerCase();

            return matchesSearch && matchesCategory;
        });
    }

    sortCards(cards) {
        return cards.sort((a, b) => {
            let comparison = 0;

            switch (this.currentSortValue) {
                case 'name':
                    comparison = a.name.localeCompare(b.name);
                    break;
                case 'category':
                    comparison = a.category.localeCompare(b.category) || a.name.localeCompare(b.name);
                    break;
                default:
                    comparison = 0;
            }

            return this.currentOrderValue === 'asc' ? comparison : -comparison;
        });
    }

    updateSortButtonStates() {
        if (!this.hasSortButtonTarget) return;

        this.sortButtonTargets.forEach(button => {
            const sortBy = button.dataset.sortBy;
            const isActive = sortBy === this.currentSortValue;

            button.classList.toggle('filter__sort-button--active', isActive);

            // Update arrow icon
            const icon = button.querySelector('.filter__sort-icon');
            if (icon && isActive) {
                icon.textContent = this.currentOrderValue === 'asc' ? '↑' : '↓';
            } else if (icon) {
                icon.textContent = '↕';
            }
        });
    }

    updateCategoryButtonStates() {
        if (!this.hasCategoryFilterTarget) return;

        this.categoryFilterTargets.forEach(button => {
            const category = button.dataset.category;
            const isActive = category === this.selectedCategoryValue;

            button.classList.toggle('filter__category-button--active', isActive);
        });
    }

    toggleNoResults(show) {
        if (this.hasNoResultsTarget) {
            this.noResultsTarget.style.display = show ? 'block' : 'none';
        }
    }

    updateStats(visible, total) {
        const statsEl = document.querySelector('.filter__stats');
        if (statsEl) {
            if (visible === total) {
                statsEl.textContent = `${total} benchmark${total !== 1 ? 's' : ''}`;
            } else {
                statsEl.textContent = `${visible} / ${total} benchmark${total !== 1 ? 's' : ''}`;
            }
        }
    }

    getUniqueCategories() {
        const cards = this.getCardsWithData();
        const categories = [...new Set(cards.map(card => card.category))].filter(c => c);
        return categories.sort();
    }
}
