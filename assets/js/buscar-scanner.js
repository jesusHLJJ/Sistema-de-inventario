let video = null;
let stream = null;
let detector = null;
let escaneando = false;

async function abrirScanner() {

    if (!('BarcodeDetector' in window)) {
        alert("Tu navegador no soporta lector de códigos");
        return;
    }

    const scannerContainer = document.getElementById('scanner-container');
    const videoElement = document.getElementById('video');

    if (!scannerContainer || !videoElement) {
        console.error("No existe el contenedor o el video");
        return;
    }

    scannerContainer.classList.remove('d-none');
    video = videoElement;

    detector = new BarcodeDetector({
        formats: ['ean_13', 'ean_8', 'code_128', 'upc_a']
    });

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: { ideal: "environment" },
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        });

        video.srcObject = stream;
        await video.play();

        escaneando = true;
        detectar();

    } catch (err) {
        console.error("Error cámara:", err);
        alert("No se pudo acceder a la cámara");
    }
}

async function detectar() {
    if (!escaneando) return;

    try {
        const codigos = await detector.detect(video);

        if (codigos.length > 0) {
            const codigo = codigos[0].rawValue;

            const input = document.getElementById('busqueda_general');

            if (input) {
                input.value = codigo;
                input.focus();
                input.closest('form').submit();
            } else {
                const input = document.getElementById('id_producto');
                input.value = codigo;

                document.querySelector('input[name="marca"]').focus();
                return;
            }


            navigator.vibrate?.(200);
            cerrarScanner();
            return;
        }
    } catch (e) {
        console.error("Error detectando código:", e);
    }

    requestAnimationFrame(detectar);
}

function cerrarScanner() {
    escaneando = false;

    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }

    const scannerContainer = document.getElementById('scanner-container');
    if (scannerContainer) {
        scannerContainer.classList.add('d-none');
    }
}
