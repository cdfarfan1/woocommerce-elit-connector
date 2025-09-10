<?php
if (!defined('ABSPATH')) {
    exit;
}

function nb_calculate_price_with_markup($original_price) {
    // Obtener el markup desde la configuración (priorizar ELIT, fallback a NB)
    $markup_percentage = get_option('elit_markup_percentage', get_option('nb_markup_percentage', 35));
    
    // Validar que el precio original sea numérico
    if (!is_numeric($original_price)) {
        error_log('Error en nb_calculate_price_with_markup: precio no numérico - ' . print_r($original_price, true));
        return $original_price;
    }
    
    try {
        // Convertir a float para asegurar cálculos precisos
        $original_price = floatval($original_price);
        $markup_multiplier = (100 + floatval($markup_percentage)) / 100;
        
        // Calcular el precio con markup
        $final_price = $original_price * $markup_multiplier;
        
        // Redondear a 2 decimales
        return round($final_price, 2);
    } catch (Exception $e) {
        error_log('Error en nb_calculate_price_with_markup: ' . $e->getMessage());
        return $original_price;
    }
}

function nb_get_markup_percentage() {
    return floatval(get_option('elit_markup_percentage', get_option('nb_markup_percentage', 35)));
}

// Función auxiliar para validar y formatear precios
function nb_format_price($price) {
    if (!is_numeric($price)) {
        return 0;
    }
    return round(floatval($price), 2);
}

// Agregar esta función para depuración
function nb_debug_price_calculation($original_price) {
    $markup_percentage = nb_get_markup_percentage();
    $final_price = nb_calculate_price_with_markup($original_price);
    
    error_log(sprintf(
        'Cálculo de precio - Original: %s, Markup: %s%%, Final: %s',
        $original_price,
        $markup_percentage,
        $final_price
    ));
    
    return $final_price;
}