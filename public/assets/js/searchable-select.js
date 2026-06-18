/**
 * Searchable Select - Componente de select com busca
 * Uso: new SearchableSelect(element, options)
 * 
 * options:
 *   placeholder: string - texto placeholder do campo de busca
 *   onSelect: function(value, text, dataset) - callback ao selecionar
 *   multiple: boolean - permite múltipla seleção
 *   onDeselect: function(value) - callback ao remover (multiple)
 */
class SearchableSelect {
    constructor(selectEl, options = {}) {
        this.select = selectEl;
        this.options = options;
        this.isMultiple = options.multiple || false;
        this.selectedValues = [];
        this.isOpen = false;
        
        this.build();
        this.bindEvents();
        
        // Esconder select original
        this.select.style.display = 'none';
    }

    build() {
        // Container
        this.container = document.createElement('div');
        this.container.className = 'ss-container';
        this.select.parentNode.insertBefore(this.container, this.select.nextSibling);

        // Input de busca
        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.className = 'form-control form-control-sm ss-input';
        this.input.placeholder = this.options.placeholder || 'Buscar...';
        this.input.autocomplete = 'off';
        this.container.appendChild(this.input);

        // Tags (para multiple)
        if (this.isMultiple) {
            this.tagsContainer = document.createElement('div');
            this.tagsContainer.className = 'ss-tags';
            this.container.insertBefore(this.tagsContainer, this.input);
        }

        // Dropdown
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'ss-dropdown';
        this.dropdown.style.display = 'none';
        this.container.appendChild(this.dropdown);

        // Popular opções
        this.refreshOptions();
    }

    refreshOptions() {
        this.items = [];
        Array.from(this.select.options).forEach(opt => {
            if (opt.value === '') return;
            this.items.push({
                value: opt.value,
                text: opt.textContent,
                dataset: { ...opt.dataset },
            });
        });
    }

    bindEvents() {
        // Focus no input abre dropdown
        this.input.addEventListener('focus', () => this.open());
        
        // Digitar filtra
        this.input.addEventListener('input', () => this.filter());

        // Fechar ao clicar fora
        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) this.close();
        });

        // Teclado
        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.close();
        });
    }

    open() {
        this.isOpen = true;
        this.filter();
        this.dropdown.style.display = 'block';
    }

    close() {
        this.isOpen = false;
        this.dropdown.style.display = 'none';
    }

    filter() {
        const term = this.input.value.toLowerCase().trim();
        this.dropdown.innerHTML = '';

        const filtered = this.items.filter(item => {
            if (this.isMultiple && this.selectedValues.includes(item.value)) return false;
            if (!term) return true;
            return item.text.toLowerCase().includes(term);
        });

        if (filtered.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'ss-item ss-empty';
            empty.textContent = 'Nenhum resultado';
            this.dropdown.appendChild(empty);
            return;
        }

        filtered.slice(0, 50).forEach(item => {
            const el = document.createElement('div');
            el.className = 'ss-item';
            el.textContent = item.text;
            el.dataset.value = item.value;
            el.addEventListener('click', () => this.selectItem(item));
            this.dropdown.appendChild(el);
        });

        if (filtered.length > 50) {
            const more = document.createElement('div');
            more.className = 'ss-item ss-more';
            more.textContent = `+ ${filtered.length - 50} resultados. Continue digitando...`;
            this.dropdown.appendChild(more);
        }
    }

    selectItem(item) {
        if (this.isMultiple) {
            this.selectedValues.push(item.value);
            this.addTag(item);
            this.input.value = '';
            this.filter();
            
            // Atualizar select original
            const opt = this.select.querySelector(`option[value="${item.value}"]`);
            if (opt) opt.selected = true;

            if (this.options.onSelect) this.options.onSelect(item.value, item.text, item.dataset);
        } else {
            this.input.value = item.text;
            this.close();

            // Atualizar select original
            this.select.value = item.value;

            if (this.options.onSelect) this.options.onSelect(item.value, item.text, item.dataset);
        }
    }

    addTag(item) {
        const tag = document.createElement('span');
        tag.className = 'ss-tag';
        tag.innerHTML = `${item.text} <button type="button" class="ss-tag-remove" data-value="${item.value}">&times;</button>`;
        tag.querySelector('.ss-tag-remove').addEventListener('click', () => this.removeTag(item.value, tag));
        this.tagsContainer.appendChild(tag);
    }

    removeTag(value, tagEl) {
        this.selectedValues = this.selectedValues.filter(v => v !== value);
        tagEl.remove();

        // Atualizar select original
        const opt = this.select.querySelector(`option[value="${value}"]`);
        if (opt) opt.selected = false;

        if (this.options.onDeselect) this.options.onDeselect(value);
        if (this.isOpen) this.filter();
    }

    // Adicionar item programaticamente
    addOption(value, text, dataset = {}) {
        const opt = new Option(text, value);
        Object.entries(dataset).forEach(([k, v]) => opt.dataset[k] = v);
        this.select.add(opt);
        this.items.push({ value, text, dataset });
    }

    // Selecionar programaticamente
    setValue(value) {
        const item = this.items.find(i => i.value == value);
        if (item) this.selectItem(item);
    }

    // Limpar seleção
    clear() {
        if (this.isMultiple) {
            this.selectedValues = [];
            if (this.tagsContainer) this.tagsContainer.innerHTML = '';
            Array.from(this.select.options).forEach(o => o.selected = false);
        } else {
            this.input.value = '';
            this.select.value = '';
        }
    }
}
