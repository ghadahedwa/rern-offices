/**
 * زرّ «تثبيت التطبيق» في الشريط العلوي.
 *
 * - أندرويد والكمبيوتر (كروم/إيدج): المتصفح يُطلق `beforeinstallprompt` حين يصير الموقع قابلاً للتثبيت؛
 *   يُلتقط مبكراً في partials/head (window.__pwaDeferred) لأنه قد يسبق تحميل هذه الحزمة، والزرّ يعرض نافذة التثبيت.
 * - آيفون/آيباد: لا تثبيت برمجي في Safari — الزرّ يفتح تعليمات «مشاركة ← إضافة إلى الشاشة الرئيسية».
 * - التطبيق المثبَّت نفسه (display-mode: standalone) لا يعرض الزرّ.
 *
 * ⚠️ التسجيل فوري إن وُجد window.Alpine وإلا عند alpine:init — تبويبٌ مفتوح قبل النشر يحقن الحزمة بعد بدء Alpine
 * (نفس قاعدة attendance-grid).
 */
function registerPwaInstall() {
    window.Alpine.data('pwaInstall', () => ({
        canPrompt: false,
        isIos: false,
        installed: false,
        showIos: false,

        init() {
            const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
            const ua = window.navigator.userAgent;

            this.installed = standalone;
            this.isIos = /iPad|iPhone|iPod/.test(ua) || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
            this.canPrompt = !!window.__pwaDeferred;

            this._onInstallable = () => { this.canPrompt = !!window.__pwaDeferred; };
            this._onInstalled = () => { this.installed = true; this.canPrompt = false; };
            window.addEventListener('pwa:installable', this._onInstallable);
            window.addEventListener('appinstalled', this._onInstalled);
        },

        destroy() {
            window.removeEventListener('pwa:installable', this._onInstallable);
            window.removeEventListener('appinstalled', this._onInstalled);
        },

        get visible() {
            return !this.installed && (this.canPrompt || this.isIos);
        },

        async install() {
            if (this.canPrompt && window.__pwaDeferred) {
                const prompt = window.__pwaDeferred;
                window.__pwaDeferred = null;
                this.canPrompt = false;
                prompt.prompt();
                await prompt.userChoice;
                return;
            }

            if (this.isIos) {
                this.showIos = true;
            }
        },
    }));
}

if (window.Alpine) {
    registerPwaInstall();
} else {
    document.addEventListener('alpine:init', registerPwaInstall);
}
