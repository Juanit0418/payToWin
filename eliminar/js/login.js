document.getElementById("loginForm").addEventListener("submit", async (e) => {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    try {
        const res = await fetch(window.ROUTES.LOGIN, {
            method: "POST",
            body: formData,
        });

        const data = await res.json();

        if (!res.ok) {
            modal.error(data.title, data.error, data.tipo);
            return;
        }

        if (data.success) {
            window.location.reload();
        } else {
            modal.error("Error", "Respuesta inesperada del servidor", "error");
        }
    } catch (error) {
        modal.error("Error", "Error de red o servidor.", "error");
    }
});
