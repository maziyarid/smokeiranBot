<?php
/**
 * Custom Fields Handler
 */

if (!defined('ABSPATH')) exit;

class SIR_Custom_Fields {
    
    /**
     * Default WooCommerce product custom fields mapping
     */
    public static function get_product_fields_mapping() {
        return [
            // ACF or Standard Meta Fields
            'brand' => [
                'meta_key' => '_product_brand',
                'label' => 'برند',
                'type' => 'text'
            ],
            'model' => [
                'meta_key' => '_product_model',
                'label' => 'مدل',
                'type' => 'text'
            ],
            'country' => [
                'meta_key' => '_product_country',
                'label' => 'کشور سازنده',
                'type' => 'text'
            ],
            'batteryCapacity' => [
                'meta_key' => '_battery_capacity',
                'label' => 'ظرفیت باتری',
                'type' => 'text'
            ],
            'outputPower' => [
                'meta_key' => '_output_power',
                'label' => 'توان خروجی',
                'type' => 'text'
            ],
            'tankCapacity' => [
                'meta_key' => '_tank_capacity',
                'label' => 'ظرفیت تانک',
                'type' => 'text'
            ],
            'coilResistance' => [
                'meta_key' => '_coil_resistance',
                'label' => 'مقاومت کویل',
                'type' => 'text'
            ],
            'chargingType' => [
                'meta_key' => '_charging_type',
                'label' => 'نوع شارژ',
                'type' => 'text'
            ],
            'displayType' => [
                'meta_key' => '_display_type',
                'label' => 'نوع نمایشگر',
                'type' => 'text'
            ],
            'weight' => [
                'meta_key' => '_product_weight_custom',
                'label' => 'وزن',
                'type' => 'text'
            ],
            'dimensions' => [
                'meta_key' => '_product_dimensions_custom',
                'label' => 'ابعاد',
                'type' => 'text'
            ],
            'materials' => [
                'meta_key' => '_product_materials',
                'label' => 'مواد سازنده',
                'type' => 'textarea'
            ],
            'warranty' => [
                'meta_key' => '_warranty_info',
                'label' => 'گارانتی',
                'type' => 'text'
            ],
            'colors' => [
                'meta_key' => '_available_colors',
                'label' => 'رنگ‌های موجود',
                'type' => 'array'
            ],
            'chipset' => [
                'meta_key' => '_chipset',
                'label' => 'چیپست',
                'type' => 'text'
            ],
            'resistance_range' => [
                'meta_key' => '_resistance_range',
                'label' => 'محدوده مقاومت',
                'type' => 'text'
            ],
            'airflow' => [
                'meta_key' => '_airflow_type',
                'label' => 'جریان هوا',
                'type' => 'text'
            ],
            'fill_type' => [
                'meta_key' => '_fill_type',
                'label' => 'نوع شارژ مایع',
                'type' => 'text'
            ],
        ];
    }
    
    /**
     * Save custom fields to product
     */
    public static function save_product_fields($product_id, $custom_fields) {
        if (empty($custom_fields) || !is_array($custom_fields)) {
            return;
        }
        
        $mapping = self::get_product_fields_mapping();
        
        foreach ($custom_fields as $key => $value) {
            if (isset($mapping[$key]) && !empty($value)) {
                $meta_key = $mapping[$key]['meta_key'];
                
                if ($mapping[$key]['type'] === 'array' && is_array($value)) {
                    $value = implode(', ', $value);
                }
                
                // Use appropriate sanitization based on field type
                if ($mapping[$key]['type'] === 'textarea') {
                    $sanitized_value = sanitize_textarea_field($value);
                } else {
                    $sanitized_value = sanitize_text_field($value);
                }
                
                update_post_meta($product_id, $meta_key, $sanitized_value);
            }
        }
        
        // Also save raw custom fields for reference
        update_post_meta($product_id, '_sir_custom_fields', $custom_fields);
    }
    
    /**
     * Get custom fields from product
     */
    public static function get_product_fields($product_id) {
        $mapping = self::get_product_fields_mapping();
        $fields = [];
        
        foreach ($mapping as $key => $config) {
            $value = get_post_meta($product_id, $config['meta_key'], true);
            if (!empty($value)) {
                $fields[$key] = $value;
            }
        }
        
        return $fields;
    }
    
    /**
     * Generate custom fields from research data
     */
    public static function generate_from_research($research_data) {
        $fields = [];
        
        // Battery capacity
        if (preg_match('/(\d+)\s*mAh/i', $research_data, $match)) {
            $fields['batteryCapacity'] = $match[1] . ' mAh';
        }
        
        // Output power
        if (preg_match('/(\d+)\s*[Ww](?:att)?/i', $research_data, $match)) {
            $fields['outputPower'] = $match[1] . 'W';
        }
        
        // Tank capacity
        if (preg_match('/(\d+(?:\.\d+)?)\s*ml/i', $research_data, $match)) {
            $fields['tankCapacity'] = $match[1] . ' ml';
        }
        
        // Brand extraction
        $brands = ['VOOPOO', 'Vaporesso', 'UWELL', 'GeekVape', 'SMOK', 'Aspire', 'Innokin', 'Lost Vape', 'Eleaf'];
        foreach ($brands as $brand) {
            if (stripos($research_data, $brand) !== false) {
                $fields['brand'] = $brand;
                break;
            }
        }
        
        // Charging type
        if (stripos($research_data, 'Type-C') !== false || stripos($research_data, 'USB-C') !== false) {
            $fields['chargingType'] = 'USB Type-C';
        } elseif (stripos($research_data, 'Micro USB') !== false) {
            $fields['chargingType'] = 'Micro USB';
        }
        
        // Country
        if (stripos($research_data, 'Shenzhen') !== false || stripos($research_data, 'China') !== false) {
            $fields['country'] = 'چین';
        }
        
        return $fields;
    }
}
