<?php

/**
 * Validador Pro Final: Sincronización al Guardar (Submit)
 * Coincidencia exacta con Cron Job: IVA 1.16, Factor 0.96 y TC Dinámico.
 */

// 1. PROCESAMIENTO AJAX PARA CÁLCULO DE PRECIOS
add_action('wp_ajax_check_sku_exists', function() {
    global $wpdb;

    $sku = sanitize_text_field($_POST['sku']);
    $precio_base_usd = floatval($_POST['precio_syscom']);
    $token = get_option('sws_syscom_token', '');
    
    // Obtener Tipo de Cambio dinámico
    $tipo_cambio = 1.0;
    $response_tc = wp_remote_get("https://developers.syscom.mx/api/v1/tipocambio", [
        'headers' => ['Authorization' => 'Bearer ' . $token]
    ]);
    
    if (!is_wp_error($response_tc) && wp_remote_retrieve_response_code($response_tc) === 200) {
        $body_tc = json_decode(wp_remote_retrieve_body($response_tc), true);
        $tipo_cambio = isset($body_tc['normal']) ? floatval($body_tc['normal']) : 1.0;
    }

    // Factores de Intrax
    $iva_multiplicador = 1.16;
    $otro_factor = 0.96;
    $precio_convertido = $precio_base_usd * $tipo_cambio * $iva_multiplicador * $otro_factor;

    $product_id = wc_get_product_id_by_sku($sku);
    $exists = $product_id ? true : false;

    // Consulta de Markup en tabla kmq_intrax_precios
    $tabla_precios = "kmq_intrax_precios"; 
    $regla = $wpdb->get_row($wpdb->prepare(
        "SELECT venta_publico, integrador FROM $tabla_precios WHERE %f >= desde AND %f <= hasta LIMIT 1",
        $precio_convertido, $precio_convertido
    ));

    if ($regla) {
        $porcentaje_reg = floatval($regla->venta_publico);
        $porcentaje_who = floatval($regla->integrador);
        $factor_p = ($porcentaje_reg < 1) ? (1 + $porcentaje_reg) : $porcentaje_reg;
        $factor_i = ($porcentaje_who < 1) ? (1 + $porcentaje_who) : $porcentaje_who;

        $final_regular = $precio_convertido * $factor_p;
        $final_wholesale = $precio_convertido * $factor_i;
        $debug_msg = "TC: $tipo_cambio";
    } else {
        $final_regular = $precio_convertido;
        $final_wholesale = $precio_convertido;
        $debug_msg = "TC: $tipo_cambio (Sin rango)";
    }

    wp_send_json_success([
        'exists' => $exists,
        'regular_price' => number_format($final_regular, 2, '.', ''),
        'wholesale_price' => number_format($final_wholesale, 2, '.', ''),
        'percent_reg' => $porcentaje_reg ?? 0,
        'percent_who' => $porcentaje_who ?? 0,
        'debug' => $debug_msg
    ]);
    wp_die();
});

// 2. PERSISTENCIA DE META DATA AL GUARDAR
add_action('save_post_product', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (isset($_POST['_hidden_syscom_product_id'])) {
        update_post_meta($post_id, '_producto_id_syscom_', sanitize_text_field($_POST['_hidden_syscom_product_id']));
    }
    if (isset($_POST['_hidden_syscom_precio_original'])) {
        update_post_meta($post_id, '_precio_original_syscom', sanitize_text_field($_POST['_hidden_syscom_precio_original']));
    }
});

// 3. INTERFAZ Y LÓGICA JAVASCRIPT (SÓLO EN SUBMIT)
add_action('admin_enqueue_scripts', function($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'])) return;
    $token = get_option('sws_syscom_token', '');
    ?>
    <div id="syscom-validator-loader" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.9); z-index:999999; align-items:center; justify-content:center; flex-direction:column; color:#fff; font-family:sans-serif;">
        <div style="width:40px; height:40px; border:4px solid rgba(255,255,255,0.1); border-top:4px solid #3b82f6; border-radius:50%; animation:sysspin 0.8s linear infinite; margin-bottom:15px;"></div>
        <div style="font-weight:600; letter-spacing:1px;">SINCRONIZANDO CON SYSCOM...</div>
    </div>
    <style>@keyframes sysspin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>
    <?php
    $js_code = "
    (function($) {
        'use strict';
        $(function() {
            var yaSincronizado = false;
            $('#post').on('submit', function(e) {
                var \$form = $(this);
                var modeloBusqueda = $('#_sku').val().trim();
                if (modeloBusqueda === '' || yaSincronizado) return true;

                e.preventDefault();
                $('#syscom-validator-loader').css('display', 'flex');

                $.ajax({
                    url: 'https://developers.syscom.mx/api/v1/productos?busqueda=' + encodeURIComponent(modeloBusqueda),
                    method: 'GET',
                    headers: { 'Authorization': 'Bearer " . esc_js($token) . "', 'Accept': 'application/json' },
                    success: function(response) {
                        var producto = response.productos ? response.productos.find(p => p.modelo.toUpperCase() === modeloBusqueda.toUpperCase()) : null;
                        if (producto) {
                            $.ajax({
                                url: ajaxurl,
                                method: 'POST',
                                data: {
                                    action: 'check_sku_exists',
                                    sku: producto.modelo,
                                    precio_syscom: producto.precios.precio_descuento
                                },
                                success: function(res) {
                                    $('#_regular_price').val(res.data.regular_price).trigger('change');
                                    $('#wholesale_customer_wholesale_price').val(res.data.wholesale_price).trigger('change');
                                    $('#_stock').val(producto.total_existencia).trigger('change');
                                    if (!$('#_manage_stock').is(':checked')) $('#_manage_stock').click();
                                    if ($('#title').val() === '') $('#title').val(producto.titulo);

                                    console.log('--- 📊 REPORTE INTRAX ---');
                                    console.log('💵 TC: ' + res.data.debug.split('TC: ')[1]);
                                    console.log('📈 Markups: Publico ' + res.data.percent_reg + '% | Integrador ' + res.data.percent_who + '%');
                                    console.log('💰 Final: PVP $' + res.data.regular_price + ' | WHO $' + res.data.wholesale_price);

                                    \$form.append('<input type=\"hidden\" name=\"_hidden_syscom_product_id\" value=\"' + producto.producto_id + '\">');
                                    \$form.append('<input type=\"hidden\" name=\"_hidden_syscom_precio_original\" value=\"' + producto.precios.precio_descuento + '\">');

                                    yaSincronizado = true;
                                    $('#syscom-validator-loader').hide();
                                    \$form.submit();
                                }
                            });
                        } else {
                            yaSincronizado = true;
                            $('#syscom-validator-loader').hide();
                            \$form.submit();
                        }
                    },
                    error: function() { yaSincronizado = true; $('#syscom-validator-loader').hide(); \$form.submit(); }
                });
            });
        });
    })(jQuery);";
    wp_add_inline_script('jquery', $js_code);
}, 100);