<?php
if (!defined('ABSPATH')) exit;
if (!class_exists('Elementor_Brand_Price_List_Widget') && class_exists('\Elementor\Widget_Base')) {
    class Elementor_Brand_Price_List_Widget extends \Elementor\Widget_Base {
        private $plugin;
        public function __construct($data = array(), $args = null) {
            $this->plugin = $GLOBALS['brand_price_lists_instance'] ?? null;
            parent::__construct($data, $args);
        }
        public function get_name(){ return 'brand_price_list'; }
        public function get_title(){ return esc_html__('Brand Price List','brand-price-lists-ajax'); }
        public function get_icon(){ return 'eicon-table'; }
        public function get_categories(): array { return array('brand-price-lists','general'); }
        public function get_keywords(){ return array('brand','price','woocommerce','product','ajax','لیست قیمت','برند'); }

        protected function register_controls(){
            $s = $this->plugin ? $this->plugin->settings() : array();
            $tax = !empty($s['brand_taxonomy']) ? $s['brand_taxonomy'] : 'product_brand';

            $this->start_controls_section('content_section', array('label'=>'محتوا','tab'=>\Elementor\Controls_Manager::TAB_CONTENT));
            $this->add_control('title', array('label'=>'عنوان','type'=>\Elementor\Controls_Manager::TEXT,'default'=>$s['title']??'لیست قیمت محصولات','label_block'=>true));
            $this->add_control('brand_mode', array('label'=>'نوع نمایش برند','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'all','options'=>array('all'=>'همه برندها','single'=>'یک برند','multiple'=>'چند برند')));
            $this->add_control('brand', array('label'=>'برند','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'','options'=>$this->brand_options($tax),'condition'=>array('brand_mode'=>'single')));
            $this->add_control('brands', array('label'=>'برندها','type'=>\Elementor\Controls_Manager::SELECT2,'multiple'=>true,'default'=>array(),'options'=>$this->brand_options($tax),'condition'=>array('brand_mode'=>'multiple'),'label_block'=>true));
            $this->add_control('show_title', array('label'=>'نمایش عنوان کلی','type'=>\Elementor\Controls_Manager::SWITCHER,'return_value'=>'yes','default'=>'yes'));
            $this->add_control('show_brand_header', array('label'=>'نمایش نام برند','type'=>\Elementor\Controls_Manager::SWITCHER,'return_value'=>'yes','default'=>!empty($s['show_brand_header'])?'yes':''));
            $this->add_control('show_brand_logo', array('label'=>'نمایش لوگوی برند','type'=>\Elementor\Controls_Manager::SWITCHER,'return_value'=>'yes','default'=>!empty($s['show_brand_logo'])?'yes':''));
            $this->add_control('brand_order', array('label'=>'ترتیب برندها','type'=>\Elementor\Controls_Manager::SELECT,'default'=>$s['brand_order']??'name','options'=>array('name'=>'نام','count'=>'تعداد محصولات')));

            $this->add_control('filter_heading', array('label'=>'فیلتر محصولات','type'=>\Elementor\Controls_Manager::HEADING,'separator'=>'before'));
            $this->add_control('product_search', array('label'=>'جستجوی محصول','type'=>\Elementor\Controls_Manager::TEXT,'placeholder'=>'نام محصول / SKU','label_block'=>true));
            $this->add_control('stock_filter', array('label'=>'وضعیت موجودی','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'all','options'=>array('all'=>'همه','instock'=>'موجود','outofstock'=>'ناموجود','onbackorder'=>'پیش‌فروش')));
            $this->add_control('sale_only', array('label'=>'فقط محصولات دارای تخفیف','type'=>\Elementor\Controls_Manager::SWITCHER,'return_value'=>'yes','default'=>''));
            $this->add_control('featured_only', array('label'=>'فقط محصولات ویژه','type'=>\Elementor\Controls_Manager::SWITCHER,'return_value'=>'yes','default'=>''));
            $this->add_control('category', array('label'=>'دسته محصول','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'','options'=>$this->category_options(),'label_block'=>true));
            $this->add_control('orderby', array('label'=>'مرتب‌سازی محصولات','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'title','options'=>array('title'=>'نام','date'=>'جدیدترین','menu_order'=>'ترتیب منو','price'=>'قیمت')));
            $this->add_control('order', array('label'=>'جهت مرتب‌سازی','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'ASC','options'=>array('ASC'=>'صعودی','DESC'=>'نزولی')));
            $this->add_control('batch_size', array('label'=>'تعداد محصولات در هر بار','type'=>\Elementor\Controls_Manager::NUMBER,'default'=>5,'min'=>1,'max'=>50));

            $repeater = new \Elementor\Repeater();
            $repeater->add_control('key', array('label'=>'بخش/ستون','type'=>\Elementor\Controls_Manager::SELECT,'options'=>array('image'=>'تصویر','name'=>'نام محصول','sku'=>'کد کالا','price'=>'قیمت','sale_price'=>'قیمت ویژه','stock'=>'موجودی','category'=>'دسته‌بندی','button'=>'دکمه'),'default'=>'name'));
            $repeater->add_control('label', array('label'=>'عنوان','type'=>\Elementor\Controls_Manager::TEXT,'default'=>'نام محصول'));
            $repeater->add_control('enabled', array('label'=>'نمایش','type'=>\Elementor\Controls_Manager::SWITCHER,'return_value'=>'1','default'=>'1'));
            $this->add_control('columns', array('label'=>'ترتیب و نمایش بخش‌های محصول','type'=>\Elementor\Controls_Manager::REPEATER,'fields'=>$repeater->get_controls(),'default'=>$this->default_columns($s),'title_field'=>'{{{ label }}}','description'=>'ردیف‌ها را با Drag & Drop جابه‌جا کنید؛ ترتیب همین لیست در جدول نمایش داده می‌شود.'));
            $this->end_controls_section();

            $this->start_controls_section('layout_section', array('label'=>'Layout','tab'=>\Elementor\Controls_Manager::TAB_CONTENT));
            $this->add_control('layout', array('label'=>'Layout','type'=>\Elementor\Controls_Manager::SELECT,'default'=>'zigzag','options'=>array('zigzag'=>'زیگزاگ 10/90','stacked'=>'عمودی','compact'=>'فشرده','table'=>'جدولی')));
            $this->add_responsive_control('brand_width', array('label'=>'عرض Brand','type'=>\Elementor\Controls_Manager::SLIDER,'size_units'=>array('%'),'range'=>array('%'=>array('min'=>5,'max'=>40)),'default'=>array('unit'=>'%','size'=>10),'selectors'=>array('{{WRAPPER}} .brand-block'=>'--brand-width: {{SIZE}}{{UNIT}};')));
            $this->add_responsive_control('products_width', array('label'=>'عرض Product','type'=>\Elementor\Controls_Manager::SLIDER,'size_units'=>array('%'),'range'=>array('%'=>array('min'=>60,'max'=>95)),'default'=>array('unit'=>'%','size'=>90),'selectors'=>array('{{WRAPPER}} .brand-block'=>'--products-width: {{SIZE}}{{UNIT}};')));
            $this->add_responsive_control('gap', array('label'=>'فاصله کارت‌ها','type'=>\Elementor\Controls_Manager::SLIDER,'size_units'=>array('px'),'range'=>array('px'=>array('min'=>0,'max'=>80)),'default'=>array('unit'=>'px','size'=>24),'selectors'=>array('{{WRAPPER}} .brands'=>'gap: {{SIZE}}{{UNIT}};')));
            $this->add_responsive_control('radius', array('label'=>'گردی کارت','type'=>\Elementor\Controls_Manager::SLIDER,'size_units'=>array('px'),'range'=>array('px'=>array('min'=>0,'max'=>40)),'default'=>array('unit'=>'px','size'=>18),'selectors'=>array('{{WRAPPER}} .brand-block'=>'border-radius: {{SIZE}}{{UNIT}};')));
            $this->add_control('zigzag', array('label'=>'زیگزاگ','type'=>\Elementor\Controls_Manager::SWITCHER,'return_value'=>'yes','default'=>'yes','condition'=>array('layout'=>'zigzag')));
            $this->end_controls_section();

            $this->start_controls_section('brand_style', array('label'=>'Brand','tab'=>\Elementor\Controls_Manager::TAB_STYLE));
            $this->add_control('brand_bg', array('label'=>'پس‌زمینه','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>array('{{WRAPPER}} .brand-info'=>'background-color: {{VALUE}};')));
            $this->add_control('brand_color', array('label'=>'رنگ متن','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>array('{{WRAPPER}} .brand-info, {{WRAPPER}} .brand-info h2'=>'color: {{VALUE}};')));
            $this->add_group_control(\Elementor\Group_Control_Typography::get_type(),array('name'=>'brand_typography','selector'=>'{{WRAPPER}} .brand-info h2'));
            $this->add_responsive_control('brand_padding',array('label'=>'Padding','type'=>\Elementor\Controls_Manager::DIMENSIONS,'size_units'=>array('px'),'selectors'=>array('{{WRAPPER}} .brand-info'=>'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};')));
            $this->end_controls_section();

            $this->start_controls_section('product_style', array('label'=>'Product','tab'=>\Elementor\Controls_Manager::TAB_STYLE));
            $this->add_control('product_bg', array('label'=>'پس‌زمینه','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>array('{{WRAPPER}} .brand-products'=>'background-color: {{VALUE}};')));
            $this->add_control('product_text_color', array('label'=>'رنگ متن','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>array('{{WRAPPER}} .brand-products, {{WRAPPER}} .brand-products td'=>'color: {{VALUE}};')));
            $this->add_control('product_border_color', array('label'=>'رنگ خطوط','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>array('{{WRAPPER}} .brand-products table, {{WRAPPER}} .brand-products th, {{WRAPPER}} .brand-products td'=>'border-color: {{VALUE}};')));
            $this->add_group_control(\Elementor\Group_Control_Typography::get_type(),array('name'=>'product_typography','selector'=>'{{WRAPPER}} .brand-products'));
            $this->add_control('product_hover_bg',array('label'=>'Hover پس‌زمینه','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>array('{{WRAPPER}} .brand-products tbody tr:hover'=>'background-color: {{VALUE}};')));
            $this->add_control('product_hover_color',array('label'=>'Hover متن','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>array('{{WRAPPER}} .brand-products tbody tr:hover td, {{WRAPPER}} .brand-products tbody tr:hover a'=>'color: {{VALUE}};')));
            $this->end_controls_section();

            $this->start_controls_section('price_style', array('label'=>'Price','tab'=>\Elementor\Controls_Manager::TAB_STYLE));
            $this->add_control('price_color', array('label'=>'رنگ قیمت','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>array('{{WRAPPER}} .price-cell'=>'color: {{VALUE}};')));
            $this->add_control('sale_price_color', array('label'=>'رنگ قیمت ویژه','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>array('{{WRAPPER}} .sale-price-cell'=>'color: {{VALUE}};')));
            $this->add_group_control(\Elementor\Group_Control_Typography::get_type(),array('name'=>'price_typography','selector'=>'{{WRAPPER}} .price-cell, {{WRAPPER}} .sale-price-cell'));
            $this->end_controls_section();

            $this->start_controls_section('button_style', array('label'=>'Button','tab'=>\Elementor\Controls_Manager::TAB_STYLE));
            $this->add_control('button_text',array('label'=>'متن دکمه بیشتر','type'=>\Elementor\Controls_Manager::TEXT,'default'=>'بارگذاری محصولات بیشتر'));
            $this->add_control('button_color',array('label'=>'رنگ متن','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>array('{{WRAPPER}} .load-more, {{WRAPPER}} .buy'=>'color: {{VALUE}};')));
            $this->add_control('button_bg',array('label'=>'پس‌زمینه','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>array('{{WRAPPER}} .load-more, {{WRAPPER}} .buy'=>'background-color: {{VALUE}};')));
            $this->add_control('button_hover_bg',array('label'=>'Hover پس‌زمینه','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>array('{{WRAPPER}} .load-more:hover, {{WRAPPER}} .buy:hover'=>'background-color: {{VALUE}};')));
            $this->add_control('button_hover_color',array('label'=>'Hover متن','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>array('{{WRAPPER}} .load-more:hover, {{WRAPPER}} .buy:hover'=>'color: {{VALUE}};')));
            $this->add_group_control(\Elementor\Group_Control_Typography::get_type(),array('name'=>'button_typography','selector'=>'{{WRAPPER}} .load-more, {{WRAPPER}} .buy'));
            $this->add_responsive_control('button_padding',array('label'=>'Padding','type'=>\Elementor\Controls_Manager::DIMENSIONS,'size_units'=>array('px'),'selectors'=>array('{{WRAPPER}} .load-more, {{WRAPPER}} .buy'=>'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};')));
            $this->add_control('button_radius',array('label'=>'گردی','type'=>\Elementor\Controls_Manager::SLIDER,'size_units'=>array('px'),'range'=>array('px'=>array('min'=>0,'max'=>50)),'selectors'=>array('{{WRAPPER}} .load-more, {{WRAPPER}} .buy'=>'border-radius: {{SIZE}}{{UNIT}};')));
            $this->end_controls_section();

            $this->start_controls_section('general_style', array('label'=>'عمومی','tab'=>\Elementor\Controls_Manager::TAB_STYLE));
            $this->add_group_control(\Elementor\Group_Control_Typography::get_type(),array('name'=>'list_typography','selector'=>'{{WRAPPER}} .price-list'));
            $this->add_control('title_color',array('label'=>'رنگ عنوان','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>array('{{WRAPPER}} .price-list-title'=>'color: {{VALUE}};')));
            $this->add_group_control(\Elementor\Group_Control_Typography::get_type(),array('name'=>'title_typography','selector'=>'{{WRAPPER}} .price-list-title'));
            $this->add_responsive_control('section_margin',array('label'=>'فاصله بخش‌ها','type'=>\Elementor\Controls_Manager::DIMENSIONS,'size_units'=>array('px'),'selectors'=>array('{{WRAPPER}} .brand-block'=>'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};')));
            $this->end_controls_section();
        }

        private function default_columns($s){
            $cols=!empty($s['columns'])&&is_array($s['columns'])?$s['columns']:array();
            return array_map(function($c){return array('key'=>$c['key']??'name','label'=>$c['label']??'','enabled'=>!empty($c['enabled'])?'1':'');},$cols);
        }
        private function brand_options($tax){$o=array(''=>'انتخاب کنید');if(!taxonomy_exists($tax))return $o;$terms=get_terms(array('taxonomy'=>$tax,'hide_empty'=>true,'orderby'=>'name','order'=>'ASC'));if(!is_wp_error($terms))foreach($terms as $t)$o[$t->slug]=$t->name;return $o;}
        private function category_options(){$o=array(''=>'همه دسته‌ها');$terms=get_terms(array('taxonomy'=>'product_cat','hide_empty'=>true,'orderby'=>'name','order'=>'ASC'));if(!is_wp_error($terms))foreach($terms as $t)$o[$t->term_id]=$t->name;return $o;}
        protected function render(){
            if(!$this->plugin)return;
            $s=$this->get_settings_for_display();
            $mode=$s['brand_mode']??'all';
            $brands='';
            if($mode==='single') $brands=sanitize_title($s['brand']??'');
            elseif($mode==='multiple') $brands=is_array($s['brands']??null)?array_map('sanitize_title',$s['brands']):array();
            echo $this->plugin->render_price_list(array(
                'brand'=>$brands,
                'title'=>($s['show_title']??'yes')==='yes'?($s['title']??''):'',
                'show_brand_header'=>($s['show_brand_header']??'')==='yes'?'1':'0',
                'show_brand_logo'=>($s['show_brand_logo']??'')==='yes'?'1':'0',
                'brand_order'=>sanitize_key($s['brand_order']??'name'),
                'columns'=>is_array($s['columns']??null)?$s['columns']:array(),
                'batch_size'=>min(50,max(1,absint($s['batch_size']??5))),
                'zigzag'=>($s['zigzag']??'yes')==='yes'?'1':'0',
                'layout'=>sanitize_key($s['layout']??'zigzag'),
                'product_search'=>sanitize_text_field($s['product_search']??''),
                'stock_filter'=>sanitize_key($s['stock_filter']??'all'),
                'sale_only'=>($s['sale_only']??'')==='yes'?'1':'0',
                'featured_only'=>($s['featured_only']??'')==='yes'?'1':'0',
                'category'=>absint($s['category']??0),
                'orderby'=>sanitize_key($s['orderby']??'title'),
                'order'=>($s['order']??'ASC')==='DESC'?'DESC':'ASC',
                'button_text'=>sanitize_text_field($s['button_text']??'بارگذاری محصولات بیشتر'),
            ));
        }
    }
}

/**
 * Elementor integration.
 *
 * This follows Elementor's current widget registration API:
 * elementor/widgets/register
 *
 * @see https://developers.elementor.com/docs/managers/registering-widgets/
 */
function brand_price_lists_register_elementor_widget( $widgets_manager ) {
    $widget_file = __DIR__ . '/class-elementor-widget.php';

    if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
        return;
    }

    if ( file_exists( $widget_file ) ) {
        require_once $widget_file;
    }

    if ( class_exists( 'Elementor_Brand_Price_List_Widget' ) ) {
        $widgets_manager->register( new \Elementor_Brand_Price_List_Widget() );
    }
}
add_action( 'elementor/widgets/register', 'brand_price_lists_register_elementor_widget' );

/**
 * Register a dedicated Elementor panel category.
 */
function brand_price_lists_register_elementor_category( $elements_manager ) {
    if ( method_exists( $elements_manager, 'add_category' ) ) {
        $elements_manager->add_category(
            'brand-price-lists',
            array(
                'title' => esc_html__( 'Brand Price Lists', 'brand-price-lists-ajax' ),
                'icon'  => 'eicon-table',
            )
        );
    }
}
add_action( 'elementor/elements/categories_registered', 'brand_price_lists_register_elementor_category' );

/**
 * Helpful admin notice when Elementor is not active.
 */
function brand_price_lists_elementor_admin_notice() {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    if ( class_exists( '\Elementor\Plugin' ) ) {
        return;
    }

    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || 'plugins' !== $screen->id ) {
        return;
    }

    echo '<div class="notice notice-warning"><p><strong>Brand Price Lists AJAX:</strong> Elementor باید فعال باشد تا ویجت <strong>Brand Price List</strong> در صفحه‌ساز نمایش داده شود.</p></div>';
}
add_action( 'admin_notices', 'brand_price_lists_elementor_admin_notice' );

