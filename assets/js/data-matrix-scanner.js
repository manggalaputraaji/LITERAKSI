import { BrowserMultiFormatReader } from '@zxing/browser';
import { BarcodeFormat, DecodeHintType } from '@zxing/library';

const scanner = document.querySelector('[data-data-matrix-scanner]');
if (scanner) {
    // Semua elemen pemindai berada di dalam satu wadah agar skrip aman di halaman lain.
    const video = scanner.querySelector('video');
    const startButton = scanner.querySelector('[data-scanner-start]');
    const stopButton = scanner.querySelector('[data-scanner-stop]');
    const status = scanner.querySelector('[data-scanner-status]');
    const csrfInput = scanner.querySelector('[name="csrf"]');
    const hints = new Map([[DecodeHintType.POSSIBLE_FORMATS, [BarcodeFormat.DATA_MATRIX]]]);
    const reader = new BrowserMultiFormatReader(hints);
    let controls = null;
    let processing = false;
    if (video) video.hidden = true;

    const setStatus = (message, isError = false) => {
        status.textContent = message;
        status.classList.toggle('is-error', isError);
    };

    const stopScanner = () => {
        if (controls) controls.stop();
        controls = null;
        if (video) {
            video.srcObject = null;
            video.hidden = true;
        }
        startButton.hidden = false;
        stopButton.hidden = true;
    };

    const processToken = async (token) => {
        if (processing) return;
        processing = true;
        // Hentikan kamera sebelum mengirim token agar satu hasil pindai tidak terkirim berulang.
        stopScanner();
        setStatus('Memeriksa kode peminjaman...');
        try {
            const response = await fetch('assets/logic/fungsi.php?action=scan_data_matrix&role=admin', {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: new URLSearchParams({ token, csrf: csrfInput.value })
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Kode tidak dapat diproses.');
            }
            setStatus(result.message);
            window.setTimeout(() => window.location.reload(), 1200);
        } catch (error) {
            console.error('Unable to process scanned Data Matrix.', error);
            setStatus(error instanceof Error ? error.message : 'Kode tidak dapat diproses.', true);
            processing = false;
        }
    };

    startButton.addEventListener('click', async () => {
        processing = false;
        if (video) video.hidden = false;
        setStatus('Meminta akses kamera...');
        try {
            controls = await reader.decodeFromVideoDevice(undefined, video, (result, _error, scanControls) => {
                if (result && !processing) {
                    void processToken(result.getText());
                } else if (scanControls && !controls) {
                    controls = scanControls;
                }
            });
            startButton.hidden = true;
            stopButton.hidden = false;
            setStatus('Arahkan kamera ke kode Data Matrix.');
        } catch (error) {
            console.error('Unable to start Data Matrix scanner.', error);
            stopScanner();
            setStatus('Kamera tidak dapat dibuka. Gunakan konfirmasi manual.', true);
        }
    });

    stopButton.addEventListener('click', () => {
        stopScanner();
        setStatus('Pemindai dihentikan.');
    });
}
