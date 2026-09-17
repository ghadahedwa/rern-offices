/**
 * شبكة تسجيل الحضور — حالة الكشف في المتصفح حتى يُحفظ دفعةً واحدة.
 *
 * ⚠️ لماذا في المتصفح لا في خصائص Livewire: طلبٌ لكل نقرة = مئات الطلبات للشهر الواحد،
 *    وانقطاعٌ في المنتصف يترك نصفه محفوظاً ونصفه لا.
 * ⚠️ لماذا ملفٌ في الحزمة لا `<script>` في القالب: التنقّل بـwire:navigate لا يضمن تشغيل
 *    السكربت قبل تهيئة Alpine للعنصر.
 * ⚠️ **والتسجيل لا يعتمد على `alpine:init` وحده** (بلاغ السيرفر ٢٠٢٦-٠٩-١٧): تبويبٌ مفتوح قبل
 *    النشر ينتقل بـwire:navigate، وLivewire لا يُعيد تحميل الصفحة إلا إن تغيّر **query string**
 *    الأصل لا اسمه — وVite يغيّر الاسم (الهاش). فيحقن الملف الجديد في صفحةٍ بدأ فيها Alpine
 *    فعلاً، و`alpine:init` مضى: «attendanceGrid is not defined»، خلايا بيضاء لا تُعلَّم.
 *    فإن وُجد Alpine يُسجَّل فوراً، وإلا انتظر `alpine:init`.
 *
 * data-config: { rows: [{id, open: ['Y-m-d'], marks: {'Y-m-d': statusId}, reviewed}],
 *                fingerprint, brush, statuses: {id: {name, color}} }
 *
 * ⚠️ الإعداد يُقرأ **مرة واحدة** من `data-config` لا من وسيط `x-data`: الـkeepalive وفلتر
 *    العامل يُعيدان عرض القالب، وتغيّر قيمة `x-data` نفسها قد يُعيد تهيئة المكوّن فتضيع
 *    تغييرات لم تُحفظ. والبصمة المحفوظة هنا هي بصمة لحظة الفتح — وهو المطلوب للمقارنة.
 */
function registerAttendanceGrid() {
    window.Alpine.data('attendanceGrid', () => ({
        config: null,
        marks: {},
        reviewed: {},
        open: {},
        initial: '',
        brush: 0,
        painting: false,
        paintRow: null,
        paintValue: null,
        saving: false,
        showLeave: false,
        leaveUrl: null,

        init() {
            this.config = JSON.parse(this.$el.dataset.config);
            this.brush = this.config.brush;

            for (const row of this.config.rows) {
                this.marks[row.id] = { ...row.marks };
                this.reviewed[row.id] = row.reviewed;
                this.open[row.id] = row.open.length;
            }

            this.initial = this.snapshot();

            this._stopPaint = () => { this.painting = false; };
            this._beforeUnload = (event) => {
                if (this.dirty) {
                    event.preventDefault();
                    event.returnValue = '';
                }
            };
            // ⚠️ الخروج بـwire:navigate لا يمرّ بـbeforeunload — فيُعترض حدثه (قابل للإلغاء)
            this._navigate = (event) => {
                if (this.dirty) {
                    event.preventDefault();
                    this.leaveUrl = event.detail?.url ? String(event.detail.url) : null;
                    this.showLeave = true;
                }
            };

            window.addEventListener('mouseup', this._stopPaint);
            window.addEventListener('beforeunload', this._beforeUnload);
            document.addEventListener('livewire:navigate', this._navigate);
        },

        destroy() {
            window.removeEventListener('mouseup', this._stopPaint);
            window.removeEventListener('beforeunload', this._beforeUnload);
            document.removeEventListener('livewire:navigate', this._navigate);
        },

        /** صورة مرتّبة المفاتيح — ترتيب الإدخال في الكائن لا يُحسب تغييراً. */
        snapshot() {
            const ids = Object.keys(this.marks).sort();

            return JSON.stringify(ids.map((id) => [
                id,
                Object.keys(this.marks[id]).sort().map((date) => [date, this.marks[id][date]]),
                !!this.reviewed[id],
            ]));
        },

        get dirty() {
            return this.snapshot() !== this.initial;
        },

        mark(id, date) {
            return this.marks[id]?.[date] ?? null;
        },

        /**
         * لون الحالة inline — ألوان الحالات من الداتابيز، والفئات المركَّبة لا يراها البناء.
         *
         * ⚠️ «حاضر» **صبغةٌ باهتة من لونها** (‎1f = ١٢٪) لا لونٌ مصمت: مئات خلايا الحضور بأخضر
         *    مصمت تُغرق الغياب والإجازة — والشاشة قائمة على أن الاستثناء وحده يلفت العين.
         */
        cellStyle(id, date) {
            const status = this.config.statuses[this.mark(id, date)];

            return status
                ? `background-color: ${status.color}`
                : `background-color: ${this.config.present}1f`;
        },

        /**
         * حرفان لا حرف: «إ» منفردةً تُقرأ «!» في خليةٍ صغيرة، و«ا» خطٌّ لا حرف.
         * واللون وحده لا يكفي لمن لا يميّز الألوان.
         */
        label(id, date) {
            const status = this.config.statuses[this.mark(id, date)];

            return status ? status.name.slice(0, 2) : '';
        },

        /** الضغط يطبّق الأداة، والضغط على يومٍ معلَّمٍ بها يعيده «حاضر». */
        down(id, date) {
            const current = this.mark(id, date);
            const value = (this.brush === 0 || current === this.brush) ? null : this.brush;

            this.paint(id, date, value);
            this.painting = true;
            this.paintRow = id;
            this.paintValue = value;
        },

        /** السحب داخل صفٍّ واحد — عبورُ الصفوف بالسحب خطأٌ أسهل من أن يُقصد. */
        enter(id, date) {
            if (this.painting && this.paintRow === id) {
                this.paint(id, date, this.paintValue);
            }
        },

        paint(id, date, value) {
            if (value === null) {
                delete this.marks[id][date];
            } else {
                this.marks[id][date] = value;
            }
        },

        count(id, status) {
            return Object.values(this.marks[id] ?? {}).filter((value) => value === status).length;
        },

        present(id) {
            return this.open[id] - Object.keys(this.marks[id] ?? {}).length;
        },

        reviewedCount(ids) {
            return ids.filter((id) => this.reviewed[id]).length;
        },

        /** زرّ المقر: يعلّم الصفوف المعروضة كلها، أو يلغيها إن كانت كلها معلَّمة. */
        toggleReviewAll(ids) {
            const value = this.reviewedCount(ids) !== ids.length;

            ids.forEach((id) => { this.reviewed[id] = value; });
        },

        async save() {
            if (this.saving || !this.dirty) {
                return;
            }

            this.saving = true;

            try {
                await this.$wire.save(this.marks, this.reviewed, this.config.fingerprint);
            } finally {
                this.saving = false;
            }
        },

        /** يُعيد بناء الكشف من الداتابيز (مفتاح الشبكة يتغيّر). */
        discard() {
            this.initial = this.snapshot();
            this.$wire.reload();
        },

        leave() {
            this.initial = this.snapshot();
            this.showLeave = false;

            if (this.leaveUrl) {
                window.Livewire.navigate(this.leaveUrl);
            }
        },
    }));
}

if (window.Alpine) {
    registerAttendanceGrid();
} else {
    document.addEventListener('alpine:init', registerAttendanceGrid);
}
