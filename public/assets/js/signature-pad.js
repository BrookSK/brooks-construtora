/**
 * SignaturePad reutilizável — assinatura desenhada em <canvas>.
 * Uso:
 *   const pad = new SignaturePad(canvasEl, { hiddenInput: inputEl });
 *   pad.isEmpty();   // true se nada foi desenhado
 *   pad.toDataURL(); // PNG base64
 *   pad.clear();
 *   pad.sync();      // grava o dataURL no hiddenInput (se informado)
 *
 * Também suporta inicialização automática:
 *   SignaturePad.autoInit();  // inicializa todos os <canvas class="signature-canvas" data-input="#id">
 */
class SignaturePad {
    constructor(canvas, options = {}) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.hiddenInput = options.hiddenInput || null;
        this.drawing = false;
        this.dirty = false;
        this._bind();
        // Redimensiona após o layout estabilizar
        setTimeout(() => this.resize(), 100);
        window.addEventListener('resize', () => this.resize());
    }

    resize() {
        const ratio = window.devicePixelRatio || 1;
        // Preserva o conteúdo atual
        const data = this.dirty ? this.canvas.toDataURL() : null;
        this.canvas.width = this.canvas.offsetWidth * ratio;
        this.canvas.height = this.canvas.offsetHeight * ratio;
        this.ctx.scale(ratio, ratio);
        this.ctx.lineWidth = 2;
        this.ctx.lineCap = 'round';
        this.ctx.strokeStyle = '#000';
        if (data) {
            const img = new Image();
            img.onload = () => this.ctx.drawImage(img, 0, 0, this.canvas.offsetWidth, this.canvas.offsetHeight);
            img.src = data;
        }
    }

    _pos(e) {
        const r = this.canvas.getBoundingClientRect();
        const p = e.touches ? e.touches[0] : e;
        return { x: p.clientX - r.left, y: p.clientY - r.top };
    }

    _bind() {
        const start = (e) => { this.drawing = true; this.dirty = true; const p = this._pos(e); this.ctx.beginPath(); this.ctx.moveTo(p.x, p.y); e.preventDefault(); };
        const move = (e) => { if (!this.drawing) return; const p = this._pos(e); this.ctx.lineTo(p.x, p.y); this.ctx.stroke(); e.preventDefault(); };
        const end = () => { this.drawing = false; };
        this.canvas.addEventListener('mousedown', start);
        this.canvas.addEventListener('mousemove', move);
        this.canvas.addEventListener('mouseup', end);
        this.canvas.addEventListener('mouseleave', end);
        this.canvas.addEventListener('touchstart', start);
        this.canvas.addEventListener('touchmove', move);
        this.canvas.addEventListener('touchend', end);
    }

    isEmpty() { return !this.dirty; }

    toDataURL() { return this.canvas.toDataURL('image/png'); }

    clear() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.dirty = false;
        if (this.hiddenInput) this.hiddenInput.value = '';
    }

    sync() {
        if (this.hiddenInput) this.hiddenInput.value = this.dirty ? this.toDataURL() : '';
        return this.dirty;
    }

    static autoInit() {
        const pads = {};
        document.querySelectorAll('canvas.signature-canvas').forEach(c => {
            const sel = c.dataset.input;
            const input = sel ? document.querySelector(sel) : null;
            pads[c.id] = new SignaturePad(c, { hiddenInput: input });
        });
        return pads;
    }
}
