/**
 * Gemini chat: sends the prompt via AJAX and appends the rendered answer.
 */
export default (sendUrl) => ({
    message: '',
    loading: false,
    error: null,
    pending: null,
    started: false,

    init() {
        this.scroll();
    },

    scroll() {
        this.$nextTick(() => {
            const box = this.$refs.messages;
            if (box) box.scrollTop = box.scrollHeight;
        });
    },

    ask(text) {
        this.message = text;
        this.send();
    },

    async send() {
        const text = this.message.trim();
        if (!text || this.loading) return;

        this.loading = true;
        this.started = true;
        this.error = null;
        this.pending = text;
        this.message = '';
        this.scroll();

        try {
            const { data } = await window.axios.post(sendUrl, { message: text });
            this.$refs.messages.insertAdjacentHTML('beforeend', data.html);
            if (data.title && this.$refs.title) this.$refs.title.textContent = data.title;
        } catch (e) {
            this.error = e.response?.data?.message || e.message;
            this.message = text;
        } finally {
            this.pending = null;
            this.loading = false;
            this.scroll();
        }
    },
});
