import { Controller } from '@hotwired/stimulus';
import hljs from 'highlight.js';

/**
 * Syntax highlighter controller for PHP code
 */
export default class extends Controller {
    static initialized = false;

    connect() {
        if (!this.constructor.initialized) {
            hljs.configure({
                ignoreUnescapedHTML: true,
                languages: ['php']
            });
            this.constructor.initialized = true;
        }
        
        this.highlightCode();
    }
    
    highlightCode() {
        try {
            let codeElement = this.element.querySelector('code');
            if (!codeElement) {
                codeElement = document.createElement('code');
                codeElement.textContent = this.element.textContent;
                this.element.textContent = '';
                this.element.appendChild(codeElement);
            }
            
            codeElement.className = 'language-php';
            hljs.highlightElement(codeElement);
            this.element.classList.add('hljs');
        } catch (error) {
            console.error('SyntaxHighlighter Error:', error);
        }
    }
}
