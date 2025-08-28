document.addEventListener("DOMContentLoaded", () => {
    const sliderButton = document.getElementById("sliderButton");
    const sliderTrack = document.querySelector(".sliderTrack");
    let isDragging = false;
    let startX = 0;

    const maxOffset = sliderTrack.offsetWidth - sliderButton.offsetWidth;

    sliderButton.addEventListener("mousedown", (e) => {
        isDragging = true;
        startX = e.clientX - sliderButton.offsetLeft;
    });

    document.addEventListener("mousemove", (e) => {
        if (!isDragging) return;
        let offset = e.clientX - startX;
        if (offset < 0) offset = 0;
        if (offset > maxOffset) offset = maxOffset;
        sliderButton.style.left = offset + "px";

        if (offset >= maxOffset) {
            verifyHuman();
            isDragging = false;
        }
    });

    document.addEventListener("mouseup", () => {
        if (!isDragging) return;
        sliderButton.style.left = "0px";
        isDragging = false;
    });

    function verifyHuman() {
        fetch("/common/verificar_humano.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "verify" }),
        })
            .then((res) => res.json())
            .then((data) => {
                if (data.verified) {
                    alert("✅ Verificación completada");
                    window.location.reload();
                } else {
                    alert("❌ No autorizado");
                    sliderButton.style.left = "0px";
                }
            })
            .catch(() => {
                alert("⚠ Error en la verificación");
                sliderButton.style.left = "0px";
            });
    }
});
