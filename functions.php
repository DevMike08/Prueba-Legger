<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );
         
if ( !function_exists( 'child_theme_configurator_css' ) ):
    function child_theme_configurator_css() {
        wp_enqueue_style( 'chld_thm_cfg_child', trailingslashit( get_stylesheet_directory_uri() ) . 'style.css', array( 'hello-elementor','hello-elementor','hello-elementor-theme-style','hello-elementor-header-footer' ) );
    }
endif;
add_action( 'wp_enqueue_scripts', 'child_theme_configurator_css', 10 );

// END ENQUEUE PARENT ACTION


//Caputura de Leads

// Capturar datos del formulario de Elementor y guardarlos en un archivo CSV
add_action('elementor_pro/forms/new_record', 'guardar_datos_formulario_elementor', 10, 2);

function guardar_datos_formulario_elementor($record, $handler) {
    // Obtener los campos del formulario
    $fields = $record->get('fields');

    // Obtener la IP del cliente
    $ip_cliente = $_SERVER['REMOTE_ADDR'];

    // Ruta del archivo CSV
    $archivo_csv = WP_CONTENT_DIR . '/uploads/formulario_elementor.csv';

    // Abrir o crear el archivo CSV en modo escritura
    $archivo = fopen($archivo_csv, 'a');

    // Escribir los encabezados del CSV si el archivo está vacío
    if (filesize($archivo_csv) == 0) {
        fputcsv($archivo, array('Nombre del Cliente', 'NIT', 'Nombre del Punto', 'Nombre del Grupo', 'Ciudad', 'Promotor', 'RTC', 'Capital', 'IP del Cliente'));
    }

    // Escribir los datos del formulario en el archivo CSV
    fputcsv($archivo, array(
        $fields['name_cli']['value'],
        $fields['nit']['value'],
        $fields['name_punt']['value'],
        $fields['name_group']['value'],
        $fields['city']['value'],
        $fields['promotor']['value'],
        $fields['rtc']['value'],
        $fields['capital']['value'],
        $ip_cliente
    ));

    // Cerrar el archivo CSV
    fclose($archivo);
}

// Añadir un botón en el menú de "Ajustes"
add_action('admin_menu', 'agregar_boton_ajustes');

function agregar_boton_ajustes() {
    add_options_page(
        'Descargar CSV', // Título de la página
        'Descargar CSV', // Título del menú
        'manage_options', // Capacidad requerida
        'descargar-csv', // Identificador de la página
        'descargar_csv_page' // Función de callback para mostrar la página
    );
}

// Función de callback para mostrar la página de descarga de CSV
function descargar_csv_page() {
    ?>
    <div class="wrap">
        <h2>Descargar CSV</h2>
        <p>Aquí puedes descargar el archivo CSV generado por el formulario de Elementor.</p>
        <p><a href="<?php echo site_url('/wp-content/uploads/formulario_elementor.csv'); ?>" class="button button-primary">Descargar CSV</a></p>
    </div>
    <?php
}

//	SERVICIO REST
	

// Capturar datos del formulario de Elementor y enviarlos a un servicio REST
add_action('elementor_pro/forms/new_record', 'guardar_y_enviar_datos_formulario_elementor', 10, 2);

function guardar_y_enviar_datos_formulario_elementor($record, $handler) {
    // Obtener los campos del formulario
    $fields = $record->get('fields');

    // Construir la información del lead en formato JSON
    $lead_info = json_encode(array(
        'nombre_cliente' => $fields['name_cli']['value'],
        'nit' => $fields['nit']['value'],
        'nombre_punto' => $fields['name_punt']['value'],
        'nombre_grupo' => $fields['name_group']['value'],
        'ciudad' => $fields['city']['value'],
        'promotor' => $fields['promotor']['value'],
        'rtc' => $fields['rtc']['value'],
        'capital' => $fields['capital']['value']
    ));

    // Construir el identificador con el formato AAAA-mm-dd
    $identificador = date('Y-m-d');

    // Construir el tipo con la frase "prueba legger" seguido de su nombre
    $tipo = 'prueba legger Miguel Escobar';

    // Construir la solicitud POST
    $request_args = array(
        'headers' => array(
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode(array(
            'identificador' => $identificador,
            'tipo' => $tipo,
            'info' => $lead_info
        ))
    );

    // Realizar la solicitud POST al servicio REST
    $response = wp_remote_post('https://app-edu-recaudocursos-php.azurewebsites.net/api-cursos/public/crear-logs', $request_args);

    // Verificar si la solicitud fue exitosa y manejar la respuesta
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        // La solicitud fue exitosa
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Verificar la respuesta del servicio
        if ($data['result'] === 1) {
            // Registro exitoso, no hacer nada adicional
        } else {
            // Hubo un error en el servicio, registrar el error si es necesario
            error_log('Error al enviar datos del lead al servicio REST: ' . $data['error']);
        }
    } else {
        // Hubo un error al realizar la solicitud
        $error_message = is_wp_error($response) ? $response->get_error_message() : 'Error desconocido al enviar datos del lead al servicio REST';
        error_log($error_message);
    }
}
