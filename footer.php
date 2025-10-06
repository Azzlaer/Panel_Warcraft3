    </main>
  </div>
</div>

<!-- JS de Bootstrap y jQuery -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
/*
 * Script para manejar la navegación del panel:
 * - Carga el contenido de cada sección dentro de <main id="main">
 * - Resalta el enlace activo en el menú lateral
 */
$(document).on('click', '.nav-link[data-section]', function (e) {
    e.preventDefault();

    const section = $(this).data('section'); // Ej: "pages/bot_manager"
    // Mensaje de carga
    $('#main').html(
        '<div class="text-center p-5 text-light">⏳ Cargando ' + section + '...</div>'
    );

    // Cambiar clase activa
    $('.sidebar .nav-link').removeClass('active');
    $(this).addClass('active');

    // Cargar sección vía AJAX
    $.get(section + '.php', function (data) {
        $('#main').html(data);
    }).fail(function () {
        $('#main').html(
            '<div class="alert alert-danger">⚠️ Error cargando sección ' + section + '</div>'
        );
    });
});
</script>
</body>
</html>
