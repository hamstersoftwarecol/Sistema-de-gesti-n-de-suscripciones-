/**
 * Drag & drop Kanban board (SortableJS, touch friendly). Persists card order through the API.
 */
export default (moveUrl) => ({
    saving: false,

    async init() {
        const { default: Sortable } = await import('sortablejs');

        this.$el.querySelectorAll('[data-kanban-list]').forEach((list) => {
            Sortable.create(list, {
                group: 'kanban',
                animation: 160,
                delay: 120,
                delayOnTouchOnly: true,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                onEnd: (event) => this.persist(event),
            });
        });
    },

    async persist(event) {
        const card = event.item.dataset.cardId;
        const column = event.to.dataset.columnId;
        const order = [...event.to.querySelectorAll('[data-card-id]')].map((el) => el.dataset.cardId);

        this.refreshCounts();
        this.saving = true;

        try {
            await window.axios.post(moveUrl, { card, column, order });
        } catch (e) {
            alert(e.response?.data?.message || 'Error');
        } finally {
            this.saving = false;
        }
    },

    refreshCounts() {
        this.$el.querySelectorAll('[data-kanban-list]').forEach((list) => {
            const counter = this.$el.querySelector(`[data-count-for="${list.dataset.columnId}"]`);
            if (counter) counter.textContent = list.querySelectorAll('[data-card-id]').length;
        });
    },
});
