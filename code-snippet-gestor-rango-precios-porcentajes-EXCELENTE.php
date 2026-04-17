<?php


/**
 * Plugin Name: Gestor de Precios INTRAX (Versión Ultra-Precisión)
 * Description: Actualización masiva con SQL directo. Fórmula corregida: costo × (1 + margen/100).
 */

add_action('admin_menu', function() {
    add_submenu_page(
        null,
        'Gestor Rango Precios INTRAX',
        'Gestor Rango Precios INTRAX',
        'manage_options',
        'intrax-price-manager',
        'intrax_render_price_manager'
    );
});

function intrax_render_price_manager() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'intrax_precios';
	$token = get_option('sws_syscom_token', '');
	
	$iva_multiplicador = 1.16;
	$otro_factor = 0.96;
	$reporte_agrupado = []; // Usaremos esta variable para acumular todo


    // --- PROCESAR ELIMINACIÓN ---
    if (isset($_POST['delete_row_id'])) {
        $id_to_delete = intval($_POST['delete_row_id']);
        $wpdb->delete($table_name, array('id' => $id_to_delete));
        echo '<div class="updated notice is-dismissible"><p>Rango eliminado correctamente.</p></div>';
    }

    // --- PROCESAR GUARDADO SQL (PRECISIÓN ABSOLUTA) ---
    if (isset($_POST['intrax_save_settings']) && !empty($_POST['intrax_json_data'])) {
        $changed_ranges = json_decode(stripslashes($_POST['intrax_json_data']), true);

        if (is_array($changed_ranges)) {

            foreach ($changed_ranges as $row) {
				
				
				$min = number_format(floatval($row['min']), 2, '.', '');
				$max = number_format(floatval($row['max']), 2, '.', '');


				$multiplier_publico     = number_format(1 + (floatval($row['venta_publico']) / 100), 4, '.', '');
				$multiplier_integrador  = number_format(1 + (floatval($row['integrador']) / 100), 4, '.', '');

				$id = intval($row['id']);

				$save_data = [
					'desde'                => $min,
					'hasta'                => $max,
					'venta_publico'        => $multiplier_publico,
					'descuento_integrador' => $multiplier_integrador,
				];

				if ($id > 1000000000) {
					$wpdb->insert($table_name, $save_data);
				} else {
					$wpdb->update($table_name, $save_data, array('id' => $id));
				}
			  
				$price_updates = [
					'_regular_price'                     => $multiplier_publico,
					'wholesale_customer_wholesale_price' => $multiplier_integrador,
				];
				

				$products_found = $wpdb->get_results($wpdb->prepare("
					SELECT 
						p.ID, 
						pm_sku.meta_value as sku,
						pm_reg.meta_value as precio_actual
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm_reg ON p.ID = pm_reg.post_id
					INNER JOIN {$wpdb->postmeta} pm_syscom_tag ON p.ID = pm_syscom_tag.post_id
					LEFT JOIN {$wpdb->postmeta} pm_sku ON p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku'
					WHERE p.post_type = 'product' 
					AND p.post_status = 'draft' 
					AND pm_reg.meta_key = '_regular_price'
					AND pm_syscom_tag.meta_key = '_producto_syscom_api'
					AND CAST(pm_reg.meta_value AS DECIMAL(20,2)) BETWEEN %f AND %f
				", $min, $max));

				if ($products_found) {
					
					
					// Extraemos solo los valores de la columna 'sku' del array de objetos
					$lista_skus = wp_list_pluck($products_found, 'sku');

					// Limpiamos posibles valores vacíos y los unimos por coma
					$skus_texto = implode(', ', array_filter($lista_skus));

					$reporte_agrupado['auditoria'][] = [
						'rango' => "$min - $max",
						'borradores_encontrados' => count($products_found),
						'skus_detectados' => $skus_texto // Aquí se guardan los nombres de los productos/modelos
					];
					
					foreach ($price_updates as $key => $current_multiplier) {
						
							
						foreach ($products_found as $prod) {
					
							$sku = $prod->sku;
							$precio_descuento = 0; 

							if (!empty($sku)) {

								$tipo_cambio = 1; // Valor por defecto
								$response_cambio = wp_remote_get("https://developers.syscom.mx/api/v1/tipocambio", [
									'headers' => [
										'Authorization' => 'Bearer ' . $token,
										'Accept'        => 'application/json',
									]
								]);

								if (!is_wp_error($response_cambio) && wp_remote_retrieve_response_code($response_cambio) === 200) {
									$data_cambio = json_decode(wp_remote_retrieve_body($response_cambio), true);
									$tipo_cambio = isset($data_cambio['normal']) ? floatval($data_cambio['normal']) : 1;
								}						

								$api_url = "https://developers.syscom.mx/api/v1/productos?busqueda=" . urlencode($sku);

									$response_descuento = wp_remote_get($api_url, [
										'headers' => [
											'Authorization' => 'Bearer ' . $token, // Agregado espacio después de Bearer
											'Accept'        => 'application/json',
										],
										'timeout' => 15
									]);

									if (!is_wp_error($response_descuento) && wp_remote_retrieve_response_code($response_descuento) === 200) {

										$body = wp_remote_retrieve_body($response_descuento);
										$api_data = json_decode($body, true);

										if ($api_data && isset($api_data['productos']) && is_array($api_data['productos'])) {

											$encontrado_exacto = false;

											foreach ($api_data['productos'] as $item) {
												// 1. Normalización estricta para asegurar el match

												// $precio_desx = $item['precios']['precio_descuento'];
												$garantia = $item['garantia'];
												$modelo_api = strtoupper(trim($item['modelo']));
												$sku_local  = strtoupper(trim($sku));

												// 2. COMPARACIÓN ESTRICTA
												if ($modelo_api === $sku_local) {

													$precio_base = floatval($item['precios']['precio_descuento']);

													$precio_final = $precio_base * $iva_multiplicador * $otro_factor * $current_multiplier * $tipo_cambio;
 													
													$precio_final_a_tienda = number_format($precio_final, 2, '.', '');
													
													$precio_descuento = $precio_final; 
													$encontrado_exacto = true;

													$reporte_update[] = [
														'key' => $key,
														'SKU' => $sku,
														'Precio Api' => $precio_base,
														'tipo_cambio' => $tipo_cambio,
														'IVA' => $iva_multiplicador,
														'OTRO FACTOR' => $otro_factor,
														'Porcentaje' => $current_multiplier,
														'Precio FINAL' => $precio_final_a_tienda,
														'Resultado' => 'Actualizado ✅'
													];
													
													
													
													
													break;								


												}
											}

											// 3. SEGURIDAD: Si no hubo match exacto en la lista, el precio es 0
											if (!$encontrado_exacto) {
												$precio_descuento = 0;
											}
										}
									
									} else {
										// Si la API falla (401, 404, etc.), aseguramos que el precio sea 0
										$precio_descuento = 0;
									}


							}


							if ($encontrado_exacto && $precio_descuento > 0) {

								$precio_formateado = number_format($precio_descuento, 2, '.', '');

									// 1. LIMPIEZA DE PRECIOS (Solo el de oferta para no bloquear)
									$wpdb->query($wpdb->prepare("
										UPDATE {$wpdb->postmeta} 
										SET meta_value = '' 
										WHERE post_id = %d AND meta_key = '_sale_price'
									", $prod->ID));

									// 2. ACTUALIZACIÓN DE LA LLAVE ESPECÍFICA ($key)
									// Esto guarda el valor en el campo que corresponde (Público o Mayoreo)
									$wpdb->query($wpdb->prepare("
										UPDATE {$wpdb->postmeta} 
										SET meta_value = %s 
										WHERE post_id = %d AND meta_key = %s
									", $precio_formateado, $prod->ID, $key));

									// 3. CONDICIONAL PARA EL PRECIO ACTIVO (_price)
									// CORRECCIÓN: Solo actualizamos '_price' si estamos editando el precio público
									if ($key === '_regular_price') {
										$wpdb->query($wpdb->prepare("
											UPDATE {$wpdb->postmeta} 
											SET meta_value = %s 
											WHERE post_id = %d AND meta_key = '_price'
										", $precio_formateado, $prod->ID));
									}





									$producto_actualizado = true;
									$producto_actualizado_con_exito = true; // Marcamos que el producto se tocó


							}


							update_post_meta($prod->ID, 'cambio_precio_en_modulo_rango_precio', date('Y-m-d H:i:s'));

							if ($producto_actualizado) {
								$total_productos_tocados++;
							}
						
						
						}
						
						// --- HERE:
						// 
						
						
					}
					
					
				}
				
				
				
            }

			if (!empty($reporte_agrupado['auditoria'])) {
				echo "<script>
				
				console.group('%c🔍 SKUS SELECT SQL', 'color: black; background: #00ffff; padding: 10px; font-weight: bold;');
				const reportAuditoria = " . json_encode($reporte_agrupado['auditoria']) . ";
				console.table(reportAuditoria);
				console.groupEnd();
				
				console.group('%c🔍 UPDATES SQL', 'color: violet; background: green; padding: 10px; font-weight: bold;');
				const reportUPDATE = " . json_encode($reporte_update) . ";
				console.table(reportUPDATE);
				console.groupEnd();
				

				

				</script>";
				
			}

            wp_cache_flush();
        echo '<div class="updated notice is-dismissible"><p>🚀 <strong>Éxito:</strong> Se actualizaron <strong>' . $total_productos_tocados . '</strong> productos sin redondeos.</p>		</div>';
        
		}
		
		
		
    }

    // Cargar datos de la tabla
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY desde ASC", ARRAY_A);
    $formatted_data = [];
    if (!empty($results)) {
        foreach ($results as $row) {
            // Al leer de BD: el multiplicador guardado es (1 + margen/100), así que para
            // mostrarlo como porcentaje de margen en la UI: (multiplicador - 1) × 100
            $formatted_data[] = [
                'id'  => (int)$row['id'],
                'min' => number_format((float)$row['desde'], 2, '.', ''),
                'max' => number_format((float)$row['hasta'], 2, '.', ''),
                'venta_publico'        => number_format(((float)$row['venta_publico'] - 1) * 100, 2, '.', ''),
                'descuento_integrador' => number_format(((float)$row['integrador'] - 1) * 100, 2, '.', ''),
            ];
        }
    } else {
        $formatted_data = [['id' => 1, 'min' => "0.00", 'max' => "10.00", 'venta_publico' => "160.00", 'descuento_integrador' => "80.00"]];
    }
    $json_data = json_encode($formatted_data);
    ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <div class="wrap" style="margin-top: 20px;">
        <div class="bg-slate-50 p-8 font-sans text-slate-800 rounded-xl border border-slate-200 shadow-sm max-w-[2000px]">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">INTRAX <span class="text-blue-600">Sniper</span></h1>
                    <p class="text-slate-500 italic">Fórmula corregida: precio = costo × (1 + margen/100)</p>
                </div>
                <div class="text-right">
                    <span class="bg-green-100 text-green-700 text-xs font-bold px-3 py-1 rounded-full border border-green-200">MARKUP SOBRE COSTO ✓</span>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden">
                <div class="grid grid-cols-12 gap-4 bg-slate-900 p-4 font-bold text-[10px] uppercase text-slate-400 tracking-widest">
                    <div class="col-span-5 text-white">Rango Costo (Desde - Hasta)</div>
                    <div class="col-span-3 text-center text-white">Margen Público (%)</div>
                    <div class="col-span-3 text-center text-white">Margen Integrador (%)</div>
                    <div class="col-span-1 text-center"></div>
                </div>

                <div id="rows-container" class="p-4 space-y-3"></div>

                <div class="p-4 bg-slate-50 border-t flex justify-center">
                    <button type="button" onclick="PriceApp.addRow()" class="text-blue-600 font-bold flex items-center gap-2 hover:bg-blue-600 hover:text-white px-6 py-2 rounded-full transition-all border border-blue-200">
                        <i data-lucide="plus-circle" class="w-5 h-5"></i> Nuevo Rango
                    </button>
                </div>
            </div>

            <form method="post" id="main-save-form" class="mt-8 flex flex-col items-end" onsubmit="return PriceApp.prepareSave(event)">
                <input type="hidden" name="intrax_json_data" id="intrax_json_input">
                <button type="submit" name="intrax_save_settings" id="submit-btn" class="bg-blue-600 hover:bg-blue-800 text-white px-12 py-4 rounded-xl font-black text-lg shadow-lg transition-all active:scale-95 flex items-center gap-3">
                    <i data-lucide="zap" class="fill-current"></i> GUARDAR Y ACTUALIZAR AHORA
                </button>
            </form>
        </div>
    </div>

    <div id="delete-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[9999]">
        <div class="bg-white p-8 rounded-2xl max-w-sm w-full shadow-2xl">
            <h2 class="text-2xl font-bold mb-2 text-center text-slate-900">¿Eliminar rango?</h2>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="PriceApp.closeModal()" class="flex-1 py-3 bg-slate-100 text-slate-600 rounded-xl font-bold">Cancelar</button>
                <form method="post" class="flex-1">
                    <input type="hidden" name="delete_row_id" id="modal-delete-id">
                    <button type="submit" class="w-full py-3 bg-red-600 text-white rounded-xl font-bold">Eliminar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    const PriceApp = {
        rows: JSON.parse('<?php echo $json_data; ?>'),
        changedIds: new Set(),

        init() {
            this.renderTable();
            lucide.createIcons();
        },

        // ----------------------------------------------------------------
        // renderTable: construye el DOM una sola vez al init o al agregar/
        // eliminar filas. Al editar campos, solo actualiza el estado interno
        // y el estilo del borde — SIN re-renderizar toda la tabla, lo que
        // evitaba el salto/scroll raro al escribir.
        // ----------------------------------------------------------------
        renderTable() {
            const container = document.getElementById("rows-container");
            container.innerHTML = "";
            this.rows.forEach(row => this.renderRow(row, container));
            lucide.createIcons();
        },

        renderRow(row, container) {
            const changed = this.changedIds.has(row.id);
            const div = document.createElement("div");
            div.id = `row-${row.id}`;
            div.className = `grid grid-cols-12 gap-4 items-center p-4 rounded-xl border transition-all ${changed ? 'border-blue-400 bg-blue-50' : 'border-slate-200 bg-white'}`;

            div.innerHTML = `
                <div class="col-span-5 flex items-center gap-3">
                    <div class="relative flex-1">
                        <span class="absolute right-3 top-2 text-[10px] font-bold text-slate-400 uppercase">Min</span>
                        <input type="number" step="0.01" value="${parseFloat(row.min).toFixed(2)}"
                            oninput="PriceApp.update(${row.id}, 'min', this.value)"
                            class="w-full pt-6 pb-2 px-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none font-mono text-slate-700">
                    </div>
                    <div class="text-slate-300 font-bold">/</div>
                    <div class="relative flex-1">
                        <span class="absolute right-3 top-2 text-[10px] font-bold text-slate-400 uppercase">Max</span>
                        <input type="number" step="0.01" value="${parseFloat(row.max).toFixed(2)}"
                            oninput="PriceApp.update(${row.id}, 'max', this.value)"
                            class="w-full pt-6 pb-2 px-3 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none font-mono text-slate-700">
                    </div>
                </div>
                <div class="col-span-3">
                    <input type="number" step="0.01" value="${parseFloat(row.venta_publico).toFixed(2)}"
                        oninput="PriceApp.update(${row.id}, 'venta_publico', this.value)"
                        class="w-full p-3 border border-slate-200 rounded-lg text-center font-black text-blue-600 text-lg">
                </div>
                <div class="col-span-3">
                    <input type="number" step="0.01" value="${parseFloat(row.descuento_integrador).toFixed(2)}"
                        oninput="PriceApp.update(${row.id}, 'descuento_integrador', this.value)"
                        class="w-full p-3 border border-slate-200 rounded-lg text-center font-black text-emerald-600 text-lg">
                </div>
                <div class="col-span-1 text-right">
                    <button type="button" onclick="PriceApp.confirmDelete(${row.id})" class="text-slate-300 hover:text-red-500 p-2">
                        <i data-lucide="trash-2" class="w-5 h-5"></i>
                    </button>
                </div>
            `;

            // Bloquear scroll del mouse sobre inputs numéricos para evitar
            // cambios accidentales al hacer scroll sobre la tabla
            div.querySelectorAll('input[type="number"]').forEach(input => {
                input.addEventListener('wheel', e => e.preventDefault(), { passive: false });
            });

            if (container) {
                container.appendChild(div);
            }
        },

        // Actualiza solo el estado interno y el estilo del borde de la fila.
        // NO re-renderiza toda la tabla → elimina el scroll/salto al escribir.
        update(id, field, value) {
            const row = this.rows.find(r => r.id === id);
            if (row) {
                row[field] = value;
                if (!this.changedIds.has(id)) {
                    this.changedIds.add(id);
                    // Solo actualizamos el estilo visual de la fila afectada
                    const rowEl = document.getElementById(`row-${id}`);
                    if (rowEl) {
                        rowEl.classList.remove('border-slate-200', 'bg-white');
                        rowEl.classList.add('border-blue-400', 'bg-blue-50');
                    }
                }
            }
        },

        addRow() {
            const id = Date.now();
            const newRow = { id, min: "0.00", max: "0.00", venta_publico: "160.00", descuento_integrador: "80.00" };
            this.rows.push(newRow);
            this.changedIds.add(id);
            const container = document.getElementById("rows-container");
            this.renderRow(newRow, container);
            lucide.createIcons();
        },

        prepareSave(event) {
            const dataToSave = this.rows.filter(r => this.changedIds.has(r.id));
            if (dataToSave.length === 0) return false;
            document.getElementById("intrax_json_input").value = JSON.stringify(dataToSave);
            document.getElementById("submit-btn").innerHTML = '<i data-lucide="loader" class="animate-spin"></i> PROCESANDO SQL...';
            lucide.createIcons();
            return true;
        },

        confirmDelete(id) {
            if (id > 1000000000) {
                this.rows = this.rows.filter(r => r.id !== id);
                const rowEl = document.getElementById(`row-${id}`);
                if (rowEl) rowEl.remove();
            } else {
                document.getElementById("modal-delete-id").value = id;
                document.getElementById("delete-modal").classList.remove("hidden");
            }
        },

        closeModal() {
            document.getElementById("delete-modal").classList.add("hidden");
        }
    };

    document.addEventListener("DOMContentLoaded", () => PriceApp.init());
    </script>
    <?php
}