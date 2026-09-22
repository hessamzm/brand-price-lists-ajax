<?php
/**
 * Plugin Name: Brand Price Lists AJAX
 * Description: نمایش سریع و AJAX لیست قیمت محصولات ووکامرس بر اساس برند با بارگذاری مرحله‌ای محصولات و ستون‌های قابل تنظیم.
 * Version: 1.0.2
 * Author: hessamzm
 * Author URI: https://github.com/hessamzm
 * Requires at least: 7.0
 * Requires PHP: 8.2
 */

if (!defined('ABSPATH')) exit;

class Sayeh_WC_Brand_Price_Lists {
    const OPTION = 'sayeh_price_list_settings';
    const NONCE = 'sayeh_price_list_ajax';
    const BRAND_CACHE = 'sayeh_price_list_brands_v2';
    const BATCH_SIZE = 5;

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('sayeh_price_list', array($this, 'shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_assets'));
        add_action('wp_ajax_sayeh_load_products', array($this, 'ajax_load_products'));
        add_action('wp_ajax_nopriv_sayeh_load_products', array($this, 'ajax_load_products'));
        add_action('update_option_' . self::OPTION, array($this, 'clear_brand_cache'));
        add_action('created_term', array($this, 'clear_term_cache'), 10, 3);
        add_action('edited_term', array($this, 'clear_term_cache'), 10, 3);
        add_action('delete_term', array($this, 'clear_term_cache'), 10, 4);
    }

    public function admin_menu() {
        add_menu_page(
            'لیست قیمت برندها', 'لیست قیمت برندها', 'manage_woocommerce',
            'sayeh-price-lists', array($this, 'settings_page'), 'dashicons-list-view', 56
        );
    }

    public function register_settings() {
        register_setting('sayeh_price_list_group', self::OPTION, array($this, 'sanitize_settings'));
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

    private function settings() {
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
            <p>این افزونه لیست قیمت را با شورت‌کد <code>[sayeh_price_list]</code> نمایش می‌دهد. برندها ابتدا سریع بارگذاری می‌شوند و محصولات هر برند با AJAX و در بسته‌های ۵تایی دریافت می‌شوند.</p>
            <form method="post" action="options.php">
                <?php settings_fields('sayeh_price_list_group'); ?>
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
                <table class="widefat" id="sayeh-columns-table"><thead><tr><th>ستون</th><th>عنوان نمایشی</th><th>نمایش</th><th>عملیات</th></tr></thead><tbody>
                <?php foreach ($s['columns'] as $i => $col) : ?><tr>
                    <td><select name="<?php echo esc_attr(self::OPTION); ?>[columns][<?php echo esc_attr($i); ?>][key]"><?php foreach ($this->available_columns() as $key => $label) : ?><option value="<?php echo esc_attr($key); ?>" <?php selected($col['key'], $key); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></td>
                    <td><input type="text" name="<?php echo esc_attr(self::OPTION); ?>[columns][<?php echo esc_attr($i); ?>][label]" value="<?php echo esc_attr($col['label']); ?>"></td>
                    <td><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[columns][<?php echo esc_attr($i); ?>][enabled]" value="1" <?php checked($col['enabled'], 1); ?>></td>
                    <td><button type="button" class="button sayeh-remove-row">حذف</button></td>
                </tr><?php endforeach; ?></tbody></table>
                <p><button type="button" class="button" id="sayeh-add-column">افزودن ستون</button></p>
                <?php submit_button('ذخیره تنظیمات'); ?>
            </form>
        </div>
        <script>
        document.addEventListener('DOMContentLoaded',function(){const t=document.querySelector('#sayeh-columns-table tbody'),a=document.querySelector('#sayeh-add-column'),p='<?php echo esc_js(self::OPTION); ?>';
        document.querySelectorAll('.sayeh-remove-row').forEach(b=>b.addEventListener('click',()=>b.closest('tr').remove()));
        a.addEventListener('click',function(){const i=t.querySelectorAll('tr').length,r=document.createElement('tr');r.innerHTML='<td><select name="'+p+'[columns]['+i+'][key]"><option value="image">تصویر</option><option value="name">نام محصول</option><option value="sku">کد کالا</option><option value="price">قیمت</option><option value="sale_price">قیمت ویژه</option><option value="stock">موجودی</option><option value="category">دسته‌بندی</option><option value="button">خرید</option></select></td><td><input type="text" name="'+p+'[columns]['+i+'][label]"></td><td><input type="checkbox" name="'+p+'[columns]['+i+'][enabled]" value="1" checked></td><td><button type="button" class="button sayeh-remove-row">حذف</button></td>';t.appendChild(r);r.querySelector('.sayeh-remove-row').addEventListener('click',()=>r.remove());});});
        </script>
        <?php
    }

    public function frontend_assets() {
        wp_register_style('sayeh-price-list', false);
        wp_enqueue_style('sayeh-price-list');
        wp_add_inline_style('sayeh-price-list', $this->css());
        wp_register_script('sayeh-price-list', false, array(), '2.0.2', true);
        wp_enqueue_script('sayeh-price-list');
        wp_add_inline_script('sayeh-price-list', $this->js());
        wp_localize_script('sayeh-price-list', 'SayehPriceList', array(
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
.sayeh-price-list{width:100%;margin:28px 0;direction:rtl}.sayeh-price-list *{box-sizing:border-box}.sayeh-price-list-title{margin:0 0 24px;font-size:clamp(24px,3vw,34px);font-weight:800}.sayeh-brands{display:flex;flex-direction:column;gap:24px}.sayeh-brand-block{display:grid;grid-template-columns:minmax(0,10fr) minmax(0,90fr);gap:0;border:1px solid #e8edf3;border-radius:20px;overflow:hidden;background:#fff;box-shadow:0 8px 28px rgba(15,23,42,.06)}.sayeh-brand-block.is-reverse{grid-template-columns:minmax(0,90fr) minmax(0,10fr)}.sayeh-brand-block.is-reverse .sayeh-brand-info{order:2}.sayeh-brand-block.is-reverse .sayeh-brand-products{order:1}.sayeh-brand-info{padding:18px 10px;background:linear-gradient(145deg,#111827,#26364f);color:#fff;min-height:180px;display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;gap:8px}.sayeh-brand-logo{width:48px;height:48px;object-fit:contain;border-radius:10px;background:#fff;padding:5px;margin:0;box-shadow:0 5px 14px rgba(0,0,0,.14)}.sayeh-brand-info h2{margin:0;color:#fff;font-size:16px;line-height:1.5;word-break:break-word}.sayeh-brand-count{opacity:.78;font-size:11px;line-height:1.5}.sayeh-brand-products{padding:0;min-width:0}.sayeh-product-table-wrap{overflow-x:auto}.sayeh-price-list table{width:100%;border-collapse:collapse;min-width:620px}.sayeh-price-list th,.sayeh-price-list td{padding:11px 10px;border-bottom:1px solid #edf0f4;text-align:right;vertical-align:middle}.sayeh-price-list th{background:#f8fafc;font-size:13px;font-weight:800;position:sticky;top:0;z-index:1}.sayeh-price-list td{font-size:14px}.sayeh-price-list tr:last-child td{border-bottom:0}.sayeh-price-list img.sayeh-product-image{width:52px;height:52px;object-fit:contain;border-radius:9px;background:#fafafa;display:block}.sayeh-price-list .sayeh-buy{display:inline-block;padding:8px 12px;border-radius:9px;text-decoration:none;font-weight:700;white-space:nowrap}.sayeh-more-wrap{text-align:center;padding:14px;border-top:1px solid #edf0f4}.sayeh-load-more{border:0;cursor:pointer;padding:10px 17px;border-radius:10px;font-weight:800;background:#111827;color:#fff}.sayeh-load-more:disabled{opacity:.55;cursor:wait}.sayeh-empty,.sayeh-error{padding:22px;text-align:center;color:#64748b}.sayeh-skeleton{height:48px;margin:10px;border-radius:9px;background:linear-gradient(90deg,#f3f4f6 25%,#e5e7eb 37%,#f3f4f6 63%);background-size:400% 100%;animation:sayehShimmer 1.2s infinite}@keyframes sayehShimmer{0%{background-position:100% 0}100%{background-position:-100% 0}}@media(max-width:900px){.sayeh-brand-block,.sayeh-brand-block.is-reverse{grid-template-columns:1fr}.sayeh-brand-block.is-reverse .sayeh-brand-info,.sayeh-brand-block.is-reverse .sayeh-brand-products{order:initial}.sayeh-brand-info{min-height:110px;flex-direction:row;justify-content:flex-start;padding:14px 16px;text-align:right}.sayeh-brand-logo{width:42px;height:42px}.sayeh-brand-info h2{font-size:15px}.sayeh-price-list table{min-width:620px}}
CSS;
    }

    private function js() {
        return <<<'JS'
(function(){'use strict';
function init(){document.querySelectorAll('.sayeh-price-list').forEach(function(root){if(root.dataset.ready)return;root.dataset.ready='1';root.addEventListener('click',function(e){var btn=e.target.closest('.sayeh-load-more');if(!btn)return;e.preventDefault();loadBox(btn.closest('.sayeh-brand-block'),btn);});var blocks=root.querySelectorAll('.sayeh-brand-block');if('IntersectionObserver' in window){var io=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){var box=entry.target;io.unobserve(box);if(!box.dataset.loaded)loadBox(box,box.querySelector('.sayeh-load-more'));}});},{rootMargin:'350px 0px'});blocks.forEach(function(box){io.observe(box);});}else{blocks.forEach(function(box){loadBox(box,box.querySelector('.sayeh-load-more'));});}});}
function loadBox(box,btn){if(!box||box.dataset.loading==='1')return;var list=box.querySelector('.sayeh-product-list');var offset=parseInt((btn&&btn.dataset.offset)||box.dataset.offset||'0',10);var brand=box.dataset.brand,tax=(btn&&btn.dataset.taxonomy)||box.dataset.taxonomy||box.closest('.sayeh-price-list').dataset.taxonomy||'';box.dataset.loading='1';if(btn){btn.disabled=true;btn.textContent=SayehPriceList.loading;}var fd=new FormData();fd.append('action','sayeh_load_products');fd.append('nonce',SayehPriceList.nonce);fd.append('brand',brand);fd.append('taxonomy',tax);fd.append('offset',offset);fd.append('limit',SayehPriceList.batchSize);fetch(SayehPriceList.ajaxUrl,{method:'POST',credentials:'same-origin',body:fd}).then(function(r){if(!r.ok)throw new Error('HTTP');return r.json();}).then(function(res){if(!res.success)throw new Error('AJAX');var skeleton=list.querySelector('.sayeh-skeleton-row');if(skeleton)skeleton.remove();list.insertAdjacentHTML('beforeend',res.data.html||'');box.dataset.loaded='1';box.dataset.offset=String(offset+Number(res.data.count||0));if(btn){if(res.data.has_more){btn.dataset.offset=String(offset+SayehPriceList.batchSize);btn.disabled=false;btn.textContent=SayehPriceList.more;}else{var wrap=btn.closest('.sayeh-more-wrap');if(wrap)wrap.remove();}}else if(res.data.has_more){var wrap=box.querySelector('.sayeh-more-wrap');if(wrap){var b=wrap.querySelector('.sayeh-load-more');b.dataset.offset=String(offset+SayehPriceList.batchSize);}}}).catch(function(){if(btn){btn.disabled=false;btn.textContent=SayehPriceList.more;}var old=box.querySelector('.sayeh-error');if(!old){old=document.createElement('div');old.className='sayeh-error';old.textContent=SayehPriceList.error;box.querySelector('.sayeh-product-list').insertAdjacentElement('afterend',old);}}).finally(function(){box.dataset.loading='0';});}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();})();
JS;
    }

    public function shortcode($atts) {
        if (!class_exists('WooCommerce')) return '<p>ووکامرس فعال نیست.</p>';
        $s = $this->settings();
        $atts = shortcode_atts(array('brand'=>'','limit'=>$s['per_page'],'orderby'=>'title','order'=>'ASC'), $atts, 'sayeh_price_list');
        $tax = sanitize_key($s['brand_taxonomy']);
        $brands = $this->get_brands($tax, $atts['brand']);
        if (is_wp_error($brands) || empty($brands)) return '<p>برندی برای نمایش پیدا نشد.</p>';
        $enabled_columns = array_values(array_filter($s['columns'], function($c){return !empty($c['enabled']);}));
        ob_start();
        echo '<div class="sayeh-price-list" data-taxonomy="'.esc_attr($tax).'">';
        if (!empty($s['title'])) echo '<h1 class="sayeh-price-list-title">'.esc_html($s['title']).'</h1>';
        echo '<div class="sayeh-brands">';
        $i=0;
        foreach($brands as $brand){
            $count=(int)$brand->count;
            $logo='';
            if(!empty($s['show_brand_logo'])){$logo_id=get_term_meta($brand->term_id,'thumbnail_id',true);if($logo_id){$src=wp_get_attachment_image_url($logo_id,'thumbnail');if($src)$logo='<img class="sayeh-brand-logo" loading="lazy" decoding="async" src="'.esc_url($src).'" alt="'.esc_attr($brand->name).'">';}}
            $reverse=($i%2===1)?' is-reverse':'';
            echo '<section class="sayeh-brand-block'.$reverse.'" data-brand="'.esc_attr($brand->term_id).'">';
            echo '<aside class="sayeh-brand-info">';
            if($logo) echo $logo;
            if(!empty($s['show_brand_header'])) echo '<h2>'.esc_html($brand->name).'</h2>';
            echo '<span class="sayeh-brand-count">'.esc_html(number_format_i18n($count)).' محصول</span></aside>';
            echo '<div class="sayeh-brand-products">';
            echo '<div class="sayeh-product-table-wrap"><table><thead><tr>';
            foreach($enabled_columns as $col) echo '<th>'.esc_html($col['label']).'</th>';
            echo '</tr></thead><tbody class="sayeh-product-list"><tr class="sayeh-skeleton-row"><td colspan="'.count($enabled_columns).'"><div class="sayeh-skeleton"></div></td></tr></tbody></table></div>';
            echo '<div class="sayeh-more-wrap"><button type="button" class="sayeh-load-more" data-brand="'.esc_attr($brand->term_id).'" data-taxonomy="'.esc_attr($tax).'" data-offset="0">'.esc_html('بارگذاری محصولات بیشتر').'</button></div>';
            if($count<=self::BATCH_SIZE) echo '<style>.sayeh-brand-block[data-brand="'.esc_attr($brand->term_id).'"] .sayeh-more-wrap{display:none}</style>';
            echo '</div></section>';
            $i++;
        }
        echo '</div></div>';
        return ob_get_clean();
    }

    private function get_brands($tax, $brand='') {
        $key = self::BRAND_CACHE . '_' . md5($tax.'|'.$brand.'|'.wp_json_encode($this->settings()['brand_order']));
        $cached = get_transient($key);
        if(false !== $cached) return $cached;
        $s=$this->settings();$args=array('taxonomy'=>$tax,'hide_empty'=>true,'orderby'=>$s['brand_order']==='count'?'count':'name','order'=>$s['brand_order']==='count'?'DESC':'ASC');
        if($brand!=='')$args['slug']=sanitize_title($brand);
        $brands=get_terms($args);
        if(!is_wp_error($brands))set_transient($key,$brands,10*MINUTE_IN_SECONDS);
        return $brands;
    }

    public function ajax_load_products() {
        check_ajax_referer(self::NONCE,'nonce');
        if(!class_exists('WooCommerce'))wp_send_json_error();
        $s=$this->settings();$tax=sanitize_key(wp_unslash($_POST['taxonomy']??'')); if(!$tax)$tax=sanitize_key($s['brand_taxonomy']);$brand=absint($_POST['brand']??0);$offset=max(0,absint($_POST['offset']??0));
        if(!$brand || !taxonomy_exists($tax))wp_send_json_error();
        $columns=array_values(array_filter($s['columns'],function($c){return !empty($c['enabled']);}));
        $limit=self::BATCH_SIZE;
        $q=new WP_Query(array('post_type'=>'product','post_status'=>'publish','posts_per_page'=>$limit+1,'offset'=>$offset,'orderby'=>'title','order'=>'ASC','fields'=>'ids','no_found_rows'=>true,'ignore_sticky_posts'=>true,'tax_query'=>array(array('taxonomy'=>$tax,'field'=>'term_id','terms'=>$brand))));
        $ids=$q->posts;$has_more=count($ids)>$limit;if($has_more)$ids=array_slice($ids,0,$limit);
        ob_start();foreach($ids as $id){$product=wc_get_product($id);if(!$product)continue;echo '<tr>';foreach($columns as $col){echo $this->render_cell($col['key'],$product);}echo '</tr>';}
        wp_send_json_success(array('html'=>ob_get_clean(),'has_more'=>$has_more,'count'=>count($ids)));
    }

    private function render_cell($key,$product){
        $id=$product->get_id();$url=get_permalink($id);
        switch($key){
            case 'image':$image=$product->get_image('thumbnail',array('class'=>'sayeh-product-image','loading'=>'lazy','decoding'=>'async'));return '<td><a href="'.esc_url($url).'">'.$image.'</a></td>';
            case 'name':return '<td><a href="'.esc_url($url).'">'.esc_html($product->get_name()).'</a></td>';
            case 'sku':return '<td>'.esc_html($product->get_sku()?:'—').'</td>';
            case 'price':return '<td>'.wp_kses_post(wc_price($product->get_regular_price())).'</td>';
            case 'sale_price':return '<td>'.($product->get_sale_price()!==''?wp_kses_post(wc_price($product->get_sale_price())):'—').'</td>';
            case 'stock':return '<td>'.esc_html($product->get_stock_status()==='instock'?'موجود':'ناموجود').'</td>';
            case 'category':return '<td>'.wp_kses_post(wc_get_product_category_list($id,', ')).'</td>';
            case 'button':return '<td><a class="sayeh-buy button" href="'.esc_url($url).'">مشاهده محصول</a></td>';
        }return '<td>—</td>';
    }

    public function clear_brand_cache(){global $wpdb;$like=$wpdb->esc_like('_transient_'.self::BRAND_CACHE).'%' ;$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",$like,$wpdb->esc_like('_transient_timeout_'.self::BRAND_CACHE).'%'));}
    public function clear_term_cache($term_id=0,$tt_id=0,$taxonomy='',$deleted_term=0){if($taxonomy){$s=$this->settings();if($taxonomy===$s['brand_taxonomy'])$this->clear_brand_cache();}}
}
new Sayeh_WC_Brand_Price_Lists();
