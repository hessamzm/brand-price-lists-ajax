<?php
/**
 * Plugin Name: Brand Price Lists AJAX
 * Description: نمایش سریع و AJAX لیست قیمت محصولات ووکامرس بر اساس برند با بارگذاری مرحله‌ای محصولات و ستون‌های قابل تنظیم.
 * Version: 2.0.0
 * Author: hessamzm
 * Author URI: https://github.com/hessamzm
 * Requires at least: 7.0
 * Requires PHP: 8.2
 * Requires Plugins: elementor, woocommerce
 * Text Domain: brand-price-lists-ajax
 */

if (!defined('ABSPATH')) exit;

class WC_Brand_Price_Lists {
    const OPTION = 'price_list_settings';
    const NONCE = 'price_list_ajax';
    const BRAND_CACHE = 'price_list_brands_v2';
    const BATCH_SIZE = 5;

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('price_list', array($this, 'shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_assets'));
        add_action('wp_ajax_price_list_load_products', array($this, 'ajax_load_products'));
        add_action('wp_ajax_nopriv_price_list_load_products', array($this, 'ajax_load_products'));
        add_action('update_option_' . self::OPTION, array($this, 'clear_brand_cache'));
        add_action('created_term', array($this, 'clear_term_cache'), 10, 3);
        add_action('edited_term', array($this, 'clear_term_cache'), 10, 3);
        add_action('delete_term', array($this, 'clear_term_cache'), 10, 4);
    }

    public function admin_menu() {
        add_menu_page(
            'لیست قیمت برندها', 'لیست قیمت برندها', 'manage_woocommerce',
            'price-lists', array($this, 'settings_page'), 'dashicons-list-view', 56
        );
    }

    public function register_settings() {
        register_setting('price_list_group', self::OPTION, array($this, 'sanitize_settings'));
    }

    private function defaults() {
        return array(
            'brand_taxonomy' => 'product_brand',
            'title' => 'لیست قیمت محصولات',
            'show_brand_header' => 1,
            'show_brand_logo' => 1,
            'columns' => array(
                array('key' => 'image', 'label' => 'تصویر', 'enabled' => 1),
                array('key' => 'name', 'label' => 'نام محصول', 'enabled' => 1),
                array('key' => 'sku', 'label' => 'کد کالا', 'enabled' => 1),
                array('key' => 'price', 'label' => 'قیمت', 'enabled' => 1),
                array('key' => 'sale_price', 'label' => 'قیمت ویژه', 'enabled' => 1),
                array('key' => 'stock', 'label' => 'موجودی', 'enabled' => 0),
                array('key' => 'button', 'label' => 'خرید', 'enabled' => 1),
            ),
            'per_page' => 100,
            'brand_order' => 'name',
        );
    }

    public function settings() {
        return wp_parse_args(get_option(self::OPTION, array()), $this->defaults());
    }

    public function sanitize_settings($input) {
        $defaults = $this->defaults();
        $out = $defaults;
        $out['brand_taxonomy'] = isset($input['brand_taxonomy']) ? sanitize_key($input['brand_taxonomy']) : $defaults['brand_taxonomy'];
        $out['title'] = isset($input['title']) ? sanitize_text_field($input['title']) : $defaults['title'];
        $out['show_brand_header'] = empty($input['show_brand_header']) ? 0 : 1;
        $out['show_brand_logo'] = empty($input['show_brand_logo']) ? 0 : 1;
        $out['per_page'] = isset($input['per_page']) ? max(1, min(500, absint($input['per_page']))) : 100;
        $out['brand_order'] = (isset($input['brand_order']) && in_array($input['brand_order'], array('name','count'), true)) ? $input['brand_order'] : 'name';

        $allowed = array('image','name','sku','price','sale_price','stock','category','button');
        $columns = array();
        if (!empty($input['columns']) && is_array($input['columns'])) {
            foreach ($input['columns'] as $col) {
                $key = isset($col['key']) ? sanitize_key($col['key']) : '';
                if (!in_array($key, $allowed, true)) continue;
                $columns[] = array(
                    'key' => $key,
                    'label' => isset($col['label']) ? sanitize_text_field($col['label']) : $key,
                    'enabled' => empty($col['enabled']) ? 0 : 1,
                );
            }
        }
        $out['columns'] = $columns ? $columns : $defaults['columns'];
        return $out;
    }

    private function available_columns() {
        return array(
            'image' => 'تصویر', 'name' => 'نام محصول', 'sku' => 'کد کالا',
            'price' => 'قیمت', 'sale_price' => 'قیمت ویژه', 'stock' => 'موجودی',
            'category' => 'دسته‌بندی', 'button' => 'خرید',
        );
    }

    public function settings_page() {
        if (!current_user_can('manage_woocommerce')) return;
        $s = $this->settings();
        $taxonomies = get_object_taxonomies('product', 'objects');
        ?>
        <div class="wrap" dir="rtl">
            <h1>لیست قیمت برندهای فروشگاه سایه</h1>
            <p>این افزونه لیست قیمت را با شورت‌کد <code>[price_list]</code> نمایش می‌دهد. برندها ابتدا سریع بارگذاری می‌شوند و محصولات هر برند با AJAX و در بسته‌های ۵تایی دریافت می‌شوند.</p>
            <form method="post" action="options.php">
                <?php settings_fields('price_list_group'); ?>
                <table class="form-table">
                    <tr><th>عنوان لیست</th><td><input type="text" name="<?php echo esc_attr(self::OPTION); ?>[title]" value="<?php echo esc_attr($s['title']); ?>" class="regular-text"></td></tr>
                    <tr><th>Taxonomy برند</th><td><select name="<?php echo esc_attr(self::OPTION); ?>[brand_taxonomy]">
                        <?php foreach ($taxonomies as $tax) : ?><option value="<?php echo esc_attr($tax->name); ?>" <?php selected($s['brand_taxonomy'], $tax->name); ?>><?php echo esc_html($tax->label . ' (' . $tax->name . ')'); ?></option><?php endforeach; ?>
                        <option value="product_brand" <?php selected($s['brand_taxonomy'], 'product_brand'); ?>>product_brand</option>
                    </select><p class="description">مثلاً در برخی سایت‌ها برند با افزونه Perfect Brands یا مشابه آن با taxonomy دیگری ذخیره می‌شود.</p></td></tr>
                    <tr><th>هدر اختصاصی برند</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[show_brand_header]" value="1" <?php checked($s['show_brand_header'], 1); ?>> نمایش هدر برای هر برند</label></td></tr>
                    <tr><th>لوگوی برند</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[show_brand_logo]" value="1" <?php checked($s['show_brand_logo'], 1); ?>> نمایش تصویر/لوگوی برند در صورت وجود</label></td></tr>
                    <tr><th>تعداد محصولات در هر برند</th><td><input type="number" min="1" max="500" name="<?php echo esc_attr(self::OPTION); ?>[per_page]" value="<?php echo esc_attr($s['per_page']); ?>"><p class="description">این تنظیم برای سازگاری حفظ شده است؛ نسخه AJAX در هر درخواست ۵ محصول بارگذاری می‌کند.</p></td></tr>
                    <tr><th>ترتیب برندها</th><td><select name="<?php echo esc_attr(self::OPTION); ?>[brand_order]"><option value="name" <?php selected($s['brand_order'], 'name'); ?>>نام برند</option><option value="count" <?php selected($s['brand_order'], 'count'); ?>>تعداد محصول</option></select></td></tr>
                </table>
                <h2>ستون‌های لیست</h2><p>با افزودن/حذف ردیف‌ها و تغییر عنوان هر ستون، ظاهر لیست را سفارشی کنید.</p>
                <table class="widefat" id="price-columns-table"><thead><tr><th>ستون</th><th>عنوان نمایشی</th><th>نمایش</th><th>عملیات</th></tr></thead><tbody>
                <?php foreach ($s['columns'] as $i => $col) : ?><tr>
                    <td><select name="<?php echo esc_attr(self::OPTION); ?>[columns][<?php echo esc_attr($i); ?>][key]"><?php foreach ($this->available_columns() as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($col['key'], $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></td>
                    <td><input type="text" name="<?php echo esc_attr(self::OPTION); ?>[columns][<?php echo esc_attr($i); ?>][label]" value="<?php echo esc_attr($col['label']); ?>"></td>
                    <td><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[columns][<?php echo esc_attr($i); ?>][enabled]" value="1" <?php checked($col['enabled'], 1); ?>></td>
                    <td><button type="button" class="button price-remove-row">حذف</button></td>
                </tr><?php endforeach; ?></tbody></table>
                <p><button type="button" class="button" id="price-add-column">افزودن ستون</button></p>
                <?php submit_button('ذخیره تنظیمات'); ?>
            </form>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded',function(){const t=document.querySelector('#price-columns-table tbody'),a=document.querySelector('#price-add-column'),p='<?php echo esc_js(self::OPTION); ?>';
        document.querySelectorAll('.price-remove-row').forEach(b=>b.addEventListener('click',()=>b.closest('tr').remove()));
        a.addEventListener('click',function(){const i=t.querySelectorAll('tr').length,r=document.createElement('tr');r.innerHTML='<td><select name="'+p+'[columns]['+i+'][key]"><option value="image">تصویر</option><option value="name">نام محصول</option><option value="sku">کد کالا</option><option value="price">قیمت</option><option value="sale_price">قیمت ویژه</option><option value="stock">موجودی</option><option value="category">دسته‌بندی</option><option value="button">خرید</option></select></td><td><input type="text" name="'+p+'[columns]['+i+'][label]"></td><td><input type="checkbox" name="'+p+'[columns]['+i+'][enabled]" value="1" checked></td><td><button type="button" class="button price-remove-row">حذف</button></td>';t.appendChild(r);r.querySelector('.price-remove-row').addEventListener('click',()=>r.remove());});});
        </script>
        <?php
    }

    public function frontend_assets() {
        wp_register_style('price-list', false);
        wp_enqueue_style('price-list');
        wp_add_inline_style('price-list', $this->css());
        wp_register_script('price-list', false, array(), '3.1.0', true);
        wp_enqueue_script('price-list');
        wp_add_inline_script('price-list', $this->js());
        wp_localize_script('price-list', 'PriceList', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE),
            'batchSize' => self::BATCH_SIZE,
            'loading' => 'در حال بارگذاری...',
            'more' => 'بارگذاری محصولات بیشتر',
            'error' => 'خطا در بارگذاری محصولات. دوباره تلاش کنید.',
        ));
    }

    private function css() {
        return <<<'CSS'
.price-list{width:100%;margin:28px 0;direction:rtl;font-family:inherit}.price-list *{box-sizing:border-box}.price-list-title{margin:0 0 24px;font-weight:800}.brands{display:flex;flex-direction:column;gap:24px}.brand-block{display:grid;grid-template-columns:minmax(0,var(--brand-width,10%)) minmax(0,var(--products-width,90%));border:1px solid #e8edf3;border-radius:18px;overflow:hidden;background:#fff;box-shadow:0 8px 28px rgba(15,23,42,.06)}.brand-block.is-reverse{grid-template-columns:minmax(0,var(--products-width,90%)) minmax(0,var(--brand-width,10%))}.brand-block.is-reverse .brand-info{order:2}.brand-block.is-reverse .brand-products{order:1}.brand-info{padding:18px 10px;background:#111827;color:#fff;min-height:180px;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;gap:8px}.brand-logo{width:48px;height:48px;object-fit:contain;border-radius:10px;background:#fff;padding:5px;box-shadow:0 5px 14px rgba(0,0,0,.14)}.brand-info h2{margin:0;color:inherit;line-height:1.5;word-break:break-word}.brand-count{opacity:.78;font-size:11px}.brand-products{padding:0;min-width:0;background:#fff}.product-table-wrap{overflow-x:auto}.price-list table{width:100%;border-collapse:collapse;min-width:620px}.price-list th,.price-list td{padding:11px 10px;border-bottom:1px solid #edf0f4;text-align:right;vertical-align:middle;border-color:#edf0f4}.price-list th{background:#f8fafc;font-weight:800;position:sticky;top:0;z-index:1}.price-list tr:last-child td{border-bottom:0}.price-list td a{color:inherit}.price-list img.product-image{width:52px;height:52px;object-fit:contain;border-radius:9px;background:#fafafa;display:block}.price-cell,.sale-price-cell{font-weight:800}.price-list .buy,.load-more{display:inline-block;padding:8px 12px;border:0;border-radius:9px;text-decoration:none;font-weight:700;white-space:nowrap;background:#111827;color:#fff}.load-more{cursor:pointer}.load-more:disabled{opacity:.55;cursor:wait}.more-wrap{text-align:center;padding:14px;border-top:1px solid #edf0f4}.empty,.error{padding:22px;text-align:center;color:#64748b}.skeleton{height:48px;margin:10px;border-radius:9px;background:linear-gradient(90deg,#f3f4f6 25%,#e5e7eb 37%,#f3f4f6 63%);background-size:400% 100%;animation:priceListShimmer 1.2s infinite}@keyframes priceListShimmer{0%{background-position:100% 0}100%{background-position:-100% 0}}.layout-stacked .brand-block,.layout-stacked .brand-block.is-reverse{display:block}.layout-stacked .brand-info{min-height:110px;flex-direction:row;justify-content:flex-start;padding:14px 16px;text-align:right}.layout-compact .brand-block{box-shadow:none;border-radius:10px}.layout-compact .brand-info{min-height:90px;padding:10px}.layout-table .brand-block{display:block}.layout-table .brand-info{min-height:70px;flex-direction:row;justify-content:flex-start;padding:12px 16px;text-align:right}.product-hover-enabled .brand-products tbody tr{transition:background-color .18s ease,color .18s ease}.product-hover-enabled .brand-products tbody tr:hover{background:#f8fafc}.button-hover-enabled .load-more,.button-hover-enabled .buy{transition:background-color .18s ease,color .18s ease,transform .18s ease}.button-hover-enabled .load-more:hover,.button-hover-enabled .buy:hover{transform:translateY(-1px)}@media(max-width:900px){.brand-block,.brand-block.is-reverse{grid-template-columns:1fr}.brand-block.is-reverse .brand-info,.brand-block.is-reverse .brand-products{order:initial}.brand-info{min-height:110px;flex-direction:row;justify-content:flex-start;padding:14px 16px;text-align:right}.price-list table{min-width:620px}}@media(max-width:600px){.brand-info{min-height:88px}.brand-logo{width:38px;height:38px}.price-list table{min-width:560px}.price-list th,.price-list td{padding:9px 8px}}
CSS;
    }

    private function js() {
        return <<<'JS'
(function(){'use strict';
function init(){document.querySelectorAll('.price-list').forEach(function(root){if(root.dataset.ready)return;root.dataset.ready='1';root.addEventListener('click',function(e){var btn=e.target.closest('.load-more');if(!btn)return;e.preventDefault();loadBox(btn.closest('.brand-block'),btn);});var blocks=root.querySelectorAll('.brand-block');if('IntersectionObserver' in window){var io=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){var box=entry.target;io.unobserve(box);if(!box.dataset.loaded)loadBox(box,box.querySelector('.load-more'));}});},{rootMargin:'350px 0px'});blocks.forEach(function(box){io.observe(box);});}else blocks.forEach(function(box){loadBox(box,box.querySelector('.load-more'));});});}
function loadBox(box,btn){if(!box||box.dataset.loading==='1')return;var root=box.closest('.price-list'),list=box.querySelector('.product-list'),offset=parseInt((btn&&btn.dataset.offset)||box.dataset.offset||'0',10),brand=box.dataset.brand,tax=box.dataset.taxonomy||root.dataset.taxonomy||'';box.dataset.loading='1';if(btn){btn.disabled=true;btn.textContent=PriceList.loading;}var fd=new FormData();fd.append('action','price_list_load_products');fd.append('nonce',PriceList.nonce);fd.append('brand',brand);fd.append('taxonomy',tax);fd.append('offset',offset);fd.append('limit',Math.min(50,parseInt(root.dataset.batchSize||PriceList.batchSize,10)));fd.append('columns',root.dataset.columns||'[]');fd.append('filters',root.dataset.filters||'{}');fd.append('button_text',root.dataset.buttonText||'بارگذاری محصولات بیشتر');fetch(PriceList.ajaxUrl,{method:'POST',credentials:'same-origin',body:fd}).then(function(r){if(!r.ok)throw new Error('HTTP');return r.json();}).then(function(res){if(!res.success)throw new Error(res.data&&res.data.message?res.data.message:'AJAX');var skeleton=list.querySelector('.skeleton-row');if(skeleton)skeleton.remove();list.insertAdjacentHTML('beforeend',res.data.html||'');box.dataset.loaded='1';box.dataset.offset=String(offset+Number(res.data.count||0));var wrap=box.querySelector('.more-wrap');if(btn){if(res.data.has_more){btn.dataset.offset=String(offset+Number(res.data.count||0));btn.disabled=false;btn.textContent=res.data.button_text||PriceList.more;}else if(wrap)wrap.remove();}else if(res.data.has_more&&wrap){var b=wrap.querySelector('.load-more');if(b){b.dataset.offset=String(offset+Number(res.data.count||0));b.textContent=res.data.button_text||PriceList.more;}}if(!res.data.count&&wrap)wrap.remove();}).catch(function(err){if(btn){btn.disabled=false;btn.textContent=PriceList.more;}var old=box.querySelector('.error');if(!old){old=document.createElement('div');old.className='error';old.textContent=PriceList.error;box.querySelector('.product-list').insertAdjacentElement('afterend',old);}}).finally(function(){box.dataset.loading='0';});}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();})();
JS;
    }

    public function shortcode($atts) {
        $atts = shortcode_atts(array('brand'=>'','limit'=>$this->settings()['per_page'],'orderby'=>'title','order'=>'ASC'), $atts, 'price_list');
        return $this->render_price_list($atts);
    }

    public function render_price_list($atts = array()) {
        if (!class_exists('WooCommerce')) return '<p>ووکامرس فعال نیست.</p>';
        $s = $this->settings();
        $atts = wp_parse_args($atts, array('brand'=>'','limit'=>$s['per_page'],'orderby'=>'title','order'=>'ASC','title'=>$s['title'],'show_brand_header'=>!empty($s['show_brand_header'])?'1':'0','show_brand_logo'=>!empty($s['show_brand_logo'])?'1':'0','brand_order'=>$s['brand_order'],'batch_size'=>self::BATCH_SIZE,'zigzag'=>'1','layout'=>'zigzag','product_search'=>'','stock_filter'=>'all','sale_only'=>'0','featured_only'=>'0','category'=>0,'button_text'=>'بارگذاری محصولات بیشتر'));
        $batch_size = min(50, max(1, absint($atts['batch_size'])));
        $tax = sanitize_key($s['brand_taxonomy']);
        $brand_filter = $atts['brand'];
        $brands = $this->get_brands($tax, $brand_filter, $atts['brand_order']);
        if (is_wp_error($brands) || empty($brands)) return '<p>برندی برای نمایش پیدا نشد.</p>';
        $enabled_columns = !empty($atts['columns']) && is_array($atts['columns']) ? array_values(array_filter($atts['columns'], function($c){ return !empty($c['enabled']) && !empty($c['key']); })) : array_values(array_filter($s['columns'], function($c){return !empty($c['enabled']);}));
        if (!$enabled_columns) $enabled_columns = array(array('key'=>'name','label'=>'نام محصول','enabled'=>'1'));
        $filters = array('search'=>sanitize_text_field($atts['product_search']),'stock'=>in_array($atts['stock_filter'],array('all','instock','outofstock','onbackorder'),true)?$atts['stock_filter']:'all','sale'=>!empty($atts['sale_only']),'featured'=>!empty($atts['featured_only']),'category'=>absint($atts['category']),'orderby'=>in_array($atts['orderby'],array('title','date','menu_order','price'),true)?$atts['orderby']:'title','order'=>($atts['order']==='DESC'?'DESC':'ASC'));
        $layout = in_array($atts['layout'],array('zigzag','stacked','compact','table'),true)?$atts['layout']:'zigzag';
        $classes='price-list layout-'.esc_attr($layout);
        if($layout==='zigzag' && $atts['zigzag']==='1') $classes.=' zigzag-enabled';
        $classes.=' product-hover-enabled button-hover-enabled';
        ob_start();
        echo '<div class="'.esc_attr($classes).'" data-taxonomy="'.esc_attr($tax).'" data-batch-size="'.esc_attr($batch_size).'" data-columns="'.esc_attr(wp_json_encode($enabled_columns)).'" data-filters="'.esc_attr(wp_json_encode($filters)).'" data-button-text="'.esc_attr($atts['button_text']).'">';
        if ($atts['title'] !== '') echo '<h1 class="price-list-title">'.esc_html($atts['title']).'</h1>';
        echo '<div class="brands">'; $i=0;
        foreach($brands as $brand){
            $count=(int)$brand->count; $logo='';
            if($atts['show_brand_logo']==='1'){$logo_id=get_term_meta($brand->term_id,'thumbnail_id',true);if($logo_id){$src=wp_get_attachment_image_url($logo_id,'thumbnail');if($src)$logo='<img class="brand-logo" loading="lazy" decoding="async" src="'.esc_url($src).'" alt="'.esc_attr($brand->name).'">';}}
            $reverse=($layout==='zigzag' && $atts['zigzag']==='1' && $i%2===1)?' is-reverse':'';
            echo '<section class="brand-block'.$reverse.'" data-brand="'.esc_attr($brand->term_id).'" data-taxonomy="'.esc_attr($tax).'">';
            echo '<aside class="brand-info">'; if($logo) echo $logo; if($atts['show_brand_header']==='1') echo '<h2>'.esc_html($brand->name).'</h2>'; echo '<span class="brand-count">'.esc_html(number_format_i18n($count)).' محصول</span></aside>';
            echo '<div class="brand-products"><div class="product-table-wrap"><table><thead><tr>'; foreach($enabled_columns as $col) echo '<th data-column="'.esc_attr($col['key']).'">'.esc_html($col['label']??'').'</th>'; echo '</tr></thead><tbody class="product-list"><tr class="skeleton-row"><td colspan="'.count($enabled_columns).'"><div class="skeleton"></div></td></tr></tbody></table></div>';
            echo '<div class="more-wrap">'; if($count>$batch_size) echo '<button type="button" class="load-more" data-brand="'.esc_attr($brand->term_id).'" data-taxonomy="'.esc_attr($tax).'" data-offset="0">'.esc_html($atts['button_text']).'</button>'; echo '</div></div></section>'; $i++;
        }
        echo '</div></div>'; return ob_get_clean();
    }

    private function get_brands($tax, $brand='', $order='name') {
        $key = self::BRAND_CACHE . '_' . md5($tax.'|'.wp_json_encode($brand).'|'.$order);
        $cached = get_transient($key); if(false !== $cached) return $cached;
        $args=array('taxonomy'=>$tax,'hide_empty'=>true,'orderby'=>$order==='count'?'count':'name','order'=>$order==='count'?'DESC':'ASC');
        if(is_array($brand) && $brand){$args['slug']=array_map('sanitize_title',$brand);} elseif($brand!==''){$args['slug']=sanitize_title($brand);}
        $brands=get_terms($args); if(!is_wp_error($brands))set_transient($key,$brands,10*MINUTE_IN_SECONDS); return $brands;
    }

    public function ajax_load_products() {
        check_ajax_referer(self::NONCE,'nonce');
        if(!class_exists('WooCommerce')) wp_send_json_error(array('message'=>'WooCommerce فعال نیست.'));
        $s=$this->settings(); $tax=sanitize_key(wp_unslash($_POST['taxonomy']??'')); if(!$tax)$tax=sanitize_key($s['brand_taxonomy']); $brand=absint($_POST['brand']??0); $offset=max(0,absint($_POST['offset']??0)); $limit=min(50,max(1,absint($_POST['limit']??self::BATCH_SIZE)));
        $posted_columns=array(); if(!empty($_POST['columns'])){$decoded=json_decode(wp_unslash($_POST['columns']),true);if(is_array($decoded))$posted_columns=$decoded;}
        $filters=array(); if(!empty($_POST['filters'])){$decoded=json_decode(wp_unslash($_POST['filters']),true);if(is_array($decoded))$filters=$decoded;}
        if(!$brand || !taxonomy_exists($tax)) wp_send_json_error(array('message'=>'برند یا taxonomy نامعتبر است.'));
        $columns=$posted_columns ? array_values(array_filter($posted_columns,function($c){return !empty($c['enabled'])&&!empty($c['key']);})) : array_values(array_filter($s['columns'],function($c){return !empty($c['enabled']);}));
        $orderby=in_array($filters['orderby']??'title',array('title','date','menu_order','price'),true)?$filters['orderby']:'title'; $order=($filters['order']??'ASC')==='DESC'?'DESC':'ASC';
        $args=array('post_type'=>'product','post_status'=>'publish','posts_per_page'=>$limit+1,'offset'=>$offset,'orderby'=>$orderby==='price'?'meta_value_num':$orderby,'order'=>$order,'fields'=>'ids','no_found_rows'=>true,'ignore_sticky_posts'=>true,'tax_query'=>array(array('taxonomy'=>$tax,'field'=>'term_id','terms'=>$brand)));
        if($orderby==='price'){$args['meta_key']='_price';}
        if(!empty($filters['search'])){$args['s']=sanitize_text_field($filters['search']);}
        if(!empty($filters['category'])){$args['tax_query'][]=array('taxonomy'=>'product_cat','field'=>'term_id','terms'=>absint($filters['category']));}
        if(!empty($filters['sale'])){$args['meta_query'][]=array('key'=>'_sale_price','value'=>'','compare'=>'!=');}
        if(!empty($filters['featured'])){$args['tax_query'][]=array('taxonomy'=>'product_visibility','field'=>'name','terms'=>array('featured'));}
        if(!empty($filters['stock']) && $filters['stock']!=='all'){$args['meta_query'][]=array('key'=>'_stock_status','value'=>sanitize_key($filters['stock']));}
        $q=new WP_Query($args); $ids=$q->posts; $has_more=count($ids)>$limit; if($has_more)$ids=array_slice($ids,0,$limit);
        ob_start(); foreach($ids as $id){$product=wc_get_product($id);if(!$product)continue;echo '<tr>';foreach($columns as $col)echo $this->render_cell($col['key'],$product);echo '</tr>';}
        wp_send_json_success(array('html'=>ob_get_clean(),'has_more'=>$has_more,'count'=>count($ids),'button_text'=>sanitize_text_field($_POST['button_text']??'بارگذاری محصولات بیشتر')));
    }

    private function render_cell($key,$product){
        $id=$product->get_id();$url=get_permalink($id);
        switch($key){
            case 'image':$image=$product->get_image('thumbnail',array('class'=>'product-image','loading'=>'lazy','decoding'=>'async'));return '<td data-column="image"><a href="'.esc_url($url).'">'.$image.'</a></td>';
            case 'name':return '<td data-column="name"><a href="'.esc_url($url).'">'.esc_html($product->get_name()).'</a></td>';
            case 'sku':return '<td data-column="sku">'.esc_html($product->get_sku()?:'—').'</td>';
            case 'price':return '<td data-column="price" class="price-cell">'.wp_kses_post(wc_price($product->get_regular_price())).'</td>';
            case 'sale_price':return '<td data-column="sale_price" class="sale-price-cell">'.($product->get_sale_price()!==''?wp_kses_post(wc_price($product->get_sale_price())):'—').'</td>';
            case 'stock':return '<td data-column="stock">'.esc_html($product->get_stock_status()==='instock'?'موجود':($product->get_stock_status()==='onbackorder'?'پیش‌فروش':'ناموجود')).'</td>';
            case 'category':return '<td data-column="category">'.wp_kses_post(wc_get_product_category_list($id,', ')).'</td>';
            case 'button':return '<td data-column="button"><a class="buy button" href="'.esc_url($url).'">مشاهده محصول</a></td>';
        }return '<td>—</td>';
    }

    public function clear_brand_cache(){global $wpdb;$like=$wpdb->esc_like('_transient_'.self::BRAND_CACHE).'%' ;$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",$like,$wpdb->esc_like('_transient_timeout_'.self::BRAND_CACHE).'%'));}
    public function clear_term_cache($term_id=0,$tt_id=0,$taxonomy='',$deleted_term=0){if($taxonomy){$s=$this->settings();if($taxonomy===$s['brand_taxonomy'])$this->clear_brand_cache();}}
}
$GLOBALS['brand_price_lists_instance'] = new WC_Brand_Price_Lists();

/**
 * Load Elementor integration after Elementor has initialized.
 * The integration registers the widget through `elementor/widgets/register`.
 */
function brand_price_lists_load_elementor_integration() {
    $file = __DIR__ . '/includes/class-elementor-widget.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
}
add_action( 'elementor/init', 'brand_price_lists_load_elementor_integration', 20 );
