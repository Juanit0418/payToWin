(function(){
    // Código de alertas personalizado
    const alertas = document.querySelectorAll('.alerta');
    const alerta__exito = document.querySelector('.alerta__exito');

    if(alertas.length > 0){
        alertas.forEach( alerta => {
            setTimeout(() => {
                alerta.remove();
            }, 5000);
        });
    }

    if(alerta__exito){
        setTimeout(() => {
            window.location.href = "/login";
        }, 3500);
    }
})();