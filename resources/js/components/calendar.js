/**
 * Full calendar (month / week / day / agenda) fed by a JSON endpoint.
 */
export default (eventsUrl, locale = 'es', labels = {}) => ({
    calendar: null,
    filters: ['renewal', 'invoice', 'trial', 'task'],

    async init() {
        const [{ Calendar }, dayGrid, timeGrid, list, interaction, locales] = await Promise.all([
            import('@fullcalendar/core'),
            import('@fullcalendar/daygrid'),
            import('@fullcalendar/timegrid'),
            import('@fullcalendar/list'),
            import('@fullcalendar/interaction'),
            import('@fullcalendar/core/locales-all'),
        ]);

        const mobile = window.matchMedia('(max-width: 640px)').matches;

        this.calendar = new Calendar(this.$refs.calendar, {
            plugins: [dayGrid.default, timeGrid.default, list.default, interaction.default],
            locales: locales.default,
            locale,
            initialView: mobile ? 'listMonth' : 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: mobile ? 'dayGridMonth,listMonth' : 'dayGridMonth,timeGridWeek,timeGridDay,listMonth',
            },
            height: 'auto',
            firstDay: 1,
            dayMaxEvents: 3,
            navLinks: true,
            nowIndicator: true,
            events: (info, success, failure) => {
                window.axios
                    .get(eventsUrl, { params: { start: info.startStr, end: info.endStr, types: this.filters.join(',') } })
                    .then((response) => success(response.data))
                    .catch(failure);
            },
            eventClick: (info) => {
                if (info.event.url) {
                    info.jsEvent.preventDefault();
                    window.location.href = info.event.url;
                }
            },
        });

        this.calendar.render();
    },

    toggle(type) {
        this.filters = this.filters.includes(type) ? this.filters.filter((t) => t !== type) : [...this.filters, type];
        this.calendar?.refetchEvents();
    },
});
