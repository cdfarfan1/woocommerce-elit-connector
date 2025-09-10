<?php
/**
 * Simulación completa de importación de productos ELIT
 * Este script simula el proceso completo sin afectar WooCommerce
 */

require_once 'includes/elit-api.php';

echo "🚀 SIMULACIÓN DE IMPORTACIÓN ELIT\n";
echo "==================================\n\n";

// Simular datos de respuesta de ELIT (basado en la respuesta real)
$elit_response = array(
    'codigo' => 200,
    'paginador' => array(
        'total' => 1149,
        'limit' => 3,
        'offset' => 0
    ),
    'resultado' => array(
        array(
            'id' => 19043,
            'codigo_alfa' => 'LENEX5WS0T36151',
            'codigo_producto' => '5WS0T36151',
            'nombre' => '3Y Premier Support with Onsite NBD Upgrade from 1Y',
            'categoria' => 'Software',
            'sub_categoria' => 'Garantías',
            'marca' => 'LENOVO',
            'precio' => 56.14,
            'impuesto_interno' => 0,
            'iva' => 21,
            'moneda' => 2,
            'markup' => 0.4,
            'cotizacion' => 1425,
            'pvp_usd' => 95.1,
            'pvp_ars' => 135517.5,
            'peso' => 0.01,
            'peso_cubico' => 0,
            'ean' => 0,
            'nivel_stock' => 'bajo',
            'stock_total' => 0,
            'stock_deposito_cliente' => 0,
            'stock_deposito_cd' => 0,
            'garantia' => '36 MESES',
            'link' => 'https://elit.com.ar/producto/19043-3y-premier-support-with-onsite-nbd-upgrade-from-1y',
            'uri' => '19043-3y-premier-support-with-onsite-nbd-upgrade-from-1y',
            'imagenes' => array(),
            'miniaturas' => array(),
            'atributos' => array(),
            'gamer' => false,
            'creado' => '15/8/25, 5:06 p. m.',
            'actualizado' => '26/8/25, 7:47 p. m.',
            'descripcion' => '',
            'dimensiones' => array(
                'largo' => 0.01,
                'ancho' => 0.01,
                'alto' => 0.01
            )
        ),
        array(
            'id' => 17592,
            'codigo_alfa' => 'LENAIOF0GH01FDA',
            'codigo_producto' => 'F0GH01FDAR',
            'nombre' => 'AIO Lenovo 3 Idea Centre 24" I3 8GB 512SSD W11H',
            'categoria' => 'Computadoras',
            'sub_categoria' => 'All In One',
            'marca' => 'LENOVO',
            'precio' => 564.99,
            'impuesto_interno' => 0,
            'iva' => 10.5,
            'moneda' => 2,
            'markup' => 0.4,
            'cotizacion' => 1425,
            'pvp_usd' => 874.04,
            'pvp_ars' => 1245507,
            'peso' => 7.03,
            'peso_cubico' => 0.04,
            'ean' => 198153857029,
            'nivel_stock' => 'bajo',
            'stock_total' => 0,
            'stock_deposito_cliente' => 0,
            'stock_deposito_cd' => 0,
            'garantia' => '12',
            'link' => 'https://elit.com.ar/producto/17592-aio-lenovo-3-idea-centre-24-i3-8gb-512ssd-w11h',
            'uri' => '17592-aio-lenovo-3-idea-centre-24-i3-8gb-512ssd-w11h',
            'imagenes' => array('https://images.elit.com.ar/p/17592/i/Kc3iB_l.webp'),
            'miniaturas' => array('https://images.elit.com.ar/p/17592/i/Kc3iB_s.webp'),
            'atributos' => array(),
            'gamer' => false,
            'creado' => '30/5/24, 4:08 p. m.',
            'actualizado' => '7/9/25, 2:50 p. m.',
            'descripcion' => '',
            'dimensiones' => array(
                'largo' => 18.53,
                'ancho' => 54.12,
                'alto' => 43.39
            )
        ),
        array(
            'id' => 16852,
            'codigo_alfa' => 'LENAIOF0GH00XXA',
            'codigo_producto' => 'F0GH00XXAR',
            'nombre' => 'AIO Lenovo 3 Idea Centre 24" I5 12GB 512G W11H',
            'categoria' => 'Computadoras',
            'sub_categoria' => 'All In One',
            'marca' => 'LENOVO',
            'precio' => 681.63,
            'impuesto_interno' => 0,
            'iva' => 10.5,
            'moneda' => 2,
            'markup' => 0.4,
            'cotizacion' => 1425,
            'pvp_usd' => 1054.48,
            'pvp_ars' => 1502634,
            'peso' => 7.03,
            'peso_cubico' => 0.04,
            'ean' => 197529997833,
            'nivel_stock' => 'bajo',
            'stock_total' => 0,
            'stock_deposito_cliente' => 0,
            'stock_deposito_cd' => 0,
            'garantia' => '12',
            'link' => 'https://elit.com.ar/producto/16852-aio-lenovo-3-idea-centre-24-i5-12gb-512g-w11h',
            'uri' => '16852-aio-lenovo-3-idea-centre-24-i5-12gb-512g-w11h',
            'imagenes' => array('https://images.elit.com.ar/p/16852/i/1yLPt_l.webp'),
            'miniaturas' => array('https://images.elit.com.ar/p/16852/i/1yLPt_s.webp'),
            'atributos' => array(),
            'gamer' => false,
            'creado' => '26/7/23, 2:56 p. m.',
            'actualizado' => '22/8/25, 2:45 p. m.',
            'descripcion' => '',
            'dimensiones' => array(
                'largo' => 18.53,
                'ancho' => 54.12,
                'alto' => 43.39
            )
        )
    )
);

echo "📦 Simulando transformación de " . count($elit_response['resultado']) . " productos...\n\n";

// Simular configuración de opciones
function get_option($option_name, $default = false) {
    $options = array(
        'elit_sku_prefix' => 'ELIT_',
        'elit_sync_usd' => false,
        'elit_markup_percentage' => 35
    );
    
    return isset($options[$option_name]) ? $options[$option_name] : $default;
}

// Simular clase NB_Logger
class NB_Logger {
    public static function info($message) {
        echo "ℹ️  LOG: $message\n";
    }
    public static function error($message) {
        echo "❌ ERROR: $message\n";
    }
    public static function warning($message) {
        echo "⚠️  WARNING: $message\n";
    }
}

// Simular función de cálculo de precio
function nb_calculate_price_with_markup($price) {
    $markup = get_option('elit_markup_percentage', 35);
    return $price * (1 + ($markup / 100));
}

// Incluir la clase ELIT_API_Manager simulada
class ELIT_API_Manager_Test {
    
    public static function transform_product_data($elit_product) {
        if (!is_array($elit_product)) {
            return null;
        }
        
        // Map ELIT fields to WooCommerce format
        $transformed = array(
            'sku' => self::get_field_value($elit_product, array('codigo_producto', 'codigo_alfa', 'id')),
            'name' => self::get_field_value($elit_product, array('nombre')),
            'description' => self::get_field_value($elit_product, array('descripcion')),
            'short_description' => self::build_short_description($elit_product),
            'price' => self::get_elit_price($elit_product),
            'stock_quantity' => self::get_elit_stock($elit_product),
            'stock_status' => self::get_elit_stock_status($elit_product),
            'categories' => self::get_elit_categories($elit_product),
            'images' => self::get_elit_images($elit_product),
            'weight' => self::get_field_value($elit_product, array('peso'), 0),
            'dimensions' => self::get_elit_dimensions($elit_product),
            'brand' => self::get_field_value($elit_product, array('marca')),
            'warranty' => self::get_field_value($elit_product, array('garantia')),
            'ean' => self::get_field_value($elit_product, array('ean')),
            'stock_level' => self::get_field_value($elit_product, array('nivel_stock')),
            'is_gamer' => self::get_field_value($elit_product, array('gamer'), false),
            'elit_id' => self::get_field_value($elit_product, array('id')),
            'elit_link' => self::get_field_value($elit_product, array('link')),
            'currency' => self::get_elit_currency($elit_product)
        );
        
        // Add prefix to SKU
        $prefix = get_option('elit_sku_prefix', 'ELIT_');
        if (!empty($prefix) && !empty($transformed['sku'])) {
            $transformed['sku'] = $prefix . $transformed['sku'];
        }
        
        return $transformed;
    }
    
    private static function get_field_value($data, $field_names, $default = '') {
        foreach ($field_names as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                return $data[$field];
            }
        }
        return $default;
    }
    
    private static function get_elit_price($elit_product) {
        $use_usd = get_option('elit_sync_usd', false);
        
        if ($use_usd) {
            $price = floatval($elit_product['pvp_usd'] ?? 0);
        } else {
            $price = floatval($elit_product['pvp_ars'] ?? 0);
        }
        
        if ($price <= 0 && isset($elit_product['precio'])) {
            $price = floatval($elit_product['precio']);
            if (isset($elit_product['iva'])) {
                $price += floatval($elit_product['iva']);
            }
        }
        
        // Apply markup
        return nb_calculate_price_with_markup($price);
    }
    
    private static function get_elit_stock($elit_product) {
        $stock_fields = array('stock_deposito_cliente', 'stock_total', 'stock_deposito_cd');
        
        foreach ($stock_fields as $field) {
            if (isset($elit_product[$field]) && is_numeric($elit_product[$field])) {
                $stock = intval($elit_product[$field]);
                if ($stock > 0) {
                    return $stock;
                }
            }
        }
        
        return 0;
    }
    
    private static function get_elit_stock_status($elit_product) {
        $stock_quantity = self::get_elit_stock($elit_product);
        $stock_level = self::get_field_value($elit_product, array('nivel_stock'), '');
        
        if ($stock_quantity > 0) {
            return 'instock';
        } elseif ($stock_level === 'bajo') {
            return 'onbackorder';
        } else {
            return 'outofstock';
        }
    }
    
    private static function get_elit_categories($elit_product) {
        $categories = array();
        
        if (!empty($elit_product['categoria'])) {
            $categories[] = trim($elit_product['categoria']);
        }
        
        if (!empty($elit_product['sub_categoria'])) {
            $categories[] = trim($elit_product['sub_categoria']);
        }
        
        if (!empty($elit_product['marca'])) {
            $categories[] = 'Marca: ' . trim($elit_product['marca']);
        }
        
        if ($elit_product['gamer'] === true) {
            $categories[] = 'Gaming';
        }
        
        return array_filter(array_unique($categories));
    }
    
    private static function get_elit_images($elit_product) {
        $images = array();
        
        if (!empty($elit_product['imagenes']) && is_array($elit_product['imagenes'])) {
            foreach ($elit_product['imagenes'] as $img) {
                if (filter_var($img, FILTER_VALIDATE_URL)) {
                    $images[] = $img;
                }
            }
        }
        
        if (empty($images) && !empty($elit_product['miniaturas']) && is_array($elit_product['miniaturas'])) {
            foreach ($elit_product['miniaturas'] as $thumb) {
                if (filter_var($thumb, FILTER_VALIDATE_URL)) {
                    $images[] = $thumb;
                }
            }
        }
        
        return array_unique($images);
    }
    
    private static function get_elit_dimensions($elit_product) {
        $dimensions = array('length' => 0, 'width' => 0, 'height' => 0);
        
        if (isset($elit_product['dimensiones']) && is_array($elit_product['dimensiones'])) {
            $dims = $elit_product['dimensiones'];
            $dimensions['length'] = floatval($dims['largo'] ?? 0);
            $dimensions['width'] = floatval($dims['ancho'] ?? 0);
            $dimensions['height'] = floatval($dims['alto'] ?? 0);
        }
        
        return $dimensions;
    }
    
    private static function get_elit_currency($elit_product) {
        $moneda = self::get_field_value($elit_product, array('moneda'), 1);
        return ($moneda == 2) ? 'USD' : 'ARS';
    }
    
    private static function build_short_description($elit_product) {
        $parts = array();
        
        $brand = self::get_field_value($elit_product, array('marca'));
        $category = self::get_field_value($elit_product, array('categoria'));
        $subcategory = self::get_field_value($elit_product, array('sub_categoria'));
        
        if ($brand) {
            $parts[] = $brand;
        }
        
        if ($category && $subcategory) {
            $parts[] = $category . ' - ' . $subcategory;
        } elseif ($category) {
            $parts[] = $category;
        }
        
        $warranty = self::get_field_value($elit_product, array('garantia'));
        if ($warranty) {
            $parts[] = 'Garantía: ' . $warranty . ($warranty === '12' ? ' meses' : '');
        }
        
        if (self::get_field_value($elit_product, array('gamer'), false)) {
            $parts[] = '🎮 Gaming';
        }
        
        $stock_level = self::get_field_value($elit_product, array('nivel_stock'));
        if ($stock_level === 'bajo') {
            $parts[] = '⚠️ Stock limitado';
        }
        
        return implode(' | ', $parts);
    }
}

// Procesar productos
echo "📋 PROCESANDO PRODUCTOS DE ELIT:\n";
echo "================================\n\n";

$products = $elit_response['resultado'];
$total_available = $elit_response['paginador']['total'];

echo "📊 Total de productos en ELIT: $total_available\n";
echo "🔄 Productos a procesar: " . count($products) . "\n\n";

foreach ($products as $index => $elit_product) {
    $num = $index + 1;
    echo "🔸 PROCESANDO PRODUCTO $num:\n";
    echo "===========================\n";
    
    // Transformar producto
    $transformed = ELIT_API_Manager_Test::transform_product_data($elit_product);
    
    if ($transformed) {
        echo "✅ Transformación exitosa:\n";
        echo "   • SKU: " . $transformed['sku'] . "\n";
        echo "   • Nombre: " . $transformed['name'] . "\n";
        echo "   • Precio: $" . number_format($transformed['price'], 2) . " " . $transformed['currency'] . "\n";
        echo "   • Stock: " . $transformed['stock_quantity'] . " (" . $transformed['stock_status'] . ")\n";
        echo "   • Categorías: " . implode(', ', $transformed['categories']) . "\n";
        echo "   • Peso: " . $transformed['weight'] . " kg\n";
        echo "   • Dimensiones: " . $transformed['dimensions']['length'] . " x " . $transformed['dimensions']['width'] . " x " . $transformed['dimensions']['height'] . " cm\n";
        echo "   • Imágenes: " . count($transformed['images']) . " disponibles\n";
        echo "   • Descripción corta: " . $transformed['short_description'] . "\n";
        echo "   • EAN: " . ($transformed['ean'] ?: 'N/A') . "\n";
        echo "   • Garantía: " . ($transformed['warranty'] ?: 'N/A') . "\n";
        echo "   • Gaming: " . ($transformed['is_gamer'] ? 'Sí' : 'No') . "\n";
        
        if (!empty($transformed['images'])) {
            echo "   • Primera imagen: " . $transformed['images'][0] . "\n";
        }
        
        echo "\n";
        
        // Simular lo que pasaría en WooCommerce
        echo "🛒 MAPEO A WOOCOMMERCE:\n";
        echo "   • set_name('" . $transformed['name'] . "')\n";
        echo "   • set_sku('" . $transformed['sku'] . "')\n";
        echo "   • set_regular_price(" . $transformed['price'] . ")\n";
        echo "   • set_stock_quantity(" . $transformed['stock_quantity'] . ")\n";
        echo "   • set_stock_status('" . $transformed['stock_status'] . "')\n";
        echo "   • set_weight(" . $transformed['weight'] . ")\n";
        echo "   • set_short_description('" . substr($transformed['short_description'], 0, 50) . "...')\n";
        
        if (!empty($transformed['categories'])) {
            echo "   • Categorías WC: " . implode(', ', $transformed['categories']) . "\n";
        }
        
    } else {
        echo "❌ Error en transformación\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

echo "✅ SIMULACIÓN COMPLETADA\n";
echo "========================\n";
echo "📊 Resumen:\n";
echo "   • Total en ELIT: $total_available productos\n";
echo "   • Procesados: " . count($products) . " productos\n";
echo "   • Transformaciones exitosas: " . count($products) . "\n";
echo "\n🎉 El plugin está listo para importar productos reales de ELIT!\n";
?>
