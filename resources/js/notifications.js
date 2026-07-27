// resources/js/notifications.js
// Daftarkan di resources/js/app.js:
//   import './notifications.js'

window.notificationBell = function () {
    return {
        // ── State ────────────────────────────────────────────────────────────
        open:          false,
        loading:       false,
        notifications: [],
        counts:        { total: 0, rental_new: 0, rental_return: 0, rental_late: 0, payment: 0, reminder: 0, system: 0 },
        filter:        'all',
        page:          1,
        hasMore:       false,
        pollTimer:     null,
        hasFetched:    false,

        tabs: [
            { key: 'all',           label: 'Semua'        },
            { key: 'rental_late',   label: 'Telat'        },
            { key: 'reminder',      label: 'Reminder'     },
            { key: 'rental_return', label: 'Pengembalian' },
            { key: 'payment',       label: 'Pembayaran'   },
            { key: 'system',        label: 'Sistem'       },
        ],

        // ── Init ─────────────────────────────────────────────────────────────
        init() {
            this.fetchCount()
            // Polling badge setiap 30 detik
            this.pollTimer = setInterval(() => this.fetchCount(), 30_000)

            // Cleanup saat komponen dihancurkan
            this.$el.addEventListener('alpine:destroy', () => {
                clearInterval(this.pollTimer)
            })
        },

        // ── Toggle panel ─────────────────────────────────────────────────────
        toggle() {
            this.open = !this.open
            if (this.open && !this.hasFetched) {
                this.hasFetched = true
                this.fetchNotifications()
            }
        },

        // ── Fetch hanya count (ringan, untuk badge) ───────────────────────────
        async fetchCount() {
            try {
                const res = await fetch('/notifications/count', {
                    headers: this._headers(),
                })
                if (!res.ok) return
                this.counts = await res.json()
            } catch { /* silent */ }
        },

        // ── Fetch list notifikasi ─────────────────────────────────────────────
        async fetchNotifications(append = false) {
            this.loading = true
            try {
                const params = new URLSearchParams({ page: this.page })
                if (this.filter !== 'all') params.set('type', this.filter)

                const res = await fetch(`/notifications/data?${params}`, {
                    headers: this._headers(),
                })
                if (!res.ok) throw new Error('Request gagal')

                const json = await res.json()

                this.notifications = append
                    ? [...this.notifications, ...json.notifications]
                    : json.notifications

                this.hasMore = json.notifications.length === 20
                this.counts  = json.counts
            } catch (e) {
                console.error('Gagal memuat notifikasi:', e)
            } finally {
                this.loading = false
            }
        },

        // ── Tandai satu dibaca ────────────────────────────────────────────────
        async onItemClick(notif) {
            // Redirect jika ada action_url
            if (notif.action_url) {
                window.location.href = notif.action_url
                return
            }

            if (notif.is_read) return

            // Optimistic update
            const idx = this.notifications.findIndex(n => n.id === notif.id)
            if (idx !== -1) this.notifications[idx].is_read = true
            this.counts.total = Math.max(0, this.counts.total - 1)

            try {
                await fetch(`/notifications/${notif.id}/read`, {
                    method:  'POST',
                    headers: this._headers(),
                })
            } catch {
                await this.fetchNotifications()
            }
        },

        // ── Tandai semua dibaca ───────────────────────────────────────────────
        async markAllRead() {
            this.notifications = this.notifications.map(n => ({ ...n, is_read: true }))
            this.counts.total  = 0

            try {
                const body = this.filter !== 'all' ? JSON.stringify({ type: this.filter }) : '{}'
                const res = await fetch('/notifications/read-all', {
                    method:  'POST',
                    headers: this._headers(),
                    body,
                })
                const json = await res.json()
                this.counts = json.counts
            } catch {
                await this.fetchNotifications()
            }
        },

        // ── Hapus satu ────────────────────────────────────────────────────────
        async deleteNotif(id) {
            this.notifications = this.notifications.filter(n => n.id !== id)

            try {
                await fetch(`/notifications/${id}`, {
                    method:  'DELETE',
                    headers: this._headers(),
                })
                await this.fetchCount()
            } catch { /* silent */ }
        },

        // ── Ganti filter ──────────────────────────────────────────────────────
        changeFilter(key) {
            this.filter = key
            this.page   = 1
            this.hasMore = false
            this.fetchNotifications(false)
        },

        // ── Load more ─────────────────────────────────────────────────────────
        loadMore() {
            if (!this.loading && this.hasMore) {
                this.page++
                this.fetchNotifications(true)
            }
        },

        // ── Helpers ───────────────────────────────────────────────────────────
        tabCount(key) {
            if (key === 'all') return this.counts.total
            return this.counts[key] ?? 0
        },

        iconBg(type) {
            const map = {
                rental_new:    'notif-icon-wrap ni-blue',
                rental_return: 'notif-icon-wrap ni-green',
                rental_late:   'notif-icon-wrap ni-red',
                payment:       'notif-icon-wrap ni-emerald',
                reminder:      'notif-icon-wrap ni-amber',
                system:        'notif-icon-wrap ni-slate',
            }
            return map[type] ?? 'notif-icon-wrap ni-slate'
        },

        dotColor(type) {
            const map = {
                rental_new:    'notif-dot dot-blue',
                rental_return: 'notif-dot dot-green',
                rental_late:   'notif-dot dot-red',
                payment:       'notif-dot dot-emerald',
                reminder:      'notif-dot dot-amber',
                system:        'notif-dot dot-slate',
            }
            return map[type] ?? 'notif-dot dot-slate'
        },

        _headers() {
            return {
                'Content-Type':     'application/json',
                'Accept':           'application/json',
                'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            }
        },
    }
}