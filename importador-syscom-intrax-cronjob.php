<?php

set_time_limit(0);
require __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Client;
use Automattic\WooCommerce\HttpClient\HttpClientException;

// ====================================================================================
// Configuración Base de Datos MySQL (INTRAX)
// ====================================================================================


// define('DB_HOST', 'localhost');
define('DB_HOST', '35.215.119.31');
define('DB_NAME', 'dby49adiueiih2');
define('DB_USER', 'upykafnjjc4ko');
define('DB_PASS', 'intrax-melany-bd-66$');

$url_API_woo = 'https://www.intrax.mx/';
$ck_API_woo = 'ck_13d7f990bae1675c0d27e577c9813c608be80b61';
$cs_API_woo = 'cs_b7eb8a076de147e05507d2596e05d7bf18efa93b';

// ====================================================================================
// Lógica de Inicio
// ====================================================================================


print("\n");
status_message("CRON JOB INTRAX: " . date("Y-m-d h:i:sa"));

// --------------TESTER PARAMS

/*
$parametro_api_rest = [
'marca=hikvision&categoria=65818',
];  // 6 productos

*/

$parametro_api_rest = [
'marca=westerndigitalwd&categoria=1340',
];  // 16 productos



/*

$parametro_api_rest = [
    'marca=hiksemibyhikvision',
    'marca=witek',
    'marca=ruijie',
    'marca=tplink',
    'marca=ezviz',
    'categoria=2929,2927,66457',
    'categoria=66492,66491',
    'marca=jimiiot&categoria=66491,65657',
    'marca=ugreen',
    'marca=sfire',
    'marca=yonusa',
    'categoria=66565',
    'categoria=1380',
    'marca=linkedprobyepcom&categoria=65815,1421,66407',
    'marca=precision',
    'marca=ecoflow',
    'marca=accesspro&categoria=2925',
    'marca=hikvision&categoria=2925',
    'marca=hikvision&categoria=351',
    'marca=hikvision&categoria=37',
    'marca=hikvision&categoria=214',
    'marca=hikvision&categoria=66447',
    'marca=hikvision&categoria=66564',
    'marca=hikvision&categoria=66566',
    'marca=hikvision&categoria=66584',
    'marca=hikvision&categoria=66533',
    'marca=hikvision&categoria=1926',
    'marca=hikvision&categoria=65890',
    'marca=hikvision&categoria=427',
    'marca=hikvision&categoria=469',
    'marca=hikvision&categoria=65818',
    'marca=hikvision&categoria=66532',
    'marca=hikvision&categoria=66442',
    'marca=hikvision&categoria=66491',
    'marca=linkedpro&categoria=66495',
    'marca=linkedpro&categoria=65811'
];
*/

// 1. Obtenemos el total de elementos para la referencia de progreso
$total_parametros = count($parametro_api_rest);
$contador_parametros = 1;

foreach ($parametro_api_rest as $una_marca) {

    status_message("\n Procesando parámetro $contador_parametros de $total_parametros");
    status_message("\n PROCESANDO MARCA: $una_marca");
    consulta_paginado_api_rest($una_marca);
    $contador_parametros++;
    
}

// Mensaje final de confirmación de éxito

print("\n");
status_message("OPERACIÓN COMPLETADA: $total_parametros parámetros procesados correctamente.");
status_message("hora: " . date("Y-m-d H:i:s"));
print("\n");


// ====================================================================================
// Funciones de Negocio
// ====================================================================================

function consulta_paginado_api_rest($parametro_api_rest) {
    global $url_API_woo, $ck_API_woo, $cs_API_woo;

    // --- OPTIMIZACIÓN: Carga inventario UNA VEZ ---
    print("\n");
    status_message("Cargando inventario local completo en memoria...");
    $inventario_local = obtener_inventario_local_completo();
    status_message("Inventario cargado (" . count($inventario_local) . " productos). Iniciando sincronizacion...");

    $pagina_actual = 1;
    $totalPaginas = 1; 

    $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImY1ZTc3MGUyZTYyY2MyM2VkMjk4ODk2YWVhNzM1ZTczNGZlNThlMzk5ZjQ0ZjhiZmMyY2U3NDU2MzFmMmJkZThjMjBiYWU1OTgxNDA5MDkwIn0.eyJhdWQiOiJQTmdiVnFpNmdQc3pRTTl5OVBuYWd2Z0lCM1ZhMW5uTSIsImp0aSI6ImY1ZTc3MGUyZTYyY2MyM2VkMjk4ODk2YWVhNzM1ZTczNGZlNThlMzk5ZjQ0ZjhiZmMyY2U3NDU2MzFmMmJkZThjMjBiYWU1OTgxNDA5MDkwIiwiaWF0IjoxNzY0ODc3NjAzLCJuYmYiOjE3NjQ4Nzc2MDMsImV4cCI6MTc5NjQxMzYwMywic3ViIjoiIiwic2NvcGVzIjpbXX0.LRYnmspeBFrg8HpH1HtM3-SjNnhd90zOph6DTfb8y2uJTwhcW77VrnV1xmjlSfEyrKZuGb6XNfbb-Dl9Y2FvCLzr4JWwoxMFBE3BmDo6t0eNCZ0WrogWhe-u7D5LwUkuZBM2ZjmQS3UQJExSlCMbYEQuZEhzA6hFH7syGrrU6L-hKxWETaiviNIHOPsjAclMGIBnuSBOR2uPiyxJoljPwEmUs3ko9m1zlBMnBYJaQLj_tsRz4YHSTyGOtonevpG2vCKUTcUAQwdYTup8qPFa8CV6mHEO9SvEjpbBdL3BdgheSApCahYi1WHdsX0tkJbe5gHpbMD9hdbrDGZG93ypIfx_m10YarzITghOkFRqx3kAeTFOuCNeLcyHDTLZ9b1x3bsMOiprAIEgT-OS5-IIj1ja2EBwkYHcQKRUtMSEFC5SEtU41qzVckCwYv9hEqBLOBO6SLV50-VsbOU9KTfCJl9Gd9wzyPkIpwUVHNsYqIiE59LgBhrK4NTnv2cIDdoVc6_fPjU3CteyqWEtaAU90PBd9zwvy0-bu2sxE48jssQIaM1r3U-xL2ENXHzxIgU5tosBMWVSnoIRvtDPg5fhCdSGxHL2ZBHNs7KCWUBg38PVjCJWezIr-PtF23MTZrk0EKXsCZss5A9MlGQstcenpcnxP9BXy1rBpTK-GlGc0FQ";


        // ... después de obtener tu $access_token ...

        $tipo_cambio = obtener_tipo_cambio_syscom($token);
        status_message("💵 Tipo de cambio obtenido: " . $tipo_cambio);

    do {
        $url_API = "https://developers.syscom.mx/api/v1/productos?" . $parametro_api_rest . "&pagina=" . $pagina_actual;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_API);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
        
        status_message("LECTURA API ORIGEN (Pagina $pagina_actual): " . date("h:i:sa"));
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            status_message('Error en API origen en pagina ' . $pagina_actual);
            break; 
        }

        $data_origin = json_decode($response, true);

        if ($pagina_actual === 1) {
            $totalPaginas = (int)$data_origin['paginas'];
            $cantidad_total = (int)$data_origin['cantidad'];
            print("\n");
            status_message("Resumen Global: " . $cantidad_total . " productos en " . $totalPaginas . " paginas.");
            print("\n");
        }

        $items_origin = $data_origin['productos'];
        
        if (!empty($items_origin)) {
            status_message("Procesando pagina $pagina_actual de $totalPaginas...");
            print("\n");
            procesar_batch_woocommerce($url_API_woo, $ck_API_woo, $cs_API_woo, $items_origin, $parametro_api_rest, $inventario_local, $tipo_cambio);
        } else {
            status_message("Pagina $pagina_actual vacia.");
        }

        $pagina_actual++;
        if($pagina_actual <= $totalPaginas) sleep(1);

    } while ($pagina_actual <= $totalPaginas);
}

function procesar_batch_woocommerce($url_API_woo, $ck_API_woo, $cs_API_woo, $items_origin, $parametro_api_rest, $inventario_local, $tipo_cambio) {
    $woocommerce = new Client($url_API_woo, $ck_API_woo, $cs_API_woo, [
        'wp_api' => true,
        'version' => 'wc/v3',
        'timeout' => 8400,
        'query_string_auth' => true
    ]);

    // $prefix = 'it';
    $hora_inicio = date("h:i:sa");
    
    $batch = ['create' => [], 'update' => []];
    $procesados = 0;

    foreach ($items_origin as $product) {
        
        // =========================================================================
        // 1. LÓGICA DE SKU (MODELO)
        // =========================================================================

        $modelo_original = (string)$product['modelo'];

        /*

        $sku_limpio = str_replace(['/', '\\'], '-', $modelo_original);
        $sku = trim($sku_limpio);
        */

        $sku = trim($modelo_original);
    
        // =========================================================================
        // 2. LÓGICA DE PRECIOS Y STOCK
        // =========================================================================


        $precio_syscom = (float)$product['precios']['precio_descuento'];
        
        // $markup = obtener_markup_db($precio_syscom);

        $markup = obtener_markup_db($precio_syscom, $tipo_cambio);

        $factor_publico = ($markup->venta_publico < 1) ? (1 + $markup->venta_publico) : $markup->venta_publico;
        $factor_integrador = ($markup->integrador < 1) ? (1 + $markup->integrador) : $markup->integrador;        

        $precio_final = number_format(($markup->precio_convertido * $factor_publico), 2, '.', '');
        $precio_integrador = number_format(($markup->precio_convertido * $factor_integrador), 2, '.', '');

        // Rango de precio para auditoría y referencia (solo informativo, no se usa en cálculos)
        $rango_precio = $markup->rango;

        $stock_syscom = (int)$product['total_existencia'];

        // ¿EXISTE EL PRODUCTO LOCALMENTE?
        
            if (!isset($inventario_local[$sku])) 
        
            {


            // ========================================
            // PRODUCTO NUEVO (CREATE)
            // ========================================

            
            /*

            $batch['create'][] = [
                'sku'            => $sku,
                'name'           => $product['titulo'], 
                'regular_price'  => $precio_final,
                'manage_stock'   => true,
                'stock_quantity' => $stock_syscom,
                'status'         => 'draft',
                'description'    => $product['sat_description'],
                'images'         => [['src' => $product['img_portada']]],
                'meta_data'      => [
                    
                    ['key' => '_fecha_creacion_sync', 'value' => date("Y-m-d H:i:s")],
                    ['key' => '_producto_syscom_api', 'value' => date("Y-m-d H:i:s")],
                    ['key' => '_modelo_original_syscom', 'value' => $modelo_original],
                    ['key' => '_precio_original_syscom', 'value' => $precio_syscom],
                    ['key' => 'wholesale_customer_have_wholesale_price', 'value' => 'yes'],
                    ['key' => 'wholesale_customer_wholesale_price', 'value' => $precio_integrador]
                    
                ]
            ];
            
            */

            

        } 
        else

        {
            // ========================================
            // PRODUCTO EXISTENTE (UPDATE)
            // ========================================
            $local = $inventario_local[$sku];

            // 1. Mapeo de variables
            $precio_actual_web = (float)$local['precio'];
            $stock_actual_web  = (int)$local['stock'];
            $precio_final_calculado = (float)$precio_final;
            $nuevo_stock = $stock_syscom;

            // 2. Determinar Flags de cambio
            $cambio_precio = (abs($precio_actual_web - $precio_final_calculado) > 0.01);
            $cambio_stock  = ($stock_actual_web !== $nuevo_stock);

            // Inicializamos meta_data
            $meta_data = [];

            // 3. --- AUDITORÍA ---
            if ($cambio_precio && $cambio_stock) {
                status_message("CAMBIO DOBLE ($sku): Precio y Stock variaron.");
                status_message("hora: " . date("Y-m-d H:i:s"));
                print("\n");

                $meta_data[] = [
                    'key'   => 'cambios_precio_y_stock',
                    'value' => [
                        'fecha' => date("Y-m-d H:i:s"),
                        'precio' => "anterior: $precio_actual_web | actual: $precio_final_calculado",
                        'stock'  => "anterior: $stock_actual_web | actual: $nuevo_stock"
                    ]
                ];  
            } else {
                if ($cambio_precio) {

                    print("\n");
                    status_message("CAMBIO PRECIO ($sku): $precio_actual_web -> $precio_final_calculado");
                    print("\n");
                    status_message("precio syscom: " . $precio_syscom);
                    status_message("tipo de cambio: " . $tipo_cambio);
                    print("\n");
                    status_message("precio_base_local ó convertido: " . $markup->precio_convertido);
                    status_message("rango de precio: " . $rango_precio);
                    status_message("markup venta publico: " . $markup->venta_publico);
                    status_message("markup venta integrador: " . $markup->integrador);
                    print("\n");
                    status_message("precio venta publico: " . $precio_final);
                    status_message("precio venta integrador: " . $precio_integrador);
                    print("\n");
                    status_message("hora: " . date("Y-m-d H:i:s"));
                    print("\n");
                    
                    $meta_data[] = [
                        'key'   => 'cambio_de_precio',
                        'value' => [ 'fecha' => date("Y-m-d H:i:s"), 'detalle' => "$precio_actual_web -> $precio_final_calculado" ]
                    ];
                }
                if ($cambio_stock) {
                    status_message("CAMBIO STOCK ($sku): $stock_actual_web -> $nuevo_stock");
                    status_message("hora: " . date("Y-m-d H:i:s"));
                    print("\n");

                    $meta_data[] = [
                        'key'   => 'cambio_de_stock',
                        'value' => [ 'fecha' => date("Y-m-d H:i:s"), 'detalle' => "$stock_actual_web -> $nuevo_stock" ]
                    ];
                }
            }

            // MODIFICACIÓN: Siempre nos aseguramos de que el precio original esté actualizado en caso de que cambie en Syscom,
            // aunque no actualicemos el precio o el stock en WooCommerce. Esto garantiza que la info sea fresca.
           
            $meta_data[] = ['key' => '_precio_original_syscom', 'value' => $precio_syscom];

            // MODIFICACIÓN: asignamos el precio de  integrador a la metadata para tener un histórico, aunque no haya cambios en el precio público.
            
            $meta_data[] = ['key' => 'wholesale_customer_have_wholesale_price', 'value' => 'yes'];
            $meta_data[] = ['key' => 'wholesale_customer_wholesale_price', 'value' => $precio_integrador];

            // 4. Agregamos al batch SOLO si hubo cambios

            if ($cambio_precio || $cambio_stock) {

                    status_message("SI HUBO CAMBIOS EN STOCK O PRECIO EN: ($sku)");

                    $meta_data[] = [
                        'key'   => '_last_sync_update',
                        'value' => [ 'fecha' => date("Y-m-d H:i:s")]
                    ];


                $batch['update'][] = [
                    'id'             => $local['id'],
                    'name'           => $product['titulo'],
                    'regular_price'  => $precio_final,
                    'stock_quantity' => $stock_syscom,
                    'meta_data'      => $meta_data
                ];
            }

        }

        // EJECUTAR BATCH CADA 100
        if ((count($batch['create']) + count($batch['update'])) >= 100) {
            enviar_a_woocommerce($woocommerce, $batch);
            $batch = ['create' => [], 'update' => []];
        }
        $procesados++;
    }

    // ENVIAR REMANENTES
    if (!empty($batch['create']) || !empty($batch['update'])) {
        enviar_a_woocommerce($woocommerce, $batch);
    }
    else
    {
        status_message("IMPORTANTE: No hay cambios para enviar en este batch final.");
        print("\n");
    }

    status_message(calcularDiferenciaDeTiempo($hora_inicio, date("h:i:sa"), $procesados, "Pagina actual"));
}

function enviar_a_woocommerce($woocommerce, $batch) {
    try {
        status_message("Enviando batch a WooCommerce... (Nuevos: " . count($batch['create']) . " | Actualizados: " . count($batch['update']) . ")");
        $woocommerce->post('products/batch', $batch);
    } catch (Exception $e) {
        status_message("Error en Batch: " . $e->getMessage());
    }
}

// ====================================================================================
// Consultas de Base de Datos
// ====================================================================================



function obtener_inventario_local_completo() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec("SET SESSION group_concat_max_len = 1000000");

        $sql = "SELECT p.ID as id, 
                MAX(CASE WHEN pm.meta_key = '_sku' THEN pm.meta_value END) as sku,
                MAX(CASE WHEN pm.meta_key = '_price' THEN pm.meta_value END) as precio,
                MAX(CASE WHEN pm.meta_key = '_stock' THEN pm.meta_value END) as stock
                FROM kmq_posts p
                INNER JOIN kmq_postmeta pm ON p.ID = pm.post_id
                WHERE p.post_type = 'product' AND p.post_status IN ('publish', 'draft')
                GROUP BY p.ID HAVING sku IS NOT NULL AND sku != ''";

        $stmt = $pdo->query($sql);
        $indexado = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $indexado[$row['sku']] = [
                'id'     => $row['id'],
                'precio' => $row['precio'],
                'stock'  => $row['stock']
            ];
        }
        return $indexado;
    } catch (PDOException $e) {
        exit("❌ Error DB Local: " . $e->getMessage());
    }
}


function obtener_markup_db($precio_origen, $tipo_cambio) {
    try {

         $iva_multiplicador = 1.16;
         
         $otro_factor = 0.96;
 
        // 1. Convertimos el precio de la API (USD) a moneda local usando el TC de Syscom
        $precio_convertido = (float)$precio_origen * (float)$tipo_cambio * (float)$iva_multiplicador * (float)$otro_factor; // Aplicamos IVA aquí para que el rango considere el precio final con impuestos incluido

        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        
        // 2. Buscamos el rango de ganancia basado en el precio ya convertido
        $stmt = $pdo->prepare("SELECT venta_publico, integrador, rango FROM kmq_intrax_precios WHERE :precio BETWEEN desde AND hasta LIMIT 1");
        
        // Usamos el precio convertido para la comparación de rangos
        $stmt->execute(['precio' => $precio_convertido]);
        $res = $stmt->fetch(PDO::FETCH_OBJ);
        
        if ($res) {
            return (object) [
                'venta_publico' => (float) $res->venta_publico,
                'integrador'    => (float) $res->integrador,
                'rango'         => $res->rango,
                'precio_convertido' => $precio_convertido // Guardamos el valor convertido por si lo necesitas
            ];
        }

        // Retorno por defecto si no hay coincidencia
        return (object) ['venta_publico' => 1.0, 'integrador' => 1.0, 'rango' => 'N/A'];

    } catch (Exception $e) {
        return (object) ['venta_publico' => 1.0, 'integrador' => 1.0, 'rango' => 'Error'];
    }
}





function obtener_tipo_cambio_syscom($token) {
    $url = "https://developers.syscom.mx/api/v1/tipocambio";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $token
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $data = json_decode($response, true);
        // Extraemos el valor "normal" (ej. 17.51)
        return isset($data['normal']) ? (float)$data['normal'] : 1.0;
    }
    
    status_message("⚠️ No se pudo obtener tipo de cambio, usando 1.0 por defecto.");
    return 1.0; 
}


// ====================================================================================
// Helpers
// ====================================================================================

function calcularDiferenciaDeTiempo($inicio_str, $fin_str, $cant, $param) {
    $inicio = new DateTime($inicio_str);
    $fin = new DateTime($fin_str);
    $diff = $inicio->diff($fin);
    $segundos = ($diff->h * 3600) + ($diff->i * 60) + $diff->s;
    return "Proceso terminado. $cant productos analizados para ($param). Tiempo: $segundos seg.";
}

function status_message($message) {
    echo $message . PHP_EOL;
}