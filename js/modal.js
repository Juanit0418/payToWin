const modal = {
    error: async (title, text, tipo) => {
        try {
            const response = await fetch(window.ROUTES.MODAL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ title, text, tipo }),
            });

            if (response.ok) {
                const modalHtml = await response.text();

                let container = document.getElementById("modalContainer");
                if (!container) {
                    container = document.createElement("div");
                    container.id = "modalContainer";
                    document.body.appendChild(container);
                }
                container.innerHTML = modalHtml;

                const closeBtn = container.querySelector(".close");
                if (closeBtn) {
                    closeBtn.addEventListener("click", () => {
                        container.remove();
                    });
                }
            }
        } catch (error) {
            console.warn("Error al ejecutar el modal", error);
        }
    },
};
