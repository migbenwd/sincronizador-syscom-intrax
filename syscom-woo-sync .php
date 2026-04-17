<?php
/**
 * Plugin Name: Syscom WooCommerce Sync (Pro Edition)
 * Plugin URI: https://tu-sitio.com
 * Description: Sincronizador profesional de Syscom. Soporte para catálogos masivos (+5000 items), paginación JS, búsqueda en tiempo real y protección de memoria.
 * Version: 1.9.5
 * Author: Tu Nombre
 * License: GPL v2 or later
 * Text Domain: syscom-woo-sync
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('Syscom_Woo_Sync_Ultimate')) return;


define('SWS_VERSION', '1.9.5');

class Syscom_Woo_Sync {
    
    private static $instance = null;
    private $endpoints = [];
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Hooks AJAX
        add_action('wp_ajax_sws_preview', [$this, 'ajax_preview']);
        add_action('wp_ajax_sws_sync', [$this, 'ajax_sync']);
        add_action('wp_ajax_sws_save', [$this, 'ajax_save']);
        add_action('wp_ajax_sws_reset', [$this, 'ajax_reset']);
        add_action('wp_ajax_sws_clear_history', [$this, 'ajax_clear_history']);
        
        // Link en plugins
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_action_links']);

        $saved = get_option('sws_endpoints');
        $this->endpoints = !empty($saved) ? $saved : $this->get_default_endpoints();
    }

    public function add_action_links($links) {
        $mylinks = array(
            '<a href="' . admin_url('admin.php?page=syscom-woo-sync') . '">Configuración</a>',
        );
        return array_merge($mylinks, $links);
    }
    
    private function get_default_endpoints() {
        return [
            ['id' => 'hiksemi', 'name' => 'HIKSEMI BY VISION', 'query' => 'marca=hiksemibyhikvision', 'enabled' => true],
            ['id' => 'witek', 'name' => 'WITEK', 'query' => 'marca=witek', 'enabled' => true],
            ['id' => 'ruijie', 'name' => 'RUIJIE', 'query' => 'marca=ruijie', 'enabled' => true],
            ['id' => 'tplink', 'name' => 'TPLINK', 'query' => 'marca=tplink', 'enabled' => true],
            ['id' => 'ezviz', 'name' => 'EZVIZ', 'query' => 'marca=ezviz', 'enabled' => true],
            ['id' => 'ferreteria', 'name' => 'Ferretería y Vialidad', 'query' => 'categoria=2929,2927,66457', 'enabled' => true],
            ['id' => 'bodycam', 'name' => 'Bodycam y Dashcam', 'query' => 'categoria=66492,66491', 'enabled' => true],
            ['id' => 'jimiiot', 'name' => 'JIMIIOT (GPS)', 'query' => 'marca=jimiiot&categoria=66491,65657', 'enabled' => true],
            ['id' => 'ugreen', 'name' => 'UGREEN', 'query' => 'marca=ugreen', 'enabled' => true],
            ['id' => 'sfire', 'name' => 'SFIRE', 'query' => 'marca=sfire', 'enabled' => true],
            ['id' => 'yonusa', 'name' => 'YONUSA', 'query' => 'marca=yonusa', 'enabled' => true],
            ['id' => 'montajes', 'name' => 'Pantallas y Montajes', 'query' => 'categoria=66565', 'enabled' => true],
            ['id' => 'kits-ip', 'name' => 'Kits IP Megapixel', 'query' => 'categoria=1380', 'enabled' => true],
            ['id' => 'linkedpro-epcom', 'name' => 'LINKEDPRO BY EPCOM', 'query' => 'marca=linkedprobyepcom&categoria=65815,1421,66407', 'enabled' => true],
            ['id' => 'precision', 'name' => 'PRECISION', 'query' => 'marca=precision', 'enabled' => true],
            ['id' => 'ecoflow', 'name' => 'ECOFLOW', 'query' => 'marca=ecoflow', 'enabled' => true],
            ['id' => 'accesspro-veh', 'name' => 'Accespro - Acceso Vehicular', 'query' => 'marca=accesspro&categoria=2925', 'enabled' => true],
            ['id' => 'hik-vehicular', 'name' => 'Hikvision - Acceso Vehicular', 'query' => 'marca=hikvision&categoria=2925', 'enabled' => true],
            ['id' => 'hik-alarmas', 'name' => 'Hikvision - Paneles Alarma', 'query' => 'marca=hikvision&categoria=351', 'enabled' => true],
            ['id' => 'hik-acceso', 'name' => 'Hikvision - Control Acceso', 'query' => 'marca=hikvision&categoria=37', 'enabled' => true],
            ['id' => 'hik-cctv', 'name' => 'Hikvision - CCTV IP/Analógica', 'query' => 'marca=hikvision&categoria=214', 'enabled' => true],
            ['id' => 'hik-energia', 'name' => 'Hikvision - Respaldos Energía', 'query' => 'marca=hikvision&categoria=66447', 'enabled' => true],
            ['id' => 'hik-monitores', 'name' => 'Hikvision - Monitores', 'query' => 'marca=hikvision&categoria=66564', 'enabled' => true],
            ['id' => 'hik-pantallas', 'name' => 'Hikvision - Pantallas', 'query' => 'marca=hikvision&categoria=66566', 'enabled' => true],
            ['id' => 'hik-interactivas', 'name' => 'Hikvision - Pantallas Int.', 'query' => 'marca=hikvision&categoria=66584', 'enabled' => true],
            ['id' => 'hik-videowalls', 'name' => 'Hikvision - Videowalls', 'query' => 'marca=hikvision&categoria=66533', 'enabled' => true],
            ['id' => 'hik-switches', 'name' => 'Hikvision - Switches', 'query' => 'marca=hikvision&categoria=1926', 'enabled' => true],
            ['id' => 'hik-routers', 'name' => 'Hikvision - Routers', 'query' => 'marca=hikvision&categoria=65890', 'enabled' => true],
            ['id' => 'hik-fuentes', 'name' => 'Hikvision - Fuentes Alim.', 'query' => 'marca=hikvision&categoria=427', 'enabled' => true],
            ['id' => 'hik-interfonos', 'name' => 'Hikvision - Videoporteros', 'query' => 'marca=hikvision&categoria=469', 'enabled' => true],
            ['id' => 'hik-bocinas', 'name' => 'Hikvision - Bocinas', 'query' => 'marca=hikvision&categoria=65818', 'enabled' => true],
            ['id' => 'hik-conf', 'name' => 'Hikvision - Videoconferencia', 'query' => 'marca=hikvision&categoria=66532', 'enabled' => true],
            ['id' => 'hik-poder', 'name' => 'Hikvision - Fuentes Poder', 'query' => 'marca=hikvision&categoria=66442', 'enabled' => true],
            ['id' => 'hik-dashcam', 'name' => 'Hikvision - DashCam', 'query' => 'marca=hikvision&categoria=66491', 'enabled' => true],
            ['id' => 'lp-gabinetes', 'name' => 'Linkedpro - Gabinetes', 'query' => 'marca=linkedpro&categoria=66495', 'enabled' => true],
            ['id' => 'lp-cableado', 'name' => 'Linkedpro - Cableado', 'query' => 'marca=linkedpro&categoria=65811', 'enabled' => true],
        ];
    }
    
public function add_admin_menu() {
        // 1. Menú Principal (y primera opción por defecto: Sincronizador)
        add_menu_page(
            'Syscom Sync', 
            'Syscom Sync', 
            'manage_options', 
            'syscom-woo-sync', 
            [$this, 'render_page'], 
            'dashicons-cloud-upload', 
            56
        );

        // 2. Submenú explícito para Sincronizador
        add_submenu_page(
            'syscom-woo-sync', 
            'Sincronizador', 
            'Sincronizador', 
            'manage_options', 
            'syscom-woo-sync', 
            [$this, 'render_page']
        );

        // 3. Nuevo Submenú: Gestor Precio
        add_submenu_page(
            'syscom-woo-sync', 
            'Gestor de Precios', 
            'Gestor Precio', 
            'manage_options', 
            'intrax-price-manager', 
            [$this, 'render_price_page'] // Requiere la función render_price_page
        );

        // 4. Nuevo Submenú: Auditoría Productos
        add_submenu_page(
            'syscom-woo-sync', 
            'Auditoría de Productos', 
            'Auditoría Productos', 
            'manage_options', 
            'auditoria-productos', 
            [$this, 'render_audit_page'] // Requiere la función render_audit_page
        );
	
	// 4. Nuevo Submenú: Auditoría Productos
        add_submenu_page(
            'syscom-woo-sync', 
            'Visor De Logos', 
            'Visor Logs', 
            'manage_options', 
            'intrax-log-viewer', 
            [$this, 'render_visor_page']
        );
	
	
    }
    
    public function enqueue_assets($hook) {
        if ('toplevel_page_syscom-woo-sync' !== $hook) return;
        
        // CSS Profesional
        add_action('admin_head', function() { ?>
            <style>
                :root { 
                    --sws-bg: #0f172a; --sws-panel: #1e293b; --sws-text: #f1f5f9; --sws-text-dim: #94a3b8;
                    --sws-accent: #3b82f6; --sws-accent-hover: #2563eb;
                    --sws-success: #10b981; --sws-warn: #f59e0b; --sws-danger: #ef4444; 
                    --sws-border: #334155;
                }
                body { background: #f0f0f1; } 
                .sws-wrap { background: var(--sws-bg); color: var(--sws-text); padding: 25px; font-family: 'Inter', sans-serif; margin: 20px 20px 0 0; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
                .sws-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--sws-border); padding-bottom: 20px; margin-bottom: 25px; }
                .sws-header h1 { color: #fff; margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.5px; }
                .sws-badge { background: var(--sws-accent); color: white; padding: 4px 8px; border-radius: 6px; font-size: 11px; vertical-align: middle; }

                .sws-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; }
                
                .sws-card { background: var(--sws-panel); padding: 20px; border-radius: 10px; border: 1px solid var(--sws-border); margin-bottom: 25px; }
                .sws-card h3 { margin: 0 0 15px 0; color: #fff; font-size: 16px; border-bottom: 1px solid var(--sws-border); padding-bottom: 10px; }
                
                .sws-input-group { margin-bottom: 15px; }
                .sws-label { display: block; margin-bottom: 6px; font-size: 12px; color: var(--sws-accent); font-weight: 700; text-transform: uppercase; }
                .sws-input { width: 100%; background: #0f172a; border: 1px solid var(--sws-border); color: #fff; padding: 10px 12px; border-radius: 6px; box-sizing: border-box; transition: all 0.2s; }
                .sws-input:focus { border-color: var(--sws-accent); outline: none; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); }
                
                .sws-btn { cursor: pointer; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; color: white; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-size: 13px; text-decoration: none; }
                .sws-btn-primary { background: var(--sws-accent); } .sws-btn-primary:hover { background: var(--sws-accent-hover); }
                .sws-btn-success { background: var(--sws-success); } .sws-btn-success:hover { filter: brightness(1.1); }
                .sws-btn-warn { background: var(--sws-warn); color: #1e293b; }
                .sws-btn-danger { background: var(--sws-danger); }
                .sws-btn-secondary { background: var(--sws-border); color: var(--sws-text); } .sws-btn-secondary:hover { background: #475569; }
                .sws-btn:disabled { opacity: 0.6; cursor: not-allowed; }

                /* Endpoints List */
                .sws-endpoints-box { max-height: 350px; overflow-y: auto; padding-right: 5px; }
                .sws-ep-item { background: #0f172a; padding: 12px; border-radius: 6px; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; border: 1px solid var(--sws-border); }
                .sws-ep-item:hover { border-color: var(--sws-text-dim); }
                .sws-ep-item.active { border-left: 44px solid var(--sws-success); }
                .sws-ep-check { transform: scale(1.3); cursor: pointer; accent-color: var(--sws-success); }
                .ep-name { font-weight: 700; color: #fff; font-size: 13px; }
                .ep-query { font-size: 11px; color: var(--sws-text-dim); font-family: monospace; margin-top: 2px; }

                /* Stats Grid */
                .sws-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-top: 20px; }
                .sws-stat { background: #0f172a; padding: 15px; text-align: center; border-radius: 8px; border: 1px solid var(--sws-border); }
                .sws-stat-num { display: block; font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 4px; }
                .sws-stat-label { font-size: 10px; text-transform: uppercase; color: var(--sws-text-dim); font-weight: 700; letter-spacing: 0.5px; }

                /* Logs Console */
                .sws-logs { background: #0f172a; color: #4ade80; padding: 15px; height: 200px; overflow-y: auto; font-family: 'Consolas', monospace; font-size: 11px; margin-top: 20px; border-radius: 8px; border: 1px solid var(--sws-border); }
                .sws-log-line { border-bottom: 1px solid #1e293b; padding: 4px 0; display: flex; gap: 10px; }
                .sws-log-time { color: var(--sws-text-dim); min-width: 60px; }
                .sws-log-err { color: var(--sws-danger); }
                .sws-log-warn { color: var(--sws-warn); }

                /* Table */
                .sws-table-container { overflow-x: auto; }
                table.sws-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 800px; }
                table.sws-table th { text-align: left; padding: 12px 15px; background: var(--sws-panel); color: var(--sws-accent); position: sticky; top: 0; font-weight: 700; text-transform: uppercase; font-size: 11px; border-bottom: 2px solid var(--sws-border); }
                table.sws-table td { padding: 10px 15px; border-bottom: 1px solid var(--sws-border); color: var(--sws-text-dim); }
                table.sws-table tr:hover td { background: #262f45; color: #fff; }
                
                .sws-diff-old { text-decoration: line-through; color: var(--sws-danger); margin-right: 6px; opacity: 0.7; font-size: 0.9em; }
                .sws-diff-new { color: var(--sws-success); font-weight: 700; }
                
                /* Pagination & Search */
                .sws-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; gap: 15px; flex-wrap: wrap;}
                .sws-search { flex: 1; max-width: 300px; }
                .sws-pagination { display: flex; align-items: center; gap: 10px; font-size: 12px; color: var(--sws-text-dim); }
                .sws-filters { display: flex; gap: 8px; }
                .sws-filter-btn { background: #0f172a; border: 1px solid var(--sws-border); color: var(--sws-text-dim); padding: 6px 12px; border-radius: 20px; font-size: 11px; cursor: pointer; transition: all 0.2s; }
                .sws-filter-btn.active { background: var(--sws-accent); color: white; border-color: var(--sws-accent); }
                
                .sws-add-form { background: #0f172a; padding: 15px; margin-bottom: 15px; border-radius: 6px; display: none; border: 1px dashed var(--sws-border); }
                
                /* Activity Log */
                .sws-history-list { list-style: none; padding: 0; margin: 0; }
                .sws-history-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--sws-border); font-size: 12px; color: var(--sws-text-dim); }
                .sws-history-item:last-child { border: none; }
                .sws-history-date { color: var(--sws-accent); font-weight: bold; }
                
                /* Spinner */
                .sws-spinner { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.3); border-top: 2px solid #fff; border-radius: 50%; animation: spin 1s linear infinite; display: inline-block; margin-right: 5px; }
                @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                
                /* Final Report */
                #final-results-area { display: none; margin-top: 30px; animation: fadeIn 0.5s ease; border: 2px solid var(--sws-success); padding: 0; overflow: hidden; }
                #final-results-header { background: #1e293b; padding: 15px; border-bottom: 1px solid var(--sws-border); }
                @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
            </style>
        <?php });

        // JS Logic Completa
        add_action('admin_footer', function() { ?>
            <script>
            jQuery(document).ready(function($) {
                const api = { url: '<?php echo admin_url('admin-ajax.php'); ?>', nonce: '<?php echo wp_create_nonce('sws_nonce'); ?>' };
                
                // State
                let productsCache = [];
                let filteredProducts = [];
                let processedResults = []; // Almacena resultados de sync
                let currentPage = 1;
                const itemsPerPage = 50;
                let currentFilter = 'all';

                function log(msg, type='info') {
                    const time = new Date().toLocaleTimeString();
                    const color = type === 'error' ? '#ef4444' : (type === 'warn' ? '#f59e0b' : '#4ade80');
                    $('#sws-logs').prepend(`<div style="border-bottom:1px solid #1e293b; padding:4px 0; color:${color};">[${time}] ${msg}</div>`);
                }

                // --- TABLA GENERICA (Reutilizable para Preview y Reporte Final) ---
                function renderTable(dataSet = filteredProducts, targetBodyId = '#table-body', isReport = false) {
                    const $tbody = $(targetBodyId);
                    $tbody.empty();
                    
                    // Paginación solo si es preview principal, el reporte final puede ser largo o paginado aparte
                    // Para simplificar, el reporte final muestra todo o una muestra. 
                    // Aquí usamos la paginación global solo para la vista principal.
                    
                    let dataToShow = dataSet;
                    if(!isReport) {
                        const start = (currentPage - 1) * itemsPerPage;
                        const end = start + itemsPerPage;
                        dataToShow = dataSet.slice(start, end);
                    } else {
                        // En reporte final mostramos los primeros 1000 para no trabar
                        dataToShow = dataSet.slice(0, 1000); 
                    }
                    
                    if(dataToShow.length === 0) {
                        $tbody.html('<tr><td colspan="6" style="text-align:center; padding:20px;">Sin datos</td></tr>');
                        if(!isReport) $('#page-info').text('0 de 0');
                        return;
                    }

                    dataToShow.forEach(p => {
                        let actionHtml = '';
                        if(p.action === 'create') actionHtml = '<span style="color:#9ece6a; font-weight:bold;">CREAR</span>';
                        else if(p.action === 'update') actionHtml = '<span style="color:#3b82f6; font-weight:bold;">ACTUALIZAR</span>';
                        else actionHtml = '<span style="color:#64748b;">SKIP</span>';
                        
                        // Si es reporte final, usamos el status_msg real que viene del servidor
                        if(isReport && p.status_msg) {
                            actionHtml = `<span style="color:#fff;">${p.status_msg}</span>`;
                        }
                        
                        // Comparativas Precio
                        let price = `$${p.price_final || 0}`;
                        if(p.action === 'update' && p.changes && p.changes.includes('price')) 
                            price = `<span class="sws-diff-old">$${p.woo_price}</span> ➜ <span class="sws-diff-new">$${p.price_final}</span>`;
                        
                        // Comparativas Stock
                        let stock = p.stock || 0;
                        if(p.action === 'update' && p.changes && p.changes.includes('stock')) 
                            stock = `<span class="sws-diff-old">${p.woo_stock}</span> ➜ <span class="sws-diff-new">${p.stock}</span>`;
                        
                        // Link
                        let link = '-';
                        if(p.woo_id) {
                            link = `<a href="post.php?post=${p.woo_id}&action=edit" target="_blank" class="sws-btn sws-btn-secondary" style="font-size:10px; padding:4px 8px;">Editar ↗</a>`;
                        } else if (p.new_id) { // ID retornado tras crear
                             link = `<a href="post.php?post=${p.new_id}&action=edit" target="_blank" class="sws-btn sws-btn-secondary" style="font-size:10px; padding:4px 8px;">Editar ↗</a>`;
                        }

                        $tbody.append(`<tr>
                            <td style="font-family:monospace; color:#fff;">${p.sku}</td>
                            <td title="${p.title}">${p.title ? (p.title.length > 40 ? p.title.substring(0,40)+'...' : p.title) : 'Sin título'}</td>
                            <td>${price}</td>
                            <td>${stock}</td>
                            <td>${actionHtml}</td>
                            <td>${link}</td>
                        </tr>`);
                    });

                    if(!isReport) {
                        const totalPages = Math.ceil(dataSet.length / itemsPerPage);
                        $('#page-info').text(`Página ${currentPage} de ${totalPages} (${dataSet.length} items)`);
                        $('#btn-prev').prop('disabled', currentPage === 1);
                        $('#btn-next').prop('disabled', currentPage >= totalPages);
                    }
                }

                // Filtros y Búsqueda
                function applyFilters() {
                    const term = $('#search-input').val().toLowerCase();
                    filteredProducts = productsCache.filter(p => {
                        if(currentFilter !== 'all') {
                            if(currentFilter === 'create' && p.action !== 'create') return false;
                            if(currentFilter === 'update' && p.action !== 'update') return false;
                            if(currentFilter === 'skip' && p.action !== 'skip') return false;
                        }
                        if(term.length >= 2 && !p.sku.toLowerCase().includes(term) && !p.title.toLowerCase().includes(term)) return false;
                        return true;
                    });
                    currentPage = 1;
                    renderTable(filteredProducts, '#table-body');
                }

                $('.sws-filter-btn').click(function() {
                    $('.sws-filter-btn').css('background', 'transparent').css('color','#94a3b8');
                    $(this).css('background', '#3b82f6').css('color','white');
                    currentFilter = $(this).data('filter');
                    applyFilters();
                });
                
                $('#search-input').on('input', applyFilters);
                $('#btn-prev').click(function() { if(currentPage > 1) { currentPage--; renderTable(filteredProducts, '#table-body'); } });
                $('#btn-next').click(function() { if(currentPage * itemsPerPage < filteredProducts.length) { currentPage++; renderTable(filteredProducts, '#table-body'); } });


                // --- Guardado automático y recarga al cambiar estado de endpoints ---
                
                $('#ep-list').on('change', '.sws-ep-check', function() {
                    const $checkbox = $(this);
                    const name = $checkbox.closest('.sws-ep-item').find('.ep-name').text();
                    
                    // Bloqueamos visualmente para indicar que se está procesando
                    $checkbox.prop('disabled', true);
                    log(`💾 Guardando cambio en "${name}" y reordenando...`, 'warn');

                    // Recopilamos todos los endpoints con sus estados actuales
                    let endpoints = [];
                    $('.sws-ep-item').each(function() {
                        const $el = $(this);
                        endpoints.push({
                            id: $el.data('id'), 
                            name: $el.find('.ep-name').text(), 
                            query: $el.find('.ep-query').text(), 
                            enabled: $el.find('input').is(':checked')
                        });
                    });

                    // Enviamos la configuración completa al servidor
                    $.post(api.url, {
                        action: 'sws_save', 
                        nonce: api.nonce, 
                        token: $('#token').val(), 
                        markup: $('#markup').val(), 
                        prefix: $('#prefix').val(), 
                        sim_mode: $('#sim_mode').is(':checked') ? 1 : 0, 
                        endpoints: JSON.stringify(endpoints)
                    }, function(res) {
                        if(res.success) {
                            // Recarga inmediata para aplicar el orden (Activos arriba)
                            location.reload(); 
                        } else {
                            alert('Error al guardar el cambio.');
                            $checkbox.prop('disabled', false);
                        }
                    });
                });


        // --- Aviso de cambios pendientes en Endpoints ---
        /*
        $('#ep-list').on('change', '.sws-ep-check', function() {
            const isChecked = $(this).is(':checked');
            const name = $(this).closest('.sws-ep-item').find('.ep-name').text();
            
            // Feedback visual inmediato de la fila
            if (isChecked) {
                $(this).closest('.sws-ep-item').addClass('active');
            } else {
                $(this).closest('.sws-ep-item').removeClass('active');
            }

            // Mostrar el mensaje en la consola de logs
            log(`⚠️ Cambio detectado en "${name}". Debes presionar "Guardar Config" para aplicar y ordenar los cambios.`, 'warn');
            
            // Opcional: Resaltar el botón de guardado para llamar la atención
            $('#btn-save').css('box-shadow', '0 0 15px rgba(59, 130, 246, 0.5)');
            setTimeout(() => {
                $('#btn-save').css('box-shadow', 'none');
            }, 1000);
        });
        */



                $('#btn-save').click(function() {
                    let endpoints = [];
                    $('.sws-ep-item').each(function() {
                        const $el = $(this);
                        endpoints.push({
                            id: $el.data('id'), 
                            name: $el.find('.ep-name').text(), 
                            query: $el.find('.ep-query').text(), 
                            enabled: $el.find('input').is(':checked')
                        });
                    });
                    const simMode = $('#sim_mode').is(':checked') ? 1 : 0;
                    
                    $.post(api.url, {
                        action: 'sws_save', 
                        nonce: api.nonce, 
                        token: $('#token').val(), 
                        markup: $('#markup').val(), 
                        prefix: $('#prefix').val(), 
                        sim_mode: simMode, 
                        endpoints: JSON.stringify(endpoints)
                    }, function(res) {
                        if(res.success) {
                            // En lugar de solo log, recargamos para aplicar el nuevo orden
                            location.reload(); 
                        } else {
                            log('❌ Error al guardar', 'error');
                        }
                    });
                });


                // Restaurar
                $('#btn-reset').click(function() {
                    if(confirm('¿Restaurar los 36 endpoints por defecto? Esto borrará cualquier endpoint personalizado.')) {
                        $.post(api.url, { action: 'sws_reset', nonce: api.nonce }, function() {
                            location.reload();
                        });
                    }
                });



				// Lógica para desmarcar todos los checkboxes
				$('#btn-uncheck-all').click(function() {
					// Busca todos los checkboxes dentro de la lista de endpoints
					$('#ep-list .sws-ep-check').prop('checked', false);
					
					// Remueve la clase 'active' de los contenedores para feedback visual
					$('.sws-ep-item').removeClass('active');
					
					log('⚪ Todos los endpoints han sido desmarcados.', 'warn');
				});

                // Agregar Endpoint
                $('#btn-toggle-add').click(function() { $('#add-form').slideToggle(); });
                $('#btn-add-ep').click(function() {
                    const name = $('#new-name').val();
                    const query = $('#new-query').val();
                    if(!name || !query) return alert('Completa los campos');
                    const id = 'custom-' + Date.now();
                    const html = `
                        <div class="sws-ep-item active" data-id="${id}">
                            <div style="display:flex; align-items:center; gap:12px;">
                                <input type="checkbox" class="sws-ep-check" checked>
                                <div><div class="ep-name">${name}</div><div class="ep-query">${query}</div></div>
                            </div>
                        </div>`;
                    $('#ep-list').prepend(html);
                    $('#new-name').val(''); $('#new-query').val('');
                    $('#add-form').slideUp();
                    log(`➕ Endpoint "${name}" agregado. Recuerda guardar.`, 'success');
                });

                // 1. Preview
                $('#btn-preview').click(function() {
                    const $btn = $(this);
                    $btn.prop('disabled', true).html('<div class="sws-spinner"></div> Analizando...');
                    $('#sws-logs').empty();
                    log('🚀 Conectando a API Syscom...');
                    
                    $.post(api.url, { action: 'sws_preview', nonce: api.nonce }, function(res) {
                        $btn.prop('disabled', false).html('1. Obtener Análisis');
                        if(res.success) {
                            productsCache = res.data.products;
                            applyFilters();
                            updateStats(res.data.stats);
                            log(`✅ Análisis: ${res.data.total} productos detectados.`, 'success');
                            if(res.data.total > 5000) log('ℹ️ Se han detectado más de 5,000 productos. Usa la búsqueda para filtrar.', 'warn');
                            $('#btn-sync').prop('disabled', false);
                            if(res.data.logs) res.data.logs.forEach(l => log(l));
                        } else {
                            log('❌ Error: ' + res.data.message, 'error');
                        }
                    }).fail(function() {
                        $btn.prop('disabled', false).html('1. Obtener Análisis');
                        log('❌ Error crítico del servidor (Posible Timeout).', 'error');
                    });
                });

                // 2. Sync
                $('#btn-sync').click(function() {
                    if(!productsCache.length) return alert('Sin datos. Ejecuta paso 1.');
                    const isSim = $('#sim_mode').is(':checked');
                    if(!confirm(`⚠️ Iniciar sincronización en ${isSim ? 'SIMULACIÓN' : 'PRODUCCIÓN'}?`)) return;
                    
                    const $btn = $(this);
                    const originalText = $btn.html();
                    $btn.prop('disabled', true);
                    processedResults = []; // Reset results
                    $('#final-results-area').hide(); // Hide previous report
                    
                    const chunkSize = 20; 
                    let chunks = [];
                    for (let i=0; i<productsCache.length; i+=chunkSize) chunks.push(productsCache.slice(i, i+chunkSize));
                    
                    // Enviar is_first_batch en la primera llamada para crear entrada en historial
                    processChunks(chunks, 0, $btn, originalText, isSim, true);
                });

                function processChunks(chunks, idx, $btn, originalText, isSim, isFirst) {
                    if(idx >= chunks.length) {
                        $btn.prop('disabled', false).html(originalText);
                        log('🏁 PROCESO FINALIZADO.', 'success');
                        showFinalReport(isSim);
                        
                        // Recargar historial (sin recargar pagina completa para no perder reporte)
                        // En una app real, hariamos un fetch del historial nuevo. Aqui simplificamos.
                        return;
                    }
                    
                    const pct = Math.round(((idx) / chunks.length) * 100);
                    $btn.html(`<div class="sws-spinner"></div> ${pct}%...`);
                    
                    $.post(api.url, {
                        action: 'sws_sync', nonce: api.nonce, 
                        products: JSON.stringify(chunks[idx]), 
                        sim_mode: isSim ? 1 : 0,
                        is_first_batch: isFirst ? 'true' : 'false'
                    }, function(res) {
                        if(res.success) {
                            if(res.data.logs) res.data.logs.forEach(l => log(l));
                            if(res.data.processed) processedResults = processedResults.concat(res.data.processed);
                            processChunks(chunks, idx+1, $btn, originalText, isSim, false);
                        } else {
                            log(`❌ Error fatal lote ${idx+1}`, 'error');
                            $btn.prop('disabled', false).text('Reintentar desde fallo');
                        }
                    }).fail(function() {
                        log('❌ Error de red en lote ' + (idx+1), 'error');
                        $btn.prop('disabled', false).text('Reintentar');
                    });
                }

                function showFinalReport(isSim) {
                    $('#final-results-area').show();
                    
                    let summaryHtml = `
                        <div id="final-results-header">
                            <h3 style="color:#fff; margin-top:0;">📊 Reporte Final de Ejecución (${isSim ? 'Simulación' : 'Producción'})</h3>
                            <p style="color:#94a3b8;">Total Procesados: <strong style="color:#fff;">${processedResults.length}</strong></p>
                        </div>
                        <div class="sws-table-container" style="max-height: 500px; overflow-y:auto;">
                            <table class="sws-table">
                                <thead><tr><th>SKU</th><th>Producto</th><th>Estado Final</th></tr></thead>
                                <tbody id="report-tbody"></tbody>
                            </table>
                        </div>
                    `;
                    $('#final-results-content').html(summaryHtml);
                    
                    // Render report rows
                    const $reportBody = $('#report-tbody');
                    processedResults.forEach(p => {
                        let statusColor = p.status_msg.includes('Error') ? '#ef4444' : '#10b981';
                        $reportBody.append(`
                            <tr>
                                <td style="font-family:monospace;">${p.sku}</td>
                                <td>${p.title}</td>
                                <td style="color:${statusColor}; font-weight:bold;">${p.status_msg}</td>
                            </tr>
                        `);
                    });
                    
                    $('html, body').animate({ scrollTop: $("#final-results-area").offset().top }, 1000);
                }

                function updateStats(s) {
                    $('#stat-total').text(s.total.toLocaleString());
                    $('#stat-new').text(s.create.toLocaleString());
                    $('#stat-upd').text(s.update.toLocaleString());
                    $('#stat-skip').text(s.skip.toLocaleString());
                }
                
                $('#clear-history').click(function(e) {
                    e.preventDefault();
                    if(confirm('¿Borrar historial?')) $.post(api.url, { action: 'sws_clear_history', nonce: api.nonce }, function() { location.reload(); });
                });
            });
            </script>
        <?php });
    }
    
    public function render_page() {
        if (!current_user_can('read')) return; 
        
        $token = get_option('sws_syscom_token', '');
        $markup = get_option('sws_markup_factor', 1.30);
        $prefix = get_option('sws_sku_prefix', 'it');
        $sim = get_option('sws_simulation_mode', 1);
        $history = get_option('sws_activity_log', []);
        ?>
        <div class="sws-wrap">
            <header class="sws-header">
                <div><h1>⚡ Syscom Sync <span class="sws-badge">Pro v<?php echo SWS_VERSION; ?></span></h1></div>
                <div style="display:flex; gap:10px;">
                    <button id="btn-save" class="sws-btn sws-btn-primary">💾 Guardar Config</button>
                    <button id="btn-reset" class="sws-btn sws-btn-warn">↻ Defaults</button>
                </div>
            </header>
            
            <div class="sws-grid">
                <div>
                    <div class="sws-card">
                        <h3>🔧 Configuración</h3>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                            <div class="sws-input-group"><label class="sws-label">Token API</label><input type="password" id="token" class="sws-input" value="<?php echo esc_attr($token); ?>"></div>
                            <div class="sws-input-group" style="display:flex; align-items:flex-end;">
                                <label style="color:#fff; display:flex; align-items:center; gap:10px; cursor:pointer; background: #262f45; padding: 8px 12px; border-radius: 6px; border:1px solid #334155;">
                                    <input type="checkbox" id="sim_mode" <?php checked($sim); ?> style="transform:scale(1.2)"> Modo Simulación
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sws-card">
						<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
							<h3 style="margin:0; border:none;">🔗 Endpoints Activos</h3>
							<div style="display:flex; gap:8px;">
							<button id="btn-uncheck-all" class="sws-btn sws-btn-secondary" style="font-size:11px; padding: 6px 12px;">Desmarcar Todos</button>
							<button id="btn-toggle-add" class="sws-btn sws-btn-success" style="font-size:11px; padding: 6px 12px;">+ Agregar Endpoint</button>
							</div>
						</div>
						
                        <div id="add-form" class="sws-add-form">
                            <input type="text" id="new-name" class="sws-input" placeholder="Nombre" style="margin-bottom:10px;">
                            <input type="text" id="new-query" class="sws-input" placeholder="Query" style="margin-bottom:10px;">
                            <div style="text-align:right;"><button id="btn-add-ep" class="sws-btn sws-btn-primary">Confirmar</button></div>
                        </div>
                        <div id="ep-list" class="sws-endpoints-box">
                            <?php foreach($this->endpoints as $ep): ?>
                            <div class="sws-ep-item <?php echo $ep['enabled']?'active':''; ?>" data-id="<?php echo esc_attr($ep['id']); ?>">
                                <div style="display:flex; align-items:center; gap:12px; width:100%;">
                                    <input type="checkbox" class="sws-ep-check" <?php checked($ep['enabled']); ?>>
                                    <div style="flex:1;"><div class="ep-name"><?php echo esc_html($ep['name']); ?></div><div class="ep-query"><?php echo esc_html($ep['query']); ?></div></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="sws-card">
                        <h3>🚀 Operaciones</h3>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <button id="btn-preview" class="sws-btn sws-btn-primary" style="width:100%; justify-content:center; padding: 12px;">1. Obtener Análisis</button>
                            <button id="btn-sync" class="sws-btn sws-btn-danger" style="width:100%; justify-content:center; padding: 12px;" disabled>2. Ejecutar Sincronización</button>
                        </div>
                        <div class="sws-stats">
                            <div class="sws-stat"><span id="stat-total" class="sws-stat-num">0</span><span class="sws-stat-label">Total</span></div>
                            <div class="sws-stat"><span id="stat-new" class="sws-stat-num" style="color:var(--sws-success)">0</span><span class="sws-stat-label">Nuevos</span></div>
                            <div class="sws-stat"><span id="stat-upd" class="sws-stat-num" style="color:var(--sws-accent)">0</span><span class="sws-stat-label">Update</span></div>
                            <div class="sws-stat"><span id="stat-skip" class="sws-stat-num" style="color:#64748b">0</span><span class="sws-stat-label">Skip</span></div>
                        </div>
                        <a href="<?php echo admin_url('edit.php?post_status=draft&post_type=product'); ?>" target="_blank" class="sws-btn sws-btn-secondary" style="width:100%; justify-content:center; margin-top:20px;">📂 Ver Borradores ↗</a>
                    </div>
                    
                    <div class="sws-card">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;"><h3>🕒 Historial</h3><a href="#" id="clear-history" style="font-size:11px; color:#ef4444;">Limpiar</a></div>
                        <ul class="sws-history-list">
                            <?php if(empty($history)): ?><li class="sws-history-item">Sin actividad.</li><?php else: ?>
                                <?php foreach(array_reverse($history) as $entry): ?>
                                    <li class="sws-history-item"><span class="sws-history-date"><?php echo esc_html($entry['date']); ?></span><span><?php echo esc_html($entry['type']); ?></span><span style="color:var(--sws-accent)"><?php echo esc_html($entry['count']); ?> items</span></li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="sws-logs" id="sws-logs"></div>
                </div>
            </div>
            
            <div class="sws-card">
                <div class="sws-controls">
                    <h3>📋 Vista Detallada</h3>
                    <div class="sws-filters">
                        <button class="sws-filter-btn active" data-filter="all">Todos</button>
                        <button class="sws-filter-btn" data-filter="create">Nuevos</button>
                        <button class="sws-filter-btn" data-filter="update">Actualizados</button>
                        <button class="sws-filter-btn" data-filter="skip">Sin Cambios</button>
                    </div>
                    <input type="text" id="search-input" class="sws-input sws-search" placeholder="🔍 Buscar por SKU o Título...">
                    <div class="sws-pagination">
                        <button id="btn-prev" class="sws-btn sws-btn-secondary" disabled>«</button>
                        <span id="page-info">0 de 0</span>
                        <button id="btn-next" class="sws-btn sws-btn-secondary" disabled>»</button>
                    </div>
                </div>
                <div class="sws-table-container">
                    <table class="sws-table">
                        <thead><tr><th>SKU</th><th>Producto</th><th>Precio (Orig ➜ Nuevo)</th><th>Stock</th><th>Estado</th><th>Link</th></tr></thead>
                        <tbody id="table-body"><tr><td colspan="6" style="text-align:center; padding:30px; color:#64748b;">Ejecuta el "Análisis" para ver los datos.</td></tr></tbody>
                    </table>
                </div>
            </div>

            <div id="final-results-area" class="sws-card" style="border: 2px solid var(--sws-success);">
                <div id="final-results-content"></div>
            </div>
        </div>
        <?php
    }
    
    // --- AJAX ---
    
    public function ajax_save() {
        check_ajax_referer('sws_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Sin permisos']);
        
        update_option('sws_syscom_token', sanitize_text_field($_POST['token']));
        update_option('sws_markup_factor', floatval($_POST['markup']));
        update_option('sws_sku_prefix', sanitize_text_field($_POST['prefix']));
        update_option('sws_simulation_mode', intval($_POST['sim_mode']));
        
        $ep_data = json_decode(stripslashes($_POST['endpoints']), true);
        
        /*
        if(is_array($ep_data)) update_option('sws_endpoints', $ep_data);
        wp_send_json_success();
        */

        if(is_array($ep_data)) {
                // --- NUEVA LÓGICA DE ORDENAMIENTO ---
                usort($ep_data, function($a, $b) {
                    // Si el estado es igual, mantiene orden original. 
                    // Si son diferentes, pone los 'true' (habilitados) arriba.
                    return (int)($b['enabled'] === true || $b['enabled'] === 'true') - (int)($a['enabled'] === true || $a['enabled'] === 'true');
                });
                // ------------------------------------
                update_option('sws_endpoints', $ep_data);
            }
            
            wp_send_json_success();



    }
    
    public function ajax_reset() {
        check_ajax_referer('sws_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();
        delete_option('sws_endpoints');
        wp_send_json_success();
    }

    public function ajax_clear_history() {
        check_ajax_referer('sws_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();
        delete_option('sws_activity_log');
        wp_send_json_success();
    }
    
    public function ajax_preview() {
        check_ajax_referer('sws_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();
        @ini_set('memory_limit', '1024M'); @set_time_limit(300);
        
        $token = get_option('sws_syscom_token');
        $markup = floatval(get_option('sws_markup_factor', 1.30));
        $prefix = get_option('sws_sku_prefix', 'it');
        $active_eps = array_filter($this->endpoints, function($e) { return $e['enabled'] == 'true' || $e['enabled'] === true; });
        
        if(empty($token)) wp_send_json_error(['message' => 'Falta token']);
        
        $all_products = []; $logs = [];
        
        foreach($active_eps as $ep) {
            $page = 1; $max_pages = 1;
            do {
                $url = 'https://developers.syscom.mx/api/v1/productos?' . $ep['query'] . '&pagina=' . $page;
                $res = wp_remote_get($url, ['headers' => ['Authorization' => 'Bearer '.$token], 'timeout' => 20]);
                if(is_wp_error($res)) { $logs[] = "❌ Error {$ep['name']}"; break; }
                
                $data = json_decode(wp_remote_retrieve_body($res), true);
                if(empty($data['productos'])) break;
                
                $max_pages = intval($data['paginas']);
                $logs[] = "📥 {$ep['name']}: Pag $page/$max_pages";
                
                foreach($data['productos'] as $p) {
                    $all_products[] = $this->map_product($p, $markup, $prefix);
                }
                $page++;
                if(count($all_products) > 5500) break 2; // Límite de seguridad
                usleep(100000); 
            } while ($page <= $max_pages);
        }
        
        $analyzed = $this->analyze_products($all_products);
        $stats = $this->get_stats($analyzed);
        wp_send_json_success(['products' => $analyzed, 'stats' => $stats, 'total' => count($analyzed), 'logs' => $logs]);
    }
    

    private function map_product($p, $markup, $prefix) {

        global $wpdb;
        
        $modelo_original = $p['modelo']; 
        
        $price_final = floatval($p['precios']['precio_descuento']);

        /*
        $sku_formateado = trim(str_replace(['/', '\\'], '-', $modelo_original));
        */

        $sku_formateado = trim((string)$modelo_original);

        return [
            'sku'             => $sku_formateado, 
            'modelo_original' => $modelo_original,
            'title'           => $p['titulo'],
            'price_final'     => $price_final,
            'price_api'       => floatval($p['precios']['precio_descuento'] ?? 0),
            'stock'           => intval($p['total_existencia']),
            'image_url'       => $p['img_portada'] ?? '',
            'action'          => 'create',
            'woo_id'          => null,
            'woo_price'       => 0,
            'woo_stock'       => 0,
            'changes'         => []
        ];
    }
    
    private function analyze_products($products) {
        global $wpdb;
        $skus = array_column($products, 'sku');
        if(empty($skus)) return $products;
        
        $chunks = array_chunk($skus, 1000);
        $existing = [];
        
        foreach($chunks as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '%s'));
            $sql = "SELECT p.ID, pm.meta_value as sku, 
                    (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = '_price') as price,
                    (SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = p.ID AND meta_key = '_stock') as stock
                    FROM {$wpdb->posts} p 
                    JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
                    WHERE pm.meta_key='_sku' AND pm.meta_value IN ($placeholders)
                    AND p.post_status IN ('publish', 'draft', 'private')";
            $rows = $wpdb->get_results($wpdb->prepare($sql, $chunk));
            foreach($rows as $row) $existing[$row->sku] = $row;
        }
        
        foreach($products as &$p) {
            if(isset($existing[$p['sku']])) {
                $woo = $existing[$p['sku']];
                $p['woo_id'] = $woo->ID;
                $p['woo_price'] = floatval($woo->price);
                $p['woo_stock'] = intval($woo->stock);
                
                $price_diff = abs($p['price_final'] - $p['woo_price']) > 0.01;
                $stock_diff = $p['stock'] !== $p['woo_stock'];
                
                if($price_diff || $stock_diff) {
                    $p['action'] = 'update';
                    if($price_diff) $p['changes'][] = 'price';
                    if($stock_diff) $p['changes'][] = 'stock';
                } else {
                    $p['action'] = 'skip';
                }
            }
        }
        return $products;
    }
    
    private function get_stats($products) {
        $s = ['total' => count($products), 'create' => 0, 'update' => 0, 'skip' => 0];
        foreach($products as $p) {
            if($p['action'] == 'create') $s['create']++;
            elseif($p['action'] == 'update') $s['update']++;
            else $s['skip']++;
        }
        return $s;
    }
    
    public function ajax_sync() {
        global $wpdb;
        check_ajax_referer('sws_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Sin permisos']);
        
        $products = json_decode(stripslashes($_POST['products']), true);
        $sim = isset($_POST['sim_mode']) ? (int)$_POST['sim_mode'] : 1;
        $logs = [];
        $processed_items = []; 
        
        if(!is_array($products)) wp_send_json_error(['message' => 'Datos inválidos']);
        
        if(isset($_POST['is_first_batch']) && $_POST['is_first_batch'] == 'true') {
            $history = get_option('sws_activity_log', []);
            $new_entry = ['date' => current_time('mysql'), 'type' => $sim ? 'Simulación' : 'Producción', 'count' => 'Iniciado...'];
            array_unshift($history, $new_entry);
            $history = array_slice($history, 0, 5);
            update_option('sws_activity_log', $history);
        }

            
        // --- INICIO CAMBIO ESTRATÉGICO: OBTENCIÓN DE TIPO DE CAMBIO ---
        $token = get_option('sws_syscom_token', '');
        $tipo_cambio = 1.0;
        $response_tc = wp_remote_get("https://developers.syscom.mx/api/v1/tipocambio", [
        'headers' => ['Authorization' => 'Bearer ' . $token]
        ]);

        if (!is_wp_error($response_tc) && wp_remote_retrieve_response_code($response_tc) === 200) {
        $body_tc = json_decode(wp_remote_retrieve_body($response_tc), true);
        $tipo_cambio = isset($body_tc['normal']) ? floatval($body_tc['normal']) : 1.0;
        }

        // --- FIN CAMBIO TIPO DE CAMBIO ---
        // --- DEFINICIÓN DE FACTORES GUÍA (IVA 1.16 y Factor 0.96) ---

        $iva_multiplicador = 1.16;
        $otro_factor = 0.96;
        
        foreach($products as $p) {
            if($p['action'] == 'skip') continue;
            
            $status_msg = "";
            $new_id = null;
            
            if($sim) {
                $logs[] = "[SIM] {$p['action']} SKU: {$p['sku']}";
                $status_msg = "Simulado";
            } else {
                try {

                

                    $precio_base_api = floatval($p['price_api']);

                    $precio_convertido = $precio_base_api * $tipo_cambio * $iva_multiplicador * $otro_factor;

                    $resultado_sql = $wpdb->get_row($wpdb->prepare(
                        "SELECT * FROM kmq_intrax_precios WHERE %f BETWEEN desde AND hasta LIMIT 1", 
                        $precio_convertido
                    ));
                    
                    $markup_publico = ($resultado_sql !== null) ? floatval($resultado_sql->venta_publico) : 0.0;
                    $markup_instalador = ($resultado_sql !== null) ? floatval($resultado_sql->integrador) : 0.0;

                    // Lógica de factor: si el valor es < 1 (ej: 0.20) se suma 1. Si es >= 1 (ej: 1.20) se usa directo.
                    $f_pub = ($markup_publico < 1 && $markup_publico > 0) ? (1 + $markup_publico) : $markup_publico;
                    $f_ins = ($markup_instalador < 1 && $markup_instalador > 0) ? (1 + $markup_instalador) : $markup_instalador;
                    
                    // Si no hay rango (markup 0), el factor debe ser 0 en 0
                    $f_pub = ($f_pub == 0) ? 0 : $f_pub;
                    $f_ins = ($f_ins == 0) ? 0 : $f_ins;

                    $precio_final_producto = number_format($precio_convertido * $f_pub, 2, '.', '');
                    $precio_final_instalador = number_format($precio_convertido * $f_ins, 2, '.', '');
                  
                    if($precio_final_producto === null) {
                        $precio_final_producto = floatval(0);
                    }                    

                    if($p['action'] == 'create') {
                        
                        $prod = new WC_Product_Simple();
                        $prod->set_name($p['title']);
                        $prod->set_sku($p['sku']);
                        $prod->set_status('draft'); 
                        $prod->set_regular_price($precio_final_producto);
                        $prod->set_manage_stock(true);
                        $prod->set_stock_quantity($p['stock']);
                        
                        $prod->update_meta_data('_modelo_original_syscom', $p['modelo_original']);
                        $prod->update_meta_data('_precio_original_syscom', $p['price_api']);
                        $prod->update_meta_data('_producto_syscom_api', date("Y-m-d H:i:s"));
                        $prod->update_meta_data('wholesale_customer_have_wholesale_price', 'yes');
                        $prod->update_meta_data('wholesale_customer_wholesale_price', $precio_final_instalador);

                

						if (!empty($p['image_url'])) {
							$image_id = $this->upload_image_from_url($p['image_url'], $p['sku']);
							if ($image_id) {
							    $prod->set_image_id($image_id);
							}
						}
						
                        $new_id = $prod->save();
                        

                            $logs[] = "🟢 CREADO EN PROD | " .
                            "SKU: {$p['sku']} | " .
                            "PRECIO BASE: {$precio_base_api} | " .
                            "iva_multiplicador: {$iva_multiplicador} | " .
                            "otro_factor: {$otro_factor} | " .
                            "PRECIO CONVERTIDO: {$precio_convertido} | " .
                            "precio_final_producto: {$precio_final_producto} | " .
                            "precio_final_instalador: {$precio_final_instalador} | " .
                            "\n";                            

                            $status_msg = "Creado";                        

                    } 
                    elseif($p['action'] == 'update' && $p['woo_id']) {
                        
                        $prod = wc_get_product($p['woo_id']);
                        if($prod) {
                            if(in_array('price', $p['changes'])) $prod->set_regular_price($precio_final_producto);
                            if(in_array('stock', $p['changes'])) $prod->set_stock_quantity($p['stock']);
                            
                            $prod->update_meta_data('_producto_syscom_api', date("Y-m-d H:i:s"));
                            $prod->update_meta_data('_precio_original_syscom', $p['price_api']);
                            $prod->update_meta_data('wholesale_customer_have_wholesale_price', 'yes');
                            $prod->update_meta_data('wholesale_customer_wholesale_price', $precio_final_instalador);
                            $prod->update_meta_data('_last_sync_update',  date("Y-m-d H:i:s"));
                            

                            $prod->save();

                            $logs[] = "🟢 ACTUALIZADO EN PROD | " .
                            "SKU: {$p['sku']} | " .
                            "PRECIO BASE: {$precio_base_api} | " .
                            "iva_multiplicador: {$iva_multiplicador} | " .
                            "otro_factor: {$otro_factor} | " .
                            "PRECIO CONVERTIDO: {$precio_convertido} | " .
                            "precio_final_producto: {$precio_final_producto} | " .
                            "precio_final_instalador: {$precio_final_instalador} | " .
                            "\n";                            

                            $status_msg = "Actualizado";


                        }
                    }
                } catch(Exception $e) {
                    $logs[] = "❌ Error {$p['sku']}: " . $e->getMessage();
                    $status_msg = "Error";
                }
            }
            
            $processed_items[] = ['sku' => $p['sku'], 'title' => $p['title'], 'status_msg' => $status_msg, 'new_id' => $new_id];
        }
        
        wp_send_json_success(['logs' => $logs, 'processed' => $processed_items]);
    }
	
    private function upload_image_from_url($url, $sku) {
        if (empty($url)) return null;
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($url);
        if (is_wp_error($tmp)) return null;

        $file_array = [
            'name'     => $sku . '.jpg',
            'tmp_name' => $tmp
        ];

        $id = media_handle_sideload($file_array, 0);
        if (is_wp_error($id)) {
            @unlink($tmp);
            return null;
        }
        return $id; 
    }

    public function render_price_page() {
        echo '<div class="sws-wrap"><h1>💰 Gestor de Precios</h1><p>Configuración de reglas de precios aquí.</p></div>';
    }

    public function render_audit_page() {
        echo '<div class="sws-wrap"><h1>🔍 Auditoría de Productos</h1><p>Historial de cambios y logs aquí.</p></div>';
    }
	
    public function render_visor_page() {
        echo '<div class="sws-wrap"><h1>🔍 Visor de Logs</h1><p>Visor de Logs</p></div>';
    }
}

add_action('plugins_loaded', ['Syscom_Woo_Sync', 'get_instance']);