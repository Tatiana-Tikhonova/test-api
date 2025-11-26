<?php

namespace Test_Api;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
class Test_Api_Data_Handler
{
    private $api_file_path = ABSPATH . '/api-data.json';

    public function get(int $product_id, string $search = '', string $get_color = ''): array
    {
        $res = [];
        $variations = $this->all($product_id);
        if (!$variations) return $res;

        if ($search && !empty($variations['combinations'][$search])) {
            $res = $variations['combinations'][$search];
        } else {
            foreach ($variations['combinations'] as $key => $combination) {
                if ($get_color) {
                    if (strpos($key, $get_color) !== false) {
                        $res = $combination;
                        $search = $key;
                        break;
                    }
                } elseif ($this->available($combination)) {
                    $res = $combination;
                    $search = $key;
                    break;
                }
            }
        }

        if ($res && $search) {
            $exploded_key = explode('_', $search);
            $color = strtolower($exploded_key[0] ?? '');
            $size = strtoupper($exploded_key[1] ?? '');
            $res['color'] = $color;
            $res['size'] = $size;

            if (!empty($variations['compatibility'][$color])) {
                foreach ($variations['compatibility'][$color] as $sz) {
                    $key = "{$color}_{$sz}";
                    $res['compatibility'][$sz] = $this->available($variations['combinations'][$key]);
                }
            }
        }
        return $res;
    }

    private function available($combination)
    {
        return (
            isset($combination['stock'])
            && !empty(intval($combination['stock']))
            && isset($combination['available'])
            && !empty(rest_sanitize_boolean($combination['available']))
        );
    }


    private function all($product_id): ?array
    {
        $transient_name = "product_{$product_id}_variations";
        $product_variations = get_transient($transient_name);

        if (!empty($product_variations) && is_array($product_variations)) {
            return $product_variations;
        } else {
            $data = $this->get_api_data();
            if (
                !empty($data['products'][$product_id])
                && is_array($data['products'][$product_id])
            ) {
                $product_variations = $data['products'][$product_id];
                set_transient($transient_name, wp_slash($product_variations), 10 * MINUTE_IN_SECONDS);
            } else {
                $product_variations = null;
            }
            return $product_variations;
        }
    }
    private function get_api_data(): ?array
    {
        if (!is_readable($this->api_file_path)) return null;
        $data = json_decode(file_get_contents($this->api_file_path), true);
        return is_array($data) ? $data : null;
    }
}
